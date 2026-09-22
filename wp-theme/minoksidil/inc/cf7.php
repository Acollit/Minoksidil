<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;

/**
 * Интеграция Contact Form 7.
 *
 * При активном плагине автоматически создаются две формы с разметкой под
 * дизайн темы (классы modal-form__*) и почтой на minoksidil_order_email:
 *  - «Обратный звонок» — модальное окно в футере (опция minoksidil_cf7_modal)
 *  - «Обратная связь»  — CTA-блоки на страницах Контакты/Доставка (minoksidil_cf7_cta)
 *
 * Привязка форм к местам вывода меняется в Minoksidil → Настройки.
 * Если CF7 не активен, модалка использует старую AJAX-форму темы (фолбэк).
 */

function minoksidil_cf7_active(): bool {
    return class_exists('WPCF7_ContactForm');
}

/**
 * Выводит форму CF7 для локации ('modal' | 'cta').
 * Возвращает false, если CF7 не активен или форма не назначена/удалена.
 */
function minoksidil_cf7_form(string $location, string $html_class = 'modal-form'): bool {
    if (!minoksidil_cf7_active()) return false;

    $id = (int) get_option('minoksidil_cf7_' . $location, 0);
    if (!$id || get_post_type($id) !== 'wpcf7_contact_form' || get_post_status($id) !== 'publish') {
        return false;
    }

    echo do_shortcode(sprintf('[contact-form-7 id="%d" html_class="%s"]', $id, esc_attr($html_class)));
    return true;
}

// Разметку форм задаём сами — авто-абзацы CF7 ломают flex-раскладку
add_filter('wpcf7_autop_or_not', '__return_false');

// Дефолтный CSS плагина не нужен — стили в assets/css/cf7.css
add_filter('wpcf7_load_css', '__return_false');

add_action('wp_enqueue_scripts', function (): void {
    if (!minoksidil_cf7_active()) return;
    wp_enqueue_style('minoksidil-cf7', get_template_directory_uri() . '/assets/css/cf7.css', ['minoksidil-main'], '1.0.0');
});

// ===== Автосоздание форм при активном CF7 =====
add_action('admin_init', function (): void {
    if (!minoksidil_cf7_active() || !current_user_can('manage_options')) return;

    $forms = [
        'minoksidil_cf7_modal' => ['title' => 'Обратный звонок (модальное окно)', 'submit' => 'Перезвоните мне'],
        'minoksidil_cf7_cta'   => ['title' => 'Обратная связь (CTA на страницах)', 'submit' => 'Отправить'],
    ];

    foreach ($forms as $option => $cfg) {
        $id = (int) get_option($option, 0);
        if ($id && get_post_status($id) === 'publish') continue;

        $new_id = minoksidil_cf7_create_form($cfg['title'], $cfg['submit']);
        if ($new_id) update_option($option, $new_id);
    }
});

/**
 * Создаёт форму CF7 с шаблоном под дизайн темы. Возвращает ID формы или 0.
 */
function minoksidil_cf7_create_form(string $title, string $submit_label): int {
    $privacy = get_option('minoksidil_privacy_url', '#');
    $consent = get_option('minoksidil_consent_url', '#');

    $form_body = '[text* your-name class:modal-form__input placeholder "Имя"]
<div class="modal-form__row">
[email your-email class:modal-form__input placeholder "E-mail"]
[tel* your-phone class:modal-form__input placeholder "+7 (999) 999-99-99"]
</div>
[text your-message class:modal-form__input placeholder "Комментарий"]
[acceptance privacy] Нажимая кнопку вы подтверждаете, что ознакомлены с нашей <a href="' . esc_url($privacy) . '">Политикой конфиденциальности</a> [/acceptance]
[acceptance consent] Нажимая кнопку вы подтверждаете, что даёте <a href="' . esc_url($consent) . '">Согласие на обработку персональных данных</a> [/acceptance]
[submit class:modal-form__btn "' . $submit_label . '"]';

    $admin_email = minoksidil_notification_email();

    $contact_form = WPCF7_ContactForm::get_template(['title' => $title]);
    $props        = $contact_form->get_properties();

    $props['form'] = $form_body;
    $props['mail'] = array_merge($props['mail'], [
        'recipient'          => $admin_email,
        'subject'            => 'Заявка с сайта [_site_title]: ' . $title,
        'body'               => "Имя: [your-name]\nТелефон: [your-phone]\nE-mail: [your-email]\nСообщение: [your-message]\n\n--\nПисьмо отправлено с сайта [_site_title] ([_site_url])",
        'additional_headers' => 'Reply-To: [your-email]',
    ]);

    $contact_form->set_properties($props);
    $contact_form->save();

    return (int) $contact_form->id();
}

// Актуальный email из настроек темы — даже если в форме CF7 указан старый адрес
add_filter('wpcf7_mail_components', function (array $components): array {
    $email = minoksidil_notification_email();
    if ($email !== '') {
        $components['recipient'] = $email;
    }
    return $components;
});

// ===== Сохранение заявки в админку до отправки письма =====
// wpcf7_before_send_mail срабатывает после успешной валидации, но до отправки —
// заявка попадёт в Minoksidil → Заявки, даже если само письмо не дойдёт.
add_action('wpcf7_before_send_mail', function ($contact_form): void {
    $submission = WPCF7_Submission::get_instance();
    if (!$submission) return;

    $data = $submission->get_posted_data();

    minoksidil_save_request_lead(
        sanitize_text_field((string) ($data['your-name'] ?? '')),
        sanitize_text_field((string) ($data['your-phone'] ?? '')),
        sanitize_email((string) ($data['your-email'] ?? '')),
        sanitize_textarea_field((string) ($data['your-message'] ?? '')),
        'CF7: ' . $contact_form->title()
    );
});

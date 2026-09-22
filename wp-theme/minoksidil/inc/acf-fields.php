<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;

/**
 * ACF-поля для редактирования контента страниц.
 *
 * Группы регистрируются программно (acf_add_local_field_group) и появляются
 * на экранах редактирования соответствующих страниц. В шаблонах значения
 * выводятся через minoksidil_acf()/minoksidil_acf_rows() с фолбэком на
 * дефолтные тексты — до заполнения полей сайт выглядит как раньше.
 */

// ===== Хелперы вывода =====

/**
 * Значение ACF-поля с фолбэком на дефолт (если ACF выключен или поле пустое).
 */
function minoksidil_acf(string $field, $default = '', $post_id = false) {
    if (!function_exists('get_field')) return $default;
    $value = get_field($field, $post_id);
    if ($value === null || $value === false || $value === '') return $default;
    return $value;
}

/**
 * Строки репитера с фолбэком на дефолтный массив.
 */
function minoksidil_acf_rows(string $field, array $default = [], $post_id = false): array {
    if (!function_exists('get_field')) return $default;
    $rows = get_field($field, $post_id);
    return (is_array($rows) && $rows) ? $rows : $default;
}

/**
 * URL картинки из ACF-поля image (return_format array/url/id) с фолбэком.
 */
function minoksidil_acf_img($value, string $default = ''): string {
    if (is_array($value))   return (string) ($value['url'] ?? $default);
    if (is_numeric($value)) return wp_get_attachment_image_url((int) $value, 'large') ?: $default;
    if (is_string($value) && $value !== '') return $value;
    return $default;
}

// ===== Регистрация групп полей =====

add_action('acf/init', function (): void {
    if (!function_exists('acf_add_local_field_group')) return;

    // Конструктор поля
    $f = function (string $key, string $name, string $label, string $type = 'text', array $extra = []): array {
        return array_merge([
            'key'   => 'field_minoks_' . $key,
            'label' => $label,
            'name'  => $name,
            'type'  => $type,
        ], $extra);
    };

    // textarea: переносы строк превращаются в <br> при выводе
    $ta = function (string $key, string $name, string $label, string $default = '', int $rows = 3) use ($f): array {
        return $f($key, $name, $label, 'textarea', ['default_value' => $default, 'rows' => $rows, 'new_lines' => 'br']);
    };

    $txt = function (string $key, string $name, string $label, string $default = '') use ($f): array {
        return $f($key, $name, $label, 'text', ['default_value' => $default]);
    };

    $wys = function (string $key, string $name, string $label, string $default = '') use ($f): array {
        return $f($key, $name, $label, 'wysiwyg', [
            'default_value' => $default,
            'tabs'          => 'all',
            'toolbar'       => 'basic',
            'media_upload'  => 0,
        ]);
    };

    $img = function (string $key, string $name, string $label) use ($f): array {
        return $f($key, $name, $label, 'image', [
            'return_format' => 'array',
            'preview_size'  => 'medium',
            'library'       => 'all',
        ]);
    };

    // Конструктор группы
    $group = function (string $key, string $title, array $fields, array $location, int $order = 0): void {
        acf_add_local_field_group([
            'key'                   => 'group_minoks_' . $key,
            'title'                 => $title,
            'fields'                => $fields,
            'location'              => [$location],
            'menu_order'            => $order,
            'position'              => 'normal',
            'style'                 => 'default',
            'label_placement'       => 'top',
            'instruction_placement' => 'label',
            'active'                => true,
        ]);
    };

    $tpl = fn(string $file): array => [[
        'param'    => 'page_template',
        'operator' => '==',
        'value'    => 'page-templates/' . $file,
    ]];

    // ─── Главная страница ────────────────────────────────────────
    $group('front', 'Главная страница', [
        $ta('front_hero_title', 'hero_title', 'Hero — заголовок',
            "- Оригинальные средства\n- Все в наличии\n- Быстрая доставка"),
        $ta('front_hero_desc', 'hero_desc', 'Hero — описание',
            'Восстановление волос - это сейчас простой и недорогой процесс. Если возникнут любые вопросы - мы всегда на связи!'),
        $txt('front_catalog_title', 'catalog_title', 'Каталог — заголовок', 'Всё, что нужно для роста'),
        $txt('front_catalog_btn', 'catalog_btn', 'Каталог — текст кнопки', 'Все товары'),
        $txt('front_why_title', 'why_title', '«Почему мы» — заголовок', 'Почему мы?'),
        $f('front_why_cards', 'why_cards', '«Почему мы» — карточки', 'repeater', [
            'layout'       => 'block',
            'button_label' => 'Добавить карточку',
            'sub_fields'   => [
                $ta('front_why_card_title', 'card_title', 'Заголовок', '', 2),
                $img('front_why_card_img', 'card_img', 'Картинка'),
                $ta('front_why_card_desc', 'card_desc', 'Описание', '', 3),
            ],
        ]),
        $txt('front_reviews_title', 'reviews_title', 'Истории восстановления — заголовок', 'Истории восстановления'),
        $txt('front_reviews_btn', 'reviews_btn', 'Истории восстановления — текст кнопки', 'Попробовать'),
        $txt('front_article_title', 'article_title', 'Статья — заголовок', 'Как правильно наносить лосьон для роста волос'),
        $txt('front_article_link', 'article_link', 'Статья — текст ссылки', 'Больше статей'),
        $ta('front_article_desc', 'article_desc', 'Статья — текст',
            'Правильное нанесение лосьона — это не сложная процедура и не отдельный ритуал на полчаса. Наоборот, всё должно быть максимально просто, понятно и удобно. В идеале нанесение должно занимать буквально минуту и быть такой же привычной рутиной, как чистка зубов.', 4),
    ], [[
        'param'    => 'page_type',
        'operator' => '==',
        'value'    => 'front_page',
    ]]);

    // ─── Гид ─────────────────────────────────────────────────────
    $group('guide', 'Страница «Гид»', [
        $ta('guide_hero_title', 'hero_title', 'Hero — заголовок',
            "Minoxidillum\n— ваш проводник в мир густых волос"),
        $txt('guide_about_title', 'about_title', 'О нас — заголовок', 'О нас'),
        $wys('guide_about_text_1', 'about_text_1', 'О нас — текст (колонка 1)',
            '<p>Мы продаём только оригинальные лосьоны для восстановления волос. Привозим их из Америки и Индии, не работаем с сомнительными копиями и не собираем ассортимент ради количества. В нашей линейке — только те препараты, которые действительно помогают восстанавливать волосы и многократно проверены на практике.</p><p>Для нас важно не просто продать человеку лосьон, а показать, что восстановление волос — это доступный и не сложный процесс, которым может воспользоваться практически любой человек. Без лишних усложнений, без мифов и без ощущения, что для результата нужны какие-то недостижимые решения.</p>'),
        $wys('guide_about_text_2', 'about_text_2', 'О нас — текст (колонка 2)',
            '<p>Именно поэтому мы не ограничиваемся только продажей. Мы собираем понятную информацию, инструкции, ответы на частые вопросы и рекомендации по использованию, чтобы человек мог либо самостоятельно разобраться в теме, либо обратиться к нам за консультацией.</p><p>Наша идея простая: дать человеку оригинальный препарат, понятную схему, рабочий инструмент и поддержку. Чтобы восстановление волос было не пугающей историей, а лёгким процессом с хорошими результатами!</p>'),
        $txt('guide_articles_title', 'articles_title', 'Статьи — заголовок', 'Статьи'),
        $txt('guide_articles_link', 'articles_link', 'Статьи — текст ссылки', 'Больше статей'),
        $txt('guide_results_title', 'results_title', 'Результаты — заголовок', 'Результаты, которых мы достигаем вместе с вами'),
        $ta('guide_results_desc', 'results_desc', 'Результаты — описание',
            'У нас нет волшебной таблетки «на раз». Зато есть реальные фото людей разного возраста. Вот лишь несколько примеров того, как обычное регулярное использование с правильно подобранным лосьоном возвращает волосы.', 4),
        $txt('guide_results_link', 'results_link', 'Результаты — текст ссылки', 'Смотреть все результаты'),
        $txt('guide_partner_title', 'partner_title', 'Сотрудничество — заголовок', 'Мы открыты к сотрудничеству'),
        $ta('guide_partner_text', 'partner_text', 'Сотрудничество — текст',
            'У нас действует простая система промокодов: клиент получает скидку на заказ, а тот, кто вас рекомендует, — денежное вознаграждение с каждой продажи.', 4),
        $txt('guide_wholesale_title', 'wholesale_title', 'Опт — заголовок', 'Поставляем наши товары оптом под реализацию'),
        $ta('guide_wholesale_text', 'wholesale_text', 'Опт — текст',
            'Если вам интересен такой формат работы, напишите нам — обсудим условия', 3),
    ], $tpl('page-guide.php'));

    // ─── Контакты ────────────────────────────────────────────────
    $group('contacts', 'Страница «Контакты»', [
        $txt('contacts_banner_title', 'banner_title', 'Баннер — заголовок', 'Контакты'),
        $txt('contacts_partner_title', 'partner_title', 'Сотрудничество — заголовок', 'Мы открыты к сотрудничеству'),
        $ta('contacts_partner_text', 'partner_text', 'Сотрудничество — текст',
            'У нас действует простая система промокодов: клиент получает скидку на заказ, а тот, кто нас рекомендует, — денежное вознаграждение с каждой продажи.', 4),
        $txt('contacts_wholesale_title', 'wholesale_title', 'Опт — заголовок', 'Поставляем наши товары оптом под реализацию'),
        $ta('contacts_wholesale_text', 'wholesale_text', 'Опт — текст',
            'Если вам интересен такой формат работы, напишите нам — обсудим условия', 3),
        $txt('contacts_cta_title', 'cta_title', 'CTA — заголовок', 'Давайте обсудим ваш случай'),
        $ta('contacts_cta_text', 'cta_text', 'CTA — текст',
            'Если вы всё ещё сомневаетесь, какой лосьон выбрать, или хотите узнать, как начать — просто напишите. Мы на связи, чтобы вы наконец увидели первые новые волосы уже через 2.5 месяца.', 4),
    ], $tpl('page-contacts.php'));

    // ─── Доставка и оплата ───────────────────────────────────────
    $group('delivery', 'Страница «Доставка и оплата»', [
        $txt('delivery_banner_title', 'banner_title', 'Баннер — заголовок', 'Доставка и оплата'),
        $txt('delivery_payment_title', 'payment_title', 'Оплата — заголовок', 'Как оплатить:'),
        $f('delivery_payment_cards', 'payment_cards', 'Оплата — карточки', 'repeater', [
            'layout'       => 'block',
            'button_label' => 'Добавить карточку',
            'instructions' => 'Если карточки не добавлены — выводятся стандартные три способа оплаты.',
            'sub_fields'   => [
                $img('delivery_payment_icon', 'icon', 'Иконка'),
                $ta('delivery_payment_text', 'text', 'Текст', '', 2),
            ],
        ]),
        $wys('delivery_payment_descr', 'payment_descr', 'Оплата — описание',
            '<p>✅ После оформления заказа мы подтверждаем наличие товара, способ доставки и итоговую стоимость.</p><p>💵 Оплата производится после подтверждения заказа.</p><p>⚡️ Отправляем в день получения заказа.</p><p>📦 Заказ можно отследить в соответствующем приложении (СДЭК/Яндекс/ОЗОН/почта). По запросу- отправляем трек-номер.</p><p>🤝 По вашему желанию, можем оформить доставку любым другим удобным для вас способом.</p><p>🌏 Авито Доставка доступна при оформлении заказа через наш профиль на Авито.</p>'),
        $txt('delivery_delivery_title', 'delivery_title', 'Доставка — заголовок', 'Способы доставки:'),
        $txt('delivery_delivery_btn', 'delivery_btn', 'Доставка — текст кнопки', 'Перейти в каталог'),
        $f('delivery_delivery_cards', 'delivery_cards', 'Доставка — карточки', 'repeater', [
            'layout'       => 'block',
            'button_label' => 'Добавить способ доставки',
            'instructions' => 'Если карточки не добавлены — выводится стандартный список способов доставки.',
            'sub_fields'   => [
                $txt('delivery_card_title', 'title', 'Название'),
                $img('delivery_card_logo', 'logo', 'Логотип'),
                $txt('delivery_card_term', 'term', 'Сроки'),
                $txt('delivery_card_price', 'price', 'Стоимость'),
                $txt('delivery_card_method', 'method', 'Способ получения'),
            ],
        ]),
        $txt('delivery_cta_title', 'cta_title', 'CTA — заголовок', 'Остались вопросы?'),
        $ta('delivery_cta_text', 'cta_text', 'CTA — текст', 'С радостью ответим на них', 2),
    ], $tpl('page-delivery.php'));

    // ─── До/После ────────────────────────────────────────────────
    $group('ba', 'Страница «До/После»', [
        $txt('ba_banner_title', 'banner_title', 'Баннер — заголовок', 'Результаты и рекомендации'),
        $txt('ba_banner_sub', 'banner_sub', 'Баннер — подзаголовок', 'Индивидуальные результаты'),
        $txt('ba_search_placeholder', 'search_placeholder', 'Поиск — placeholder', 'Поиск по используемому товару'),
        $txt('ba_disclaimer', 'disclaimer', 'Дисклеймер внизу', 'Не является лекарством'),
    ], $tpl('page-before-after.php'));

    // ─── Спасибо (Заявка) ────────────────────────────────────────
    $group('spasibo', 'Страница «Заявка»', [
        $txt('spasibo_title', 'thanks_title', 'Заголовок', 'Благодарим вас за заявку!'),
        $ta('spasibo_text', 'thanks_text', 'Текст', 'Менеджер свяжется с вами в течение 1 часа', 2),
        $txt('spasibo_btn', 'thanks_btn', 'Текст кнопки', 'Вернуться в каталог'),
    ], $tpl('spasibo.php'));

    // ─── Спасибо (Заказ) ─────────────────────────────────────────
    $group('order', 'Страница «Заказ»', [
        $txt('order_title', 'thanks_title', 'Заголовок', 'Благодарим вас за заказ!'),
        $ta('order_text', 'thanks_text', 'Текст', 'Менеджер свяжется с вами в течение 15 минут', 2),
        $txt('order_btn', 'thanks_btn', 'Текст кнопки', 'Вернуться в каталог'),
    ], $tpl('order.php'));

    // ─── Карточка товара ─────────────────────────────────────────
    $group('product', 'Блоки на странице товара', [
        $ta('product_suitable', 'product_suitable', 'Кому подходит средство',
            'Это средство подходит для людей с активным выпадением волос, вызванным разными причинами. Благодаря наличию в составе миноксидила, оно эффективно при андрогенетической алопеции — как у мужчин (с характерным поредением в теменной и лобной зонах), так и у женщин (при диффузном истончении по центральному пробору). Также средство показывает хорошие результаты при реактивном выпадении, которое случается через 2–3 месяца после перенесённого стресса, тяжёлых заболеваний, операций или резкого снижения веса — в этом случае активный компонент помогает вернуть волосы в фазу роста и остановить чрезмерное выпадение. Дополнительные ингредиенты в составе работают на улучшение микроциркуляции и питания фолликулов, поэтому средство подходит и тем, кто хочет увеличить густоту и плотность волос без явной патологии, а просто ввиду генетической предрасположенности к тонким и редким волосам.

Данное средство универсально и подходит как мужчинам, так и женщинам, однако при наличии гормональных нарушений или индивидуальной чувствительности к компонентам рекомендуется предварительная консультация со специалистом.', 10),
        $f('product_faq', 'product_faq', 'Частые вопросы (ответ открывается по клику)', 'repeater', [
            'layout'       => 'block',
            'button_label' => 'Добавить вопрос',
            'instructions' => 'Вопросы общие, ответы заполняйте индивидуально для этого товара.',
            'sub_fields'   => [
                $txt('product_faq_q', 'question', 'Вопрос'),
                $ta('product_faq_a', 'answer', 'Ответ', '', 4),
            ],
        ]),
    ], [[
        'param'    => 'post_type',
        'operator' => '==',
        'value'    => 'product',
    ]]);
});

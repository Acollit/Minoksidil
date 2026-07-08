<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;

// ===== Admin menu: Theme settings =====
add_action('admin_menu', function () {
    add_menu_page(
        'Настройки Minoksidil',
        'Minoksidil',
        'manage_options',
        'minoksidil-settings',
        'minoksidil_settings_page',
        'dashicons-store',
        58
    );

    add_submenu_page(
        'minoksidil-settings',
        'Настройки темы',
        'Настройки',
        'manage_options',
        'minoksidil-settings',
        'minoksidil_settings_page'
    );

    add_submenu_page(
        'minoksidil-settings',
        'Заказы',
        'Заказы',
        'manage_woocommerce',
        'edit.php?post_type=shop_order',
        null
    );
});

// ===== Settings page =====
function minoksidil_settings_page(): void {
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['minoksidil_save']) && check_admin_referer('minoksidil_settings_save', 'minoksidil_nonce')) {
        $options = [
            'minoksidil_phone',
            'minoksidil_email',
            'minoksidil_address',
            'minoksidil_vk',
            'minoksidil_telegram',
            'minoksidil_telegram2',
            'minoksidil_max',
            'minoksidil_avito',
            'minoksidil_order_email',
            'minoksidil_ymaps_key',
            'minoksidil_cdek_client_id',
            'minoksidil_cdek_client_secret',
            'minoksidil_hero_title',
            'minoksidil_hero_text',
            'minoksidil_hero_image',
            'minoksidil_map_image',
            'minoksidil_grass_image',
            'minoksidil_privacy_url',
            'minoksidil_consent_url',
            'minoksidil_schedule',
            'minoksidil_guide_collab_image',
            'minoksidil_guide_value_image',
            'minoksidil_guide_product_image',
            'minoksidil_cf7_modal',
            'minoksidil_cf7_cta',
        ];
        foreach ($options as $key) {
            if (isset($_POST[$key])) {
                update_option($key, sanitize_text_field(wp_unslash($_POST[$key])));
            }
        }
        // Textarea: map embed (allow iframe)
        if (isset($_POST['minoksidil_map_embed'])) {
            $allowed = ['iframe' => ['src' => true, 'width' => true, 'height' => true, 'allowfullscreen' => true, 'style' => true, 'title' => true, 'frameborder' => true]];
            update_option('minoksidil_map_embed', wp_kses(wp_unslash($_POST['minoksidil_map_embed']), $allowed));
        }
        echo '<div class="notice notice-success"><p>Настройки сохранены.</p></div>';
    }
    ?>
    <div class="wrap">
      <h1>Настройки темы Minoksidil</h1>
      <form method="post">
        <?php wp_nonce_field('minoksidil_settings_save', 'minoksidil_nonce'); ?>

        <h2 class="title">Контакты</h2>
        <table class="form-table">
          <?php minoksidil_settings_row('Телефон', 'minoksidil_phone', '+7 (999) 999-99-99'); ?>
          <?php minoksidil_settings_row('Email для уведомлений о заказах', 'minoksidil_order_email', get_option('admin_email')); ?>
          <?php minoksidil_settings_row('Email (публичный)', 'minoksidil_email', 'info@minoxidillum.ru'); ?>
          <?php minoksidil_settings_row('Адрес', 'minoksidil_address', ''); ?>
        </table>

        <h2 class="title">Социальные сети</h2>
        <table class="form-table">
          <?php minoksidil_settings_row('ВКонтакте (URL)', 'minoksidil_vk', '#'); ?>
          <?php minoksidil_settings_row('Telegram (URL)', 'minoksidil_telegram', '#'); ?>
          <?php minoksidil_settings_row('Telegram 2 (URL)', 'minoksidil_telegram2', '#'); ?>
          <?php minoksidil_settings_row('MAX (URL)', 'minoksidil_max', '#'); ?>
          <?php minoksidil_settings_row('Avito (URL)', 'minoksidil_avito', '#'); ?>
        </table>

        <h2 class="title">Главная страница</h2>
        <table class="form-table">
          <?php minoksidil_settings_row('Заголовок Hero', 'minoksidil_hero_title', 'Мягкое восстановление без жёсткой химии'); ?>
          <?php minoksidil_settings_row('Подзаголовок Hero', 'minoksidil_hero_text', ''); ?>
          <?php minoksidil_settings_row('URL изображения Hero', 'minoksidil_hero_image', ''); ?>
          <?php minoksidil_settings_row('URL изображения карты в подвале', 'minoksidil_map_image', ''); ?>
          <?php minoksidil_settings_row('URL изображения травы (cat-banner)', 'minoksidil_grass_image', ''); ?>
        </table>

        <h2 class="title">Страница Контакты</h2>
        <table class="form-table">
          <?php minoksidil_settings_row('График работы', 'minoksidil_schedule', 'Пн-Пт с 9:00 до 18:00'); ?>
          <tr>
            <th>Карта (iframe Яндекс/Google)</th>
            <td>
              <textarea name="minoksidil_map_embed" class="large-text" rows="4" placeholder='&lt;iframe src="https://yandex.ru/map-widget/..."&gt;&lt;/iframe&gt;'><?php echo esc_textarea(get_option('minoksidil_map_embed', '')); ?></textarea>
              <p class="description">Вставьте код iframe карты с Яндекс Карт или Google Maps.</p>
            </td>
          </tr>
          <?php minoksidil_settings_row('URL фото сотрудничества (guide/contacts)', 'minoksidil_guide_collab_image', ''); ?>
          <?php minoksidil_settings_row('URL фото ценности (страница Гид)', 'minoksidil_guide_value_image', ''); ?>
          <?php minoksidil_settings_row('URL фото товара (страница Гид)', 'minoksidil_guide_product_image', ''); ?>
        </table>

        <h2 class="title">API-ключи для карт доставки</h2>
        <p style="background:#e7f7ec;border-left:4px solid #1AB248;padding:10px 14px;max-width:760px;">
          <strong>Ключи больше не требуются.</strong> Карта выбора ПВЗ работает на OpenStreetMap и берёт
          координаты пунктов из локальной базы. Управление точками —
          <a href="<?php echo esc_url(admin_url('admin.php?page=minoksidil-pvz')); ?>">Minoksidil → Пункты выдачи (ПВЗ)</a>.
          Поля ниже оставлены для совместимости и на работу карты не влияют.
        </p>
        <table class="form-table">
          <tr>
            <th>Яндекс Карты API-ключ</th>
            <td>
              <input type="text" name="minoksidil_ymaps_key" class="regular-text"
                     value="<?php echo esc_attr(get_option('minoksidil_ymaps_key', '')); ?>">
              <p class="description">
                Нужен для отображения карт СДЭК и Яндекс.<br>
                Получить: <a href="https://developer.tech.yandex.ru/" target="_blank" rel="noopener">developer.tech.yandex.ru</a>
                → Создать ключ → выбрать «JavaScript API и HTTP Геокодер».
              </p>
            </td>
          </tr>
          <tr>
            <th>СДЭК — Client ID</th>
            <td>
              <input type="text" name="minoksidil_cdek_client_id" class="regular-text"
                     value="<?php echo esc_attr(get_option('minoksidil_cdek_client_id', '')); ?>">
              <p class="description">
                Личный кабинет СДЭК: <a href="https://lk.cdek.ru/" target="_blank" rel="noopener">lk.cdek.ru</a>
                → Настройки → Доступ к API.<br>
                <strong>Важно:</strong> нужны оба поля (Client ID + Client Secret) для работы виджета выбора ПВЗ.
              </p>
            </td>
          </tr>
          <tr>
            <th>СДЭК — Client Secret</th>
            <td>
              <input type="password" name="minoksidil_cdek_client_secret" class="regular-text"
                     value="<?php echo esc_attr(get_option('minoksidil_cdek_client_secret', '')); ?>"
                     autocomplete="new-password">
              <p class="description">
                Client Secret из того же раздела СДЭК. Хранится на сервере и в браузер не передаётся.
              </p>
            </td>
          </tr>
          <tr>
            <th>Яндекс — ПВЗ (Яндекс Карты)</th>
            <td>
              <p class="description" style="color:#555;">
                Используется тот же Яндекс Карты API-ключ (см. выше).<br>
                Вкладка «Яндекс» показывает карту для выбора адреса доставки — кликните на нужную точку.
              </p>
            </td>
          </tr>
          <tr>
            <th>Ozon — ПВЗ</th>
            <td>
              <p class="description" style="color:#555;">
                Ozon не предоставляет публичного API для виджета ПВЗ на сторонних сайтах.<br>
                В корзине отображается кнопка-ссылка на <a href="https://www.ozon.ru/highlight/punkty-vydachi-ozon/" target="_blank" rel="noopener">карту ПВЗ Ozon</a> и поле для ввода адреса вручную.
              </p>
            </td>
          </tr>
        </table>

        <h2 class="title">Формы обратной связи (Contact Form 7)</h2>
        <?php if (function_exists('minoksidil_cf7_active') && minoksidil_cf7_active()) : ?>
          <p class="description" style="max-width:760px;">
            Формы создаются и редактируются в разделе
            <a href="<?php echo esc_url(admin_url('admin.php?page=wpcf7')); ?>">Contact Form 7</a>
            (поля, текст письма, получатель). Здесь выбирается, какая форма выводится в каждом месте.
          </p>
          <table class="form-table">
            <?php minoksidil_settings_cf7_row('Модальное окно «Заказать звонок»', 'minoksidil_cf7_modal'); ?>
            <?php minoksidil_settings_cf7_row('CTA-блоки (Контакты, Доставка)', 'minoksidil_cf7_cta'); ?>
          </table>
        <?php else : ?>
          <p style="background:#fcf0f1;border-left:4px solid #e93922;padding:10px 14px;max-width:760px;">
            Плагин <strong>Contact Form 7</strong> не активен. Установите и активируйте его —
            формы «Обратный звонок» и «Обратная связь» будут созданы автоматически.
            Пока плагин выключен, в модальном окне работает встроенная форма темы.
          </p>
        <?php endif; ?>

        <h2 class="title">Ссылки в подвале</h2>
        <table class="form-table">
          <?php minoksidil_settings_row('URL политики конфиденциальности', 'minoksidil_privacy_url', '#'); ?>
          <?php minoksidil_settings_row('URL согласия на обработку данных', 'minoksidil_consent_url', '#'); ?>
        </table>

        <?php submit_button('Сохранить настройки', 'primary', 'minoksidil_save'); ?>
      </form>
    </div>
    <?php
}

function minoksidil_settings_cf7_row(string $label, string $key): void {
    $current = (int) get_option($key, 0);
    $forms   = get_posts([
        'post_type'      => 'wpcf7_contact_form',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ]);
    echo '<tr><th>' . esc_html($label) . '</th><td><select name="' . esc_attr($key) . '">';
    echo '<option value="0">— не выводить —</option>';
    foreach ($forms as $form) {
        printf(
            '<option value="%d"%s>%s (ID %d)</option>',
            $form->ID,
            selected($current, $form->ID, false),
            esc_html($form->post_title),
            $form->ID
        );
    }
    echo '</select></td></tr>';
}

function minoksidil_settings_row(string $label, string $key, string $default): void {
    $value = get_option($key, $default);
    printf(
        '<tr><th>%s</th><td><input type="text" name="%s" class="regular-text" value="%s"></td></tr>',
        esc_html($label),
        esc_attr($key),
        esc_attr($value)
    );
}

// ===== Admin: add "Тип доставки" column in orders list =====
add_filter('manage_woocommerce_page_wc-orders_columns', function (array $columns): array {
    $new = [];
    foreach ($columns as $key => $label) {
        $new[$key] = $label;
        if ($key === 'order_status') {
            $new['delivery_type'] = 'Тип доставки';
        }
    }
    return $new;
});

// HPOS-compatible orders column
add_action('manage_woocommerce_page_wc-orders_custom_column', function (string $column, WC_Order $order) {
    if ($column === 'delivery_type') {
        $type = $order->get_meta('_delivery_type', true);
        $labels = ['courier' => '🚚 Курьер', 'pvz' => '📦 ПВЗ'];
        echo esc_html($labels[$type] ?? '—');
    }
}, 10, 2);

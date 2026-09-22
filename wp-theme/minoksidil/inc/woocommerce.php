<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;

// ===== Первое фото товара для каталога: ACF gallery → WooCommerce fallback =====
function minoksidil_get_product_catalog_img($product): string {
    if (!$product instanceof WC_Product) {
        return MINOKSIDIL_IMG . 'product-1.webp';
    }
    $acf = function_exists('get_field') ? get_field('product_gallery', $product->get_id()) : null;
    if (!empty($acf)) {
        $first = $acf[0];
        if (is_array($first)) {
            $url = $first['sizes']['product-catalog'] ?? ($first['sizes']['medium'] ?? ($first['url'] ?? ''));
            return (string) ($url !== '' ? $url : MINOKSIDIL_IMG . 'product-1.webp');
        }
        if (is_numeric($first)) {
            return wp_get_attachment_image_url((int) $first, 'product-catalog') ?: MINOKSIDIL_IMG . 'product-1.webp';
        }
        return (string) $first;
    }
    $img_id = $product->get_image_id();
    $fallback = MINOKSIDIL_IMG . 'product-1.webp';
    if (!$img_id) {
        return $fallback;
    }
    $url = wp_get_attachment_image_url($img_id, 'product-catalog');
    return $url ?: $fallback;
}

// ===== Исправление элементов корзины (PHP 8.x: Undefined array key "line_total") =====
// PHP 8 бросает Warning при обращении к несуществующему ключу массива.
// WooCommerce обращается к line_total/line_tax до calculate_totals() — ключей ещё нет.

// При добавлении НОВОГО товара в корзину
add_filter('woocommerce_add_cart_item', function ($cart_item) {
    if (!is_array($cart_item)) {
        return $cart_item;
    }
    foreach (['line_total', 'line_tax', 'line_subtotal', 'line_subtotal_tax'] as $field) {
        if (!isset($cart_item[$field])) {
            $cart_item[$field] = 0;
        }
    }
    return $cart_item;
});

// При загрузке СУЩЕСТВУЮЩИХ товаров из сессии
add_filter('woocommerce_get_cart_item_from_session', function ($cart_item, $values, $key) {
    if (!is_array($cart_item)) {
        return $cart_item;
    }
    foreach (['line_total', 'line_tax', 'line_subtotal', 'line_subtotal_tax'] as $field) {
        if (!isset($cart_item[$field])) {
            $cart_item[$field] = 0;
        }
    }
    return $cart_item;
}, 10, 3);

// ===== Remove WooCommerce wrappers (все шаблоны переписаны вручную) =====
remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);
remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);

// ===== Фильтрация каталога по атрибутам и категориям через URL-параметры =====
// filter_type=slug1,slug2  →  tax_query по pa_type
// filter_cat=slug          →  tax_query по product_cat
add_action('pre_get_posts', function (WP_Query $query): void {
    if (is_admin() || !$query->is_main_query()) return;
    if (!function_exists('is_shop') || (!is_shop() && !is_product_category() && !is_product_tag())) return;

    $tax_query = (array) $query->get('tax_query');
    $changed   = false;

    // Атрибуты товаров
    foreach (wc_get_attribute_taxonomies() as $attr) {
        $param = 'filter_' . $attr->attribute_name;
        if (empty($_GET[$param])) continue;

        $slugs = array_filter(array_map(
            'sanitize_title',
            explode(',', sanitize_text_field(wp_unslash($_GET[$param])))
        ));
        if (empty($slugs)) continue;

        $tax_query[] = [
            'taxonomy' => 'pa_' . $attr->attribute_name,
            'field'    => 'slug',
            'terms'    => array_values($slugs),
            'operator' => 'IN',
        ];
        $changed = true;
    }

    // Категории
    if (!empty($_GET['filter_cat'])) {
        $slugs = array_filter(array_map(
            'sanitize_title',
            explode(',', sanitize_text_field(wp_unslash($_GET['filter_cat'])))
        ));
        if (!empty($slugs)) {
            $tax_query[] = [
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => array_values($slugs),
                'operator' => 'IN',
            ];
            $changed = true;
        }
    }

    if ($changed) {
        $query->set('tax_query', $tax_query);
    }
});

// ===== AJAX-фильтрация каталога =====
add_action('wp_ajax_minoksidil_filter',        'minoksidil_ajax_filter_products');
add_action('wp_ajax_nopriv_minoksidil_filter', 'minoksidil_ajax_filter_products');

function minoksidil_ajax_filter_products(): void {
    // Перехватываем весь вывод (включая PHP-предупреждения от WooCommerce/сессии),
    // чтобы они не ломали JSON-ответ через "headers already sent"
    ob_start();

    $tax_query = [];

    foreach (wc_get_attribute_taxonomies() as $attr) {
        $param = 'filter_' . $attr->attribute_name;
        if (empty($_GET[$param])) continue;
        $slugs = array_filter(array_map(
            'sanitize_title',
            explode(',', sanitize_text_field(wp_unslash($_GET[$param])))
        ));
        if (!empty($slugs)) {
            $tax_query[] = [
                'taxonomy' => 'pa_' . $attr->attribute_name,
                'field'    => 'slug',
                'terms'    => array_values($slugs),
                'operator' => 'IN',
            ];
        }
    }

    if (!empty($_GET['filter_cat'])) {
        $slugs = array_filter(array_map(
            'sanitize_title',
            explode(',', sanitize_text_field(wp_unslash($_GET['filter_cat'])))
        ));
        if (!empty($slugs)) {
            $tax_query[] = [
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => array_values($slugs),
                'operator' => 'IN',
            ];
        }
    }

    $orderby_raw = sanitize_text_field(wp_unslash($_GET['orderby'] ?? ''));
    $search      = sanitize_text_field(wp_unslash($_GET['s'] ?? ''));
    $paged       = max(1, absint($_GET['paged'] ?? 1));

    $args = [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => 12,
        'paged'          => $paged,
        'tax_query'      => $tax_query,
    ];

    if ($search !== '') {
        $args['s'] = $search;
    }
    switch ($orderby_raw) {
        case 'price':
            $args['orderby']  = 'meta_value_num';
            $args['meta_key'] = '_price';
            $args['order']    = 'ASC';
            break;
        case 'price-desc':
            $args['orderby']  = 'meta_value_num';
            $args['meta_key'] = '_price';
            $args['order']    = 'DESC';
            break;
        case 'date':
            $args['orderby'] = 'date';
            $args['order']   = 'DESC';
            break;
        default:
            $args['orderby'] = 'menu_order title';
            $args['order']   = 'ASC';
    }

    $query = new WP_Query($args);

    // HTML карточек товаров
    ob_start();
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            global $product;
            $img_url = minoksidil_get_product_catalog_img($product);
            $is_new  = $product->is_featured();
            ?>
            <a class="product-card" href="<?php the_permalink(); ?>">
              <div class="product-card__box">
                <?php if ($is_new) : ?><span class="product-card__badge">NEW</span><?php endif; ?>
                <div class="product-card__img">
                  <img src="<?php echo esc_url($img_url); ?>"
                       alt="<?php echo esc_attr($product->get_name()); ?>"
                       width="312" height="312" loading="lazy">
                </div>
                <button class="product-card__add-btn" type="button"
                        data-product-id="<?php echo absint($product->get_id()); ?>"
                        data-add-url="<?php echo esc_url($product->add_to_cart_url()); ?>">
                  <span>Добавить в корзину</span>
                  <span class="product-card__add-icon"><?php echo minoksidil_cart_svg('#000D24'); ?></span>
                </button>
              </div>
              <p class="product-card__name"><?php echo esc_html($product->get_name()); ?></p>
              <p class="product-card__price"><?php echo $product->get_price_html(); ?></p>
            </a>
            <?php
        }
    }
    $html = ob_get_clean();

    // HTML пагинации
    ob_start();
    if ($query->max_num_pages > 1) {
        echo '<div class="catalog-pagination">';
        echo paginate_links([
            'base'      => add_query_arg('paged', '%#%'),
            'format'    => '?paged=%#%',
            'current'   => $paged,
            'total'     => $query->max_num_pages,
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
        ]);
        echo '</div>';
    }
    $pagination_html = ob_get_clean();

    wp_reset_postdata();
    ob_end_clean(); // внешний буфер — сбрасываем предупреждения

    wp_send_json_success([
        'html'       => $html,
        'pagination' => $pagination_html,
        'found'      => $query->found_posts,
    ]);
}

// ===== Мета-поля товара: Состав и Способ применения =====
add_action('add_meta_boxes', function (): void {
    add_meta_box(
        'minoksidil_product_fields',
        'Состав и способ применения',
        'minoksidil_product_fields_render',
        'product',
        'normal',
        'default'
    );
});

function minoksidil_product_faq_questions(): array {
    return [
        'Как выбрать средство под себя?',
        'Что делать, если пропустил нанесение средства?',
        'Можно ли использовать средство вместе с другими средствами для волос?',
        'Когда появляются результаты роста волос?',
        'Можно ли использовать лосьон для роста волос на бороде?',
        'Какие побочные эффекты могут возникнуть при использовании?',
        'Как работает миноксидил?',
    ];
}

function minoksidil_product_suitable_default(): string {
    return '<p>Мы продаём только оригинальные лосьоны для восстановления волос. Привозим их из Америки и Индии, не работаем с сомнительными копиями и не собираем ассортимент ради количества. В нашей линейке — только те препараты, которые действительно помогают восстанавливать волосы и многократно проверены на практике.</p>'
        . '<p>Для нас важно не просто продать человеку лосьон, а показать, что восстановление волос — это доступный и не сложный процесс, которым может воспользоваться практически любой человек. Без лишних усложнений, без мифов и без ощущения, что для результата нужны какие-то недостижимые решения.</p>'
        . '<p>Именно поэтому мы не ограничиваемся только продажей. Мы собираем понятную информацию, инструкции, ответы на частые вопросы и рекомендации по использованию, чтобы человек мог либо самостоятельно разобраться в теме, либо обратиться к нам за консультацией.</p>'
        . '<p>Наша идея простая: дать человеку оригинальный препарат, понятную схему, рабочий инструмент и поддержку. Чтобы восстановление волос было не пугающей историей, а лёгким процессом с хорошими результатами!</p>';
}

function minoksidil_product_suitable_text($product_id): string {
    $product_id = (int) $product_id;
    $acf = function_exists('minoksidil_acf') ? minoksidil_acf('product_suitable', '', $product_id) : '';
    if (is_string($acf) && trim(wp_strip_all_tags($acf)) !== '') {
        $html = $acf;
    } else {
        $meta = (string) get_post_meta($product_id, '_product_suitable', true);
        $html = $meta !== '' ? wpautop($meta) : minoksidil_product_suitable_default();
    }
    if (!preg_match('/<p[\s>]/i', $html)) {
        $html = wpautop($html);
    }
    return $html;
}

function minoksidil_html_columns($html): array {
    $html = (string) $html;
    if (!preg_match_all('/<p\b[^>]*>.*?<\/p>/is', $html, $matches) || count($matches[0]) < 2) {
        return [$html, ''];
    }
    $paras = $matches[0];
    $mid   = (int) ceil(count($paras) / 2);
    return [
        implode('', array_slice($paras, 0, $mid)),
        implode('', array_slice($paras, $mid)),
    ];
}

function minoksidil_product_faq_items($product_id): array {
    $product_id = (int) $product_id;
    $rows = function_exists('minoksidil_acf_rows') ? minoksidil_acf_rows('product_faq', [], $product_id) : [];
    if (!$rows) {
        $raw = get_post_meta($product_id, '_product_faq', true);
        $rows = is_array($raw) ? $raw : [];
    }
    $out = [];
    foreach ($rows as $row) {
        $q = trim((string) ($row['question'] ?? ''));
        $a = trim((string) ($row['answer'] ?? ''));
        if ($q === '') {
            continue;
        }
        $out[] = ['question' => $q, 'answer' => $a];
    }
    if (!$out) {
        foreach (minoksidil_product_faq_questions() as $q) {
            $out[] = ['question' => $q, 'answer' => ''];
        }
    }
    return $out;
}

function minoksidil_product_fields_render(WP_Post $post): void {
    wp_nonce_field('minoksidil_product_fields_save', 'minoksidil_product_nonce');
    $sostav = (string) get_post_meta($post->ID, '_product_sostav', true);
    $sposob = (string) get_post_meta($post->ID, '_product_sposob', true);
    ?>
    <p>
      <label style="font-weight:600;display:block;margin-bottom:4px;" for="product_sostav">Состав</label>
      <textarea id="product_sostav" name="product_sostav" rows="5"
                style="width:100%;"><?php echo esc_textarea($sostav); ?></textarea>
    </p>
    <p style="margin-top:12px;">
      <label style="font-weight:600;display:block;margin-bottom:4px;" for="product_sposob">Способ применения</label>
      <textarea id="product_sposob" name="product_sposob" rows="5"
                style="width:100%;"><?php echo esc_textarea($sposob); ?></textarea>
    </p>
    <?php
}

add_action('save_post_product', function ($post_id): void {
    $post_id = (int) $post_id;
    if (!isset($_POST['minoksidil_product_nonce'])) return;
    if (!wp_verify_nonce((string) wp_unslash($_POST['minoksidil_product_nonce']), 'minoksidil_product_fields_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    foreach (['product_sostav' => '_product_sostav', 'product_sposob' => '_product_sposob'] as $field => $meta_key) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $meta_key, sanitize_textarea_field(wp_unslash($_POST[$field])));
        }
    }
});

// ===== Remove breadcrumbs =====
remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);

// ===== Product columns =====
add_filter('loop_shop_columns', fn() => 4);
add_filter('loop_shop_per_page', fn() => 12);

// ===== Product card: add custom badge, hide rating on loop =====
remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5);

// Add "NEW" badge for featured products
add_action('woocommerce_before_shop_loop_item_title', function () {
    global $product;
    if ($product->is_featured()) {
        echo '<span class="product-card__badge">NEW</span>';
    }
}, 5);

// ===== Customise "Add to cart" button text =====
add_filter('woocommerce_product_add_to_cart_text', fn() => 'В корзину');
add_filter('woocommerce_product_single_add_to_cart_text', fn() => 'Добавить в корзину');

// ===== Email: notify admin on new order =====
// Стандартное письмо WooCommerce «Новый заказ» админу отключаем —
// вместо него уходит только наше письмо из minoksidil_send_order_email_to_admin()
add_filter('woocommerce_email_enabled_new_order', '__return_false');

add_action('woocommerce_checkout_order_processed', 'minoksidil_send_order_email_to_admin', 10, 3);

function minoksidil_send_order_email_to_admin(int $order_id, array $posted_data, WC_Order $order): void {
    $admin_email = minoksidil_notification_email();

    $subject = sprintf('Новый заказ #%d на сайте %s', $order->get_order_number(), get_bloginfo('name'));

    ob_start();
    minoksidil_render_order_email($order);
    $message = ob_get_clean();

    $headers = ['Content-Type: text/html; charset=UTF-8'];

    wp_mail($admin_email, $subject, $message, $headers);
}

function minoksidil_render_order_email(WC_Order $order): void {
    $delivery_type     = (string) $order->get_meta('_delivery_type', true);
    $delivery_label    = minoksidil_delivery_type_labels()[$delivery_type] ?? $delivery_type;
    $delivery_address  = minoksidil_order_delivery_address($order);
    $pvz_service_label = minoksidil_order_pvz_service_label($order);
    $address_label     = $delivery_type === 'pvz' ? 'Адрес ПВЗ' : 'Адрес';
    ?>
    <!DOCTYPE html>
    <html>
    <head><meta charset="UTF-8"><title>Новый заказ</title></head>
    <body style="font-family: Arial, sans-serif; color: #000d24; background: #f3f3f3; padding: 20px;">
      <div style="max-width: 600px; margin: 0 auto; background: #fff; border-radius: 16px; padding: 40px;">
        <h1 style="color: #0063b1; margin-bottom: 8px;">
          Новый заказ #<?php echo esc_html($order->get_order_number()); ?>
        </h1>
        <p style="color: #666; margin-top: 0;">
          <?php echo esc_html(wp_date('d.m.Y H:i', strtotime($order->get_date_created()->format('Y-m-d H:i:s')))); ?>
        </p>

        <h2 style="border-top: 1px solid #eee; padding-top: 20px;">Данные покупателя</h2>
        <table style="width:100%; border-collapse: collapse;">
          <?php
          $contact_method = $order->get_meta('_contact_method', true);
          $contact_value  = $order->get_meta('_contact_value', true);
          $contact_label  = minoksidil_contact_labels()[$contact_method] ?? '';
          ?>
          <tr><td style="padding:6px 0; color:#666; width:140px;">Имя:</td>
              <td style="padding:6px 0; font-weight:600;"><?php echo esc_html($order->get_formatted_billing_full_name()); ?></td></tr>
          <tr><td style="padding:6px 0; color:#666;">Телефон:</td>
              <td style="padding:6px 0; font-weight:600;"><?php echo esc_html($order->get_billing_phone()); ?></td></tr>
          <?php if ($contact_label): ?>
          <tr><td style="padding:6px 0; color:#666;">Способ связи:</td>
              <td style="padding:6px 0; font-weight:600;"><?php echo esc_html($contact_label); ?></td></tr>
          <?php endif; ?>
          <?php if ($contact_value): ?>
          <tr><td style="padding:6px 0; color:#666;">Контакт:</td>
              <td style="padding:6px 0; font-weight:600;"><?php echo esc_html($contact_value); ?></td></tr>
          <?php endif; ?>
          <?php $raw_email = $order->get_billing_email(); if ($raw_email && !str_ends_with($raw_email, '@order.local')): ?>
          <tr><td style="padding:6px 0; color:#666;">Email:</td>
              <td style="padding:6px 0; font-weight:600;"><?php echo esc_html($raw_email); ?></td></tr>
          <?php endif; ?>
        </table>

        <h2 style="border-top: 1px solid #eee; padding-top: 20px;">Доставка</h2>
        <table style="width:100%; border-collapse: collapse;">
          <tr><td style="padding:6px 0; color:#666; width:140px;">Способ:</td>
              <td style="padding:6px 0; font-weight:600;"><?php echo esc_html($delivery_label); ?></td></tr>
          <?php if ($delivery_type === 'pvz') : ?>
          <tr><td style="padding:6px 0; color:#666;">Служба:</td>
              <td style="padding:6px 0; font-weight:600;"><?php echo esc_html($pvz_service_label !== '' ? $pvz_service_label : '—'); ?></td></tr>
          <?php endif; ?>
          <?php if ($delivery_address !== '') : ?>
          <tr><td style="padding:6px 0; color:#666;"><?php echo esc_html($address_label); ?>:</td>
              <td style="padding:6px 0; font-weight:600;"><?php echo esc_html($delivery_address); ?></td></tr>
          <?php endif; ?>
        </table>

        <?php if ($order->get_customer_note()): ?>
        <h2 style="border-top: 1px solid #eee; padding-top: 20px;">Комментарий</h2>
        <p><?php echo esc_html($order->get_customer_note()); ?></p>
        <?php endif; ?>

        <h2 style="border-top: 1px solid #eee; padding-top: 20px;">Состав заказа</h2>
        <table style="width:100%; border-collapse: collapse; border: 1px solid #eee;">
          <thead>
            <tr style="background:#0063b1; color:#fff;">
              <th style="padding:10px; text-align:left;">Товар</th>
              <th style="padding:10px; text-align:center;">Кол-во</th>
              <th style="padding:10px; text-align:right;">Сумма</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($order->get_items() as $item): ?>
            <tr style="border-bottom: 1px solid #eee;">
              <td style="padding:10px;"><?php echo esc_html($item->get_name()); ?></td>
              <td style="padding:10px; text-align:center;"><?php echo absint($item->get_quantity()); ?></td>
              <td style="padding:10px; text-align:right;"><?php echo wp_kses_post($order->get_formatted_line_subtotal($item)); ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
          <tfoot>
            <?php $coupon_items = $order->get_items('coupon'); ?>
            <?php if ($coupon_items): ?>
            <tr style="border-bottom:1px solid #eee;">
              <td colspan="2" style="padding:10px; color:#666;">Сумма без скидки:</td>
              <td style="padding:10px; text-align:right; width:33%;">
                <?php echo wp_kses_post(wc_price($order->get_subtotal(), ['currency' => $order->get_currency()])); ?>
              </td>
            </tr>
            <?php foreach ($coupon_items as $coupon_item): ?>
            <tr style="border-bottom:1px solid #eee;">
              <td colspan="2" style="padding:10px; color:#2e7d32;">
                Промокод: <strong><?php echo esc_html(strtoupper($coupon_item->get_code())); ?></strong>
              </td>
              <td style="padding:10px; text-align:right; color:#2e7d32;">
                −<?php echo wp_kses_post(wc_price((float) $coupon_item->get_discount() + (float) $coupon_item->get_discount_tax(), ['currency' => $order->get_currency()])); ?>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            <tr style="background:#f3f3f3; font-weight:700;">
              <td colspan="2" style="padding:10px;">Итого:</td>
              <td style="padding:10px; text-align:right; color:#0063b1; font-size:18px; width: 33%;">
                <?php echo wp_kses_post($order->get_formatted_order_total()); ?>
              </td>
            </tr>
          </tfoot>
        </table>

        <p style="margin-top: 30px;">
          <a href="<?php echo esc_url(admin_url('post.php?post=' . $order->get_id() . '&action=edit')); ?>"
             style="background:#0063b1; color:#fff; padding:12px 24px; border-radius:8px; text-decoration:none;">
            Открыть заказ в админке
          </a>
        </p>
      </div>
    </body>
    </html>
    <?php
}

// ===== Save custom checkout fields to order meta =====
add_action('woocommerce_checkout_create_order', 'minoksidil_save_checkout_meta', 10, 2);

function minoksidil_delivery_type_labels(): array {
    return [
        'pvz'     => 'Пункт выдачи заказов (ПВЗ)',
        'courier' => 'Экспресс курьер по Москве',
        'post'    => 'Почта России',
    ];
}

function minoksidil_pvz_service_labels(): array {
    return [
        'cdek'   => 'СДЭК',
        'yandex' => 'Яндекс Маркет',
        'ozon'   => 'Ozon',
    ];
}

function minoksidil_order_delivery_address(WC_Order $order): string {
    $pvz = trim((string) $order->get_meta('_pvz_address', true));
    if ($pvz !== '') {
        return $pvz;
    }
    $shipping = trim((string) $order->get_shipping_address_1());
    return $shipping;
}

function minoksidil_order_pvz_service_label(WC_Order $order): string {
    $service = (string) $order->get_meta('_pvz_service', true);
    if ($service === '') {
        return '';
    }
    $labels = minoksidil_pvz_service_labels();
    return $labels[$service] ?? strtoupper($service);
}

function minoksidil_save_checkout_meta(WC_Order $order, array $data): void {
    $delivery_type = isset($_POST['delivery_type']) ? sanitize_text_field(wp_unslash($_POST['delivery_type'])) : 'courier';
    $order->update_meta_data('_delivery_type', $delivery_type);

    $pvz_service = isset($_POST['pvz_service']) ? sanitize_text_field(wp_unslash($_POST['pvz_service'])) : '';
    $pvz_code    = isset($_POST['pvz_code']) ? sanitize_text_field(wp_unslash($_POST['pvz_code'])) : '';
    $address     = isset($_POST['pvz_address']) ? sanitize_text_field(wp_unslash($_POST['pvz_address'])) : '';
    if ($address === '' && isset($_POST['shipping_address_1'])) {
        $address = sanitize_text_field(wp_unslash($_POST['shipping_address_1']));
    }

    $order->update_meta_data('_pvz_service', $pvz_service);
    $order->update_meta_data('_pvz_code', $pvz_code);
    $order->update_meta_data('_pvz_address', $address);

    if ($address !== '') {
        $order->set_shipping_address_1($address);
        $order->set_shipping_country('RU');
        if ($order->get_shipping_first_name() === '') {
            $order->set_shipping_first_name($order->get_billing_first_name());
        }
        if ($order->get_shipping_last_name() === '') {
            $order->set_shipping_last_name($order->get_billing_last_name());
        }
    }
}

// ===== Validate: require ПВЗ selection if delivery type is pvz =====
add_action('woocommerce_checkout_process', function () {
    $delivery_type = isset($_POST['delivery_type']) ? sanitize_text_field(wp_unslash($_POST['delivery_type'])) : '';
    if ($delivery_type === 'pvz') {
        $pvz_address = isset($_POST['pvz_address']) ? sanitize_text_field(wp_unslash($_POST['pvz_address'])) : '';
        if (empty($pvz_address)) {
            wc_add_notice('Пожалуйста, выберите пункт выдачи заказов на карте.', 'error');
        }
    }
});

// ===== Display delivery info in admin order view =====
add_action('woocommerce_admin_order_data_after_shipping_address', function (WC_Order $order) {
    $delivery_type = (string) $order->get_meta('_delivery_type', true);
    if ($delivery_type === '') {
        return;
    }

    $labels = minoksidil_delivery_type_labels();
    $label  = $labels[$delivery_type] ?? $delivery_type;
    echo '<p><strong>Тип доставки:</strong> ' . esc_html($label) . '</p>';

    if ($delivery_type === 'pvz') {
        $svc_label = minoksidil_order_pvz_service_label($order);
        echo '<p><strong>Служба доставки:</strong> ' . esc_html($svc_label !== '' ? $svc_label : '—') . '</p>';
    }

    $address = minoksidil_order_delivery_address($order);
    if ($address !== '') {
        $addr_label = $delivery_type === 'pvz' ? 'Адрес ПВЗ' : 'Адрес доставки';
        echo '<p><strong>' . esc_html($addr_label) . ':</strong> ' . esc_html($address) . '</p>';
    }
}, 10);

// ===== Оставляем только нужные поля оформления =====
add_filter('woocommerce_checkout_fields', function (array $fields): array {
    $keep = ['billing_first_name', 'billing_last_name', 'billing_phone', 'billing_email'];
    foreach ($fields['billing'] as $key => $field) {
        if (!in_array($key, $keep, true)) {
            unset($fields['billing'][$key]);
        }
    }
    // Email необязателен — передаём авто-email если пуст
    if (isset($fields['billing']['billing_email'])) {
        $fields['billing']['billing_email']['required'] = false;
    }
    unset($fields['shipping']);
    return $fields;
});

// ===== Если email пустой — подставляем авто-email из телефона =====
add_action('woocommerce_checkout_process', function () {
    $email = isset($_POST['billing_email']) ? trim(sanitize_email(wp_unslash($_POST['billing_email']))) : '';
    $phone = isset($_POST['billing_phone']) ? preg_replace('/\D/', '', wp_unslash($_POST['billing_phone'])) : '';
    if (empty($email) && !empty($phone)) {
        $_POST['billing_email'] = $phone . '@order.local';
    }
}, 5);

// ===== Способ связи: подписи =====
function minoksidil_contact_labels(): array {
    return [
        'telegram' => 'Telegram',
        'whatsapp' => 'WhatsApp',
        'email'    => 'E-mail',
        'phone'    => 'Телефон',
        'Телефон'  => 'Телефон', // старое значение радиокнопки — для уже созданных заказов
        'max'      => 'MAX',
        'vk'       => 'ВКонтакте',
    ];
}

// ===== Validate: контакт для выбранного способа связи обязателен =====
add_action('woocommerce_checkout_process', function (): void {
    $method = isset($_POST['contact_method']) ? sanitize_text_field(wp_unslash($_POST['contact_method'])) : '';
    $value  = isset($_POST['contact_value']) ? trim(sanitize_text_field(wp_unslash($_POST['contact_value']))) : '';
    $labels = minoksidil_contact_labels();

    if (!isset($labels[$method])) {
        wc_add_notice('Выберите способ связи.', 'error');
        return;
    }
    // «Телефон» — контактом служит billing_phone, он обязателен на уровне WooCommerce
    if ($method === 'phone' || $method === 'Телефон') {
        return;
    }
    if ($value === '') {
        wc_add_notice(sprintf('Укажите контакт для связи (%s).', $labels[$method]), 'error');
    } elseif ($method === 'email' && !is_email($value)) {
        wc_add_notice('Укажите корректный e-mail для связи.', 'error');
    }
});

// ===== Сохраняем способ связи и контакт в order meta =====
add_action('woocommerce_checkout_create_order', function (WC_Order $order, array $data): void {
    $contact = isset($_POST['contact_method']) ? sanitize_text_field(wp_unslash($_POST['contact_method'])) : '';
    $value   = isset($_POST['contact_value']) ? trim(sanitize_text_field(wp_unslash($_POST['contact_value']))) : '';
    if ($value === '' && ($contact === 'phone' || $contact === 'Телефон')) {
        $value = isset($_POST['billing_phone']) ? sanitize_text_field(wp_unslash($_POST['billing_phone'])) : '';
    }
    if ($contact) {
        $order->update_meta_data('_contact_method', $contact);
    }
    if ($value !== '') {
        $order->update_meta_data('_contact_value', $value);
    }
}, 10, 2);

// ===== Способ связи в карточке заказа в админке =====
add_action('woocommerce_admin_order_data_after_billing_address', function (WC_Order $order): void {
    $method = $order->get_meta('_contact_method', true);
    $value  = $order->get_meta('_contact_value', true);
    if (!$method && !$value) return;

    $labels = minoksidil_contact_labels();
    $label  = $labels[$method] ?? $method;
    echo '<p><strong>Способ связи:</strong> ' . esc_html($label)
        . ($value ? ' — ' . esc_html($value) : '') . '</p>';
});

// ===== AJAX: обновление количества в корзине =====
add_action('wp_ajax_minoksidil_update_cart_item', 'minoksidil_ajax_update_cart_item');
add_action('wp_ajax_nopriv_minoksidil_update_cart_item', 'minoksidil_ajax_update_cart_item');

function minoksidil_ajax_update_cart_item(): void {
    check_ajax_referer('minoksidil_nonce', 'nonce');

    $key = sanitize_text_field(wp_unslash($_POST['key'] ?? ''));
    $qty = max(0, absint($_POST['qty'] ?? 0));

    if (!$key) {
        wp_send_json_error();
    }

    if ($qty === 0) {
        WC()->cart->remove_cart_item($key);
    } else {
        WC()->cart->set_quantity($key, $qty);
    }

    WC()->cart->calculate_totals();

    wp_send_json_success([
        'total'    => WC()->cart->get_total(),
        'subtotal' => WC()->cart->get_cart_subtotal(),
    ]);
}

// ===== AJAX: callback form =====
add_action('wp_ajax_minoksidil_callback', 'minoksidil_handle_callback');
add_action('wp_ajax_nopriv_minoksidil_callback', 'minoksidil_handle_callback');

function minoksidil_handle_callback(): void {
    check_ajax_referer('minoksidil_callback', 'callback_nonce');

    $name    = sanitize_text_field(wp_unslash($_POST['callback_name'] ?? ''));
    $phone   = sanitize_text_field(wp_unslash($_POST['callback_phone'] ?? ''));
    $email   = sanitize_email(wp_unslash($_POST['callback_email'] ?? ''));
    $message = sanitize_textarea_field(wp_unslash($_POST['callback_message'] ?? ''));

    if (empty($name) || empty($phone)) {
        wp_send_json_error(['message' => 'Заполните обязательные поля.']);
    }

    // Сохраняем заявку в админку до отправки письма — иначе при сбое почты она пропадёт бесследно.
    minoksidil_save_request_lead($name, $phone, $email, $message, 'Форма на сайте (модальное окно)');

    $admin_email = minoksidil_notification_email();
    $subject     = 'Заявка на обратный звонок с сайта ' . get_bloginfo('name');
    $body        = sprintf(
        "<p><b>Имя:</b> %s</p><p><b>Телефон:</b> %s</p><p><b>Email:</b> %s</p><p><b>Сообщение:</b> %s</p>",
        esc_html($name),
        esc_html($phone),
        esc_html($email),
        esc_html($message)
    );
    $headers = ['Content-Type: text/html; charset=UTF-8'];

    wp_mail($admin_email, $subject, $body, $headers);

    wp_send_json_success(['message' => 'Заявка отправлена! Мы свяжемся с вами в ближайшее время.']);
}

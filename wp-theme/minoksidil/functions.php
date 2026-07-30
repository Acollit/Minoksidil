<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;

define('MINOKSIDIL_IMG', get_template_directory_uri() . '/assets/img/');

// ===== Theme Setup =====
function minoksidil_setup(): void {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('woocommerce', [
        'thumbnail_image_width' => 600,
        'single_image_width'    => 800,
    ]);
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');

    register_nav_menus([
        'primary' => 'Основное меню',
        'footer'  => 'Меню подвала',
    ]);
}
add_action('after_setup_theme', 'minoksidil_setup');

// ===== Enqueue Scripts & Styles =====
function minoksidil_enqueue(): void {
    $v   = '1.0.0';
    $uri = get_template_directory_uri();

    // Google Fonts (как в оригинале)
    wp_enqueue_style('minoksidil-fonts-pre1', false);
    add_action('wp_head', function () {
        echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
        echo '<link href="https://fonts.googleapis.com/css2?family=Onest:wght@400;500;600;700&display=swap" rel="stylesheet">' . "\n";
    }, 1);

    // Gulp скомпилированные стили
    wp_enqueue_style('minoksidil-vendor', $uri . '/assets/css/vendor.css', [], $v);
    wp_enqueue_style('minoksidil-main',   $uri . '/assets/css/main.css', ['minoksidil-vendor'], $v);

    // WooCommerce-специфичные стили (то чего нет в gulp-CSS)
    if (is_woocommerce() || is_cart() || is_checkout() || is_account_page()) {
        wp_enqueue_style('minoksidil-woo', $uri . '/assets/css/woocommerce-custom.css', ['minoksidil-main'], $v);
    }

    // Карты ПВЗ — на корзине (оформление встроено) и на checkout
    if (is_cart() || is_checkout()) {
        wp_enqueue_script('minoksidil-delivery-maps', $uri . '/assets/js/delivery-maps.js', ['jquery'], $v, true);
        wp_localize_script('minoksidil-delivery-maps', 'minoksidilConfig', [
            'ymapsApiKey' => esc_js(get_option('minoksidil_ymaps_key', '')),
            'ajaxUrl'     => admin_url('admin-ajax.php'),
            'nonce'       => wp_create_nonce('minoksidil_nonce'),
        ]);
    }

    // Основной JS (gulp-скомпилированный заменён на WP-адаптацию)
    wp_enqueue_script('minoksidil-main', $uri . '/assets/js/main.js', [], $v, true);
}
add_action('wp_enqueue_scripts', 'minoksidil_enqueue');

// ===== Корзина: загружать наш шаблон напрямую как полную страницу =====
// Иначе WordPress грузит page.php → внутри him WC шорткод → внутри him cart.php
// → get_header() вызывается дважды, получается дубль хедера/футера.
add_filter('template_include', function (string $template): string {
    if (is_cart()) {
        $custom = get_template_directory() . '/woocommerce/cart/cart.php';
        if (file_exists($custom)) return $custom;
    }
    return $template;
});

// Checkout отдельной страницей не нужен — редиректим на корзину,
// но пропускаем страницу "Спасибо за заказ" и оплату.
add_action('template_redirect', function (): void {
    if (is_checkout() && !is_order_received_page() && !is_checkout_pay_page()) {
        wp_safe_redirect(wc_get_cart_url());
        exit;
    }
});

// Убираем дефолтные стили WooCommerce
add_filter('woocommerce_enqueue_styles', '__return_empty_array');
add_action('wp_enqueue_scripts', function () {
    wp_dequeue_style('wc-blocks-style');
}, 100);

// ===== Include modules =====
require_once get_template_directory() . '/inc/woocommerce.php';
require_once get_template_directory() . '/inc/no-payment-gateway.php';
require_once get_template_directory() . '/inc/delivery-maps.php';
require_once get_template_directory() . '/inc/pvz-admin.php';
require_once get_template_directory() . '/inc/admin-settings.php';
require_once get_template_directory() . '/inc/cpt-results.php';
require_once get_template_directory() . '/inc/cpt-requests.php';
require_once get_template_directory() . '/inc/acf-fields.php';
require_once get_template_directory() . '/inc/cf7.php';

// ===== Image sizes =====
add_image_size('product-catalog', 600, 600, true);
add_image_size('product-single', 800, 800, false);

// ===== Helper: inline AJAX form script =====
function minoksidil_page_form_script(string $form_id): void {
    $ajax_url = esc_url(admin_url('admin-ajax.php'));
    ?>
    <script>
    (function () {
      var form = document.querySelector('#<?php echo esc_js($form_id); ?>');
      if (!form) return;
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        var btn = form.querySelector('[type="submit"]');
        var result = form.querySelector('.form-result');
        var orig = btn.textContent;
        btn.disabled = true; btn.textContent = 'Отправка...';
        fetch('<?php echo $ajax_url; ?>', {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body: new URLSearchParams(new FormData(form)),
        }).then(function(r){ return r.json(); }).then(function(d){
          if (result) {
            result.hidden = false;
            result.style.color = d.success ? 'var(--color-green)' : 'var(--color-red)';
            result.textContent = d.success ? 'Заявка отправлена! Мы свяжемся с вами.' : (d.data && d.data.message ? d.data.message : 'Ошибка отправки.');
          }
          if (d.success) form.reset();
        }).finally(function(){ btn.disabled = false; btn.textContent = orig; });
      });
    })();
    </script>
    <?php
}

// ===== Helper: cart SVG icon =====
function minoksidil_cart_svg(string $fill = '#0063B1'): string {
    return '<svg width="30" height="26" viewBox="0 0 30 26" fill="none" xmlns="http://www.w3.org/2000/svg">
      <path d="M5.53471 26C5.15159 26 4.80228 25.8805 4.48677 25.6415C4.17126 25.4015 3.95716 25.0877 3.84448 24.7L0.0583643 10.8789C-0.0543177 10.5596 -0.00383613 10.2632 0.209809 9.98947C0.424356 9.71579 0.71192 9.57895 1.0725 9.57895H7.90103L13.8506 0.61579C13.9633 0.433333 14.1211 0.285088 14.3239 0.171053C14.5267 0.0570175 14.7408 0 14.9662 0C15.1916 0 15.4057 0.0570175 15.6085 0.171053C15.8113 0.285088 15.9691 0.433333 16.0817 0.61579L22.0314 9.57895H28.9275C29.2881 9.57895 29.5756 9.71579 29.7902 9.98947C30.0038 10.2632 30.0543 10.5596 29.9416 10.8789L26.1555 24.7C26.0428 25.0877 25.8287 25.4015 25.5132 25.6415C25.1977 25.8805 24.8484 26 24.4653 26H5.53471ZM11.1801 9.57895H18.7861L14.9662 3.83158L11.1801 9.57895ZM15 20.5263C15.7437 20.5263 16.3806 20.2586 16.9106 19.7231C17.4398 19.1866 17.7044 18.5421 17.7044 17.7895C17.7044 17.0368 17.4398 16.3923 16.9106 15.8559C16.3806 15.3204 15.7437 15.0526 15 15.0526C14.2563 15.0526 13.6199 15.3204 13.0907 15.8559C12.5607 16.3923 12.2956 17.0368 12.2956 17.7895C12.2956 18.5421 12.5607 19.1866 13.0907 19.7231C13.6199 20.2586 14.2563 20.5263 15 20.5263Z"
            fill="' . esc_attr($fill) . '"/>
    </svg>';
}

/**
 * Обновление счётчика корзины в шапке через AJAX-фрагменты WooCommerce.
 */
function minoksidil_cart_count_fragment(array $fragments): array {
    $count = (function_exists('WC') && WC()->cart) ? WC()->cart->get_cart_contents_count() : 0;
    $class = 'header__count' . ($count ? '' : ' is-empty');
    $fragments['.header__count'] = '<span class="' . esc_attr($class) . '">' . esc_html($count) . '</span>';
    return $fragments;
}
add_filter('woocommerce_add_to_cart_fragments', 'minoksidil_cart_count_fragment');

/**
 * AJAX-добавление в корзину для карточек на страницах вне каталога WooCommerce
 * (главная, кастомные шаблоны). На woo-страницах скрипт грузит сам WooCommerce.
 * Приоритет 20 — чтобы WooCommerce успел зарегистрировать свои скрипты.
 */
function minoksidil_frontpage_cart_scripts(): void {
    if (!function_exists('WC') || is_woocommerce() || is_cart() || is_checkout()) {
        return;
    }

    wp_enqueue_script('wc-add-to-cart');
    wp_localize_script('wc-add-to-cart', 'wc_add_to_cart_params', [
        'ajax_url'                => WC()->ajax_url(),
        'wc_ajax_url'             => WC_AJAX::get_endpoint('%%endpoint%%'),
        'i18n_view_cart'          => esc_attr__('Посмотреть корзину', 'minoksidil'),
        'cart_url'                => apply_filters('woocommerce_add_to_cart_redirect', wc_get_cart_url(), null),
        'is_cart'                 => is_cart(),
        'cart_redirect_after_add' => get_option('woocommerce_cart_redirect_after_add'),
    ]);
}
add_action('wp_enqueue_scripts', 'minoksidil_frontpage_cart_scripts', 20);

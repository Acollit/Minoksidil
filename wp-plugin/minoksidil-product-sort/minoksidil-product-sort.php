<?php
/**
 * Plugin Name: Minoksidil — Порядок сортировки товаров
 * Description: Drag-and-drop сортировка товаров WooCommerce в админке. Заданный порядок применяется на витрине каталога, в категориях, поиске и в шорткодах товаров.
 * Version:     1.0.0
 * Author:      Minoksidil
 * Requires PHP: 7.0
 * Text Domain: minoksidil-product-sort
 *
 * @package Minoksidil_Product_Sort
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MPS_VERSION', '1.0.0' );
define( 'MPS_FILE', __FILE__ );
define( 'MPS_DIR', plugin_dir_path( __FILE__ ) );
define( 'MPS_URL', plugin_dir_url( __FILE__ ) );

/** Опция режима применения порядка: '1' — принудительно, '0' — только по умолчанию. */
define( 'MPS_OPT_FORCE', 'mps_force_order' );

/**
 * Значение опции по умолчанию задаётся при активации.
 */
register_activation_hook( __FILE__, function () {
	if ( null === get_option( MPS_OPT_FORCE, null ) ) {
		update_option( MPS_OPT_FORCE, '1' );
	}
} );

/**
 * Инициализация после загрузки плагинов (нужен активный WooCommerce).
 */
add_action( 'plugins_loaded', 'mps_bootstrap' );
function mps_bootstrap() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'mps_notice_no_wc' );
		return;
	}

	require_once MPS_DIR . 'includes/class-mps-admin.php';
	require_once MPS_DIR . 'includes/class-mps-ajax.php';
	require_once MPS_DIR . 'includes/class-mps-frontend.php';

	new MPS_Admin();
	new MPS_Ajax();
	new MPS_Frontend();
}

/**
 * Уведомление, если WooCommerce не активен.
 */
function mps_notice_no_wc() {
	echo '<div class="notice notice-error"><p>'
		. esc_html__( 'Плагин «Порядок сортировки товаров» требует активного WooCommerce.', 'minoksidil-product-sort' )
		. '</p></div>';
}

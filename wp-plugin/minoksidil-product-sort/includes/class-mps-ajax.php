<?php
/**
 * AJAX-сохранение порядка товаров (menu_order).
 *
 * @package Minoksidil_Product_Sort
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MPS_Ajax {

	public function __construct() {
		add_action( 'wp_ajax_mps_save_order', array( $this, 'save_order' ) );
	}

	/**
	 * Сохраняет новый порядок: menu_order = offset + позиция в списке.
	 */
	public function save_order() {
		if ( ! current_user_can( MPS_Admin::CAP ) ) {
			wp_send_json_error( array( 'message' => 'Недостаточно прав' ), 403 );
		}
		check_ajax_referer( 'mps_save_order', 'nonce' );

		$order  = isset( $_POST['order'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['order'] ) ) : array();
		$offset = isset( $_POST['offset'] ) ? max( 0, (int) $_POST['offset'] ) : 0;
		$order  = array_filter( $order );

		if ( empty( $order ) ) {
			wp_send_json_error( array( 'message' => 'Пустой список' ) );
		}

		global $wpdb;
		$position = $offset;
		foreach ( $order as $id ) {
			// Прямое обновление menu_order — быстро и без побочных хуков сохранения товара.
			$wpdb->update( $wpdb->posts, array( 'menu_order' => $position ), array( 'ID' => $id ) ); // phpcs:ignore WordPress.DB
			clean_post_cache( $id );
			if ( function_exists( 'wc_delete_product_transients' ) ) {
				wc_delete_product_transients( $id );
			}
			$position++;
		}

		// Сбрасываем общий кэш/таблицу поиска товаров WooCommerce.
		if ( function_exists( 'wc_delete_product_transients' ) ) {
			wc_delete_product_transients();
		}

		wp_send_json_success( array( 'updated' => count( $order ) ) );
	}
}

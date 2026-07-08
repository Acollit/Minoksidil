<?php
/**
 * Применение заданного порядка (menu_order) на витрине.
 *
 * @package Minoksidil_Product_Sort
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MPS_Frontend {

	/** Принудительный режим. */
	private $force;

	public function __construct() {
		$this->force = '1' === get_option( MPS_OPT_FORCE, '1' );

		// «Наш порядок» — сортировка по умолчанию в каталоге (всегда).
		add_filter( 'woocommerce_default_catalog_orderby', array( $this, 'default_orderby' ), 20 );

		// Шорткоды товаров: применяем порядок, если в самом шорткоде не задана сортировка.
		add_filter( 'woocommerce_shortcode_products_query', array( $this, 'shortcode_args' ), 20, 3 );

		if ( $this->force ) {
			// Жёстко переопределяем сортировку каталога и оставляем в выпадающем списке только наш вариант.
			add_filter( 'woocommerce_get_catalog_ordering_args', array( $this, 'catalog_args' ), 9999 );
			add_filter( 'woocommerce_catalog_orderby', array( $this, 'orderby_options' ), 9999 );
		}
	}

	/**
	 * Сортировка по умолчанию в каталоге.
	 */
	public function default_orderby( $default ) {
		return 'menu_order';
	}

	/**
	 * Принудительная сортировка каталога/категорий/поиска.
	 *
	 * @param array $args Аргументы сортировки WooCommerce.
	 * @return array
	 */
	public function catalog_args( $args ) {
		$args['orderby']  = 'menu_order title';
		$args['order']    = 'ASC';
		$args['meta_key'] = ''; // phpcs:ignore WordPress.DB.SlowDBQuery
		return $args;
	}

	/**
	 * Оставляем в списке сортировок каталога только «наш порядок».
	 *
	 * @param array $options Опции выпадающего списка.
	 * @return array
	 */
	public function orderby_options( $options ) {
		return array( 'menu_order' => 'По нашему порядку' );
	}

	/**
	 * Сортировка для шорткодов [products], [product_category] и т.п.
	 * Не трогаем шорткоды с явной сортировкой (best_selling, top_rated, sale…).
	 *
	 * @param array  $query_args Аргументы WP_Query.
	 * @param array  $atts       Атрибуты шорткода.
	 * @param string $type       Тип шорткода.
	 * @return array
	 */
	public function shortcode_args( $query_args, $atts, $type ) {
		if ( empty( $atts['orderby'] ) ) {
			$query_args['orderby'] = 'menu_order title';
			$query_args['order']   = 'ASC';
		}
		return $query_args;
	}
}

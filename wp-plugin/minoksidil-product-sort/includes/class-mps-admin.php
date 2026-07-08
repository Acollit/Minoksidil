<?php
/**
 * Страница админки: drag-and-drop сортировка товаров.
 *
 * @package Minoksidil_Product_Sort
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MPS_Admin {

	/** Слаг страницы. */
	const PAGE = 'mps-product-sort';

	/** Capability для доступа. */
	const CAP = 'manage_woocommerce';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
	}

	/**
	 * Пункт меню в разделе «Товары».
	 */
	public function menu() {
		add_submenu_page(
			'edit.php?post_type=product',
			'Порядок сортировки',
			'Порядок сортировки',
			self::CAP,
			self::PAGE,
			array( $this, 'render' )
		);
	}

	/**
	 * Регистрация настройки режима.
	 */
	public function settings() {
		register_setting(
			'mps_settings',
			MPS_OPT_FORCE,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_force' ),
				'default'           => '1',
			)
		);
	}

	public function sanitize_force( $value ) {
		return $value ? '1' : '0';
	}

	/**
	 * Подключение скриптов/стилей только на странице плагина.
	 *
	 * @param string $hook Текущий хук страницы.
	 */
	public function assets( $hook ) {
		if ( 'product_page_' . self::PAGE !== $hook ) {
			return;
		}

		wp_enqueue_style( 'mps-admin', MPS_URL . 'assets/css/admin.css', array(), MPS_VERSION );
		wp_enqueue_script(
			'mps-admin',
			MPS_URL . 'assets/js/admin.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			MPS_VERSION,
			true
		);
		wp_localize_script(
			'mps-admin',
			'MPS',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'mps_save_order' ),
				'i18n'    => array(
					'saving' => 'Сохранение…',
					'saved'  => 'Порядок сохранён',
					'error'  => 'Ошибка сохранения',
				),
			)
		);
	}

	/**
	 * Текущие GET-фильтры страницы.
	 *
	 * @return array{cat:string,search:string,per_page:int,paged:int}
	 */
	private function current_filters() {
		return array(
			'cat'      => isset( $_GET['product_cat'] ) ? sanitize_title( wp_unslash( $_GET['product_cat'] ) ) : '',
			'search'   => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
			'per_page' => isset( $_GET['mps_per_page'] ) ? max( 1, (int) $_GET['mps_per_page'] ) : 100,
			'paged'    => isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1,
		);
	}

	/**
	 * Рендер страницы.
	 */
	public function render() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}

		$f      = $this->current_filters();
		$offset = ( $f['paged'] - 1 ) * $f['per_page'];

		$args = array(
			'post_type'      => 'product',
			'post_status'    => array( 'publish', 'private', 'draft', 'pending' ),
			'posts_per_page' => $f['per_page'],
			'paged'          => $f['paged'],
			'orderby'        => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
		);
		if ( $f['cat'] ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					'terms'    => $f['cat'],
				),
			);
		}
		if ( '' !== $f['search'] ) {
			$args['s'] = $f['search'];
		}

		$query     = new WP_Query( $args );
		$force_on  = '1' === get_option( MPS_OPT_FORCE, '1' );
		?>
		<div class="wrap mps-wrap">
			<h1>Порядок сортировки товаров</h1>

			<form method="post" action="options.php" class="mps-settings">
				<?php settings_fields( 'mps_settings' ); ?>
				<label>
					<input type="checkbox" name="<?php echo esc_attr( MPS_OPT_FORCE ); ?>" value="1" <?php checked( $force_on ); ?> />
					Применять заданный порядок принудительно на всех витринах
					<span class="description">(каталог, категории, поиск, шорткоды; покупательский выбор сортировки в каталоге заменяется на «наш порядок»)</span>
				</label>
				<?php submit_button( 'Сохранить настройку', 'secondary', 'submit', false ); ?>
			</form>

			<?php if ( ! $force_on ) : ?>
				<div class="notice notice-info inline"><p>
					Принудительный режим выключен: заданный порядок используется как сортировка по умолчанию, но покупатель может выбрать другую сортировку.
				</p></div>
			<?php endif; ?>

			<form method="get" class="mps-filters">
				<input type="hidden" name="post_type" value="product" />
				<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE ); ?>" />

				<?php
				wp_dropdown_categories(
					array(
						'taxonomy'        => 'product_cat',
						'name'            => 'product_cat',
						'value_field'     => 'slug',
						'selected'        => $f['cat'],
						'show_option_all' => 'Все категории',
						'hierarchical'    => true,
						'hide_empty'      => false,
						'orderby'         => 'name',
					)
				);
				?>

				<input type="search" name="s" value="<?php echo esc_attr( $f['search'] ); ?>" placeholder="Поиск по названию" />

				<select name="mps_per_page">
					<?php foreach ( array( 50, 100, 200, 500 ) as $pp ) : ?>
						<option value="<?php echo esc_attr( $pp ); ?>" <?php selected( $f['per_page'], $pp ); ?>><?php echo esc_html( $pp ); ?> на странице</option>
					<?php endforeach; ?>
				</select>

				<?php submit_button( 'Показать', 'secondary', '', false ); ?>
			</form>

			<p class="mps-hint">
				Перетаскивайте товары за иконку <span class="dashicons dashicons-move"></span> — порядок сохраняется автоматически.
				<span class="mps-status" role="status" aria-live="polite"></span>
			</p>

			<?php if ( $query->have_posts() ) : ?>
				<ul class="mps-list" data-offset="<?php echo esc_attr( $offset ); ?>">
					<?php
					while ( $query->have_posts() ) :
						$query->the_post();
						$product = wc_get_product( get_the_ID() );
						if ( ! $product ) {
							continue;
						}
						?>
						<li class="mps-item" data-id="<?php echo esc_attr( get_the_ID() ); ?>">
							<span class="mps-handle dashicons dashicons-move" aria-hidden="true"></span>
							<span class="mps-thumb"><?php echo $product->get_image( array( 48, 48 ) ); // phpcs:ignore ?></span>
							<span class="mps-title">
								<a href="<?php echo esc_url( get_edit_post_link( get_the_ID() ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( get_the_title() ); ?></a>
								<?php if ( 'publish' !== get_post_status() ) : ?>
									<em class="mps-status-label">(<?php echo esc_html( get_post_status() ); ?>)</em>
								<?php endif; ?>
							</span>
							<span class="mps-sku"><?php echo $product->get_sku() ? esc_html( $product->get_sku() ) : '—'; ?></span>
							<span class="mps-price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
						</li>
					<?php endwhile; ?>
				</ul>

				<?php
				$total_pages = (int) $query->max_num_pages;
				if ( $total_pages > 1 ) {
					echo '<div class="mps-pagination tablenav-pages">';
					echo paginate_links( // phpcs:ignore
						array(
							'base'    => add_query_arg( 'paged', '%#%' ),
							'format'  => '',
							'current' => $f['paged'],
							'total'   => $total_pages,
						)
					);
					echo '</div>';
					echo '<p class="description">Сортировка работает в пределах одной страницы. Чтобы упорядочить весь каталог сразу, выберите больше товаров «на странице».</p>';
				}
				?>
			<?php else : ?>
				<p>Товары не найдены.</p>
			<?php endif; ?>
			<?php wp_reset_postdata(); ?>
		</div>
		<?php
	}
}

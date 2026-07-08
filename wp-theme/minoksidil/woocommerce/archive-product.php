<?php
defined('ABSPATH') || exit;
get_header();
?>
<main class="main">

  <!-- CATALOG BANNER -->
  <section class="cat-banner">
    <div class="cat-banner__grass" aria-hidden="true">
      <img src="<?php echo esc_url(MINOKSIDIL_IMG . 'catalog-grass.webp'); ?>" alt="" width="1920" height="469" loading="lazy">
    </div>
    <div class="container cat-banner__inner">
      <h1 class="cat-banner__title"><?php woocommerce_page_title(); ?></h1>
    </div>
  </section>

  <!-- CATALOG CONTENT -->
  <section class="catalog-page">
    <div class="container">
      <div class="catalog-page__layout">

        <?php
        // ===== Получаем активные фильтры из URL =====
        $wc_attributes   = wc_get_attribute_taxonomies(); // все атрибуты WC
        $active_attrs    = [];  // ['type' => ['slug1','slug2'], ...]
        $has_any_filter  = false;

        foreach ($wc_attributes as $attr) {
            $param = 'filter_' . $attr->attribute_name;
            if (!empty($_GET[$param])) {
                $active_attrs[$attr->attribute_name] = array_map(
                    'sanitize_title',
                    explode(',', sanitize_text_field(wp_unslash($_GET[$param])))
                );
                $has_any_filter = true;
            }
        }

        // Активные категории
        $active_cats    = [];
        $filter_cat_raw = isset($_GET['filter_cat']) ? sanitize_text_field(wp_unslash($_GET['filter_cat'])) : '';
        if ($filter_cat_raw) {
            $active_cats    = array_map('sanitize_title', explode(',', $filter_cat_raw));
            $has_any_filter = true;
        }

        // Категории (скрываем "Без категории" WooCommerce)
        $cats = get_terms([
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'parent'     => 0,
            'exclude'    => get_option('default_category'),
        ]);
        ?>

        <!-- FILTER SIDEBAR -->
        <div class="catalog-page__sidebar" data-filter-panel>
          <button class="filter-back btn-reset" type="button" data-filter-close aria-label="Закрыть фильтр">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M19 12H5M5 12L12 19M5 12L12 5" stroke="#0063B1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
          <span class="catalog-page__label">Фильтры</span>
          <aside class="filter-panel" id="filter-panel-form">


            <?php
            // ─── Атрибуты WooCommerce ─────────────────────────────
            // Собираем только атрибуты с термами, чтобы корректно определить последний
            $attrs_with_terms = [];
            foreach ($wc_attributes as $attr) {
                $terms = get_terms(['taxonomy' => 'pa_' . $attr->attribute_name, 'hide_empty' => false, 'orderby' => 'menu_order']);
                if (!is_wp_error($terms) && !empty($terms)) {
                    $attrs_with_terms[] = ['attr' => $attr, 'terms' => $terms];
                }
            }
            $last_idx = array_key_last($attrs_with_terms);
            foreach ($attrs_with_terms as $idx => $entry) :
                $attr            = $entry['attr'];
                $terms           = $entry['terms'];
                $active_for_this = $active_attrs[$attr->attribute_name] ?? [];
                $is_last         = ($idx === $last_idx);
            ?>
            <div class="filter-group <?php echo $is_last ? 'filter-group--last' : ''; ?>">
              <p class="filter-group__title"><?php echo esc_html($attr->attribute_label); ?></p>
              <div class="filter-group__options">
                <?php foreach ($terms as $term) : ?>
                  <label class="filter-option">
                    <input class="filter-option__input" type="checkbox"
                           data-filter-group="filter_<?php echo esc_attr($attr->attribute_name); ?>"
                           value="<?php echo esc_attr($term->slug); ?>"
                           <?php checked(in_array($term->slug, $active_for_this, true)); ?>>
                    <span class="filter-option__box">
                      <svg width="14" height="10" viewBox="0 0 14 10" fill="none"><path d="M1 5L5 9L13 1" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span class="filter-option__text"><?php echo esc_html($term->name); ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
            <?php endforeach; ?>

            <?php if (empty($attrs_with_terms) && (is_wp_error($cats) || empty($cats))) : ?>
              <p style="font-size:14px;color:#aaa;padding:12px 0;">
                Добавьте атрибуты товаров в WooCommerce, чтобы фильтры появились здесь.
              </p>
            <?php endif; ?>

            <button class="filter-apply btn-reset" type="button" id="filter-apply-btn" style="display:none" aria-hidden="true">Применить</button>
            <button class="filter-reset" type="button" id="filter-reset-btn"
	                    <?php echo !$has_any_filter ? 'style="opacity:0.4;pointer-events:none;"' : ''; ?>>
	              <span>Сбросить все</span>
	              <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M20.25 4.5H16.875V3.375C16.875 2.67881 16.5984 2.01113 16.1062 1.51884C15.6139 1.02656 14.9462 0.75 14.25 0.75H9.75C9.05381 0.75 8.38613 1.02656 7.89384 1.51884C7.40156 2.01113 7.125 2.67881 7.125 3.375V4.5H3.75C3.45163 4.5 3.16548 4.61853 2.9545 4.8295C2.74353 5.04048 2.625 5.32663 2.625 5.625C2.625 5.92337 2.74353 6.20952 2.9545 6.4205C3.16548 6.63147 3.45163 6.75 3.75 6.75H4.125V19.5C4.125 19.9973 4.32254 20.4742 4.67417 20.8258C5.02581 21.1775 5.50272 21.375 6 21.375H18C18.4973 21.375 18.9742 21.1775 19.3258 20.8258C19.6775 20.4742 19.875 19.9973 19.875 19.5V6.75H20.25C20.5484 6.75 20.8345 6.63147 21.0455 6.4205C21.2565 6.20952 21.375 5.92337 21.375 5.625C21.375 5.32663 21.2565 5.04048 21.0455 4.8295C20.8345 4.61853 20.5484 4.5 20.25 4.5ZM9.375 3.375H14.625V4.5H9.375V3.375ZM17.625 19.125H6.375V6.75H17.625V19.125Z" fill="#E93922"/></svg>
	            </button>

	            <button class="filter-accept btn-reset" type="button" data-filter-close>Применить</button>

          </aside>
        </div>

        <!-- PRODUCTS -->
        <div class="catalog-products">
          <span class="catalog-page__label">Все товары</span>
          <div class="catalog-controls">
            <label class="catalog-search">
              <input class="catalog-search__input" type="text" placeholder="Поиск" id="catalog-search-input">
              <svg width="22" height="22" viewBox="0 0 22 22" fill="none"><circle cx="9.5" cy="9.5" r="6.5" stroke="currentColor" stroke-width="2"/><path d="M14 14L20 20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </label>
            <div class="catalog-controls__row">
              <button class="catalog-filter-btn btn-reset" type="button" data-filter-open>
                Фильтры
                <svg width="22" height="22" viewBox="0 0 22 22" fill="none"><path d="M20.1341 4.11727C19.9994 3.81274 19.7792 3.5539 19.5002 3.3722C19.2211 3.1905 18.8953 3.09376 18.5623 3.09375H3.43733C3.10441 3.09378 2.77866 3.19051 2.49966 3.37217C2.22067 3.55383 2.00043 3.81261 1.86573 4.11707C1.73102 4.42152 1.68764 4.75855 1.74084 5.0872C1.79405 5.41584 1.94157 5.72196 2.16546 5.96836L2.17577 5.98039L7.90608 12.0957V18.5625C7.90604 18.8737 7.99047 19.179 8.15036 19.446C8.31026 19.7129 8.53962 19.9314 8.81399 20.0782C9.08835 20.225 9.39742 20.2946 9.70822 20.2795C10.019 20.2644 10.3199 20.1652 10.5787 19.9925L13.3287 18.1595C13.5642 18.0024 13.7572 17.7896 13.8906 17.54C14.024 17.2903 14.0938 17.0116 14.0936 16.7286V12.0957L19.823 5.98039L19.8333 5.96836C20.0574 5.72209 20.2051 5.41606 20.2585 5.08745C20.3118 4.75884 20.2687 4.42179 20.1341 4.11727Z" fill="#9E9E9E"/></svg>
              </button>
              <?php
              // Текущая сортировка из URL
              $current_orderby = isset($_GET['orderby'])
                  ? sanitize_text_field(wp_unslash($_GET['orderby']))
                  : apply_filters('woocommerce_default_catalog_orderby', get_option('woocommerce_default_catalog_orderby', 'menu_order'));

              $sort_options = [
                  'menu_order' => 'По умолчанию',
                  'price'      => 'Цена: по возрастанию',
                  'price-desc' => 'Цена: по убыванию',
                  'date'       => 'Новинки',
              ];
              $current_label = $sort_options[$current_orderby] ?? 'Сортировать по';
              ?>
              <div class="catalog-sort">
                <button class="catalog-sort__btn" type="button" aria-expanded="false" aria-haspopup="listbox">
                  <span class="catalog-sort__current"><?php echo esc_html($current_label); ?></span>
                  <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M5 8L11 14L17 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </button>
                <ul class="catalog-sort__dropdown" role="listbox">
                  <?php foreach ($sort_options as $val => $label) :
                      $url = add_query_arg('orderby', $val, remove_query_arg('orderby'));
                  ?>
                    <li class="catalog-sort__option <?php echo $current_orderby === $val ? 'is-selected' : ''; ?>"
                        data-href="<?php echo esc_url($url); ?>">
                      <?php echo esc_html($label); ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>
          </div>

          <?php if (woocommerce_product_loop()) : ?>
            <div class="catalog-grid">
              <?php while (have_posts()) : the_post(); global $product;
                  $img_url = minoksidil_get_product_catalog_img($product);
                  $is_new  = $product->is_featured();
              ?>
                <a class="product-card" href="<?php the_permalink(); ?>">
                  <div class="product-card__box">
                    <?php if ($is_new) : ?><span class="product-card__badge">NEW</span><?php endif; ?>
                    <div class="product-card__img">
                      <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($product->get_name()); ?>" width="312" height="312" loading="lazy">
                    </div>
                    <button class="product-card__add-btn" type="button" data-product-id="<?php echo absint($product->get_id()); ?>" data-add-url="<?php echo esc_url($product->add_to_cart_url()); ?>">
                      <span>Добавить в корзину</span>
                      <span class="product-card__add-icon"><?php echo minoksidil_cart_svg('#000D24'); ?></span>
                    </button>
                  </div>
                  <div class="product-card__inner">
                    <p class="product-card__name"><?php echo esc_html($product->get_name()); ?></p>
                    <p class="product-card__price"><?php echo $product->get_price_html(); ?></p>
                  </div>
                </a>
              <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <div class="catalog-pagination">
              <?php woocommerce_pagination(); ?>
            </div>

          <?php else : ?>
            <p class="catalog-empty">Товары не найдены.</p>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </section>

</main>

<script>
(function () {
  var AJAX_URL    = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
  var filterPanel = document.querySelector('[data-filter-panel]');
  var gridWrap    = document.querySelector('.catalog-products');

  // ===== Открытие/закрытие панели фильтров =====
  document.querySelector('[data-filter-open]')?.addEventListener('click', function () {
    filterPanel?.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  });
  document.querySelector('[data-filter-close]')?.addEventListener('click', function () {
    filterPanel?.classList.remove('is-open');
    document.body.style.overflow = '';
  });

  // ===== AJAX-фильтрация =====
  var searchInput = document.querySelector('#catalog-search-input');
  var searchTimer = null;

  // Восстанавливаем значение поиска из URL при загрузке
  (function () {
    var s = new URL(window.location.href).searchParams.get('s');
    if (s && searchInput) searchInput.value = s;
  })();

  function collectGroups() {
    var groups = {};
    document.querySelectorAll('.filter-option__input:checked').forEach(function (cb) {
      var g = cb.dataset.filterGroup;
      if (!g) return;
      if (!groups[g]) groups[g] = [];
      groups[g].push(cb.value);
    });
    return groups;
  }

  function applyFilters(paged) {
    var groups  = collectGroups();
    var search  = searchInput ? searchInput.value.trim() : '';

    // Обновляем URL в адресной строке без перезагрузки
    var pageUrl = new URL(window.location.href);
    [...pageUrl.searchParams.keys()].forEach(function (k) {
      if (k.startsWith('filter_') || k === 'paged' || k === 'page' || k === 's') {
        pageUrl.searchParams.delete(k);
      }
    });
    Object.entries(groups).forEach(function ([k, v]) { pageUrl.searchParams.set(k, v.join(',')); });
    if (search) pageUrl.searchParams.set('s', search);
    if (paged > 1) pageUrl.searchParams.set('paged', paged);
    history.pushState(null, '', pageUrl.toString());

    // Кнопка «Сбросить»: показываем/скрываем
    var resetBtn = document.querySelector('#filter-reset-btn');
    if (resetBtn) {
      var hasFilters = Object.keys(groups).length > 0 || search !== '';
      resetBtn.style.opacity       = hasFilters ? '' : '0.4';
      resetBtn.style.pointerEvents = hasFilters ? '' : 'none';
    }

    // Индикатор загрузки
    var grid = gridWrap.querySelector('.catalog-grid');
    if (grid) grid.style.opacity = '0.4';

    // AJAX-запрос
    var params = new URLSearchParams({ action: 'minoksidil_filter' });
    Object.entries(groups).forEach(function ([k, v]) { params.set(k, v.join(',')); });
    if (search) params.set('s', search);
    if (paged > 1) params.set('paged', paged);
    var orderby = pageUrl.searchParams.get('orderby');
    if (orderby) params.set('orderby', orderby);

    fetch(AJAX_URL + '?' + params.toString())
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (!res.success) return;

        var emptyEl = gridWrap.querySelector('.catalog-empty');
        if (emptyEl) emptyEl.remove();

        // Удаляем старую пагинацию
        var oldPag = gridWrap.querySelector('.catalog-pagination');
        if (oldPag) oldPag.remove();

        if (!res.data.html) {
          if (grid) grid.remove();
          var p = document.createElement('p');
          p.className   = 'catalog-empty';
          p.textContent = 'Товары не найдены.';
          gridWrap.appendChild(p);
          return;
        }

        if (grid) {
          grid.innerHTML   = res.data.html;
          grid.style.opacity = '';
        } else {
          var div = document.createElement('div');
          div.className   = 'catalog-grid';
          div.innerHTML   = res.data.html;
          gridWrap.appendChild(div);
        }

        // Вставляем новую пагинацию, если есть
        if (res.data.pagination) {
          gridWrap.insertAdjacentHTML('beforeend', res.data.pagination);
          initPagination();
        }
      })
      .catch(function () { if (grid) grid.style.opacity = ''; });
  }

  // ===== Пагинация по клику =====
  var catalogSection = document.querySelector('.catalog-page');
  function scrollToCatalog() {
    if (!catalogSection) return;
    catalogSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function initPagination() {
    document.querySelectorAll('.catalog-pagination a').forEach(function (link) {
      link.addEventListener('click', function (e) {
        e.preventDefault();
        var href = this.getAttribute('href');
        if (!href) return;
        var m = href.match(/[?&]paged=(\d+)/) || href.match(/\/page\/(\d+)/);
        var page = m ? parseInt(m[1], 10) : 1;
        scrollToCatalog();
        applyFilters(page);
      });
    });
  }
  initPagination();

	  // Клик на чекбокс → single-select в своей группе, сброс на 1-ю страницу
	  document.querySelectorAll('.filter-option__input').forEach(function (cb) {
	    cb.addEventListener('change', function () {
	      if (this.checked) {
	        var group = this.dataset.filterGroup;
	        if (group) {
	          document.querySelectorAll('.filter-option__input[data-filter-group="' + group + '"]').forEach(function (other) {
	            if (other !== cb) other.checked = false;
	          });
	        }
	      }
	      applyFilters(1);
	    });
	  });

  // Поиск с debounce 400ms — сбрасываем на 1-ю страницу
  if (searchInput) {
    searchInput.addEventListener('input', function () {
      clearTimeout(searchTimer);
      searchTimer = setTimeout(function () { applyFilters(1); }, 400);
    });
    searchInput.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { clearTimeout(searchTimer); applyFilters(1); }
    });
  }

  // ===== Сбросить фильтры =====
  document.querySelector('#filter-reset-btn')?.addEventListener('click', function () {
    document.querySelectorAll('.filter-option__input').forEach(function (cb) { cb.checked = false; });
    if (searchInput) searchInput.value = '';
    applyFilters(1);
  });

  // ===== Дропдаун сортировки =====
  var sortBtn = document.querySelector('.catalog-sort__btn');
  if (sortBtn) {
    sortBtn.addEventListener('click', function () {
      var open = this.getAttribute('aria-expanded') === 'true';
      this.setAttribute('aria-expanded', String(!open));
      this.nextElementSibling.classList.toggle('is-open');
    });
    document.addEventListener('click', function (e) {
      if (!sortBtn.closest('.catalog-sort').contains(e.target)) {
        sortBtn.setAttribute('aria-expanded', 'false');
        sortBtn.nextElementSibling.classList.remove('is-open');
      }
    });
  }

  document.querySelectorAll('.catalog-sort__option[data-href]').forEach(function (opt) {
    opt.addEventListener('click', function () {
      if (this.dataset.href) window.location.href = this.dataset.href;
    });
  });
})();
</script>

<?php get_footer(); ?>

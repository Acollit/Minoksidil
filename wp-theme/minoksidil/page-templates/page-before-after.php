<?php
/**
 * Template Name: До/После — Результаты
 */
defined('ABSPATH') || exit;
get_header();
?>
<main class="main">

  <!-- BANNER -->
  <section class="cat-banner">
    <div class="cat-banner__grass" aria-hidden="true">
      <img src="<?php echo esc_url(MINOKSIDIL_IMG . 'catalog-grass.webp'); ?>" alt="" width="1920" height="469" loading="lazy">
    </div>
    <div class="container cat-banner__inner">
      <h1 class="cat-banner__title"><?php echo esc_html(minoksidil_acf('banner_title', 'Результаты и рекомендации')); ?></h1>
      <p class="cat-banner__sub"><?php echo esc_html(minoksidil_acf('banner_sub', 'Индивидуальные результаты')); ?></p>
    </div>
  </section>

  <!-- RESULTS -->
  <section class="ba-page">
    <div class="container">

      <!-- CONTROLS -->
      <div class="catalog-controls ba-page__controls">

        <label class="catalog-search">
          <input class="catalog-search__input" type="text" placeholder="<?php echo esc_attr(minoksidil_acf('search_placeholder', 'Поиск по используемому товару')); ?>" id="results-search">
          <svg width="22" height="22" viewBox="0 0 22 22" fill="none"><circle cx="9.5" cy="9.5" r="6.5" stroke="currentColor" stroke-width="2"/><path d="M14 14L20 20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </label>
      </div>

      <!-- GRID -->
      <div class="ba-grid" id="ba-grid">
        <?php
        $results = get_posts(['post_type' => 'result', 'posts_per_page' => -1, 'post_status' => 'publish']);
        if ($results) :
            foreach ($results as $r) :
                $acf_gallery = get_field('result_gallery', $r->ID) ?: [];
                if (!empty($acf_gallery)) {
                    $gallery_urls = array_values(array_filter(array_map(function ($img) {
                        if (is_array($img)) return $img['sizes']['large'] ?? $img['url'];
                        if (is_numeric($img)) return wp_get_attachment_image_url((int) $img, 'large') ?: '';
                        return (string) $img;
                    }, $acf_gallery)));
                    $img_url = $gallery_urls[0] ?? MINOKSIDIL_IMG . 'ba-1.webp';
                } else {
                    $img_id = get_post_thumbnail_id($r->ID);
                    $img_url = $img_id ? wp_get_attachment_image_url($img_id, 'large') : MINOKSIDIL_IMG . 'ba-1.webp';
                    $gallery_urls = [$img_url];
                }
                $person   = get_post_meta($r->ID, '_result_person', true)    ?: get_the_title($r->ID);
                $form     = get_post_meta($r->ID, '_result_form', true);
                $freq     = get_post_meta($r->ID, '_result_frequency', true);
                $dur      = get_post_meta($r->ID, '_result_duration', true);
                $out      = get_post_meta($r->ID, '_result_outcome', true);
                $period_raw = get_post_meta($r->ID, '_result_period', true)
                           ?: get_post_meta($r->ID, '_result_duration', true);
                $period     = preg_replace('/[^0-9]/', '', $period_raw);
                $product  = get_post_meta($r->ID, '_result_product', true);
        ?>
          <div class="result-card" data-period="<?php echo esc_attr($period); ?>" data-person="<?php echo esc_attr(mb_strtolower($person)); ?>" data-product="<?php echo esc_attr(mb_strtolower($product)); ?>">
            <div class="result-card__image"
                 data-gallery="<?php echo esc_attr(wp_json_encode($gallery_urls)); ?>"
                 role="button" tabindex="0" aria-label="Открыть галерею">
              <img src="<?php echo esc_url($img_url); ?>" alt="Результат <?php echo esc_attr($person); ?>" width="560" height="360" loading="lazy">
              <div class="result-card__zoom" aria-hidden="true">
                <svg width="52" height="52" viewBox="0 0 52 52" fill="none">
                  <circle cx="52" cy="52" r="52" fill="rgba(0,0,0,0.4)"/>
                  <circle cx="23" cy="23" r="12" stroke="white" stroke-width="2.5"/>
                  <path d="M32 32L42 42" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                  <path d="M23 18V28M18 23H28" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
              </div>
            </div>
            <div class="result-card__body">
              <p class="result-card__name"><?php echo esc_html($person); ?></p>
              <table class="result-specs">
                <tbody>
                  <?php if ($form) : ?><tr class="result-specs__row"><td class="result-specs__label">Форма:</td><td class="result-specs__value"><?php echo esc_html($form); ?></td></tr><?php endif; ?>
                  <?php if ($freq) : ?><tr class="result-specs__row"><td class="result-specs__label">Частота применения:</td><td class="result-specs__value"><?php echo esc_html($freq); ?></td></tr><?php endif; ?>
                  <?php if ($dur)  : ?><tr class="result-specs__row"><td class="result-specs__label">Срок:</td><td class="result-specs__value"><?php echo esc_html($dur); ?></td></tr><?php endif; ?>
                  <?php if ($out)  : ?><tr class="result-specs__row"><td class="result-specs__label">Результат:</td><td class="result-specs__value"><?php echo esc_html($out); ?></td></tr><?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php
            endforeach;
        else : ?>
          <p style="color:#888; font-size:17px; text-align:center; padding: 60px; grid-column: 1/-1;">
            Результаты ещё не добавлены.
            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=result')); ?>">Добавить первый →</a>
          </p>
        <?php endif; ?>
      </div>

      <!-- FOOTER -->
      <div class="ba-page__footer">
        <p class="ba-page__disclaimer"><?php echo esc_html(minoksidil_acf('disclaimer', 'Не является лекарством', get_queried_object_id())); ?></p>
      </div>

    </div>
  </section>

  <!-- GALLERY MODAL -->
  <div class="ba-gallery-modal" id="ba-gallery-modal" role="dialog" aria-modal="true" aria-label="Галерея" hidden>
    <div class="ba-gallery-modal__overlay"></div>
    <div class="ba-gallery-modal__inner">
      <button class="ba-gallery-modal__close" type="button" aria-label="Закрыть">
        <svg width="32" height="32" viewBox="0 0 32 32" fill="none"><path d="M6 6L26 26M26 6L6 26" stroke="white" stroke-width="2.5" stroke-linecap="round"/></svg>
      </button>
      <button class="ba-gallery-modal__nav ba-gallery-modal__nav--prev" type="button" aria-label="Предыдущее">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M13 4L7 10L13 16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
      <img class="ba-gallery-modal__img" src="" alt="">
      <button class="ba-gallery-modal__nav ba-gallery-modal__nav--next" type="button" aria-label="Следующее">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M7 4L13 10L7 16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
      <span class="ba-gallery-modal__counter"></span>
    </div>
  </div>

</main>

<script>
(function () {
  // ── Фильтрация ──────────────────────────────────────────────
  var search       = document.querySelector('#results-search');
  var cards        = document.querySelectorAll('.result-card');
  var dropdown     = document.querySelector('.catalog-sort__dropdown');
  var activePeriod = 'all';

  function applyFilters() {
    var q = search ? search.value.toLowerCase().trim() : '';
    cards.forEach(function (card) {
      var periodOk = activePeriod === 'all' || card.dataset.period === activePeriod;
      var searchOk = !q
        || (card.dataset.product || '').includes(q)
        || (card.dataset.person  || '').includes(q);
      card.style.display = (periodOk && searchOk) ? '' : 'none';
    });
  }

  if (dropdown) {
    dropdown.addEventListener('click', function (e) {
      var opt = e.target.closest('.catalog-sort__option');
      if (!opt) return;
      activePeriod = opt.dataset.value || 'all';
      applyFilters();
    });
  }

  if (search) search.addEventListener('input', applyFilters);

  // ── Галерея модалка ──────────────────────────────────────────
  var modal    = document.getElementById('ba-gallery-modal');
  var modalImg = modal.querySelector('.ba-gallery-modal__img');
  var counter  = modal.querySelector('.ba-gallery-modal__counter');
  var prevBtn  = modal.querySelector('.ba-gallery-modal__nav--prev');
  var nextBtn  = modal.querySelector('.ba-gallery-modal__nav--next');
  var gallery  = [];
  var current  = 0;

  function showImage(idx) {
    current = idx;
    modalImg.src = gallery[current];
    counter.textContent = (current + 1) + ' / ' + gallery.length;
    prevBtn.disabled = current === 0;
    nextBtn.disabled = current === gallery.length - 1;
    var hasMult = gallery.length > 1;
    prevBtn.style.display = hasMult ? '' : 'none';
    nextBtn.style.display = hasMult ? '' : 'none';
  }

  function openModal(urls, startIdx) {
    gallery = urls;
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
    showImage(startIdx || 0);
  }

  function closeModal() {
    modal.hidden = true;
    document.body.style.overflow = '';
    modalImg.src = '';
  }

  document.querySelectorAll('.result-card__image[data-gallery]').forEach(function (el) {
    el.style.cursor = 'zoom-in';
    el.addEventListener('click', function () {
      var urls = JSON.parse(this.dataset.gallery || '[]');
      if (urls.length) openModal(urls, 0);
    });
    el.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); this.click(); }
    });
  });

  modal.querySelector('.ba-gallery-modal__overlay').addEventListener('click', closeModal);
  modal.querySelector('.ba-gallery-modal__close').addEventListener('click', closeModal);
  prevBtn.addEventListener('click', function () { if (current > 0) showImage(current - 1); });
  nextBtn.addEventListener('click', function () { if (current < gallery.length - 1) showImage(current + 1); });

  document.addEventListener('keydown', function (e) {
    if (modal.hidden) return;
    if (e.key === 'Escape')     closeModal();
    if (e.key === 'ArrowLeft')  prevBtn.click();
    if (e.key === 'ArrowRight') nextBtn.click();
  });
})();
</script>

<?php get_footer(); ?>

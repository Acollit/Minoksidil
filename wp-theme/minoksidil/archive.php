<?php
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
      <h1 class="cat-banner__title">
        <?php
        if (is_category())   echo esc_html(single_cat_title('', false));
        elseif (is_tag())    echo esc_html(single_tag_title('', false));
        else                 echo 'Статьи';
        ?>
      </h1>
    </div>
  </section>

  <!-- ARTICLES -->
  <section class="articles-page">
    <div class="container">

      <!-- CONTROLS -->
      <div class="catalog-controls articles-page__controls">
        <div class="catalog-sort">
          <button class="catalog-sort__btn" type="button" aria-expanded="false" aria-haspopup="listbox">
            <span class="catalog-sort__current">Все статьи</span>
            <svg width="22" height="22" viewBox="0 0 22 22" fill="none"><path d="M5 8L11 14L17 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
          <ul class="catalog-sort__dropdown" role="listbox">
            <li class="catalog-sort__option is-selected" data-href="<?php echo esc_attr(get_post_type_archive_link('post')); ?>">Все статьи</li>
            <?php foreach (get_categories(['hide_empty' => true]) as $cat) : ?>
              <li class="catalog-sort__option" data-href="<?php echo esc_attr(get_category_link($cat->term_id)); ?>"><?php echo esc_html($cat->name); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <label class="catalog-search">
          <input class="catalog-search__input" type="text" placeholder="Поиск">
          <svg width="22" height="22" viewBox="0 0 22 22" fill="none"><circle cx="9.5" cy="9.5" r="6.5" stroke="currentColor" stroke-width="2"/><path d="M14 14L20 20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </label>
      </div>

      <!-- ARTICLES GRID -->
      <?php if (have_posts()) : ?>
        <div class="articles-page__grid">
          <?php while (have_posts()) : the_post();
              $img_id  = get_post_thumbnail_id();
              $img_url = $img_id ? wp_get_attachment_image_url($img_id, 'large') : MINOKSIDIL_IMG . 'articles-1.webp';
          ?>
            <a href="<?php the_permalink(); ?>" class="article-card">
              <div class="article-card__img">
                <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" width="560" height="360" loading="lazy">
              </div>
              <div class="article-card__body">
                <time class="article-card__date" datetime="<?php echo esc_attr(get_the_date('Y-m-d')); ?>"><?php echo esc_html(get_the_date('j F Y г.')); ?></time>
                <h2 class="article-card__title"><?php the_title(); ?></h2>
                <p class="article-card__text"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 25)); ?></p>
                <span class="article-card__link">Читать подробнее</span>
              </div>
            </a>
          <?php endwhile; ?>
        </div>

        <!-- PAGINATION -->
        <div class="catalog-pagination">
          <?php
          the_posts_pagination([
              'mid_size'  => 2,
              'prev_text' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M15 5L9 12L15 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
              'next_text' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M9 5L15 12L9 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
          ]);
          ?>
        </div>

      <?php else : ?>
        <p style="text-align:center; color:#888; padding: 60px;">Статьи не найдены.</p>
      <?php endif; ?>

    </div>
  </section>

</main>

<script>
(function () {
  var btn = document.querySelector('.catalog-sort__btn');
  if (btn) btn.addEventListener('click', function() {
    var open = this.getAttribute('aria-expanded') === 'true';
    this.setAttribute('aria-expanded', !open);
    this.nextElementSibling.classList.toggle('is-open');
  });
  document.querySelectorAll('.catalog-sort__option[data-href]').forEach(function(opt) {
    opt.addEventListener('click', function() {
      if (this.dataset.href) window.location.href = this.dataset.href;
    });
  });
})();
</script>

<?php get_footer(); ?>

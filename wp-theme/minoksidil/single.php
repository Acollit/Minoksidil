<?php
defined('ABSPATH') || exit;
get_header();
?>
<main class="main">
<?php while (have_posts()) : the_post(); ?>

  <!-- BANNER -->
  <section class="cat-banner">
    <div class="cat-banner__grass" aria-hidden="true">
      <img src="<?php echo esc_url(MINOKSIDIL_IMG . 'catalog-grass.webp'); ?>" alt="" width="1920" height="469" loading="lazy">
    </div>
    <div class="container cat-banner__inner">
      <time class="art-banner__date" datetime="<?php echo esc_attr(get_the_date('Y-m-d')); ?>">
        <?php echo esc_html(get_the_date('j F Y г.')); ?>
      </time>
      <h1 class="cat-banner__title"><?php the_title(); ?></h1>
    </div>
  </section>

  <!-- BODY -->
  <div class="art-body">
    <div class="container">

      <!-- TOC (заполняется JS по h2 в контенте) -->
      <nav class="art-toc" id="art-toc" aria-label="Содержание статьи" hidden>
        <p class="art-toc__heading">СОДЕРЖАНИЕ</p>
        <ul class="art-toc__list" id="art-toc-list"></ul>
      </nav>

      <!-- CONTENT -->
      <div class="art-content" id="art-content">
        <?php the_content(); ?>
      </div>

    </div>
  </div>

  <!-- RELATED -->
  <?php
  $related = get_posts([
      'post_type'      => 'post',
      'posts_per_page' => 3,
      'post__not_in'   => [get_the_ID()],
      'category__in'   => wp_get_post_categories(get_the_ID()),
      'post_status'    => 'publish',
  ]);
  if (!$related) {
      $related = get_posts([
          'post_type'      => 'post',
          'posts_per_page' => 3,
          'post__not_in'   => [get_the_ID()],
          'post_status'    => 'publish',
      ]);
  }
  if ($related) : ?>
  <section class="art-related">
    <div class="container">
      <h2 class="art-related__title">ВАМ МОЖЕТ БЫТЬ ИНТЕРЕСНО</h2>
      <div class="articles-page__grid">
        <?php foreach ($related as $rp) :
            $ri = get_post_thumbnail_id($rp->ID);
            $ru = $ri ? wp_get_attachment_image_url($ri, 'large') : MINOKSIDIL_IMG . 'articles-1.webp';
        ?>
          <a href="<?php echo esc_url(get_permalink($rp->ID)); ?>" class="article-card">
            <div class="article-card__img">
              <img src="<?php echo esc_url($ru); ?>" alt="<?php echo esc_attr($rp->post_title); ?>" width="560" height="360" loading="lazy">
            </div>
            <div class="article-card__body">
              <time class="article-card__date"><?php echo esc_html(get_the_date('j F Y г.', $rp->ID)); ?></time>
              <h2 class="article-card__title"><?php echo esc_html($rp->post_title); ?></h2>
              <p class="article-card__text"><?php echo esc_html(wp_trim_words(get_the_excerpt($rp->ID), 20)); ?></p>
              <span class="article-card__link">Читать подробнее</span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

<?php endwhile; ?>
</main>

<script>
(function () {
  var toc     = document.getElementById('art-toc');
  var tocList = document.getElementById('art-toc-list');
  var content = document.getElementById('art-content');
  if (!toc || !tocList || !content) return;

  var headings = content.querySelectorAll('h2');
  if (!headings.length) return;

  headings.forEach(function (h, i) {
    if (!h.id) h.id = 'art-s' + (i + 1);
    var li = document.createElement('li');
    li.className = 'art-toc__item';
    var a = document.createElement('a');
    a.href = '#' + h.id;
    a.className = 'art-toc__link';
    a.textContent = h.textContent;
    li.appendChild(a);
    tocList.appendChild(li);
  });

  toc.removeAttribute('hidden');
})();
</script>

<?php get_footer(); ?>

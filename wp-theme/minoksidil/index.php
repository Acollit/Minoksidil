<?php get_header(); ?>

<main class="main">
  <div class="container">
    <div class="page-content">
      <?php if (have_posts()): while (have_posts()): the_post(); ?>
        <article <?php post_class(); ?>>
          <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
          <?php the_excerpt(); ?>
        </article>
      <?php endwhile; else: ?>
        <p>Ничего не найдено.</p>
      <?php endif; ?>
    </div>
  </div>
</main>

<?php get_footer(); ?>

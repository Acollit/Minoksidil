<?php get_header(); ?>

<main class="main">
  <div class="container">
    <div class="page-content">
      <?php while (have_posts()): the_post(); ?>
        <h1 class="page-title"><?php the_title(); ?></h1>
        <div class="page-body"><?php the_content(); ?></div>
      <?php endwhile; ?>
    </div>
  </div>
</main>

<?php get_footer(); ?>

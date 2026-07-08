
<?php get_header();
/**
 * Template Name: Политика конфиденциальности
 */
?>

<main class="main">
  <div class="polit">
  <div class="container">

      <?php while (have_posts()) : the_post(); ?>
      <h1 class="polit__title"><?php the_title(); ?></h1>
      <div class="polit__content">
        <?php the_content(); ?>
      </div>
      <?php endwhile; ?>

    </div>
  </div>
</main>

<?php get_footer(); ?>

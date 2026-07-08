<?php get_header(); ?>

<main class="main">
  <div class="error-404">
    <div class="container">
      <div class="error-404__content">
        <h1 class="error-404__code">404</h1>
        <p class="error-404__text">Извините, но такой страницы не существует</p>
        <a href="<?php echo esc_url(home_url('/')); ?>" class="btn btn--green">На главную</a>
      </div>


    </div>
  </div>
</main>

<?php get_footer(); ?>

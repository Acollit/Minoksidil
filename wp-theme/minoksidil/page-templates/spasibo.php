
<?php get_header();
/**
 * Template Name: Заявка
 */
?>

<main class="main">
  <div class="error-404">
    <div class="container">
      <div class="error-404__content">
        <h1 class="error-404__code"><?php echo esc_html(minoksidil_acf('thanks_title', 'Благодарим вас за заявку!')); ?></h1>
        <p class="error-404__text"><?php echo wp_kses_post(minoksidil_acf('thanks_text', 'Менеджер свяжется с вами в течение 1 часа')); ?></p>
        <a href="<?php echo esc_url(home_url('/shop')); ?>" class="btn btn--green"><?php echo esc_html(minoksidil_acf('thanks_btn', 'Вернуться в каталог')); ?></a>
      </div>
    </div>
  </div>
</main>

<?php get_footer(); ?>

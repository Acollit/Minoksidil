<!DOCTYPE html>
<html lang="ru" class="page">
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <meta name="theme-color" content="#0063b1">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Onest:wght@400;500;600;700&display=swap" rel="stylesheet">
  <?php wp_head(); ?>
</head>
<body class="page__body <?php echo esc_attr(implode(' ', get_body_class())); ?>">
<?php wp_body_open(); ?>
<div class="site-container">

<?php
$phone = get_option('minoksidil_phone', '+7 999 999-99-99');
$vk    = get_option('minoksidil_vk', '#');
$tg    = get_option('minoksidil_telegram', '#');
$tg2   = get_option('minoksidil_telegram2', '#');
$max   = get_option('minoksidil_max', '#');
$avito = get_option('minoksidil_avito', '#');

$shop  = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/catalog/');
$cart  = function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/');
$count = (function_exists('WC') && WC()->cart) ? WC()->cart->get_cart_contents_count() : 0;
?>

<div class="topbar">
  <div class="container">
    <a href="tel:<?php echo esc_attr(preg_replace('/\D/', '', $phone)); ?>" class="topbar__phone">
      <?php echo esc_html($phone); ?>
    </a>
    <div class="topbar__socials">

      <a href="<?php echo esc_url($tg); ?>" class="topbar__social" aria-label="Telegram" target="_blank" rel="noopener">

      </a>
       <a href="<?php echo esc_url($tg2); ?>" class="topbar__social" aria-label="Telegram2" target="_blank" rel="noopener">

      </a>
      <a href="<?php echo esc_url($max); ?>" class="topbar__social" aria-label="MAX" target="_blank" rel="noopener">

      </a>
       <a href="<?php echo esc_url($vk); ?>" class="topbar__social" aria-label="ВКонтакте" target="_blank" rel="noopener">

      </a>
      <a href="<?php echo esc_url($avito); ?>" class="topbar__social" aria-label="Avito" target="_blank" rel="noopener">

      </a>

    </div>
  </div>
</div>

<header class="header">
  <div class="container">
    <a href="<?php echo esc_url(home_url('/')); ?>" class="header__logo">
      Minoxidillum
      <span>Рост волос</span>
    </a>
    <nav class="header__nav" data-menu>
      <a href="<?php echo esc_url($shop); ?>" class="header__nav-link<?php echo is_shop() ? ' is-active' : ''; ?>" data-menu-item>Каталог</a>
      <?php
      $pages = [
          'До/после'          => 'do-posle',
          'Гид'               => 'gid',
          'Доставка и оплата' => 'dostavka-i-oplata',
          'Контакты'          => 'kontakty',
      ];
      foreach ($pages as $label => $slug) :
          $page = get_page_by_path($slug);
          $url  = $page ? get_permalink($page->ID) : home_url('/' . $slug . '/');
          $active = $page && is_page($page->ID) ? ' is-active' : '';
      ?>
        <a href="<?php echo esc_url($url); ?>" class="header__nav-link<?php echo $active; ?>" data-menu-item>
          <?php echo esc_html($label); ?>
        </a>
      <?php endforeach; ?>

    </nav>
    <div class="header__box">
      <button type="button" class="header__call-btn" data-modal-open>Заказать звонок</button>
      <a href="<?php echo esc_url($cart); ?>" class="header__cart" aria-label="Корзина">
        <?php echo minoksidil_cart_svg('#0063B1'); ?>
        <span class="header__count <?php echo $count ? '' : ' is-empty'; ?>"><?php echo esc_html($count); ?></span>
      </a>
      <button class="btn-reset burger" type="button" aria-expanded="false" aria-label="Открыть меню" data-burger></button>
    </div>
  </div>
</header>

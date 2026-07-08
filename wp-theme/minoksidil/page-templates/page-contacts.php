<?php
/**
 * Template Name: Контакты
 */
defined('ABSPATH') || exit;
get_header();

$phone     = get_option('minoksidil_phone', '+7 (999) 999-99-99');
$email     = get_option('minoksidil_email', 'info@minoxidillum.ru');
$address   = get_option('minoksidil_address', 'Адрес');
$schedule  = get_option('minoksidil_schedule', 'Пн-Пт с 9:00 до 18:00');
$vk        = get_option('minoksidil_vk', '#');
$tg        = get_option('minoksidil_telegram', '#');
$tg2       = get_option('minoksidil_telegram2', '#');
$max       = get_option('minoksidil_max', '#');
$avito     = get_option('minoksidil_avito', '#');

$map_embed = get_option('minoksidil_map_embed', '');
$privacy   = get_option('minoksidil_privacy_url', '#');
$consent   = get_option('minoksidil_consent_url', '#');

function minoksidil_social_svg_contacts(): string {
    return '<svg width="32" height="29" viewBox="0 0 32 29" fill="none"><path d="M31.7647 0L0 13.5151L8.32718 16.8232L11.2916 27.9769L16.7499 22.683L25.6301 28.8221L31.7647 0ZM11.7241 18.2529L11.4036 21.1609L10.1727 16.529L31.7647 0L11.7241 18.2529Z" fill="white"/></svg>';
}
?>
<main class="main">

  <!-- BANNER -->
  <section class="cat-banner">
    <div class="cat-banner__grass" aria-hidden="true">
      <img src="<?php echo esc_url(MINOKSIDIL_IMG . 'catalog-grass.webp'); ?>" alt="" width="1920" height="469" loading="lazy">
    </div>
    <div class="container cat-banner__inner">
      <h1 class="cat-banner__title"><?php echo esc_html(minoksidil_acf('banner_title', 'Контакты')); ?></h1>
    </div>
  </section>

  <!-- CONTACT INFO -->
  <section class="contacts-info">
    <div class="container">
      <div class="contacts-info__grid">

        <div class="contacts-info__item">
          <p class="contacts-info__label">Телефон</p>
          <a class="contacts-info__value" href="tel:<?php echo esc_attr(preg_replace('/\D/', '', $phone)); ?>"><?php echo esc_html($phone); ?></a>
        </div>

        <div class="contacts-info__item">
          <p class="contacts-info__label">Почта</p>
          <a class="contacts-info__value" href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a>
        </div>

        <div class="contacts-info__item">
          <p class="contacts-info__label">Адрес</p>
          <p class="contacts-info__value"><?php echo esc_html($address); ?></p>
        </div>

        <div class="contacts-info__item">
          <p class="contacts-info__label">Соц. сети</p>
          <div class="contacts-info__socials">
            <a href="<?php echo esc_url($tg); ?>" class="contacts-info__social" aria-label="Telegram" target="_blank" rel="noopener">

            </a>
            <a href="<?php echo esc_url($tg2); ?>" class="contacts-info__social" aria-label="Telegram 2" target="_blank" rel="noopener">

            </a>
            <a href="<?php echo esc_url($vk); ?>" class="contacts-info__social" aria-label="ВКонтакте" target="_blank" rel="noopener">

            </a>
            <a href="<?php echo esc_url($max); ?>" class="contacts-info__social" aria-label="MAX" target="_blank" rel="noopener">

            </a>
            <a href="<?php echo esc_url($avito); ?>" class="contacts-info__social" aria-label="Avito" target="_blank" rel="noopener">

            </a>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- MAP -->
  <?php if ($map_embed) : ?>
    <div class="contacts-map">
      <?php
      $allowed = ['iframe' => ['src' => true, 'width' => true, 'height' => true, 'allowfullscreen' => true, 'style' => true, 'title' => true, 'frameborder' => true]];
      echo wp_kses($map_embed, $allowed);
      ?>
    </div>
  <?php endif; ?>

  <!-- COLLAB -->
  <section class="guide-collab contacts-collab">
    <div class="container">
      <div class="guide-collab__grid">
        <div class="guide-collab__left">
          <div class="guide-collab__photo">
            <img src="<?php echo esc_url(MINOKSIDIL_IMG . 'guide-collab-2.webp'); ?>" alt="" width="820" height="520" loading="lazy">
          </div>
          <div class="guide-partner">
            <h2 class="guide-partner__title"><?php echo esc_html(minoksidil_acf('partner_title', 'Мы открыты к сотрудничеству')); ?></h2>
            <div class="guide-partner__product">
              <img src="<?php echo esc_url(MINOKSIDIL_IMG . 'collab.svg'); ?>" alt="" width="287" height="287" loading="lazy">
            </div>
            <p class="guide-partner__text"><?php echo wp_kses_post(minoksidil_acf('partner_text', 'У нас действует простая система промокодов: клиент получает скидку на заказ, а тот, кто нас рекомендует, — денежное вознаграждение с каждой продажи.')); ?></p>
          </div>
        </div>
        <div class="guide-collab__right">
          <div class="guide-wholesale">
            <h2 class="guide-wholesale__title"><?php echo esc_html(minoksidil_acf('wholesale_title', 'Поставляем наши товары оптом под реализацию')); ?></h2>
            <p class="guide-wholesale__text"><?php echo wp_kses_post(minoksidil_acf('wholesale_text', 'Если вам интересен такой формат работы, напишите нам — обсудим условия')); ?></p>
            <div class="guide-wholesale__socials">
              <a href="<?php echo esc_url($tg); ?>" class="guide-wholesale__social" aria-label="Telegram" target="_blank" rel="noopener"></a>
              <a href="<?php echo esc_url($tg2); ?>" class="guide-wholesale__social" aria-label="Telegram 2" target="_blank" rel="noopener"></a>
              <a href="<?php echo esc_url($vk); ?>" class="guide-wholesale__social" aria-label="ВКонтакте" target="_blank" rel="noopener"></a>
              <a href="<?php echo esc_url($max); ?>" class="guide-wholesale__social" aria-label="MAX" target="_blank" rel="noopener"></a>
              <a href="<?php echo esc_url($avito); ?>" class="guide-wholesale__social" aria-label="Avito" target="_blank" rel="noopener"></a>
            </div>
          </div>

        </div>
      </div>
    </div>
  </section>

  <!-- CTA + CONTACT FORM -->
  <section class="contacts-cta">
    <div class="container">
      <div class="contacts-cta__grid">
        <div class="contacts-cta__left">
          <div>
            <h2 class="contacts-cta__title"><?php echo esc_html(minoksidil_acf('cta_title', 'Давайте обсудим ваш случай')); ?></h2>
            <p class="contacts-cta__text"><?php echo wp_kses_post(minoksidil_acf('cta_text', 'Если вы всё ещё сомневаетесь, какой лосьон выбрать, или хотите узнать, как начать — просто напишите. Мы на связи, чтобы вы наконец увидели первые новые волосы уже через 2.5 месяца.')); ?></p>
          </div>
          <div class="contacts-cta__schedule">
            <p class="contacts-cta__schedule-label">График работы</p>
            <p class="contacts-cta__schedule-value">круглосуточно</p>
          </div>
        </div>

        <div class="contacts-cta__form">

        </div>

      </div>
    </div>
  </section>

</main>

<?php get_footer(); ?>

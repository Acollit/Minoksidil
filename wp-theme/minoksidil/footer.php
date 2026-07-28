<?php
$phone    = get_option('minoksidil_phone', '+7 999 999-99-99');
$email    = get_option('minoksidil_email', 'info@minoxidillum.ru');
$address  = get_option('minoksidil_address', 'Адрес');
$vk       = get_option('minoksidil_vk', '#');
$tg       = get_option('minoksidil_telegram', '#');
$tg2      = get_option('minoksidil_telegram2', '#');
$max      = get_option('minoksidil_max', '#');
$avito    = get_option('minoksidil_avito', '#');

$privacy  = get_option('minoksidil_privacy_url', '#');
$consent  = get_option('minoksidil_consent_url', '#');
$map_img  = get_option('minoksidil_map_image', MINOKSIDIL_IMG . 'footer-map.webp');
$shop_url = function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/catalog/');
?>

<footer class="footer" id="contacts">
  <div class="container">
    <div class="footer__inner">

      <div class="footer__brand">
        <a href="<?php echo esc_url(home_url('/')); ?>" class="footer__logo"> Minoxidillum
      <span>Рост волос</span></a>
        <div class="footer__socials">
          <a href="<?php echo esc_url($tg); ?>" class="footer__social" aria-label="Telegram" target="_blank" rel="noopener"></a>
          <a href="<?php echo esc_url($tg2); ?>" class="footer__social" aria-label="Telegram2" target="_blank" rel="noopener"></a>
          <a href="<?php echo esc_url($max); ?>" class="footer__social" aria-label="MAX" target="_blank" rel="noopener"></a>
          <a href="<?php echo esc_url($vk); ?>" class="footer__social" aria-label="ВКонтакте" target="_blank" rel="noopener"></a>
          <a href="<?php echo esc_url($avito); ?>" class="footer__social" aria-label="Avito" target="_blank" rel="noopener"></a>
        </div>
      </div>

      <div class="footer__cols">
        <div class="footer__col">
          <p class="footer__col-title">Меню</p>
          <nav class="footer__nav">
            <a href="<?php echo esc_url($shop_url); ?>" class="footer__nav-link">Каталог</a>
            <?php
            $footer_pages = [
                'До/после'          => 'do-posle',
                'Гид'               => 'gid',
                'Доставка и оплата' => 'dostavka-i-oplata',
                'Контакты'          => 'kontakty',
            ];
            foreach ($footer_pages as $label => $slug) :
                $page = get_page_by_path($slug);
                $url  = $page ? get_permalink($page->ID) : home_url('/' . $slug . '/');
            ?>
              <a href="<?php echo esc_url($url); ?>" class="footer__nav-link"><?php echo esc_html($label); ?></a>
            <?php endforeach; ?>
          </nav>
        </div>
        <div class="footer__col">
          <p class="footer__col-title">Контакты</p>
          <div class="footer__contacts">
            <a href="tel:<?php echo esc_attr(preg_replace('/\D/', '', $phone)); ?>" class="footer__contacts-link">
              <?php echo esc_html($phone); ?>
            </a>
            <a href="mailto:<?php echo esc_attr($email); ?>" class="footer__contacts-link">
              <?php echo esc_html($email); ?>
            </a>
            <span class="footer__contacts-link"><?php echo esc_html($address); ?></span>
          </div>
        </div>
      </div>

      <div class="footer__map">
        <iframe src="https://yandex.ru/map-widget/v1/?ll=37.468650%2C55.560074&amp;masstransit%5BstopId%5D=3557477795&amp;mode=masstransit&amp;tab=overview&amp;z=16" width="560" height="400" frameborder="1" allowfullscreen="true" style="position:relative"></iframe>
      </div>

    </div>
  </div>
</footer>

<div class="footer-bottom">
  <div class="container">
    <a href="<?php echo esc_url($privacy); ?>" class="footer-bottom__link">Политика конфиденциальности</a>
    <a href="<?php echo esc_url($consent); ?>" class="footer-bottom__link">Согласие на обработку персональных данных</a>
  </div>
</div>

<!-- Modal: точная разметка из gulp-проекта -->
<div class="modal" id="modal-question" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="modal-question-title">
  <div class="modal__overlay" data-modal-close></div>
  <div class="modal__window">
    <button class="modal__close" type="button" aria-label="Закрыть" data-modal-close>
      <svg width="72" height="71" viewBox="0 0 72 71" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M58.6387 53.1142C59.2727 53.7394 59.6289 54.5874 59.6289 55.4716C59.6289 56.3558 59.2727 57.2038 58.6387 57.829C58.0046 58.4543 57.1447 58.8055 56.248 58.8055C55.3514 58.8055 54.4914 58.4543 53.8574 57.829L36.0009 40.2149L18.1387 57.8235C17.5046 58.4487 16.6447 58.8 15.748 58.8C14.8514 58.8 13.9915 58.4487 13.3574 57.8235C12.7234 57.1983 12.3672 56.3503 12.3672 55.4661C12.3672 54.5819 12.7234 53.7339 13.3574 53.1086L31.2196 35.5001L13.363 17.886C12.729 17.2608 12.3728 16.4128 12.3728 15.5286C12.3728 14.6444 12.729 13.7964 13.363 13.1711C13.9971 12.5459 14.857 12.1947 15.7537 12.1947C16.6503 12.1947 17.5103 12.5459 18.1443 13.1711L36.0009 30.7852L53.863 13.1684C54.4971 12.5431 55.357 12.1919 56.2537 12.1919C57.1503 12.1919 58.0103 12.5431 58.6443 13.1684C59.2783 13.7936 59.6345 14.6416 59.6345 15.5258C59.6345 16.41 59.2783 17.258 58.6443 17.8832L40.7821 35.5001L58.6387 53.1142Z" fill="white"/>
      </svg>
    </button>
    <div class="modal__content">
      <h2 class="modal__title" id="modal-question-title">Остались вопросы?</h2>
      <p class="modal__subtitle">С радостью ответим на них</p>
      <?php $modal_cf7 = minoksidil_cf7_form('modal'); ?>
      <?php if (!$modal_cf7) : // фолбэк — своя AJAX-форма, если CF7 не активен ?>
      <form class="modal-form" id="modal-callback-form" method="post">
        <?php wp_nonce_field('minoksidil_callback', 'callback_nonce'); ?>
        <input type="hidden" name="action" value="minoksidil_callback">
        <input class="modal-form__input" type="text" name="callback_name" placeholder="Имя" required>
        <div class="modal-form__row">
          <input class="modal-form__input" type="email" name="callback_email" placeholder="E-mail">
          <input class="modal-form__input" type="tel" name="callback_phone" placeholder="+7 (XXX) XXX-XX-XX" required data-phone-mask>
        </div>
        <input class="modal-form__input" type="text" name="callback_message" placeholder="Комментарий">
        <label class="modal-form__check">
          <input class="modal-form__check-input" type="checkbox" name="privacy" required>
          <span class="modal-form__check-box"></span>
          <span class="modal-form__check-text">Нажимая кнопку вы подтверждаете, что ознакомлены с нашей <a href="<?php echo esc_url($privacy); ?>">Политикой конфиденциальности</a></span>
        </label>
        <label class="modal-form__check">
          <input class="modal-form__check-input" type="checkbox" name="consent" required>
          <span class="modal-form__check-box"></span>
          <span class="modal-form__check-text">Нажимая кнопку вы подтверждаете, что даёте <a href="<?php echo esc_url($consent); ?>">Согласие на обработку персональных данных</a></span>
        </label>
        <button class="modal-form__btn" type="submit">Перезвоните мне</button>
        <div class="form-result" hidden style="margin-top:12px;font-size:14px;"></div>
      </form>
      <?php endif; ?>
    </div>
  </div>
</div>

</div><!-- .site-container -->
<?php wp_footer(); ?>
</body>
</html>

<?php if (empty($modal_cf7)) minoksidil_page_form_script('modal-callback-form'); ?>

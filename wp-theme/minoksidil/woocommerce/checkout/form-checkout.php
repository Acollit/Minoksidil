<?php
defined('ABSPATH') || exit;
get_header();
?>

<main class="main">
  <section class="checkout-page">
    <div class="container">
      <h1 class="checkout-page__title">Оформление заказа</h1>


      <?php if (WC()->cart->is_empty()): ?>
        <p>Корзина пуста. <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">Перейти в каталог</a></p>
        <?php get_footer(); return; ?>
      <?php endif; ?>

      <form name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url(wc_get_checkout_url()); ?>" enctype="multipart/form-data">

        <div class="checkout-page__inner">

          <!-- LEFT: Customer fields -->
          <div class="checkout-page__left">

            <!-- Contact info -->
            <div class="checkout-block">
              <h2 class="checkout-block__title">Контактные данные</h2>
              <div class="checkout-block__fields">
                <div class="checkout-field">
                  <label for="billing_first_name">Имя <abbr title="обязательно">*</abbr></label>
                  <input type="text" id="billing_first_name" name="billing_first_name"
                         class="checkout-input" placeholder="Иван" required
                         value="<?php echo esc_attr(WC()->checkout->get_value('billing_first_name')); ?>">
                </div>
                <div class="checkout-field">
                  <label for="billing_last_name">Фамилия <abbr title="обязательно">*</abbr></label>
                  <input type="text" id="billing_last_name" name="billing_last_name"
                         class="checkout-input" placeholder="Иванов" required
                         value="<?php echo esc_attr(WC()->checkout->get_value('billing_last_name')); ?>">
                </div>
                <div class="checkout-field checkout-field--full">
                  <label for="billing_phone">Телефон <abbr title="обязательно">*</abbr></label>
                  <input type="tel" id="billing_phone" name="billing_phone"
                         class="checkout-input" placeholder="+7 (___) ___-__-__" required
                         value="<?php echo esc_attr(WC()->checkout->get_value('billing_phone')); ?>"
                         data-phone-mask>
                </div>
                <div class="checkout-field checkout-field--full">
                  <label for="billing_email">Email <abbr title="обязательно">*</abbr></label>
                  <input type="email" id="billing_email" name="billing_email"
                         class="checkout-input" placeholder="ivan@example.com" required
                         value="<?php echo esc_attr(WC()->checkout->get_value('billing_email')); ?>">
                </div>
              </div>
            </div>

            <!-- Delivery type -->
            <div class="checkout-block">
              <h2 class="checkout-block__title">Способ доставки</h2>
              <div class="checkout-delivery-types">
                <label class="delivery-type-option">
                  <input type="radio" name="delivery_type" value="courier"
                         id="delivery-courier" checked>
                  <span class="delivery-type-option__box">
                    <span class="delivery-type-option__icon">🚚</span>
                    <span class="delivery-type-option__label">Курьерская доставка</span>
                    <span class="delivery-type-option__desc">Доставим по указанному адресу</span>
                  </span>
                </label>
                <label class="delivery-type-option">
                  <input type="radio" name="delivery_type" value="pvz" id="delivery-pvz">
                  <span class="delivery-type-option__box">
                    <span class="delivery-type-option__icon">📦</span>
                    <span class="delivery-type-option__label">Пункт выдачи (ПВЗ)</span>
                    <span class="delivery-type-option__desc">СДЭК, Яндекс, Ozon</span>
                  </span>
                </label>
              </div>
            </div>

            <!-- Courier address (shown when courier selected) -->
            <div class="checkout-block" id="courier-address-block">
              <h2 class="checkout-block__title">Адрес доставки</h2>
              <div class="checkout-block__fields">
                <div class="checkout-field checkout-field--full">
                  <label for="shipping_city">Город <abbr title="обязательно">*</abbr></label>
                  <input type="text" id="shipping_city" name="shipping_city"
                         class="checkout-input" placeholder="Москва"
                         value="<?php echo esc_attr(WC()->checkout->get_value('shipping_city')); ?>">
                </div>
                <div class="checkout-field checkout-field--full">
                  <label for="shipping_address_1">Улица, дом, квартира <abbr title="обязательно">*</abbr></label>
                  <input type="text" id="shipping_address_1" name="shipping_address_1"
                         class="checkout-input" placeholder="ул. Ленина, д. 1, кв. 5"
                         value="<?php echo esc_attr(WC()->checkout->get_value('shipping_address_1')); ?>">
                </div>
              </div>
            </div>

            <!-- PVZ map selection (shown when pvz selected) -->
            <div class="checkout-block" id="pvz-block" hidden>
              <h2 class="checkout-block__title">Выберите пункт выдачи</h2>

              <!-- Service tabs -->
              <div class="pvz-tabs">
                <button type="button" class="pvz-tab is-active" data-pvz-tab="cdek">СДЭК</button>
                <button type="button" class="pvz-tab" data-pvz-tab="yandex">Яндекс</button>
                <button type="button" class="pvz-tab" data-pvz-tab="ozon">Ozon</button>
              </div>

              <!-- CDEK Widget -->
              <div class="pvz-map-wrap is-active" id="pvz-map-cdek">
                <div id="cdek-map" style="width:100%; height:450px;"></div>
                <p class="pvz-map-note">
                  Для работы виджета СДЭК необходим API-ключ Яндекс Карт.
                  Настройте в <a href="<?php echo esc_url(admin_url('admin.php?page=minoksidil-settings')); ?>">настройках темы</a>.
                </p>
              </div>

              <!-- Yandex Map -->
              <div class="pvz-map-wrap" id="pvz-map-yandex" hidden>
                <div id="yandex-map" style="width:100%; height:450px;"></div>
                <p class="pvz-map-note">Выберите пункт выдачи СДЭК на карте Яндекса.</p>
              </div>

              <!-- Ozon -->
              <div class="pvz-map-wrap" id="pvz-map-ozon" hidden>
                <div class="pvz-ozon-info">
                  <p>Чтобы выбрать пункт выдачи Ozon:</p>
                  <ol>
                    <li>Откройте карту пунктов выдачи Ozon</li>
                    <li>Выберите удобный ПВЗ</li>
                    <li>Укажите его адрес в поле ниже</li>
                  </ol>
                  <a href="https://www.ozon.ru/highlight/punkty-vydachi-ozon/" target="_blank" rel="noopener"
                     class="btn btn--outline pvz-ozon-btn">Открыть карту Ozon →</a>
                  <div class="pvz-ozon-manual">
                    <label for="ozon-address-manual">Введите адрес выбранного ПВЗ Ozon:</label>
                    <input type="text" id="ozon-address-manual" class="checkout-input"
                           placeholder="Москва, ул. Ленина, д. 1">
                    <button type="button" class="btn btn--green pvz-ozon-confirm" id="pvz-ozon-confirm">
                      Подтвердить адрес
                    </button>
                  </div>
                </div>
              </div>

              <!-- Selected PVZ display -->
              <div class="pvz-selected" id="pvz-selected" hidden>
                <div class="pvz-selected__inner">
                  <span class="pvz-selected__icon">📍</span>
                  <div>
                    <p class="pvz-selected__label">Выбранный пункт выдачи:</p>
                    <p class="pvz-selected__address" id="pvz-selected-address"></p>
                  </div>
                  <button type="button" class="pvz-selected__change" id="pvz-change">Изменить</button>
                </div>
              </div>

              <!-- Hidden fields for order meta -->
              <input type="hidden" name="pvz_service" id="pvz-service-input" value="">
              <input type="hidden" name="pvz_code" id="pvz-code-input" value="">
              <input type="hidden" name="pvz_address" id="pvz-address-input"
                     value="<?php echo esc_attr(WC()->session ? (string)WC()->session->get('pvz_address', '') : ''); ?>">
            </div>

            <!-- Order notes -->
            <div class="checkout-block">
              <h2 class="checkout-block__title">Комментарий к заказу</h2>
              <textarea name="order_comments" id="order_comments" class="checkout-textarea"
                        placeholder="Пожелания по доставке, уточнения и т.д." rows="3"><?php
                echo esc_textarea(WC()->checkout->get_value('order_comments'));
              ?></textarea>
            </div>

          </div>

          <!-- RIGHT: Order summary -->
          <div class="checkout-page__right">
            <div class="checkout-summary">
              <h2 class="checkout-summary__title">Ваш заказ</h2>

              <div class="checkout-summary__items">
                <?php foreach (WC()->cart->get_cart() as $cart_item):
                    $p   = $cart_item['data'];
                    $qty = $cart_item['quantity'];
                    $img_id  = $p->get_image_id();
                    $img_url = $img_id ? wp_get_attachment_image_url($img_id, 'thumbnail') : wc_placeholder_img_src();
                ?>
                  <div class="checkout-summary__item">
                    <img src="<?php echo esc_url($img_url); ?>"
                         alt="<?php echo esc_attr($p->get_name()); ?>"
                         width="60" height="60" class="checkout-summary__item-img">
                    <div class="checkout-summary__item-info">
                      <span class="checkout-summary__item-name"><?php echo esc_html($p->get_name()); ?></span>
                      <span class="checkout-summary__item-qty">× <?php echo absint($qty); ?></span>
                    </div>
                    <span class="checkout-summary__item-price">
                      <?php echo wp_kses_post(WC()->cart->get_product_subtotal($p, $qty)); ?>
                    </span>
                  </div>
                <?php endforeach; ?>
              </div>

              <div class="checkout-summary__totals">
                <div class="checkout-summary__row">
                  <span>Товары:</span>
                  <span><?php echo wp_kses_post(WC()->cart->get_cart_subtotal()); ?></span>
                </div>
                <div class="checkout-summary__row checkout-summary__row--total">
                  <span>Итого:</span>
                  <span class="checkout-summary__total-price">
                    <?php echo wp_kses_post(WC()->cart->get_total()); ?>
                  </span>
                </div>
              </div>

              <!-- Payment method (hidden — no real payment) -->
              <div class="checkout-summary__payment">
                <p class="checkout-summary__payment-text">
                  💳 Оплата при получении — наш менеджер свяжется с вами для подтверждения заказа.
                </p>
              </div>

              <?php do_action('woocommerce_checkout_before_order_review_heading'); ?>
              <?php do_action('woocommerce_checkout_before_order_review'); ?>

              <input type="hidden" name="payment_method" value="minoksidil_no_payment">
              <?php wp_nonce_field('woocommerce-process_checkout', 'woocommerce-process-checkout-nonce'); ?>

              <button type="submit" name="woocommerce_checkout_place_order" id="place_order"
                      class="btn btn--green checkout-summary__submit">
                Оформить заказ
              </button>

              <p class="checkout-summary__privacy">
                Нажимая кнопку, вы соглашаетесь с
                <a href="<?php echo esc_url(get_option('minoksidil_privacy_url', '#')); ?>">политикой конфиденциальности</a>
              </p>

              <?php do_action('woocommerce_checkout_after_order_review'); ?>
            </div>
          </div>

        </div>

      </form>
    </div>
  </section>
</main>

<?php get_footer(); ?>

<?php
defined('ABSPATH') || exit;
get_header();

$cart            = WC()->cart;
$count           = $cart->get_cart_contents_count();
$privacy         = get_option('minoksidil_privacy_url', '#');
$consent         = get_option('minoksidil_consent_url', '#');
// Координаты ПВЗ берём из локальной базы (таблица wp_minoksidil_pvz)
$ymaps_key      = get_option('minoksidil_ymaps_key', '');
$pvz_api_url    = esc_url_raw(rest_url('minoksidil/v1/pvz'));
$pvz_cities_url = esc_url_raw(rest_url('minoksidil/v1/pvz/cities'));
$pvz_total      = function_exists('minoksidil_pvz_counts') ? minoksidil_pvz_counts()['total'] : 0;

// WooCommerce checkout nonce — нужен для AJAX-оформления прямо со страницы корзины
$checkout_nonce = wp_create_nonce('woocommerce-process_checkout');
?>

<main class="main">
  <section class="cart-page">
    <div class="container">

      <div class="cart-page__head">
        <p class="cart-page__count">
          <?php if (!$cart->is_empty()) : ?>
            <?php echo absint($count); ?> <?php echo _n('товар', 'товара', $count, 'minoksidil'); ?>
          <?php else : ?>
            Корзина пуста
          <?php endif; ?>
        </p>
        <h1 class="cart-page__title">Корзина</h1>
      </div>

      <?php if ($cart->is_empty()) : ?>

        <div style="text-align:center; padding:80px 20px;">
          <p style="font-size:20px;color:#888;margin-bottom:32px;">В корзине пока ничего нет</p>
          <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="btn btn--green">
            Перейти в каталог
          </a>
        </div>

      <?php else : ?>

        <div class="cart-page__layout">

          <!-- ===== LEFT: товары ===== -->
          <div class="cart-page__left">

            <div class="cart-page__controls">
              <label class="cart-check-all">
                <input class="cart-check-all__input" type="checkbox" id="cart-select-all">
                <span class="cart-check-all__box"></span>
                <span class="cart-check-all__text">Выбрать все</span>
              </label>
              <button class="cart-delete-selected btn-reset" type="button" id="cart-delete-selected">
                <svg width="26" height="26" viewBox="0 0 26 26" fill="none"><path d="M21.9375 4.875H18.2812V3.65625C18.2812 2.90204 17.9816 2.17872 17.4483 1.64542C16.915 1.11211 16.1917 0.8125 15.4375 0.8125H10.5625C9.80829 0.8125 9.08497 1.11211 8.55167 1.64542C8.01836 2.17872 7.71875 2.90204 7.71875 3.65625V4.875H4.0625C3.73927 4.875 3.42927 5.0034 3.20071 5.23196C2.97215 5.46052 2.84375 5.77052 2.84375 6.09375C2.84375 6.41698 2.97215 6.72698 3.20071 6.95554C3.42927 7.1841 3.73927 7.3125 4.0625 7.3125H4.46875V21.125C4.46875 21.6637 4.68276 22.1804 5.06369 22.5613C5.44462 22.9422 5.96128 23.1562 6.5 23.1562H19.5C20.0387 23.1562 20.5554 22.9422 20.9363 22.5613C21.3172 22.1804 21.5312 21.6637 21.5312 21.125V7.3125H21.9375C22.2607 7.3125 22.5707 7.1841 22.7993 6.95554C23.0278 6.72698 23.1562 6.41698 23.1562 6.09375C23.1562 5.77052 23.0278 5.46052 22.7993 5.23196C22.5707 5.0034 22.2607 4.875 21.9375 4.875ZM10.1562 3.65625H15.8438V4.875H10.1562V3.65625ZM19.0938 20.7188H6.90625V7.3125H19.0938V20.7188Z" fill="#E93922"/></svg>
                Удалить выбранное
              </button>
            </div>

            <div class="cart-items" id="cart-items-list">
              <?php foreach ($cart->get_cart() as $key => $item) :
                  $p        = $item['data'];
                  $qty      = $item['quantity'];
                  $img_url  = minoksidil_get_product_catalog_img($p);
                  $is_new   = $p->is_featured();
                  $price_raw  = (float)$p->get_price() * $qty;
                  $remove_url = add_query_arg([
                      'remove_item' => $key,
                      '_wpnonce'    => wp_create_nonce('woocommerce-cart'),
                  ], wc_get_cart_url());
              ?>
                <div class="cart-item" data-key="<?php echo esc_attr($key); ?>">
                  <label class="cart-item__check">
                    <input class="cart-item__check-input" type="checkbox" name="selected[]" value="<?php echo esc_attr($key); ?>">
                    <span class="cart-item__check-box"></span>
                  </label>
                  <div class="cart-item__img">
                    <img src="<?php echo esc_url($img_url); ?>"
                         alt="<?php echo esc_attr($p->get_name()); ?>"
                         width="80" height="80">
                  </div>
                  <div class="cart-item__info">
                    <?php if ($is_new) : ?>
                      <span class="cart-item__badge">NEW</span>
                    <?php endif; ?>
                    <p class="cart-item__name">
                      <a href="<?php echo esc_url($p->get_permalink()); ?>"><?php echo esc_html($p->get_name()); ?></a>
                    </p>
                    <?php
                    $weight = $p->get_weight();
                    if ($weight) echo '<p class="cart-item__volume">Вес: ' . esc_html($weight) . ' г.</p>';
                    ?>
                  </div>
                  <div class="cart-item__right">
                    <div class="cart-item__prices">
                      <span class="cart-item__price"><?php echo wc_price($price_raw); ?></span>
                    </div>
                    <div class="qty-stepper" data-key="<?php echo esc_attr($key); ?>">
                      <button class="qty-stepper__btn" type="button" data-action="minus" aria-label="Уменьшить">−</button>
                      <span class="qty-stepper__value"><?php echo absint($qty); ?></span>
                      <button class="qty-stepper__btn" type="button" data-action="plus" aria-label="Увеличить">+</button>
                    </div>
                    <a href="<?php echo esc_url($remove_url); ?>" class="cart-item__delete">
                      Удалить
                      <svg width="34" height="34" viewBox="0 0 34 34" fill="none"><path d="M28.6875 6.375H23.9062V4.78125C23.9062 3.79498 23.5145 2.8491 22.8171 2.1517C22.1197 1.4543 21.1738 1.0625 20.1875 1.0625H13.8125C12.8262 1.0625 11.8803 1.4543 11.1829 2.1517C10.4855 2.8491 10.0938 3.79498 10.0938 4.78125V6.375H5.3125C4.88981 6.375 4.48443 6.54291 4.18555 6.8418C3.88666 7.14068 3.71875 7.54606 3.71875 7.96875C3.71875 8.39144 3.88666 8.79682 4.18555 9.0957C4.48443 9.39459 4.88981 9.5625 5.3125 9.5625H5.84375V27.625C5.84375 28.3295 6.1236 29.0051 6.62175 29.5033C7.11989 30.0014 7.79552 30.2812 8.5 30.2812H25.5C26.2045 30.2812 26.8801 30.0014 27.3783 29.5033C27.8764 29.0051 28.1562 28.3295 28.1562 27.625V9.5625H28.6875C29.1102 9.5625 29.5156 9.39459 29.8145 9.0957C30.1133 8.79682 30.2812 8.39144 30.2812 7.96875C30.2812 7.54606 30.1133 7.14068 29.8145 6.8418C29.5156 6.54291 29.1102 6.375 28.6875 6.375ZM13.2812 4.78125H20.7188V6.375H13.2812V4.78125ZM24.9688 27.0938H9.03125V9.5625H24.9688V27.0938Z" fill="#E93922"/></svg>
                    </a>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

          </div>

          <!-- ===== RIGHT: итог + оформление ===== -->
          <div class="cart-page__right">
            <div class="cart-aside">

              <div class="cart-aside__top">

                <!-- Сумма -->
                <div class="cart-aside__summary">
                  <div class="cart-aside__row">
                    <span class="cart-aside__label">Товары: <?php echo absint($count); ?> шт</span>
                    <span class="cart-aside__value" id="cart-subtotal"><?php echo wp_kses_post($cart->get_cart_subtotal()); ?></span>
                  </div>
                  <?php if ($cart->get_discount_total() > 0) : ?>
                    <div class="cart-aside__row">
                      <span class="cart-aside__label">Скидки и акции:</span>
                      <span class="cart-aside__value">−<?php echo wc_price($cart->get_discount_total()); ?></span>
                    </div>
                  <?php endif; ?>
                  <div class="cart-aside__row">
                    <span class="cart-aside__label">Доставка:</span>
                    <span class="cart-aside__value">Рассчитывается</span>
                  </div>
                </div>

                <div class="cart-aside__total">
                  <span class="cart-aside__total-label">Итого:</span>
                  <span class="cart-aside__total-value" id="cart-total"><?php echo wp_kses_post($cart->get_total()); ?></span>
                </div>

                <!-- Промокод -->
                <div class="cart-aside__promo">
                  <p class="cart-aside__promo-label">Применить промокод:</p>
                  <div class="cart-aside__promo-row">
                    <input class="cart-aside__promo-input" type="text" id="coupon-code" placeholder="Введите промокод">
                    <button class="cart-aside__promo-btn btn-reset" type="button" id="coupon-apply">Применить</button>
                  </div>
                  <div id="coupon-result" style="font-size:13px;margin-top:8px;"></div>
                </div>

              </div><!-- /.cart-aside__top -->

              <!-- ===== Форма оформления ===== -->
              <div class="cart-checkout">
                <h2 class="cart-checkout__title">Оформление</h2>

                <div class="cart-checkout__fields">
                  <input class="cart-checkout__input" type="text" id="co-name" placeholder="ФИО" required>
                  <input class="cart-checkout__input" type="tel" id="co-phone" placeholder="+7 (XXX) XXX-XX-XX" required data-phone-mask>
                  <!-- Контакт для выбранного способа связи (скрывается при способе «Телефон») -->
                  <input class="cart-checkout__input" type="text" id="co-contact-value"
                         placeholder="Ник в Telegram (@username)" required>
                </div>

                <!-- Способ связи -->
                <div class="cart-checkout__group">
                  <p class="cart-checkout__group-label">Способ связи</p>
                  <div class="cart-checkout__radios">
                    <label class="cart-radio">
                      <input class="cart-radio__input" type="radio" name="contact_method" value="telegram" checked>
                      <span class="cart-radio__dot"></span>
                      <span class="cart-radio__text">Telegram</span>
                    </label>
                    <label class="cart-radio">
                      <input class="cart-radio__input" type="radio" name="contact_method" value="whatsapp">
                      <span class="cart-radio__dot"></span>
                      <span class="cart-radio__text">WhatsApp</span>
                    </label>
                    <label class="cart-radio">
                      <input class="cart-radio__input" type="radio" name="contact_method" value="email">
                      <span class="cart-radio__dot"></span>
                      <span class="cart-radio__text">E-mail</span>
                    </label>
                    <label class="cart-radio">
                      <input class="cart-radio__input" type="radio" name="contact_method" value="phone">
                      <span class="cart-radio__dot"></span>
                      <span class="cart-radio__text">Телефон</span>
                    </label>
                    <label class="cart-radio">
                      <input class="cart-radio__input" type="radio" name="contact_method" value="max">
                      <span class="cart-radio__dot"></span>
                      <span class="cart-radio__text">MAX</span>
                    </label>
                    <label class="cart-radio">
                      <input class="cart-radio__input" type="radio" name="contact_method" value="vk">
                      <span class="cart-radio__dot"></span>
                      <span class="cart-radio__text">ВКонтакте</span>
                    </label>
                  </div>
                </div>

                <!-- Способ доставки -->
                <div class="cart-checkout__group">
                  <p class="cart-checkout__group-label">Способ доставки</p>
                  <div class="cart-checkout__radios" id="delivery-radios">
                    <label class="cart-radio">
                      <input class="cart-radio__input" type="radio" name="delivery_method" value="pvz">
                      <span class="cart-radio__dot"></span>
                      <span class="cart-radio__text">Пункт выдачи (ПВЗ)</span>
                    </label>

                    <label class="cart-radio">
                      <input class="cart-radio__input" type="radio" name="delivery_method" value="courier">
                      <span class="cart-radio__dot"></span>
                      <span class="cart-radio__text">Экспресс курьер по Москве</span>
                    </label>
                    <label class="cart-radio">
                      <input class="cart-radio__input" type="radio" name="delivery_method" value="post" checked>
                      <span class="cart-radio__dot"></span>
                      <span class="cart-radio__text">Почта России</span>
                    </label>

                  </div>

                  <!-- Адрес курьера / ПВЗ -->
                  <div id="co-address-wrap">
                    <a href="#" class="cart-checkout__pvz" id="open-pvz-map" style="display:none;">
                      Выбрать ПВЗ на карте
                    </a>
                    <div id="pvz-selected-display" style="display:none;margin:8px 0;padding:10px 14px;background:rgba(131,202,83,0.1);border-radius:10px;font-size:14px;color:#333;">
                      <strong>Выбран ПВЗ:</strong> <span id="pvz-address-text"></span>
                      <button type="button" id="pvz-change-btn" style="background:none;border:none;color:#0063b1;cursor:pointer;font-size:12px;text-decoration:underline;">Изменить</button>
                    </div>
                    <input class="cart-checkout__input" type="text" id="co-address" placeholder="Адрес доставки" style="margin-top:10px;">
                  </div>
                </div>

                <!-- Комментарий -->
                <textarea class="cart-checkout__input" id="co-comment" placeholder="Комментарий к заказу" rows="1" style="resize:vertical;"></textarea>

                <!-- Согласие -->
                <label class="modal-form__check" style="margin-top:12px;">
                  <input class="modal-form__check-input" type="checkbox" id="co-privacy" required>
                  <span class="modal-form__check-box"></span>
                  <span class="modal-form__check-text">
                    Согласен с <a href="<?php echo esc_url($privacy); ?>">политикой конфиденциальности</a>
                  </span>
                </label>

                <div id="cart-checkout-errors" style="display:none;margin-top:12px;padding:12px;background:#fdecea;border-radius:8px;font-size:14px;color:#c62828;"></div>

              </div><!-- /.cart-checkout -->

              <button class="cart-submit" type="button" id="cart-submit-btn">Оформить заказ</button>

            </div><!-- /.cart-aside -->
          </div><!-- /.cart-page__right -->

        </div><!-- /.cart-page__layout -->

      <?php endif; ?>

    </div>
  </section>
</main>

<!-- ===== PVZ Modal ===== -->
<div class="pvz-modal-overlay" id="pvz-modal" style="display:none;position:fixed;inset:0;z-index:2000;background:rgba(0,13,36,0.88);overflow:auto;padding:20px;">
  <div style="max-width:860px;margin:40px auto;background:#fff;border-radius:24px;padding:32px;position:relative;">
    <button type="button" id="pvz-modal-close" style="position:absolute;top:16px;right:16px;background:none;border:none;font-size:28px;cursor:pointer;color:#666;line-height:1;">×</button>
    <h2 style="font-size:24px;font-weight:700;margin-bottom:24px;">Выбрать пункт выдачи</h2>

    <!-- Поиск города -->
    <div class="pvz-city-search" style="position:relative;">
      <input type="text" id="pvz-city-input" class="pvz-city-input" placeholder="Введите город...">
      <button type="button" id="pvz-city-btn" class="pvz-city-btn">Найти ПВЗ</button>
      <div class="pvz-city-suggestions" id="pvz-city-suggestions"></div>
    </div>

    <!-- Легенда-фильтр служб доставки -->
    <div class="pvz-legend">
      <label class="pvz-legend__item">
        <input type="checkbox" class="pvz-legend__cb" data-svc="cdek" checked>
        <span class="pvz-legend__dot" style="background:#1AB248;"></span> СДЭК
      </label>
      <label class="pvz-legend__item">
        <input type="checkbox" class="pvz-legend__cb" data-svc="yandex" checked>
        <span class="pvz-legend__dot" style="background:#FFCC00;"></span> Яндекс Маркет
      </label>
      <label class="pvz-legend__item">
        <input type="checkbox" class="pvz-legend__cb" data-svc="ozon" checked>
        <span class="pvz-legend__dot" style="background:#005BFF;"></span> Ozon
      </label>
    </div>

    <!-- Единая интерактивная карта (Leaflet + OpenStreetMap) -->
    <div id="pvz-map" style="width:100%;height:460px;border-radius:12px;overflow:hidden;"></div>
    <div id="pvz-status" class="pvz-status" style="display:none;"></div>

    <?php if ($pvz_total === 0 && current_user_can('manage_options')) : ?>
      <p class="pvz-map-note" style="font-size:12px;color:#b26a00;margin-top:8px;">
        База пунктов выдачи пуста. Импортируйте координаты ПВЗ или загрузите демо-точки на странице
        <a href="<?php echo esc_url(admin_url('admin.php?page=minoksidil-pvz')); ?>">Minoksidil → Пункты выдачи (ПВЗ)</a>.
      </p>
    <?php endif; ?>

  </div>
</div>

<!-- ===== Скрытые данные для JS ===== -->
<script>
var minoksidilCart = {
  ymapsKey:      '<?php echo esc_js($ymaps_key); ?>',
  checkoutNonce: '<?php echo esc_js($checkout_nonce); ?>',
  checkoutUrl:   '/?wc-ajax=checkout',
  couponUrl:     '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
  couponNonce:   '<?php echo esc_js(wp_create_nonce('apply-coupon')); ?>',
  ajaxUrl:       '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
  pvzNonce:      '<?php echo esc_js(wp_create_nonce('minoksidil_nonce')); ?>',
  pvzApi:        '<?php echo esc_js($pvz_api_url); ?>',
  citiesApi:     '<?php echo esc_js($pvz_cities_url); ?>',
  cartUrl:       '<?php echo esc_url(wc_get_cart_url()); ?>',
  thanksUrl:     '<?php $p = get_page_by_path('order'); echo esc_js($p ? esc_url(get_permalink($p)) : esc_url(home_url('/order/'))); ?>',
};
</script>
<script>
(function () {
  'use strict';

  // ===== Qty stepper (AJAX update) =====
  document.querySelectorAll('.qty-stepper').forEach(function (stepper) {
    var key     = stepper.dataset.key;
    var valEl   = stepper.querySelector('.qty-stepper__value');
    var current = parseInt(valEl.textContent, 10) || 1;

    stepper.querySelectorAll('.qty-stepper__btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        if (this.dataset.action === 'plus')  current = Math.min(current + 1, 99);
        if (this.dataset.action === 'minus') current = Math.max(current - 1, 0);
        valEl.textContent = current;

        // AJAX update cart qty
        var fd = new FormData();
        fd.append('action', 'minoksidil_update_cart_item');
        fd.append('nonce',  minoksidilCart.pvzNonce);
        fd.append('key',    key);
        fd.append('qty',    current);
        fetch(minoksidilCart.ajaxUrl, { method: 'POST', body: fd })
          .then(function (r) { return r.json(); })
          .then(function (d) {
            if (d.success) {
              var t = document.querySelector('#cart-total');
              var s = document.querySelector('#cart-subtotal');
              if (t) t.innerHTML = d.data.total;
              if (s) s.innerHTML = d.data.subtotal;
              if (current === 0) {
                stepper.closest('.cart-item').remove();
              }
            }
          });
      });
    });
  });

  // ===== Select all =====
  var selectAll = document.querySelector('#cart-select-all');
  if (selectAll) {
    selectAll.addEventListener('change', function () {
      document.querySelectorAll('.cart-item__check-input').forEach(function (cb) {
        cb.checked = selectAll.checked;
      });
    });
  }

  // ===== Delete selected (sequential — WC session race condition fix) =====
  document.querySelector('#cart-delete-selected')?.addEventListener('click', function () {
    var checked = Array.from(document.querySelectorAll('.cart-item__check-input:checked'));
    if (!checked.length) return;

    var btn = this;
    btn.disabled = true;

    checked.reduce(function (chain, cb) {
      return chain.then(function () {
        var key = cb.closest('.cart-item').dataset.key;
        var fd = new FormData();
        fd.append('action', 'minoksidil_update_cart_item');
        fd.append('nonce',  minoksidilCart.pvzNonce);
        fd.append('key',    key);
        fd.append('qty',    0);
        return fetch(minoksidilCart.ajaxUrl, { method: 'POST', body: fd })
          .then(function (r) { return r.json(); })
          .then(function (d) {
            if (d.success) {
              cb.closest('.cart-item').remove();
              var t = document.querySelector('#cart-total');
              var s = document.querySelector('#cart-subtotal');
              if (t) t.innerHTML = d.data.total;
              if (s) s.innerHTML = d.data.subtotal;
            }
          });
      });
    }, Promise.resolve()).then(function () {
      btn.disabled = false;
      if (!document.querySelectorAll('.cart-item').length) {
        window.location.reload();
      }
    });
  });

  // ===== Contact method — поле контакта под выбранный способ =====
  var CONTACT_FIELDS = {
    telegram: { placeholder: 'Ник или номер в Telegram (@username)', type: 'text',  label: 'Telegram' },
    whatsapp: { placeholder: 'Номер WhatsApp',                       type: 'tel',   label: 'WhatsApp' },
    email:    { placeholder: 'E-mail',                               type: 'email', label: 'E-mail' },
    phone:    { placeholder: 'Номер телефона для звонка',            type: 'tel',   label: 'Телефон' },
    max:      { placeholder: 'Номер или ник в MAX',                  type: 'text',  label: 'MAX' },
    vk:       { placeholder: 'Ссылка или ник ВКонтакте',             type: 'text',  label: 'ВКонтакте' }
  };

  function updateContactUI() {
    var method = document.querySelector('input[name="contact_method"]:checked')?.value || 'telegram';
    var input  = document.querySelector('#co-contact-value');
    if (!input) return;
    // «Телефон» — контактом служит номер из поля выше, отдельное поле скрываем
    if (method === 'phone') {
      input.style.display = 'none';
      return;
    }
    var cfg = CONTACT_FIELDS[method] || CONTACT_FIELDS.telegram;
    input.style.display = '';
    input.type          = cfg.type;
    input.placeholder   = cfg.placeholder;
  }

  document.querySelectorAll('input[name="contact_method"]').forEach(function (r) {
    r.addEventListener('change', updateContactUI);
  });
  updateContactUI();

  // ===== Delivery method — показать ПВЗ или адрес =====
  function updateDeliveryUI() {
    var selected = document.querySelector('input[name="delivery_method"]:checked');
    if (!selected) return;
    var val = selected.value;
    var pvzLink    = document.querySelector('#open-pvz-map');
    var addrInput  = document.querySelector('#co-address');

    if (pvzLink) pvzLink.style.display = val === 'pvz' ? '' : 'none';
    if (addrInput) {
      addrInput.placeholder = val === 'pvz' ? 'Адрес выбранного ПВЗ' : 'Адрес доставки';
    }
  }

  document.querySelectorAll('input[name="delivery_method"]').forEach(function (r) {
    r.addEventListener('change', updateDeliveryUI);
  });
  updateDeliveryUI();

  // ===== PVZ Modal =====
  var pvzModal      = document.querySelector('#pvz-modal');
  var pvzClose      = document.querySelector('#pvz-modal-close');
  var openPvzBtn    = document.querySelector('#open-pvz-map');
  var pvzSelectedEl = document.querySelector('#pvz-selected-display');
  var pvzTextEl     = document.querySelector('#pvz-address-text');
  var pvzChangeBtn  = document.querySelector('#pvz-change-btn');
  var addrInput     = document.querySelector('#co-address');
  var pvzService = '';
  var pvzCode    = '';

  function openPvzModal() {
    if (pvzModal) {
      pvzModal.style.display = 'block';
      document.body.style.overflow = 'hidden';
      initUnifiedMap();
      // карта строится в скрытой модалке — пересчитываем размеры после показа
      if (pvzMap) setTimeout(function () { pvzMap.container.fitToViewport(); }, 120);
    }
  }

  function closePvzModal() {
    if (pvzModal) {
      pvzModal.style.display = 'none';
      document.body.style.overflow = '';
    }
  }

  if (openPvzBtn) openPvzBtn.addEventListener('click', function (e) { e.preventDefault(); openPvzModal(); });
  if (pvzClose)   pvzClose.addEventListener('click', closePvzModal);
  if (pvzModal)   pvzModal.addEventListener('click', function (e) { if (e.target === pvzModal) closePvzModal(); });
  if (pvzChangeBtn) pvzChangeBtn.addEventListener('click', openPvzModal);

  function setPvzAddress(service, code, address) {
    pvzService = service;
    pvzCode    = code || '';
    if (addrInput)     addrInput.value = address;
    if (pvzTextEl)     pvzTextEl.textContent = address;
    if (pvzSelectedEl) pvzSelectedEl.style.display = address ? 'block' : 'none';
    closePvzModal();
  }

  // ===== Единая карта ПВЗ (Яндекс Карты) =====
  // Координаты берём из локальной базы (таблица wp_minoksidil_pvz) через REST.
  var SVC = {
    cdek:   { label: 'СДЭК',          color: '#1AB248' },
    yandex: { label: 'Яндекс Маркет', color: '#E6A000' },
    ozon:   { label: 'Ozon',          color: '#005BFF' },
  };

  var mapLoaded  = false;
  var pvzMap     = null;
  var clusterers = { cdek: null, yandex: null, ozon: null };
  var visibleSvc = { cdek: true, yandex: true, ozon: true };
  var moveTimer  = null;
  var pickBound  = false;
  var balloonOpen    = false; // открыт балун точки — точки не перезагружаем
  var pendingReload  = false; // карта сдвигалась при открытом балуне — перезагрузить после закрытия

  function setMapStatus(msg, type) {
    var el = document.querySelector('#pvz-status');
    if (!el) return;
    el.innerHTML     = msg;
    el.style.display = msg ? 'block' : 'none';
    el.style.color   = type === 'error' ? '#c62828' : '#555';
  }

  function updateMapStatus(counts) {
    var total = counts.cdek + counts.yandex + counts.ozon;
    if (!total) {
      setMapStatus('В этой области нет пунктов выдачи. Сдвиньте карту, найдите город или импортируйте базу ПВЗ.', 'info');
      return;
    }
    setMapStatus(
      '<b style="color:' + SVC.cdek.color   + '">СДЭК: ' + counts.cdek + '</b> &nbsp;·&nbsp; ' +
      '<b style="color:' + SVC.yandex.color + '">Яндекс Маркет: ' + counts.yandex + '</b> &nbsp;·&nbsp; ' +
      '<b style="color:' + SVC.ozon.color   + '">Ozon: ' + counts.ozon + '</b><br>' +
      'Нажмите на метку, чтобы выбрать пункт выдачи.',
      'info'
    );
  }

  // Ленивая загрузка Яндекс Карт при первом открытии модалки
  function initUnifiedMap() {
    if (mapLoaded) return;
    mapLoaded = true;

    var key = minoksidilCart.ymapsKey || '';
    var src = 'https://api-maps.yandex.ru/2.1/?lang=ru_RU' + (key ? '&apikey=' + encodeURIComponent(key) : '');

    var js = document.createElement('script');
    js.src    = src;
    js.onload = function () { ymaps.ready(buildUnifiedMap); };
    js.onerror = function () {
      mapLoaded = false;
      var el = document.querySelector('#pvz-map');
      if (el) el.innerHTML = '<p style="padding:16px;color:#888;">Ошибка загрузки карты. Проверьте интернет-соединение.</p>';
    };
    document.head.appendChild(js);
  }

  function buildUnifiedMap() {
    pvzMap = new ymaps.Map('pvz-map', {
      center:   [55.751574, 37.573856],
      zoom:     10,
      controls: ['zoomControl', 'fullscreenControl', 'typeSelector'],
    });

    ['cdek', 'yandex', 'ozon'].forEach(function (svc) {
      var color  = SVC[svc].color;
      var Layout = ymaps.templateLayoutFactory.createClass(
        '<div class="pvz-cluster" style="background:' + color + '">{{ properties.geoObjects.length }}</div>'
      );
      clusterers[svc] = new ymaps.Clusterer({
        clusterIconLayout: Layout,
        clusterIconShape:  { type: 'Circle', coordinates: [19, 19], radius: 19 },
      });
      // Пока открыт балун — не перезагружаем точки (иначе метка удаляется
      // и окошко выбора закрывается от автосдвига карты)
      clusterers[svc].events.add('balloonopen', function () { balloonOpen = true; });
      clusterers[svc].events.add('balloonclose', function () {
        balloonOpen = false;
        if (pendingReload) {
          pendingReload = false;
          clearTimeout(moveTimer);
          moveTimer = setTimeout(loadBbox, 200);
        }
      });
      pvzMap.geoObjects.add(clusterers[svc]);
    });

    // Выбор ПВЗ — делегированно из балуна
    if (!pickBound) {
      pickBound = true;
      document.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('.pvz-pick') : null;
        if (!btn) return;
        var svc  = btn.dataset.svc;
        var code = btn.dataset.code || '';
        var addr = (btn.dataset.addr || '').trim();

        if (addr) {
          setPvzAddress(svc, code, addr);
          if (pvzMap) pvzMap.balloon.close();
          return;
        }

        // У точки нет адреса в базе (импорт из OSM: Ozon/Яндекс) —
        // определяем адрес по координатам обратным геокодированием
        var lat      = parseFloat(btn.dataset.lat);
        var lng      = parseFloat(btn.dataset.lng);
        var label    = SVC[svc] ? SVC[svc].label : svc;
        var fallback = label + ', пункт выдачи (' + lat.toFixed(5) + ', ' + lng.toFixed(5) + ')';

        btn.disabled = true;
        btn.textContent = 'Определяем адрес…';

        function finish(address) {
          setPvzAddress(svc, code, address);
          if (pvzMap) pvzMap.balloon.close();
        }

        try {
          ymaps.geocode([lat, lng], { results: 1 }).then(function (res) {
            var obj  = res.geoObjects.get(0);
            var text = obj ? (obj.getAddressLine ? obj.getAddressLine() : obj.properties.get('text')) : '';
            finish(text || fallback);
          }, function () { finish(fallback); });
        } catch (err) {
          finish(fallback);
        }
      });
    }

    // Фильтр служб через легенду
    document.querySelectorAll('.pvz-legend__cb').forEach(function (cb) {
      cb.addEventListener('change', function () {
        var svc = this.dataset.svc;
        visibleSvc[svc] = this.checked;
        if (!clusterers[svc]) return;
        if (this.checked) pvzMap.geoObjects.add(clusterers[svc]);
        else              pvzMap.geoObjects.remove(clusterers[svc]);
      });
    });

    // Подгрузка точек при перемещении/зуме
    pvzMap.events.add('boundschange', function () {
      clearTimeout(moveTimer);
      moveTimer = setTimeout(loadBbox, 350);
    });

    setupCitySearch();
    setTimeout(function () { pvzMap.container.fitToViewport(); loadBbox(); }, 60);
  }

  // Рисуем массив точек, очищая предыдущие
  function renderPoints(points) {
    var counts = { cdek: 0, yandex: 0, ozon: 0 };
    var seen   = {}; // страховка от дублей: одна метка на службу+код (или координаты)
    ['cdek', 'yandex', 'ozon'].forEach(function (s) { if (clusterers[s]) clusterers[s].removeAll(); });
    if (!Array.isArray(points)) points = [];

    points.forEach(function (p) {
      var svc = p.service;
      if (!clusterers[svc]) return;

      var dupKey = svc + '|' + ((p.code || '').trim() || (Number(p.lat).toFixed(5) + '|' + Number(p.lng).toFixed(5)));
      if (seen[dupKey]) return;
      seen[dupKey] = true;
      var safeAddr = String(p.address || '').replace(/"/g, '&quot;');
      var safeName = String(p.name || SVC[svc].label).replace(/</g, '&lt;');
      var popupHtml =
        '<div class="pvz-balloon">' +
          '<p class="pvz-balloon__svc" style="color:' + SVC[svc].color + '">' + SVC[svc].label + '</p>' +
          '<p class="pvz-balloon__name">' + safeName + '</p>' +
          (p.address ? '<p class="pvz-balloon__addr">' + String(p.address).replace(/</g, '&lt;') + '</p>' : '') +
          (p.work_time ? '<p class="pvz-balloon__work">' + String(p.work_time).replace(/</g, '&lt;') + '</p>' : '') +
          (p.phone ? '<p class="pvz-balloon__phone">' + String(p.phone).replace(/</g, '&lt;') + '</p>' : '') +
          '<button type="button" class="pvz-pick"' +
            ' data-svc="' + svc + '"' +
            ' data-code="' + String(p.code || '').replace(/"/g, '&quot;') + '"' +
            ' data-addr="' + safeAddr + '"' +
            ' data-lat="' + p.lat + '" data-lng="' + p.lng + '">' +
            'Выбрать этот ПВЗ' +
          '</button>' +
        '</div>';

      var placemark = new ymaps.Placemark(
        [p.lat, p.lng],
        { balloonContent: popupHtml },
        { preset: 'islands#circleDotIcon', iconColor: SVC[svc].color }
      );
      clusterers[svc].add(placemark);
      counts[svc]++;
    });

    updateMapStatus(counts);
  }

  // Загрузка точек в текущих границах карты
  function loadBbox() {
    if (!pvzMap) return;
    if (balloonOpen) {
      pendingReload = true; // перезагрузим после закрытия балуна
      return;
    }
    var b    = pvzMap.getBounds(); // [[minLat, minLng], [maxLat, maxLng]]
    var bbox = [b[0][1], b[0][0], b[1][1], b[1][0]].join(','); // minLng,minLat,maxLng,maxLat
    setMapStatus('Загружаем пункты выдачи…', 'info');
    fetch(minoksidilCart.pvzApi + '?bbox=' + encodeURIComponent(bbox) + '&limit=2000')
      .then(function (r) { return r.json(); })
      .then(renderPoints)
      .catch(function () { setMapStatus('Ошибка загрузки точек.', 'error'); });
  }

  // ===== Поиск города по нашей базе =====
  function setupCitySearch() {
    var input = document.querySelector('#pvz-city-input');
    var btn   = document.querySelector('#pvz-city-btn');
    var sugg  = document.querySelector('#pvz-city-suggestions');
    if (!input || !btn) return;

    function gotoCity(city) {
      if (sugg) sugg.innerHTML = '';
      setMapStatus('Загружаем пункты выдачи в городе ' + city + '…', 'info');
      fetch(minoksidilCart.pvzApi + '?city=' + encodeURIComponent(city) + '&limit=3000')
        .then(function (r) { return r.json(); })
        .then(function (points) {
          if (!Array.isArray(points) || !points.length) {
            setMapStatus('В городе «' + city + '» нет пунктов выдачи в базе.', 'info');
            return;
          }
          var lats = points.map(function (p) { return p.lat; });
          var lngs = points.map(function (p) { return p.lng; });
          pvzMap.setBounds(
            [[Math.min.apply(null, lats), Math.min.apply(null, lngs)],
             [Math.max.apply(null, lats), Math.max.apply(null, lngs)]],
            { checkZoom: true, zoomMargin: [30, 30, 30, 30], duration: 300 }
          );
          renderPoints(points);
        })
        .catch(function () { setMapStatus('Ошибка загрузки точек.', 'error'); });
    }

    btn.addEventListener('click', function () {
      var q = input.value.trim();
      if (q.length > 1) gotoCity(q);
    });
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); btn.click(); }
    });

    var timer;
    input.addEventListener('input', function () {
      clearTimeout(timer);
      var q = this.value.trim();
      if (q.length < 2 || !sugg) { if (sugg) sugg.innerHTML = ''; return; }
      timer = setTimeout(function () {
        fetch(minoksidilCart.citiesApi + '?q=' + encodeURIComponent(q))
          .then(function (r) { return r.json(); })
          .then(function (cities) {
            sugg.innerHTML = '';
            (cities || []).forEach(function (c) {
              var div = document.createElement('div');
              div.className   = 'pvz-city-suggestion';
              div.textContent = c.city + (c.cnt ? ' (' + c.cnt + ')' : '');
              div.addEventListener('click', function () {
                input.value = c.city;
                gotoCity(c.city);
              });
              sugg.appendChild(div);
            });
          }).catch(function () {});
      }, 300);
    });

    document.addEventListener('click', function (e) {
      if (sugg && !input.contains(e.target) && !sugg.contains(e.target)) sugg.innerHTML = '';
    });
  }

  // ===== Промокод =====
  document.querySelector('#coupon-apply')?.addEventListener('click', function () {
    var code   = document.querySelector('#coupon-code')?.value.trim();
    var result = document.querySelector('#coupon-result');
    if (!code) return;

    var fd = new FormData();
    fd.append('action',   'woocommerce_apply_coupon');
    fd.append('security', minoksidilCart.couponNonce);
    fd.append('coupon_code', code);

    fetch(minoksidilCart.couponUrl, { method: 'POST', body: fd })
      .then(function (r) { return r.text(); })
      .then(function (html) {
        if (result) {
          result.innerHTML = html || 'Промокод применён';
          result.style.color = html.includes('error') ? '#c62828' : '#2e7d32';
        }
        // Перезагрузить страницу для обновления цены
        setTimeout(function () { window.location.reload(); }, 1200);
      });
  });

  // ===== Оформить заказ =====
  document.querySelector('#cart-submit-btn')?.addEventListener('click', function () {
    var btn      = this;
    var errBox   = document.querySelector('#cart-checkout-errors');
    var name       = (document.querySelector('#co-name')?.value || '').trim();
    var phone      = (document.querySelector('#co-phone')?.value || '').trim();
    var address    = (document.querySelector('#co-address')?.value || '').trim();
    var comment    = (document.querySelector('#co-comment')?.value || '').trim();
    var privacy    = document.querySelector('#co-privacy')?.checked;
    var contact    = (document.querySelector('input[name="contact_method"]:checked')?.value || 'telegram');
    var contactVal = (document.querySelector('#co-contact-value')?.value || '').trim();
    var delivery   = (document.querySelector('input[name="delivery_method"]:checked')?.value || 'post');
    var email      = contact === 'email' ? contactVal : '';

    var errors = [];
    if (!name)    errors.push('Введите ваше имя.');
    if (!phone)   errors.push('Введите номер телефона.');
    if (contact === 'phone') {
      contactVal = phone; // контакт = основной номер телефона
    } else if (!contactVal) {
      var cfgLabel = (CONTACT_FIELDS[contact] || {}).label || contact;
      errors.push('Укажите контакт для связи (' + cfgLabel + ').');
    } else if (contact === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(contactVal)) {
      errors.push('Укажите корректный e-mail для связи.');
    }
    if (!privacy) errors.push('Необходимо согласие с политикой конфиденциальности.');
    if (delivery === 'pvz' && !address) errors.push('Выберите пункт выдачи на карте.');
    if (delivery === 'courier' && !address) errors.push('Введите адрес доставки для курьера.');

    if (errors.length) {
      errBox.innerHTML = errors.join('<br>');
      errBox.style.display = 'block';
      errBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      return;
    }

    errBox.style.display = 'none';
    btn.disabled = true;
    btn.textContent = 'Оформляем...';

    // Авто-email если не указан
    var billingEmail = email;
    if (!billingEmail) {
      billingEmail = phone.replace(/\D/g, '') + '@order.local';
    }

    var parts = name.split(' ');
    var firstName = parts[0] || name;
    var lastName  = parts.slice(1).join(' ') || '.';

    var data = new URLSearchParams({
      billing_first_name:  firstName,
      billing_last_name:   lastName,
      billing_phone:       phone,
      billing_email:       billingEmail,
      billing_country:     'RU',
      payment_method:      'minoksidil_no_payment',
      'woocommerce-process-checkout-nonce': minoksidilCart.checkoutNonce,
      _wp_http_referer:    window.location.pathname,
      order_comments:      comment,
      contact_method:      contact,
      contact_value:       contactVal,
      delivery_type:       delivery,
      pvz_service:         pvzService,
      pvz_code:            pvzCode,
      pvz_address:         address,
      terms:               '1', // WC требует согласие с условиями, если настроена T&C страница
    });

    // Поля доставки — адрес из поля корзины всегда уходит в заказ
    data.append('ship_to_different_address', '1');
    data.append('shipping_first_name', firstName);
    data.append('shipping_last_name',  lastName);
    data.append('shipping_country',    'RU');
    data.append('shipping_address_1',  address);

    fetch(minoksidilCart.checkoutUrl, {
      method:  'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body:    data.toString(),
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.result === 'success') {
          window.location.href = minoksidilCart.thanksUrl;
        } else {
          errBox.innerHTML = res.messages || 'Ошибка оформления. Попробуйте ещё раз.';
          errBox.style.display = 'block';
          errBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
          btn.disabled = false;
          btn.textContent = 'Оформить заказ';
        }
      })
      .catch(function () {
        errBox.innerHTML = 'Ошибка сети. Проверьте подключение и попробуйте снова.';
        errBox.style.display = 'block';
        btn.disabled = false;
        btn.textContent = 'Оформить заказ';
      });
  });

  // ===== Маска телефона =====
  document.querySelector('#co-phone')?.addEventListener('input', function () {
    var v = this.value.replace(/\D/g, '');
    if (v.startsWith('8')) v = '7' + v.slice(1);
    if (v.startsWith('7')) {
      v = '+7 (' + v.slice(1, 4) + ') ' + v.slice(4, 7) + '-' + v.slice(7, 9) + '-' + v.slice(9, 11);
    }
    this.value = v.slice(0, 18);
  });

})();
</script>

<?php get_footer(); ?>

/* Minoksidil — delivery-maps.js
 * Запускается только на странице оформления заказа (form-checkout.php).
 * Страница корзины (cart.php) содержит собственный inline JS со всей логикой ПВЗ.
 */
(function () {
  'use strict';

  // Запускаемся только если есть форма checkout WooCommerce (не корзина)
  if (!document.querySelector('.woocommerce-checkout')) return;
  if (!window.minoksidilConfig) return;

  var ymapsKey = window.minoksidilConfig.ymapsApiKey || '';

  // ===== Delivery type toggle (для form-checkout.php) =====
  var radios        = document.querySelectorAll('input[name="delivery_type"]');
  var courierBlock  = document.querySelector('#courier-address-block');
  var pvzBlock      = document.querySelector('#pvz-block');

  function onDeliveryChange() {
    var val = (document.querySelector('input[name="delivery_type"]:checked') || {}).value;
    if (!courierBlock || !pvzBlock) return;
    if (val === 'pvz') {
      courierBlock.style.display = 'none';
      pvzBlock.style.display     = '';
    } else {
      courierBlock.style.display = '';
      pvzBlock.style.display     = 'none';
    }
  }

  radios.forEach(function (r) { r.addEventListener('change', onDeliveryChange); });
  onDeliveryChange();

  // ===== Yandex Maps для form-checkout.php =====
  var yandexLoaded = false;

  function initYandexMap() {
    if (yandexLoaded || !ymapsKey) return;
    yandexLoaded = true;
    var s    = document.createElement('script');
    s.src    = 'https://api-maps.yandex.ru/2.1/?apikey=' + encodeURIComponent(ymapsKey) + '&lang=ru_RU';
    s.onload = function () {
      ymaps.ready(function () {
        var map = new ymaps.Map('yandex-map', {
          center: [55.751574, 37.573856], zoom: 11, controls: ['zoomControl', 'searchControl'],
        });
        map.events.add('click', function (e) {
          ymaps.geocode(e.get('coords')).then(function (res) {
            var addr = res.geoObjects.get(0) ? res.geoObjects.get(0).getAddressLine() : e.get('coords').join(', ');
            map.geoObjects.removeAll();
            map.geoObjects.add(new ymaps.Placemark(e.get('coords'), { hintContent: addr }, { preset: 'islands#blueDotIcon' }));
            var addrInput = document.querySelector('#pvz-address-input');
            var addrShow  = document.querySelector('#pvz-selected-address');
            var pvzBox    = document.querySelector('#pvz-selected');
            if (addrInput) addrInput.value = addr;
            if (addrShow)  addrShow.textContent = addr;
            if (pvzBox)    pvzBox.removeAttribute('hidden');
          });
        });
      });
    };
    document.head.appendChild(s);
  }

  // Активируем Yandex при переключении на вкладку
  document.querySelectorAll('.pvz-tab').forEach(function (tab) {
    tab.addEventListener('click', function () {
      if (this.dataset.pvzTab === 'yandex') initYandexMap();
    });
  });

  // Ozon manual (form-checkout.php version)
  var ozonConfirm = document.querySelector('#pvz-ozon-confirm');
  if (ozonConfirm) {
    ozonConfirm.addEventListener('click', function () {
      var val       = (document.querySelector('#ozon-address-manual') || {}).value;
      var addrInput = document.querySelector('#pvz-address-input');
      var addrShow  = document.querySelector('#pvz-selected-address');
      var pvzBox    = document.querySelector('#pvz-selected');
      if (val && addrInput) {
        addrInput.value = val.trim();
        if (addrShow) addrShow.textContent = val.trim();
        if (pvzBox)   pvzBox.removeAttribute('hidden');
        document.querySelector('#pvz-service-input').value = 'ozon';
      }
    });
  }

}());

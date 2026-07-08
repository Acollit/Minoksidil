<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;

/**
 * Админ-страница управления базой ПВЗ.
 * Автоимпорт СДЭК через API, ручной импорт CSV/JSON, демо-точки, очистка.
 * Меню: Minoksidil → Пункты выдачи (ПВЗ).
 */

add_action('admin_menu', function (): void {
    add_submenu_page(
        'minoksidil-settings',
        'Пункты выдачи (ПВЗ)',
        'Пункты выдачи (ПВЗ)',
        'manage_options',
        'minoksidil-pvz',
        'minoksidil_pvz_admin_page'
    );
}, 20);

/**
 * Загрузить все ПВЗ СДЭК через публичный API v1 (без авторизации).
 * Эндпоинт: https://integration.cdek.ru/pvzlist/v1/json
 *
 * @return array|WP_Error Массив строк для minoksidil_pvz_import() или WP_Error.
 */
function minoksidil_cdek_fetch_pvz() {
    $resp = wp_remote_get('https://integration.cdek.ru/pvzlist/v1/json?type=PVZ', [
        'timeout'   => 90,
        'sslverify' => false,
    ]);

    if (is_wp_error($resp)) return $resp;

    $http_code = (int)wp_remote_retrieve_response_code($resp);
    if ($http_code !== 200) {
        return new WP_Error('http_error', "СДЭК API вернул HTTP {$http_code}.");
    }

    $body = wp_remote_retrieve_body($resp);
    $data = json_decode($body, true);

    // v1: ответ завёрнут в {"pvz": [...]}
    if (isset($data['pvz']) && is_array($data['pvz'])) {
        $list = $data['pvz'];
    } elseif (is_array($data)) {
        $list = $data;
    } else {
        return new WP_Error('bad_response', 'Неверный ответ от API СДЭК (ожидался JSON).');
    }

    // Привести к формату нашей таблицы wp_minoksidil_pvz
    // coordY = широта (lat), coordX = долгота (lng)
    $rows = [];
    foreach ($list as $p) {
        if (!is_array($p)) continue;
        $lat = (float)($p['coordY'] ?? 0);
        $lng = (float)($p['coordX'] ?? 0);
        if (!$lat || !$lng) continue;

        $rows[] = [
            'service'   => 'cdek',
            'code'      => (string)($p['code']        ?? ''),
            'name'      => (string)($p['name']        ?? ''),
            'address'   => (string)($p['fullAddress'] ?? ($p['address'] ?? '')),
            'city'      => (string)($p['city']        ?? ''),
            'lat'       => $lat,
            'lng'       => $lng,
            'work_time' => (string)($p['workTime']    ?? ''),
            'phone'     => (string)($p['phone']       ?? ''),
        ];
    }

    return $rows;
}

/**
 * Загрузить ПВЗ из OpenStreetMap через Overpass API (без авторизации).
 * Используется для Ozon и Яндекс Маркет — у них нет публичного API.
 *
 * @param string $query   Тело запроса Overpass QL (без [out:json] — добавляется здесь).
 * @param string $service Служба: 'ozon' или 'yandex'.
 * @return array|WP_Error
 */
function minoksidil_overpass_fetch_pvz(string $query, string $service) {
    $full = '[out:json][timeout:90];' . $query . ';out center;';

    $resp = wp_remote_post('https://overpass-api.de/api/interpreter', [
        'body'      => ['data' => $full],
        'timeout'   => 100,
        'sslverify' => false,
    ]);

    if (is_wp_error($resp)) return $resp;

    $http_code = (int)wp_remote_retrieve_response_code($resp);
    if ($http_code !== 200) {
        return new WP_Error('http_error', "Overpass API вернул HTTP {$http_code}.");
    }

    $data = json_decode(wp_remote_retrieve_body($resp), true);
    if (!isset($data['elements']) || !is_array($data['elements'])) {
        return new WP_Error('bad_response', 'Неверный ответ от Overpass API.');
    }

    $rows = [];
    foreach ($data['elements'] as $el) {
        if (!is_array($el)) continue;

        // node — координаты прямо на элементе; way/relation — в center (out center)
        $lat = (float)($el['lat'] ?? ($el['center']['lat'] ?? 0));
        $lng = (float)($el['lon'] ?? ($el['center']['lon'] ?? 0));
        if (!$lat || !$lng) continue;

        $tags   = $el['tags'] ?? [];
        $city   = $tags['addr:city']   ?? '';
        $street = trim(($tags['addr:street'] ?? '') . ' ' . ($tags['addr:housenumber'] ?? ''));
        $addr   = $tags['addr:full']   ?? ($city && $street ? "{$city}, {$street}" : ($city ?: $street));

        $rows[] = [
            'service'   => $service,
            'code'      => 'osm-' . ($el['id'] ?? uniqid()),
            'name'      => $tags['name']  ?? ($tags['brand'] ?? strtoupper($service)),
            'address'   => $addr,
            'city'      => $city,
            'lat'       => $lat,
            'lng'       => $lng,
            'work_time' => $tags['opening_hours']  ?? '',
            'phone'     => $tags['phone'] ?? ($tags['contact:phone'] ?? ''),
        ];
    }

    return $rows;
}

function minoksidil_pvz_admin_page(): void {
    if (!current_user_can('manage_options')) return;

    $notice      = '';
    $notice_type = 'success';

    if (!empty($_POST['minoksidil_pvz_action']) && check_admin_referer('minoksidil_pvz', 'minoksidil_pvz_nonce')) {
        $action = sanitize_text_field(wp_unslash($_POST['minoksidil_pvz_action']));

        if ($action === 'clear') {
            global $wpdb;
            $wpdb->query('TRUNCATE TABLE ' . minoksidil_pvz_table());
            $notice = 'База пунктов выдачи очищена.';

        } elseif ($action === 'dedupe') {
            @set_time_limit(120);
            $removed = minoksidil_pvz_dedupe();
            $notice  = $removed > 0
                ? "Удалено дублей: {$removed}."
                : 'Дубли не найдены — каждая точка в базе в единственном экземпляре.';

        } elseif ($action === 'demo') {
            $res    = minoksidil_pvz_import(minoksidil_pvz_demo_rows(), 'append');
            $notice = "Демо-точки добавлены: {$res['inserted']}. Откройте карту в корзине, чтобы проверить.";

        } elseif ($action === 'cdek_import') {
            @set_time_limit(120);

            $rows = minoksidil_cdek_fetch_pvz();

            if (is_wp_error($rows)) {
                $notice      = 'Ошибка импорта СДЭК: ' . $rows->get_error_message();
                $notice_type = 'error';
            } elseif (!$rows) {
                $notice      = 'СДЭК API не вернул ни одной точки — попробуйте позже.';
                $notice_type = 'warning';
            } else {
                $res    = minoksidil_pvz_import($rows, 'replace_service', 'cdek');
                $notice = "СДЭК: импортировано {$res['inserted']} ПВЗ, пропущено {$res['skipped']}.";
            }

        } elseif ($action === 'ozon_import') {
            @set_time_limit(120);
            $query = '(node["brand"~"OZON|Ozon|Озон"];way["brand"~"OZON|Ozon|Озон"];)';
            $rows  = minoksidil_overpass_fetch_pvz($query, 'ozon');
            if (is_wp_error($rows)) {
                $notice      = 'Ошибка импорта Ozon (OSM): ' . $rows->get_error_message();
                $notice_type = 'error';
            } elseif (!$rows) {
                $notice      = 'OpenStreetMap не вернул точек Ozon — возможно, они ещё не нанесены на карту.';
                $notice_type = 'warning';
            } else {
                $res    = minoksidil_pvz_import($rows, 'replace_service', 'ozon');
                $notice = "Ozon (OSM): импортировано {$res['inserted']} ПВЗ, пропущено {$res['skipped']}. Данные из OpenStreetMap — покрытие может быть неполным.";
            }

        } elseif ($action === 'yandex_import') {
            @set_time_limit(120);
            $query = '(node["brand"~"Яндекс Маркет|Yandex Market"];way["brand"~"Яндекс Маркет|Yandex Market"];)';
            $rows  = minoksidil_overpass_fetch_pvz($query, 'yandex');
            if (is_wp_error($rows)) {
                $notice      = 'Ошибка импорта Яндекс Маркет (OSM): ' . $rows->get_error_message();
                $notice_type = 'error';
            } elseif (!$rows) {
                $notice      = 'OpenStreetMap не вернул точек Яндекс Маркет — возможно, они ещё не нанесены на карту.';
                $notice_type = 'warning';
            } else {
                $res    = minoksidil_pvz_import($rows, 'replace_service', 'yandex');
                $notice = "Яндекс Маркет (OSM): импортировано {$res['inserted']} ПВЗ, пропущено {$res['skipped']}. Данные из OpenStreetMap — покрытие может быть неполным.";
            }

        } elseif ($action === 'import') {
            $mode = in_array(($_POST['pvz_mode'] ?? ''), ['append', 'replace_all'], true)
                ? sanitize_text_field(wp_unslash($_POST['pvz_mode']))
                : 'append';

            $raw = '';
            if (!empty($_FILES['pvz_file']['tmp_name']) && is_uploaded_file($_FILES['pvz_file']['tmp_name'])) {
                $raw = (string)file_get_contents($_FILES['pvz_file']['tmp_name']);
            } elseif (!empty($_POST['pvz_data'])) {
                // Намеренно НЕ sanitize — это сырой CSV/JSON, разбираем и чистим по полям ниже
                $raw = (string)wp_unslash($_POST['pvz_data']);
            }
            $raw = trim($raw);

            if ($raw === '') {
                $notice      = 'Нет данных для импорта: вставьте JSON/CSV или выберите файл.';
                $notice_type = 'error';
            } else {
                $rows = [];
                if ($raw[0] === '[' || $raw[0] === '{') {
                    $json = json_decode($raw, true);
                    if (is_array($json)) {
                        $rows = isset($json[0]) && is_array($json[0]) ? $json : [$json];
                    }
                } else {
                    $rows = minoksidil_pvz_parse_csv($raw);
                }

                if (!$rows) {
                    $notice      = 'Не удалось разобрать данные. Проверьте формат (см. ниже).';
                    $notice_type = 'error';
                } else {
                    $res    = minoksidil_pvz_import($rows, $mode);
                    $notice = "Импорт завершён. Добавлено: {$res['inserted']}, пропущено: {$res['skipped']}.";
                    if ($res['skipped'] > 0) {
                        $notice .= ' Пропущены строки без корректной службы (cdek/yandex/ozon) или без координат.';
                    }
                }
            }
        }
    }

    $counts = minoksidil_pvz_counts();

    $json_example = "[\n" .
        '  {"service":"cdek","code":"MSK101","name":"СДЭК на Тверской","address":"Москва, ул. Тверская, 1","city":"Москва","lat":55.7601,"lng":37.6094,"work_time":"Пн-Пт 10-20","phone":"+7..."},' . "\n" .
        '  {"service":"ozon","code":"OZ55","name":"Ozon","address":"Москва, Сретенка, 27","city":"Москва","lat":55.7705,"lng":37.6330}' . "\n" .
        ']';

    $csv_example = "service,code,name,address,city,lat,lng,work_time,phone\n" .
        "cdek,MSK101,СДЭК на Тверской,\"Москва, ул. Тверская, 1\",Москва,55.7601,37.6094,Пн-Пт 10-20,\n" .
        "yandex,YA22,Яндекс Маркет,\"Москва, Зацепский Вал, 5\",Москва,55.7308,37.6390,,";
    ?>
    <div class="wrap">
      <h1>Пункты выдачи (ПВЗ)</h1>

      <?php if ($notice): ?>
        <div class="notice notice-<?php echo esc_attr($notice_type); ?> is-dismissible">
          <p><?php echo wp_kses($notice, ['em' => []]); ?></p>
        </div>
      <?php endif; ?>

      <p style="font-size:14px;max-width:820px;">
        Карта выбора ПВЗ в корзине берёт координаты из этой базы.
        Импортируйте пункты выдачи СДЭК, Яндекс Маркет и Ozon — они появятся на карте.
      </p>

      <h2 class="title">Сейчас в базе</h2>
      <table class="widefat striped" style="max-width:520px;">
        <tbody>
          <tr>
            <td><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#1AB248;margin-right:6px;vertical-align:middle;"></span>СДЭК</td>
            <td><strong><?php echo (int)$counts['cdek']; ?></strong></td>
          </tr>
          <tr>
            <td><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#FFCC00;margin-right:6px;vertical-align:middle;"></span>Яндекс Маркет</td>
            <td><strong><?php echo (int)$counts['yandex']; ?></strong></td>
          </tr>
          <tr>
            <td><span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:#005BFF;margin-right:6px;vertical-align:middle;"></span>Ozon</td>
            <td><strong><?php echo (int)$counts['ozon']; ?></strong></td>
          </tr>
          <tr><td><strong>Всего</strong></td><td><strong><?php echo (int)$counts['total']; ?></strong></td></tr>
        </tbody>
      </table>

      <!-- ===== Автоимпорт СДЭК ===== -->
      <h2 class="title" style="margin-top:32px;">Автоимпорт СДЭК</h2>

      <p style="max-width:820px;font-size:14px;">
        Загружает актуальный список всех ПВЗ СДЭК через публичный API и сохраняет в базу.
        Заменяет только точки СДЭК — Яндекс Маркет и Ozon остаются без изменений.
        Занимает 10–30 сек.
      </p>

      <form method="post">
        <?php wp_nonce_field('minoksidil_pvz', 'minoksidil_pvz_nonce'); ?>
        <input type="hidden" name="minoksidil_pvz_action" value="cdek_import">
        <?php submit_button('Импортировать ПВЗ СДЭК', 'primary', 'submit', false); ?>
      </form>

      <!-- ===== Автоимпорт Ozon и Яндекс Маркет (OpenStreetMap) ===== -->
      <h2 class="title" style="margin-top:32px;">Автоимпорт Ozon и Яндекс Маркет (OpenStreetMap)</h2>

      <p style="max-width:820px;font-size:14px;color:#7a5c00;background:#fff8e1;border-left:4px solid #f0c000;padding:8px 12px;margin-bottom:14px;">
        Ozon и Яндекс Маркет не предоставляют открытых API. Данные берутся из <strong>OpenStreetMap</strong>:
        сообщество размечает ПВЗ тегами <code>brand=OZON</code> / <code>brand=Яндекс Маркет</code>.
        Крупные города покрыты хорошо, небольшие — частично. Каждая кнопка заменяет только свою службу.
        Занимает 15–60 сек.
      </p>

      <div style="display:flex;gap:14px;flex-wrap:wrap;">
        <form method="post">
          <?php wp_nonce_field('minoksidil_pvz', 'minoksidil_pvz_nonce'); ?>
          <input type="hidden" name="minoksidil_pvz_action" value="ozon_import">
          <?php submit_button('Импортировать ПВЗ Ozon', 'primary', 'submit', false,
              ['style' => 'background:#005BFF;border-color:#0047cc;color:#fff;']); ?>
        </form>
        <form method="post">
          <?php wp_nonce_field('minoksidil_pvz', 'minoksidil_pvz_nonce'); ?>
          <input type="hidden" name="minoksidil_pvz_action" value="yandex_import">
          <?php submit_button('Импортировать ПВЗ Яндекс Маркет', 'primary', 'submit', false,
              ['style' => 'background:#E6A000;border-color:#b87d00;color:#fff;']); ?>
        </form>
      </div>

      <!-- ===== Ручной импорт CSV/JSON ===== -->
      <h2 class="title" style="margin-top:32px;">Ручной импорт (CSV / JSON)</h2>
      <form method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('minoksidil_pvz', 'minoksidil_pvz_nonce'); ?>
        <input type="hidden" name="minoksidil_pvz_action" value="import">

        <table class="form-table">
          <tr>
            <th><label for="pvz_data">Данные (JSON или CSV)</label></th>
            <td>
              <textarea id="pvz_data" name="pvz_data" rows="10" class="large-text code"
                        placeholder="Вставьте сюда JSON-массив или CSV…"></textarea>
              <p class="description">Можно вставить данные в поле выше <em>или</em> загрузить файл ниже.</p>
            </td>
          </tr>
          <tr>
            <th><label for="pvz_file">Файл (.json / .csv)</label></th>
            <td>
              <input type="file" id="pvz_file" name="pvz_file"
                     accept=".json,.csv,application/json,text/csv,text/plain">
            </td>
          </tr>
          <tr>
            <th>Режим импорта</th>
            <td>
              <label style="margin-right:18px;">
                <input type="radio" name="pvz_mode" value="append" checked>
                Добавить к существующим
              </label>
              <label>
                <input type="radio" name="pvz_mode" value="replace_all">
                Полностью заменить базу
              </label>
            </td>
          </tr>
        </table>

        <?php submit_button('Импортировать', 'primary', 'submit', false); ?>
      </form>

      <!-- ===== Демо и очистка ===== -->
      <h2 class="title" style="margin-top:32px;">Демо и очистка</h2>
      <form method="post" style="display:inline-block;margin-right:10px;">
        <?php wp_nonce_field('minoksidil_pvz', 'minoksidil_pvz_nonce'); ?>
        <input type="hidden" name="minoksidil_pvz_action" value="demo">
        <?php submit_button('Загрузить демо-точки', 'secondary', 'submit', false); ?>
      </form>
      <form method="post" style="display:inline-block;margin-right:10px;">
        <?php wp_nonce_field('minoksidil_pvz', 'minoksidil_pvz_nonce'); ?>
        <input type="hidden" name="minoksidil_pvz_action" value="dedupe">
        <?php submit_button('Удалить дубли', 'secondary', 'submit', false); ?>
      </form>
      <form method="post" style="display:inline-block;"
            onsubmit="return confirm('Удалить все точки ПВЗ из базы?');">
        <?php wp_nonce_field('minoksidil_pvz', 'minoksidil_pvz_nonce'); ?>
        <input type="hidden" name="minoksidil_pvz_action" value="clear">
        <?php submit_button('Очистить базу', 'delete', 'submit', false); ?>
      </form>

      <!-- ===== Формат данных ===== -->
      <h2 class="title" style="margin-top:32px;">Формат данных (ручной импорт)</h2>
      <p>Обязательные поля: <code>service</code> (одно из <code>cdek</code>, <code>yandex</code>, <code>ozon</code>),
         <code>lat</code>, <code>lng</code>. Необязательные:
         <code>code</code>, <code>name</code>, <code>address</code>, <code>city</code>, <code>work_time</code>, <code>phone</code>.</p>

      <p><strong>Пример JSON:</strong></p>
      <pre style="background:#f6f7f7;border:1px solid #dcdcde;padding:12px;overflow:auto;max-width:860px;"><?php echo esc_html($json_example); ?></pre>

      <p><strong>Пример CSV</strong> (первая строка — заголовки, разделитель <code>,</code> или <code>;</code>):</p>
      <pre style="background:#f6f7f7;border:1px solid #dcdcde;padding:12px;overflow:auto;max-width:860px;"><?php echo esc_html($csv_example); ?></pre>

      <p class="description" style="max-width:820px;">
        Для Яндекс Маркет и Ozon координаты доступны в партнёрских кабинетах
        или через <a href="https://overpass-turbo.eu/" target="_blank" rel="noopener">overpass-turbo.eu</a>
        (теги <code>brand=Яндекс Маркет</code> / <code>brand=OZON</code>).
      </p>

    </div>
    <?php
}

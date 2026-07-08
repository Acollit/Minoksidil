<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;

/**
 * Карта ПВЗ на собственной базе координат.
 * Точки пунктов выдачи (СДЭК, Яндекс Маркет, Ozon) хранятся в таблице
 * {prefix}minoksidil_pvz и отдаются на фронт через REST. Внешние API
 * (поиск по организациям, CDEK Client ID/Secret, Яндекс-ключ) не нужны.
 */

const MINOKSIDIL_PVZ_DB_VERSION = '1';

function minoksidil_pvz_table(): string {
    global $wpdb;
    return $wpdb->prefix . 'minoksidil_pvz';
}

// ===== Создание/обновление таблицы =====
function minoksidil_pvz_install_table(): void {
    if (get_option('minoksidil_pvz_db_version') === MINOKSIDIL_PVZ_DB_VERSION) return;

    global $wpdb;
    $table   = minoksidil_pvz_table();
    $collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        service VARCHAR(20) NOT NULL DEFAULT '',
        code VARCHAR(64) NOT NULL DEFAULT '',
        name VARCHAR(255) NOT NULL DEFAULT '',
        address VARCHAR(500) NOT NULL DEFAULT '',
        city VARCHAR(190) NOT NULL DEFAULT '',
        lat DECIMAL(10,7) NOT NULL DEFAULT 0,
        lng DECIMAL(10,7) NOT NULL DEFAULT 0,
        work_time VARCHAR(255) NOT NULL DEFAULT '',
        phone VARCHAR(100) NOT NULL DEFAULT '',
        PRIMARY KEY  (id),
        KEY service (service),
        KEY city (city),
        KEY latlng (lat, lng)
    ) {$collate};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
    update_option('minoksidil_pvz_db_version', MINOKSIDIL_PVZ_DB_VERSION);
}
add_action('after_setup_theme', 'minoksidil_pvz_install_table');

// ===== Импорт точек в базу =====
// $mode: append | replace_all | replace_service
function minoksidil_pvz_import(array $rows, string $mode = 'append', string $only_service = ''): array {
    global $wpdb;
    $table = minoksidil_pvz_table();

    if ($mode === 'replace_all') {
        $wpdb->query("TRUNCATE TABLE {$table}");
    } elseif ($mode === 'replace_service' && in_array($only_service, ['cdek', 'yandex', 'ozon'], true)) {
        $wpdb->delete($table, ['service' => $only_service], ['%s']);
    }

    // Ключи уже существующих точек — чтобы повторный импорт не плодил дубли.
    // Точка с кодом: service|code; без кода: service|lat|lng.
    $existing = [];
    foreach ($wpdb->get_results("SELECT service, code, lat, lng FROM {$table}") as $r) {
        $existing[minoksidil_pvz_row_key($r->service, $r->code, (float)$r->lat, (float)$r->lng)] = true;
    }

    $inserted = 0;
    $skipped  = 0;

    foreach ($rows as $row) {
        if (!is_array($row)) { $skipped++; continue; }

        $service = sanitize_text_field((string)($row['service'] ?? ''));
        if (!in_array($service, ['cdek', 'yandex', 'ozon'], true)) { $skipped++; continue; }

        $lat = (float)str_replace(',', '.', (string)($row['lat'] ?? ''));
        $lng = (float)str_replace(',', '.', (string)($row['lng'] ?? ''));
        if (!$lat || !$lng) { $skipped++; continue; }

        $key = minoksidil_pvz_row_key($service, (string)($row['code'] ?? ''), $lat, $lng);
        if (isset($existing[$key])) { $skipped++; continue; }
        $existing[$key] = true;

        $wpdb->insert($table, [
            'service'   => $service,
            'code'      => sanitize_text_field((string)($row['code'] ?? '')),
            'name'      => sanitize_text_field((string)($row['name'] ?? '')),
            'address'   => sanitize_text_field((string)($row['address'] ?? '')),
            'city'      => sanitize_text_field((string)($row['city'] ?? '')),
            'lat'       => $lat,
            'lng'       => $lng,
            'work_time' => sanitize_text_field((string)($row['work_time'] ?? '')),
            'phone'     => sanitize_text_field((string)($row['phone'] ?? '')),
        ], ['%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%s']);

        $inserted++;
    }

    return ['inserted' => $inserted, 'skipped' => $skipped];
}

// Ключ уникальности точки: по коду, а без кода — по координатам (~1 м)
function minoksidil_pvz_row_key(string $service, string $code, float $lat, float $lng): string {
    $code = trim($code);
    if ($code !== '') {
        return $service . '|' . $code;
    }
    return $service . '|' . number_format($lat, 5, '.', '') . '|' . number_format($lng, 5, '.', '');
}

/**
 * Удаляет дубли из базы: одинаковые (service, code), а для точек без кода —
 * одинаковые (service, lat, lng). Остаётся запись с меньшим id.
 * Возвращает число удалённых строк.
 */
function minoksidil_pvz_dedupe(): int {
    global $wpdb;
    $table = minoksidil_pvz_table();

    $by_code = (int) $wpdb->query(
        "DELETE t1 FROM {$table} t1
         INNER JOIN {$table} t2
            ON t1.service = t2.service
           AND t1.code = t2.code
           AND t1.code <> ''
           AND t1.id > t2.id"
    );

    $by_coords = (int) $wpdb->query(
        "DELETE t1 FROM {$table} t1
         INNER JOIN {$table} t2
            ON t1.service = t2.service
           AND t1.lat = t2.lat
           AND t1.lng = t2.lng
           AND t1.code = ''
           AND t1.id > t2.id"
    );

    return $by_code + $by_coords;
}

// CSV → массив строк (первая строка — заголовки)
function minoksidil_pvz_parse_csv(string $csv): array {
    $rows  = [];
    $lines = preg_split('/\r\n|\r|\n/', trim($csv));
    if (!$lines || count($lines) < 2) return $rows;

    $delimiter = (substr_count($lines[0], ';') > substr_count($lines[0], ',')) ? ';' : ',';
    $header    = array_map('trim', str_getcsv(array_shift($lines), $delimiter));

    foreach ($lines as $line) {
        if (trim($line) === '') continue;
        $cols = str_getcsv($line, $delimiter);
        $row  = [];
        foreach ($header as $i => $key) {
            $row[$key] = $cols[$i] ?? '';
        }
        $rows[] = $row;
    }
    return $rows;
}

// Кол-во точек по службам
function minoksidil_pvz_counts(): array {
    global $wpdb;
    $table = minoksidil_pvz_table();
    $out   = ['cdek' => 0, 'yandex' => 0, 'ozon' => 0, 'total' => 0];

    // Таблицы может ещё не быть (до install) — гасим ошибку
    $rows = $wpdb->get_results("SELECT service, COUNT(*) AS c FROM {$table} GROUP BY service", ARRAY_A);
    foreach ($rows ?: [] as $r) {
        if (isset($out[$r['service']])) $out[$r['service']] = (int)$r['c'];
    }
    $out['total'] = $out['cdek'] + $out['yandex'] + $out['ozon'];
    return $out;
}

// Демо-точки (Москва и СПб) — чтобы карта работала до импорта реальной базы
function minoksidil_pvz_demo_rows(): array {
    return [
        ['service' => 'cdek',   'code' => 'demo-cdek-msk-1', 'name' => 'СДЭК (пример)',          'address' => 'Москва, ул. Тверская, 1',            'city' => 'Москва',          'lat' => 55.7601, 'lng' => 37.6094, 'work_time' => 'Пн-Пт 10:00–20:00, Сб 10:00–18:00', 'phone' => ''],
        ['service' => 'cdek',   'code' => 'demo-cdek-msk-2', 'name' => 'СДЭК (пример)',          'address' => 'Москва, ул. Большая Полянка, 28',    'city' => 'Москва',          'lat' => 55.7372, 'lng' => 37.6190, 'work_time' => 'Ежедневно 09:00–21:00',              'phone' => ''],
        ['service' => 'yandex', 'code' => 'demo-ya-msk-1',   'name' => 'Яндекс Маркет (пример)', 'address' => 'Москва, Охотный Ряд, 2',             'city' => 'Москва',          'lat' => 55.7560, 'lng' => 37.6175, 'work_time' => 'Ежедневно 10:00–22:00',              'phone' => ''],
        ['service' => 'yandex', 'code' => 'demo-ya-msk-2',   'name' => 'Яндекс Маркет (пример)', 'address' => 'Москва, ул. Зацепский Вал, 5',       'city' => 'Москва',          'lat' => 55.7308, 'lng' => 37.6390, 'work_time' => 'Ежедневно 10:00–22:00',              'phone' => ''],
        ['service' => 'ozon',   'code' => 'demo-ozon-msk-1', 'name' => 'Ozon (пример)',          'address' => 'Москва, Кутузовский пр-т, 22',       'city' => 'Москва',          'lat' => 55.7445, 'lng' => 37.5380, 'work_time' => 'Ежедневно 09:00–21:00',              'phone' => ''],
        ['service' => 'ozon',   'code' => 'demo-ozon-msk-2', 'name' => 'Ozon (пример)',          'address' => 'Москва, ул. Сретенка, 27',           'city' => 'Москва',          'lat' => 55.7705, 'lng' => 37.6330, 'work_time' => 'Ежедневно 09:00–21:00',              'phone' => ''],
        ['service' => 'cdek',   'code' => 'demo-cdek-spb-1', 'name' => 'СДЭК (пример)',          'address' => 'Санкт-Петербург, Невский пр-т, 22',  'city' => 'Санкт-Петербург', 'lat' => 59.9355, 'lng' => 30.3258, 'work_time' => 'Пн-Сб 10:00–20:00',                  'phone' => ''],
        ['service' => 'yandex', 'code' => 'demo-ya-spb-1',   'name' => 'Яндекс Маркет (пример)', 'address' => 'Санкт-Петербург, ул. Восстания, 18', 'city' => 'Санкт-Петербург', 'lat' => 59.9320, 'lng' => 30.3600, 'work_time' => 'Ежедневно 10:00–22:00',              'phone' => ''],
        ['service' => 'ozon',   'code' => 'demo-ozon-spb-1', 'name' => 'Ozon (пример)',          'address' => 'Санкт-Петербург, Большой пр-т П.С., 48', 'city' => 'Санкт-Петербург', 'lat' => 59.9610, 'lng' => 30.2960, 'work_time' => 'Ежедневно 09:00–21:00',          'phone' => ''],
    ];
}

// ===== REST: точки ПВЗ (bbox / город) и список городов =====
add_action('rest_api_init', function (): void {
    register_rest_route('minoksidil/v1', '/pvz', [
        'methods'             => 'GET',
        'callback'            => 'minoksidil_pvz_rest_points',
        'permission_callback' => '__return_true',
    ]);
    register_rest_route('minoksidil/v1', '/pvz/cities', [
        'methods'             => 'GET',
        'callback'            => 'minoksidil_pvz_rest_cities',
        'permission_callback' => '__return_true',
    ]);
});

function minoksidil_pvz_rest_points(WP_REST_Request $req): WP_REST_Response {
    global $wpdb;
    $table = minoksidil_pvz_table();

    $where = ['1=1'];
    $args  = [];

    $service = sanitize_text_field((string)$req->get_param('service'));
    if (in_array($service, ['cdek', 'yandex', 'ozon'], true)) {
        $where[] = 'service = %s';
        $args[]  = $service;
    }

    $bbox = (string)$req->get_param('bbox'); // minLng,minLat,maxLng,maxLat
    if ($bbox !== '') {
        $p = array_map('floatval', explode(',', $bbox));
        if (count($p) === 4) {
            $where[] = 'lng BETWEEN %f AND %f';
            $args[]  = min($p[0], $p[2]);
            $args[]  = max($p[0], $p[2]);
            $where[] = 'lat BETWEEN %f AND %f';
            $args[]  = min($p[1], $p[3]);
            $args[]  = max($p[1], $p[3]);
        }
    }

    $city = sanitize_text_field((string)$req->get_param('city'));
    if ($city !== '') {
        $where[] = 'city LIKE %s';
        $args[]  = $wpdb->esc_like($city) . '%';
    }

    $limit = (int)$req->get_param('limit');
    $limit = $limit > 0 ? min(3000, $limit) : 1000;

    $sql = "SELECT service, code, name, address, city, lat, lng, work_time, phone
            FROM {$table}
            WHERE " . implode(' AND ', $where) . "
            LIMIT {$limit}";

    $rows = $args ? $wpdb->get_results($wpdb->prepare($sql, $args), ARRAY_A)
                  : $wpdb->get_results($sql, ARRAY_A);

    $points = array_map(function (array $r): array {
        $r['lat'] = (float)$r['lat'];
        $r['lng'] = (float)$r['lng'];
        return $r;
    }, $rows ?: []);

    return new WP_REST_Response($points, 200);
}

function minoksidil_pvz_rest_cities(WP_REST_Request $req): WP_REST_Response {
    global $wpdb;
    $table = minoksidil_pvz_table();

    $q = sanitize_text_field((string)$req->get_param('q'));
    if (mb_strlen($q) < 2) return new WP_REST_Response([], 200);

    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT city, COUNT(*) AS cnt
         FROM {$table}
         WHERE city LIKE %s
         GROUP BY city
         ORDER BY cnt DESC
         LIMIT 10",
        $wpdb->esc_like($q) . '%'
    ), ARRAY_A);

    $out = array_map(function (array $r): array {
        return ['city' => $r['city'], 'cnt' => (int)$r['cnt']];
    }, $rows ?: []);

    return new WP_REST_Response($out, 200);
}

// ===== AJAX: сохранить выбранный ПВЗ в сессию WC =====
add_action('wp_ajax_minoksidil_save_pvz', 'minoksidil_ajax_save_pvz');
add_action('wp_ajax_nopriv_minoksidil_save_pvz', 'minoksidil_ajax_save_pvz');

function minoksidil_ajax_save_pvz(): void {
    check_ajax_referer('minoksidil_nonce', 'nonce');

    $service = sanitize_text_field(wp_unslash($_POST['service'] ?? ''));
    $code    = sanitize_text_field(wp_unslash($_POST['code'] ?? ''));
    $address = sanitize_text_field(wp_unslash($_POST['address'] ?? ''));

    if (!in_array($service, ['cdek', 'yandex', 'ozon'], true)) {
        wp_send_json_error(['message' => 'Неверная служба доставки.']);
    }

    WC()->session->set('pvz_service', $service);
    WC()->session->set('pvz_code', $code);
    WC()->session->set('pvz_address', $address);

    wp_send_json_success(['address' => $address]);
}

// ===== Префилл полей ПВЗ из сессии при загрузке оформления =====
add_filter('woocommerce_checkout_get_value', function ($value, $input) {
    if ($input === 'pvz_address' && WC()->session) {
        return WC()->session->get('pvz_address', '');
    }
    if ($input === 'pvz_service' && WC()->session) {
        return WC()->session->get('pvz_service', '');
    }
    if ($input === 'pvz_code' && WC()->session) {
        return WC()->session->get('pvz_code', '');
    }
    return $value;
}, 10, 2);

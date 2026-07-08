<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;

// ===== CPT: Результаты (До/После) =====
add_action('init', function () {
    register_post_type('result', [
        'labels' => [
            'name'               => 'Результаты До/После',
            'singular_name'      => 'Результат',
            'add_new'            => 'Добавить результат',
            'add_new_item'       => 'Добавить новый результат',
            'edit_item'          => 'Редактировать результат',
            'new_item'           => 'Новый результат',
            'view_item'          => 'Просмотр результата',
            'search_items'       => 'Найти результаты',
            'not_found'          => 'Результаты не найдены',
            'not_found_in_trash' => 'В корзине ничего нет',
            'menu_name'          => 'До/После',
        ],
        'public'             => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'menu_icon'          => 'dashicons-camera',
        'supports'           => ['title', 'thumbnail'],
        'has_archive'        => false,
        'rewrite'            => ['slug' => 'results'],
    ]);
});

// ===== Meta box: характеристики результата =====
add_action('add_meta_boxes', function () {
    add_meta_box(
        'result_specs',
        'Характеристики результата',
        'minoksidil_result_specs_box',
        'result',
        'normal',
        'high'
    );
});

function minoksidil_result_specs_box(WP_Post $post): void {
    wp_nonce_field('result_specs_save', 'result_specs_nonce');
    $fields = [
        '_result_person'    => ['Имя, возраст', 'Андрей, 37 лет'],
        '_result_product'   => ['Используемый товар (для поиска)', 'Миноксидил 5% раствор KIRKLAND'],
        '_result_form'      => ['Форма (раствор/пена/таблетки)', 'Раствор 5%'],
        '_result_frequency' => ['Частота применения', '2 раза в день'],
        '_result_duration'  => ['Срок применения', '6 месяцев'],
        '_result_outcome'   => ['Результат', '80% восстановления'],
        '_result_period'    => ['Период (для фильтра, напр. 6)', '6'],
    ];
    echo '<table class="form-table">';
    foreach ($fields as $key => [$label, $placeholder]) {
        $value = get_post_meta($post->ID, $key, true);
        printf(
            '<tr><th><label for="%s">%s</label></th><td><input type="text" id="%s" name="%s" value="%s" class="regular-text" placeholder="%s"></td></tr>',
            esc_attr($key), esc_html($label),
            esc_attr($key), esc_attr($key),
            esc_attr($value), esc_attr($placeholder)
        );
    }
    echo '</table>';
}

add_action('save_post_result', function (int $post_id): void {
    if (!isset($_POST['result_specs_nonce']) || !wp_verify_nonce($_POST['result_specs_nonce'], 'result_specs_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $keys = ['_result_person', '_result_product', '_result_form', '_result_frequency', '_result_duration', '_result_outcome', '_result_period'];
    foreach ($keys as $key) {
        if (isset($_POST[$key])) {
            update_post_meta($post_id, $key, sanitize_text_field(wp_unslash($_POST[$key])));
        }
    }
});

// ===== Admin columns for results =====
add_filter('manage_result_posts_columns', function (array $cols): array {
    return [
        'cb'             => $cols['cb'],
        'thumbnail_col'  => 'Фото',
        'title'          => 'Имя',
        '_result_form'   => 'Форма',
        '_result_duration' => 'Срок',
        '_result_outcome'  => 'Результат',
        'date'           => 'Дата',
    ];
});

add_action('manage_result_posts_custom_column', function (string $col, int $post_id): void {
    if ($col === 'thumbnail_col') {
        echo get_the_post_thumbnail($post_id, [48, 48]);
    } elseif (str_starts_with($col, '_result_')) {
        echo esc_html((string)get_post_meta($post_id, $col, true));
    }
}, 10, 2);

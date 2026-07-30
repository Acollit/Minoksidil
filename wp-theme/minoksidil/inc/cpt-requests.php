<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;

/**
 * Заявки с сайта (обратный звонок, формы CF7) — сохраняются в админку,
 * чтобы письмо на почту не было единственным способом их увидеть.
 * Меню: Minoksidil → Заявки.
 */

add_action('init', function () {
    register_post_type('callback_request', [
        'labels' => [
            'name'               => 'Заявки',
            'singular_name'      => 'Заявка',
            'add_new'            => 'Добавить заявку',
            'add_new_item'       => 'Новая заявка',
            'edit_item'          => 'Заявка',
            'view_item'          => 'Просмотр заявки',
            'search_items'       => 'Найти заявки',
            'not_found'          => 'Заявок пока нет',
            'not_found_in_trash' => 'В корзине пусто',
            'menu_name'          => 'Заявки',
        ],
        'public'          => false,
        'show_ui'         => true,
        'show_in_menu'    => 'minoksidil-settings',
        'show_in_rest'    => false,
        'supports'        => ['title'],
        'capability_type' => 'post',
        'map_meta_cap'    => true,
        'has_archive'     => false,
        'rewrite'         => false,
    ]);
});

/**
 * Сохраняет заявку в БД (CPT callback_request). Вызывать до wp_mail()/CF7-отправки,
 * чтобы заявка осталась в админке, даже если письмо не дойдёт (сбой SMTP и т.п.).
 */
function minoksidil_save_request_lead(string $name, string $phone, string $email, string $message, string $source): int {
    $post_id = wp_insert_post([
        'post_type'   => 'callback_request',
        'post_title'  => $name !== '' ? $name : 'Без имени',
        'post_status' => 'publish',
    ], true);

    if (is_wp_error($post_id) || !$post_id) {
        return 0;
    }

    update_post_meta($post_id, '_request_phone', $phone);
    update_post_meta($post_id, '_request_email', $email);
    update_post_meta($post_id, '_request_message', $message);
    update_post_meta($post_id, '_request_source', $source);

    return $post_id;
}

// ===== Meta box: данные заявки =====
add_action('add_meta_boxes', function () {
    add_meta_box(
        'callback_request_details',
        'Данные заявки',
        'minoksidil_request_details_box',
        'callback_request',
        'normal',
        'high'
    );
});

function minoksidil_request_details_box(WP_Post $post): void {
    $fields = [
        '_request_phone'   => 'Телефон',
        '_request_email'   => 'Email',
        '_request_source'  => 'Источник',
        '_request_message' => 'Сообщение',
    ];
    echo '<table class="form-table">';
    foreach ($fields as $key => $label) {
        $value = (string) get_post_meta($post->ID, $key, true);
        printf(
            '<tr><th>%s</th><td>%s</td></tr>',
            esc_html($label),
            $key === '_request_message' ? nl2br(esc_html($value)) : esc_html($value)
        );
    }
    echo '</table>';
}

// ===== Admin columns =====
add_filter('manage_callback_request_posts_columns', function (array $cols): array {
    return [
        'cb'              => $cols['cb'],
        'title'           => 'Имя',
        '_request_phone'  => 'Телефон',
        '_request_email'  => 'Email',
        '_request_source' => 'Источник',
        'date'            => 'Дата',
    ];
});

add_action('manage_callback_request_posts_custom_column', function (string $col, int $post_id): void {
    if (str_starts_with($col, '_request_')) {
        echo esc_html((string) get_post_meta($post_id, $col, true));
    }
}, 10, 2);

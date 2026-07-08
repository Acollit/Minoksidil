<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;

// ===== Корзина не требует оплаты — пропускаем всю валидацию payment_method =====
add_filter('woocommerce_cart_needs_payment', '__return_false');

// ===== После создания заказа: добавляем заметку (статус выставит WC сам через payment_complete) =====
add_action('woocommerce_checkout_order_created', function (WC_Order $order): void {
    $order->add_order_note('Заказ оформлен без онлайн-оплаты. Менеджер свяжется с покупателем.');
}, 20);

// ===== Register custom "No Payment" gateway (только для отображения в адмике) =====
add_filter('woocommerce_payment_gateways', function (array $gateways): array {
    $gateways[] = 'Minoksidil_No_Payment_Gateway';
    return $gateways;
});

// ===== Gateway class =====
add_action('plugins_loaded', function () {
    if (!class_exists('WC_Payment_Gateway')) return;

    class Minoksidil_No_Payment_Gateway extends WC_Payment_Gateway {

        public function __construct() {
            $this->id                 = 'minoksidil_no_payment';
            $this->method_title       = 'Заказ без оплаты';
            $this->method_description = 'Оформление заказа без онлайн-оплаты. Счёт выставляется менеджером после подтверждения.';
            $this->has_fields         = false;
            $this->supports           = ['products'];

            $this->init_form_fields();
            $this->init_settings();

            // Принудительно включаем — независимо от настроек в БД
            $this->enabled     = 'yes';
            $this->title       = $this->get_option('title', 'Подтвердить заказ');
            $this->description = $this->get_option('description', 'Менеджер свяжется с вами для уточнения деталей и способа оплаты.');

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        }

        public function is_available(): bool {
            return true;
        }

        public function init_form_fields(): void {
            $this->form_fields = [
                'enabled' => [
                    'title'   => 'Включён',
                    'type'    => 'checkbox',
                    'label'   => 'Включить способ оформления заказа без оплаты',
                    'default' => 'yes',
                ],
                'title' => [
                    'title'       => 'Заголовок',
                    'type'        => 'text',
                    'description' => 'Отображается на странице оформления заказа.',
                    'default'     => 'Подтвердить заказ',
                ],
                'description' => [
                    'title'   => 'Описание',
                    'type'    => 'textarea',
                    'default' => 'Менеджер свяжется с вами для уточнения деталей и способа оплаты.',
                ],
            ];
        }

        public function process_payment($order_id): array {
            $order = wc_get_order($order_id);

            // Set order to "processing" — awaiting manager confirmation
            $order->update_status('processing', 'Заказ оформлен, ожидает подтверждения менеджером.');

            // Reduce stock
            wc_reduce_stock_levels($order_id);

            // Empty cart
            WC()->cart->empty_cart();

            return [
                'result'   => 'success',
                'redirect' => $this->get_return_url($order),
            ];
        }

        // Hide payment section icon
        public function get_icon(): string {
            return '';
        }
    }
});

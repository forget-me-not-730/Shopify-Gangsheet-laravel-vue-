<?php

namespace GangSheetBuilder;

class GangSheetWebhook
{

    private $orderProcessed = false;

    public function __construct()
    {
        if (defined('WC_VERSION')) {
            if (version_compare(WC_VERSION, '7.2.0', '<')) {
                add_action('woocommerce_blocks_checkout_order_processed', [$this, 'handle_checkout_order'], 10, 1);
            } else {
                add_action('woocommerce_store_api_checkout_order_processed', [$this, 'handle_checkout_order'], 10, 1);
            }
        }

        add_action('woocommerce_checkout_order_processed', [$this, 'handle_checkout_order_processed'], 9, 3);

        add_action('woocommerce_payment_complete', [$this, 'handle_payment_complete'], 8, 2);
    }

    public function handle_checkout_order($order)
    {
        if (!$this->orderProcessed) {
            $this->orderProcessed = true;
            $this->post_order($order);
        }
    }

    public function handle_payment_complete($order_id, $transaction_id)
    {
        if (!$this->orderProcessed) {
            $this->orderProcessed = true;
            $order = wc_get_order($order_id);
            $this->post_order($order);
        }
    }

    public function handle_checkout_order_processed($order_id, $posted_data, $order)
    {
        if (!$this->orderProcessed) {
            $this->orderProcessed = true;
            $order = wc_get_order($order_id);
            $this->post_order($order);
        }
    }

    public function post_order($order)
    {
        try {
            $order = get_gang_sheet_order($order);

            if (empty($order['error'])) {

                gang_sheet_api_call('POST', 'order', [
                    'order' => $order
                ]);
            }
        } catch (\Exception $exception) {
            gs_report($exception->getMessage());
        }
    }

}

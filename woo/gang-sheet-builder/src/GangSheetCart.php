<?php

namespace GangSheetBuilder;

class GangSheetCart
{
    public function __construct()
    {
        add_action('wp_ajax_gang_sheet_add_to_cart', [$this, 'add_to_cart_ajax_handler']);
        add_action('wp_ajax_nopriv_gang_sheet_add_to_cart', [$this, 'add_to_cart_ajax_handler']);

        // for ajax submission
        add_filter('woocommerce_get_item_data', [$this, 'get_item_data'], 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'add_order_meta'], 10, 4);

        // for form submission
        add_filter('woocommerce_add_to_cart_validation', [$this, 'add_to_cart_validation'], 10, 4);
        add_filter('woocommerce_add_cart_item_data', [$this, 'add_cart_item_data'], 10, 3);

        add_action('woocommerce_before_calculate_totals', [$this, 'update_cart_item_price_based_on_height'], 10, 1);
    }

    function update_cart_item_price_based_on_height($cart)
    {
        if (did_action('woocommerce_before_calculate_totals') >= 2) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['product_type']) && $cart_item['product_type'] === 6) {
                if (isset($cart_item['custom_price']) && isset($cart_item['gang_sheet_height'])) {
                    $cart_item['data']->set_price($cart_item['custom_price']);
                }
            }
        }
    }

    public function add_to_cart_validation($passed, $product_id, $quantity, $variation_id = null)
    {
        $product = get_gang_sheet_product($product_id);

        if (empty($product['error'])) {
            if (empty($_POST['gs_design_id'])) {
                $passed = false;
                wc_add_notice(__('Design is a required field.', 'webkul'), 'error');
            }
        }

        return $passed;
    }

    function add_cart_item_data($cart_item_data, $product_id, $variation_id)
    {
        if (isset($_POST['gs_design_id'])) {
            $cart_item_data['gs_design_id'] = sanitize_text_field($_POST['gs_design_id']);
        }

        if (isset($_POST['gang_sheet_height'])) {
            $cart_item_data['gang_sheet_height'] = sanitize_text_field($_POST['gang_sheet_height']);
        }

        if (isset($_POST['custom_price'])) {
            $cart_item_data['custom_price'] = (float) $_POST['custom_price'];
        }

        if (isset($_POST['product_type'])) {
            $cart_item_data['product_type'] = (int) $_POST['product_type'];
        }

        return $cart_item_data;
    }

    public function add_to_cart_ajax_handler()
    {
        try {
            $product_id = isset($_POST['product_id']) ? sanitize_text_field($_POST['product_id']) : '';
            $product = get_gang_sheet_product($product_id);

            if (empty($product['error'])) {
                $design_id = isset($_POST['gs_design_id']) ? sanitize_text_field($_POST['gs_design_id']) : '';
                $gang_sheet_height = isset($_POST['gang_sheet_height']) ? sanitize_text_field($_POST['gang_sheet_height']) : '';
                $product_type = isset($_POST['product_type']) ? (int) $_POST['product_type'] : 1;
                $custom_price = isset($_POST['custom_price']) ? (float) $_POST['custom_price'] : 0;

                if (empty($design_id)) {
                    wp_send_json_error('Something went wrong!');
                } else {
                    $quantity = isset($_POST['quantity']) ? wc_stock_amount($_POST['quantity']) : 1;
                    $variation_id = (int) $_POST['variation_id'] ?? 0;
                    $variation_title = $_POST['variation_title'] ?? '';

                    $data = [
                        'gs_design_id' => $design_id,
                        'gang_sheet_height' => $gang_sheet_height,
                        'product_type' => $product_type,
                        'custom_price' => $custom_price
                    ];

                    foreach ($_POST as $key => $value) {
                        if (str_contains($key, 'addon') || str_contains($key, 'attribute')) {
                            $data[$key] = $value;
                        }
                    }

                    $cart_has_key = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, ['attribute_size' => $variation_title], $data);

                    if (!empty($cart_has_key)) {
                        wp_send_json_success(['cart_url' => wc_get_cart_url()]);
                    } else {
                        wp_send_json_error('Something went wrong!');
                    }
                }
            }
        } catch (\Exception $exception) {
            gs_report($exception->getMessage());

            wp_send_json_error('Something went wrong!');
        }
    }

    public function get_item_data($item_data, $cart_item)
    {
        if (!gs_has_item_value($item_data, 'gs_design_id')) {
            $product_id = $cart_item['product_id'];
            $product = get_gang_sheet_product($product_id);


            if (empty($product['error'])) {
                if (empty($cart_item['gs_design_id'])) {
                    $cart_item_key = $cart_item['key'];

                    // Remove the item from the cart
                    WC()->cart->remove_cart_item($cart_item_key);

                    // Redirect to the home page
                    wp_redirect(home_url());

                    exit();
                }

                $item_data[] = [
                    'key' => 'gs_design_id',
                    'value' => wc_clean($cart_item['gs_design_id']),
                ];

                if (isset($cart_item['gang_sheet_height'])) {
                    $item_data[] = [
                        'key' => 'Gang Sheet Height',
                        'value' => wc_clean($cart_item['gang_sheet_height'])
                    ];
                }
            }
        }

        foreach ($cart_item as $key => $value) {
            if (str_contains($key, 'addon_') || str_contains($key, 'attribute_')) {
                if (!gs_has_item_value($item_data, $key) && !empty(wc_clean($value))) {
                    $item_data[] = [
                        'key' => $key,
                        'value' => wc_clean($value),
                    ];
                }
            }
        }

        return $item_data;
    }

    public function add_order_meta($item, $cart_item_key, $values, $order)
    {
        if (isset($values['gs_design_id'])) {
            $item->add_meta_data('gs_design_id', $values['gs_design_id'], true);
        }

        if (isset($values['gang_sheet_height'])) {
            $item->add_meta_data('Gang Sheet Height', $values['gang_sheet_height'], true);
        }
    }
}

<?php

if (!function_exists('get_gang_sheet_product')) {
    function get_gang_sheet_product($product_id)
    {
        try {
            if (isset($product_id)) {
                $product = wc_get_product($product_id);

                if (is_a($product, 'WC_Product')) {

                    if (!$product->is_type('variable')) {
                        return [
                            'error' => true,
                            'message' => 'Product must be a variable product'
                        ];
                    }

                    $isGangSheetProduct = false;

                    $tags = $product->get_tag_ids();
                    if (!empty($tags)) {
                        foreach ($tags as $tag_id) {
                            $tag = get_term($tag_id, 'product_tag');
                            if (strtolower($tag->name) === strtolower(GANG_SHEET_ID)) {
                                $isGangSheetProduct = true;
                            }
                        }
                    }

                    if ($isGangSheetProduct) {
                        $available_variants = $product->get_available_variations();

                        $variants = [];

                        foreach ($available_variants as $variant) {
                            foreach ($variant['attributes'] as $key => $attribute) {
                                if ($key === 'attribute_size') {

                                    $title = get_post_meta($variant['variation_id'], '_title', true);
                                    $price = get_post_meta($variant['variation_id'], '_price', true);
                                    $unit = get_post_meta($variant['variation_id'], '_unit', true);
                                    $maxAllowedFileCount = get_post_meta($variant['variation_id'], '_max_allowed_file_count', true);

                                    $width = get_post_meta($variant['variation_id'], '_g_width', true);
                                    $height = get_post_meta($variant['variation_id'], '_g_height', true);

                                    if (empty($width)) {
                                        $width = $variant['dimensions']['width'];
                                    }

                                    if (empty($height)) {
                                        $height = $variant['dimensions']['height'];
                                    }

                                    if ($width && $height) {
                                        $variants[] = [
                                            'id' => $variant['variation_id'],
                                            'title' => $title,
                                            'width' => $width,
                                            'height' => $height,
                                            'price' => $price,
                                            'unit' => $unit ?? 'in',
                                            'maxAllowedFileCount' => $maxAllowedFileCount ?? -1,
                                        ];
                                    }
                                }
                            }
                        }

                        $artBoardType = get_post_meta($product->get_id(), 'art_board_type', true);

                        return [
                            'id' => $product->get_id(),
                            'title' => $product->get_title(),
                            'artBoardType' => $artBoardType,
                            'variants' => $variants
                        ];
                    }

                    return [
                        'error' => true,
                        'message' => 'Product is not a gang sheet product'
                    ];
                }

                return [
                    'error' => true,
                    'message' => 'Product is not a valid product'
                ];
            }

            return [
                'error' => true,
                'message' => 'Product ID is required'
            ];
        } catch (\Exception $exception) {
            return [
                'error' => true,
                'message' => $exception->getMessage()
            ];
        }
    }
}


if (!function_exists('get_gang_sheet_order')) {
    function get_gang_sheet_order($order)
    {
        try {
            $items = [];

            foreach ($order->get_items() as $item) {

                $product_id = $item->get_product_id();

                $product = get_gang_sheet_product($product_id);
                $variant = null;

                if (empty($product['error'])) {
                    foreach ($product['variants'] as $variant_item) {
                        if ($variant_item['id'] == $item->get_variation_id()) {
                            $variant = $variant_item;
                            break;
                        }
                    }

                    $items[] = [
                        'id' => $item->get_id(),
                        'product' => [
                            'id' => $product_id,
                            'title' => $product['title']
                        ],
                        'variant' => $variant,
                        'item_data' => $item->get_data(),
                    ];
                }

            }

            if (count($items)) {

                $user = $order->get_user();

                $customer_id = $order->get_customer_id();

                $billing_email = $order->get_billing_email();

                // Get the Customer billing phone
                $billing_phone = $order->get_billing_phone();

                // Customer billing information details
                $billing_first_name = $order->get_billing_first_name();
                $billing_last_name = $order->get_billing_last_name();
                $billing_company = $order->get_billing_company();
                $billing_address_1 = $order->get_billing_address_1();
                $billing_address_2 = $order->get_billing_address_2();
                $billing_city = $order->get_billing_city();
                $billing_state = $order->get_billing_state();
                $billing_postcode = $order->get_billing_postcode();
                $billing_country = $order->get_billing_country();

                // Customer shipping information details
                $shipping_first_name = $order->get_shipping_first_name();
                $shipping_last_name = $order->get_shipping_last_name();
                $shipping_company = $order->get_shipping_company();
                $shipping_address_1 = $order->get_shipping_address_1();
                $shipping_address_2 = $order->get_shipping_address_2();
                $shipping_city = $order->get_shipping_city();
                $shipping_state = $order->get_shipping_state();
                $shipping_postcode = $order->get_shipping_postcode();
                $shipping_country = $order->get_shipping_country();

                // shipping method
                $shipping_method = '';
                foreach ($order->get_shipping_methods() as $method) {
                    $shipping_method = $method->get_name();
                    break;
                }

                return [
                    'id' => $order->get_id(),
                    'items' => $items,
                    'shipping_method' => $shipping_method,
                    'customer' => [
                        'id' => $user->ID ?? $customer_id,
                        'email' => $user->user_email ?? $billing_email,
                        'first_name' => $user->first_name ?? $billing_first_name,
                        'last_name' => $user->last_name ?? $billing_last_name,
                        'display_name' => $user->display_name ?? "$billing_first_name $billing_last_name",
                        'phone' => $user ? $user->get_user_meta($user->ID, 'phone', true) : $billing_phone,
                        'billing_address' => [
                            'email' => $billing_email,
                            'phone' => $billing_phone,
                            'first_name' => $billing_first_name,
                            'last_name' => $billing_last_name,
                            'company' => $billing_company,
                            'address_1' => $billing_address_1,
                            'address_2' => $billing_address_2,
                            'city' => $billing_city,
                            'state' => $billing_state,
                            'postcode' => $billing_postcode,
                            'country' => $billing_country
                        ],
                        'shipping_address' => [
                            'email' => $billing_email,
                            'first_name' => $shipping_first_name,
                            'last_name' => $shipping_last_name,
                            'company' => $shipping_company,
                            'address_1' => $shipping_address_1,
                            'address_2' => $shipping_address_2,
                            'city' => $shipping_city,
                            'state' => $shipping_state,
                            'postcode' => $shipping_postcode,
                            'country' => $shipping_country
                        ]
                    ]
                ];
            }

            return [
                'error' => true,
                'message' => 'No gang sheet items found in the order'
            ];
        } catch (\Exception $exception) {
            return [
                'error' => true,
                'message' => $exception->getMessage()
            ];
        }
    }
}

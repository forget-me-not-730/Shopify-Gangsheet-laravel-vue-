<?php

namespace GangSheetBuilder;

if (!function_exists('unzip_file')) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
}

// and the global variable must contain an object
global $wp_filesystem;
if (!$wp_filesystem) {
    // but if it does not, this function corrects the situation
    WP_Filesystem();
}

class GangSheetApi
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'rest_api_endpoints']);
    }

    public function rest_api_endpoints()
    {
        register_rest_route('gang_sheet/v1', '/verify', array(
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => [$this, 'verifyRegistration'],
            'permission_callback' => '__return_true',
        ));

        register_rest_route('gang_sheet/v1', '/settings', array(
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => [$this, 'updateShopSettings'],
            'permission_callback' => [$this, 'checkPermission'],
        ));

        register_rest_route('gang_sheet/v1', '/products', array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [$this, 'getProducts'],
            'permission_callback' => [$this, 'checkPermission'],
        ));

        register_rest_route('gang_sheet/v1', '/product/(?P<product_id>\d+)', array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [$this, 'getProduct'],
            'permission_callback' => [$this, 'checkPermission'],
            'args' => array(
                'product_id' => array(
                    'validate_callback' => function ($param, $request, $key) {
                        return is_numeric($param);
                    },
                ),
            ),
        ));

        register_rest_route('gang_sheet/v1', '/customer/(?P<customer_id>\d+)', array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [$this, 'getCustomer'],
            'permission_callback' => [$this, 'checkPermission'],
            'args' => array(
                'customer_id' => array(
                    'validate_callback' => function ($param, $request, $key) {
                        return is_numeric($param);
                    },
                ),
            ),
        ));

        register_rest_route('gang_sheet/v1', '/product/(?P<product_id>\d+)', array(
            'methods' => \WP_REST_Server::EDITABLE,
            'callback' => [$this, 'updateProduct'],
            'permission_callback' => [$this, 'checkPermission'],
            'args' => array(
                'product_id' => array(
                    'validate_callback' => function ($param, $request, $key) {
                        return is_numeric($param);
                    },
                ),
            ),
        ));

        register_rest_route('gang_sheet/v1', '/order/(?P<order_id>\d+)', array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => [$this, 'getOrder'],
            'permission_callback' => [$this, 'checkPermission'],
            'args' => array(
                'order_id' => array(
                    'validate_callback' => function ($param, $request, $key) {
                        return is_numeric($param);
                    },
                ),
            ),
        ));

        register_rest_route('gang_sheet/v1', '/upgrade', array(
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => [$this, 'upgradePlugin'],
            'permission_callback' => [$this, 'checkPermission']
        ));

        register_rest_route('gang_sheet/v1', '/customer', array(
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => [$this, 'createCustomer'],
            'permission_callback' => [$this, 'checkPermission'],
        ));
    }

    public function verifyRegistration($request)
    {
        $data = $request->get_json_params();

        return rest_ensure_response([
            'success' => true,
            'state' => $data['state'] ?? null,
            'uuid' => get_gs_shop_uuid(),
            'token' => get_gs_access_token()
        ]);
    }

    public function checkPermission($request)
    {
        $token = $request->get_header('x_gs_token') ??
            $request->get_header('X-GS-Token') ??
            $request->get_header('x-gs-token');

        if (!empty($token) && is_string($token)) {
            $token = trim(str_replace('Bearer ', '', $token));
            $gsToken = trim(get_gs_access_token());

            return $token === $gsToken;
        }

        return false;
    }

    public function updateShopSettings($request)
    {
        $data = $request->get_json_params();

        if ($data['access_token'] ?? null) {
            set_gs_access_token($data['access_token']);
        }

        if ($data['slug'] ?? null) {
            set_gs_shop_slug($data['slug']);
        }

        if ($data['options'] ?? null) {
            set_gs_options($data['options']);
        }

        return rest_ensure_response([
            'success' => true,
            'is_admin' => is_admin(),
            'token' => $data['access_token']
        ]);
    }

    public function getProducts()
    {
        try {
            // retrieve gang sheet products of which tag or category is "Gang Sheet"
            $filter = [
                'status' => 'publish',
                'limit' => -1,
                'tag' => [sanitize_title(GANG_SHEET_ID)],
                'return' => 'objects'
            ];

            $posts = wc_get_products($filter);

            $products = [];

            foreach ($posts as $post) {

                $tags = $post->get_tag_ids();
                $tagNames = [];
                if (!empty($tags)) {
                    foreach ($tags as $tag_id) {
                        $tag = get_term($tag_id, 'product_tag');
                        $tagNames[] = $tag->name;
                    }
                }

                $variants = [];
                if ($post->is_type('variable')) {
                    $available_variants = $post->get_available_variations();

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

                                $variants[] = [
                                    'id' => $variant['variation_id'],
                                    'title' => $title,
                                    'width' => $width,
                                    'height' => $height,
                                    'price' => $price,
                                    'unit' => $unit ?? 'in',
                                    'maxAllowedFileCount' => $maxAllowedFileCount ?? -1
                                ];
                            }
                        }
                    }
                }

                $image = wp_get_attachment_image_src(get_post_thumbnail_id($post->get_id()), 'single-post-thumbnail');
                $btn_label = get_post_meta($post->get_id(), 'btn_label', true);
                $art_board_type = get_post_meta($post->get_id(), 'art_board_type', true);

                $products[] = [
                    'id' => $post->get_id(),
                    'title' => $post->get_name(),
                    'image' => $image,
                    'created_at' => get_the_date('Y-m-d H:i:s', $post->get_id()),
                    'tags' => $tagNames,
                    'btnLabel' => $btn_label,
                    'artBoardType' => intval($art_board_type),
                    'variants' => $variants
                ];
            }

            return rest_ensure_response([
                'success' => true,
                'products' => $products
            ]);

        } catch (\Exception $exception) {

            return rest_ensure_response([
                'success' => false,
                'error' => $exception->getMessage()
            ]);
        }
    }

    public function getProduct($request)
    {
        $product_id = $request['product_id'];

        $product = get_gang_sheet_product($product_id);

        if (empty($product['error'])) {
            return rest_ensure_response([
                'success' => true,
                'product' => $product
            ]);
        }

        return rest_ensure_response([
            'success' => false,
            'error' => $product['message']
        ]);

    }

    public function getCustomer($request)
    {
        $customer_id = $request['customer_id'];

        $customer = get_user_by('id', $customer_id);

        if ($customer) {
            return rest_ensure_response([
                'success' => true,
                'customer' => [
                    'id' => $customer->ID,
                    'email' => $customer->data->user_email,
                    'name' => $customer->data->display_name,
                ]
            ]);
        }

        return rest_ensure_response([
            'success' => false,
            'error' => 'Customer not found'
        ]);
    }

    public function getOrder($request)
    {
        try {
            $order_id = $request['order_id'];

            $order = get_gang_sheet_order(wc_get_order($order_id));

            if (empty($order['error'])) {
                return rest_ensure_response([
                    'success' => true,
                    'order' => $order
                ]);
            }

            return rest_ensure_response([
                'success' => false,
                'error' => $order['message']
            ]);

        } catch (\Exception $exception) {
            return rest_ensure_response([
                'success' => false,
                'error' => $exception->getMessage()
            ]);
        }
    }

    public function updateProduct($request)
    {
        $data = $request->get_json_params();

        $product_id = $request['product_id'];
        $variants = $data['variants'];

        if (!empty($data['artBoardType'])) {
            $art_board_type = $data['artBoardType'];
            update_post_meta($product_id, 'art_board_type', $art_board_type);
        }

        $product_title = $data['product_title'];

        if (count($variants) === 0) {
            return rest_ensure_response([
                'success' => false,
                'error' => 'Variants are required.'
            ]);
        }

        $deleted_variants = $data['deleted_variants'];

        if ($data['btnLabel']) {
            $btnLabelMeta = [
                'key' => 'btn_label',
                'value' => $data['btnLabel'],
                'type' => 'string',
            ];

            update_post_meta($product_id, $btnLabelMeta['key'], $btnLabelMeta['value']);

        } else {
            delete_post_meta($product_id, 'btn_label');
        }

        $product = wc_get_product($product_id);

        if (!$product) {
            return new \WP_Error('no_product', 'Product not found', array('status' => 404));
        }

        if (!$product->is_type('variable')) {
            $product = new \WC_Product_Variable($product_id);
        }

        $product->set_name($product_title);

        $attributes = $product->get_attributes();

        $size_values = array_map(function ($variant) {
            return $variant['title'];
        }, $variants);

        if ($attributes["Size"] ?? null) {
            $attributes["Size"]->set_options($size_values);
        } else {
            $attribute = new \WC_Product_Attribute();
            $attribute->set_name("Size");
            $attribute->set_options($size_values);
            $attribute->set_variation(true);
            $attribute->set_visible(true);
            $attributes["Size"] = $attribute;
        }

        $product->set_attributes($attributes);

        if (count($deleted_variants)) {
            foreach ($deleted_variants as $deleted_variant) {
                $variation = new \WC_Product_Variation($deleted_variant);
                if ($variation->exists()) {
                    $variation->delete();
                }
            }
        }

        foreach ($variants as $variant) {
            $variant_id = $variant['id'] ?? null;

            if (empty($variant_id)) {
                $variation = new \WC_Product_Variation();
            } else {
                $variation = new \WC_Product_Variation($variant_id);
                if (!$variation->exists()) {
                    $variation = new \WC_Product_Variation();
                }
            }

            $variation->set_parent_id($product_id);
            $variation->set_regular_price($variant['price']);
            $variation->set_price($variant['price']);
            $variation->set_attributes(['attribute_size' => $variant['title']]);

            $variation->save();

            update_post_meta($variation->get_id(), '_title', $variant['title']);
            update_post_meta($variation->get_id(), '_g_width', $variant['width']);
            update_post_meta($variation->get_id(), '_g_height', $variant['height']);
            update_post_meta($variation->get_id(), '_price', $variant['price']);
            update_post_meta($variation->get_id(), '_unit', $variant['unit'] ?? 'in');
            update_post_meta($variation->get_id(), '_max_allowed_file_count', $variant['maxAllowedFileCount'] ?? -1);
        }

        $product->save();
        // update product default variation.
        if (!empty($size_values[0])) {
            $default_attributes = $product->get_default_attributes();
            $default_attributes['size'] = $size_values[0];
            update_post_meta($product_id, '_default_attributes', $default_attributes);
        }

        $variant_ids = $product->get_children();

        return rest_ensure_response([
            'success' => true,
            'variants' => array_map(function ($variation_id) {
                return [
                    'id' => $variation_id,
                    'title' => get_post_meta($variation_id, '_title', true),
                    'width' => get_post_meta($variation_id, '_g_width', true),
                    'height' => get_post_meta($variation_id, '_g_height', true),
                    'price' => get_post_meta($variation_id, '_price', true),
                    'unit' => get_post_meta($variation_id, '_unit', true),
                    'maxAllowedFileCount' => get_post_meta($variation_id, '_max_allowed_file_count', true)
                ];
            }, $variant_ids)
        ]);
    }

    public function upgradePlugin()
    {
        $plugin_url = 'https://app.buildagangsheet.com/plugins/gang-sheet-builder_latest.zip';

        $tmp_file = download_url($plugin_url);

        if (is_wp_error($tmp_file)) {
            @unlink($tmp_file);
            return rest_ensure_response([
                'success' => false
            ]);
        } else {

            $zip_path = GSB_DIR . '/latest.zip';
            copy($tmp_file, $zip_path);

            // this variable must already have been set when initializing the file system
            global $wp_filesystem;

            $plugin_path = str_replace(ABSPATH, $wp_filesystem->abspath(), GSB_DIR);
            $plugin_path = str_replace('gang-sheet-builder', '', $plugin_path);
            unzip_file($zip_path, $plugin_path);

            @unlink($zip_path);
        }

        @unlink($tmp_file);

        return rest_ensure_response([
            'success' => true
        ]);
    }
    public function createCustomer($request)
    {
        $data = $request->get_json_params();

        $email = sanitize_email($data['email']);
        if (!is_email($email)) {
            return new \WP_Error('invalid_email', 'Invalid email address', array('status' => 400));
        }

        // Ensure the email is unique
        if (email_exists($email)) {
            return new \WP_Error('email_exists', 'Email already exists', array('status' => 400));
        }

        $user_id = wp_create_user($data['email'], $data['password'], $data['email']);
        if (is_wp_error($user_id)) {
            return new \WP_Error('user_creation_failed', 'User creation failed', array('status' => 500));
        }

        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => sanitize_text_field($data['name']),
            'display_name' => sanitize_text_field($data['name']),
            'role' => 'customer'
        ));

        update_user_meta($user_id, 'billing_phone', sanitize_text_field($data['phone']));

        return rest_ensure_response([
            'success' => true,
            'user_id' => $user_id,
        ]);
    }
}

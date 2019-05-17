<?php

namespace GangSheetBuilder;

class GangSheetProduct
{
    public $options;
    public $buttonRendered = false;

    public function __construct()
    {
        $this->options = get_gs_options();

        add_action('wp_enqueue_scripts', [$this, 'assets']);

        add_action('woocommerce_after_add_to_cart_button', [$this, 'add_gang_sheet_builder_button']);

        add_filter('woocommerce_cart_item_thumbnail', [$this, 'get_gang_sheet_preview'], 10, 3);
    }

    public function assets()
    {
        wp_enqueue_style('gang-sheet-product', gs_asset('css/gang-sheet-product.css'), false);

        $customer = wp_get_current_user();
        if (function_exists('is_product') && is_product()) {
            $product_id = get_the_ID();
            $product = get_gang_sheet_product($product_id);
            if (empty($product['error']) && !empty($product['variants'])) {
                wp_enqueue_script('gang-sheet-product', gs_asset('scripts/gang-sheet-product.js'), false);
            }
        } else {
            ?>
            <script>
                window.appEnv = "<?php echo gs_env(); ?>"
                window.GangSheetOptions = {
                    shop_slug: "<?php echo get_gs_shop_slug(); ?>",
                    gs_version: "<?php echo (defined('GSB_VERSION') ? GSB_VERSION : 'undefined'); ?>",
                    customer: {
                        id: "<?php echo $customer->ID ?? 'undefined'; ?>",
                        email: "<?php echo $customer->user_email ?? 'undefined'; ?>"
                    }
                }
            </script>
            <?php
            wp_enqueue_script('gang-sheet-edit', gs_asset('scripts/gang-sheet-edit.js'), false);
        }

        wp_enqueue_script('gang-sheet-login', gs_asset('scripts/gang-sheet-login.js'), [], null);
    }

    public function add_gang_sheet_builder_button()
    {
        if ($this->buttonRendered) {
            return;
        }

        $product_id = get_the_ID();
        $product = get_gang_sheet_product($product_id);

        if (empty($product['error'])) {
            remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
            ?>
            <style>
                #wc-square-digital-wallet,
                .woocommerce-variation-add-to-cart .elementor-button:not(#gang-sheet-builder-button) {
                    display: none !important;
                }

                /* Styles for shop 18cf2875-7ae7-4570-8b33-32119313f8de */

                .woocommerce-variation-add-to-cart.variations_button.woocommerce-variation-add-to-cart-enabled {
                    display: block !important;
                }

                .wcuf_product_ajax_container {
                    display: none !important;
                }

                /*   End Styles for shop 18cf2875-7ae7-4570-8b33-32119313f8de */
            </style>
            <?php

            $customer = wp_get_current_user();
            $btn_label = get_post_meta($product_id, 'btn_label', true);

            if (!empty($product['variants'])) {
                ?>
                <script>
                    window.appEnv = "<?php echo gs_env(); ?>"
                    window.GangSheetOptions = {
                        'shop_uuid': "<?php echo get_gs_shop_uuid(); ?>",
                        'shop_slug': "<?php echo get_gs_shop_slug(); ?>",
                        'gs_version': "<?php echo (defined('GSB_VERSION') ? GSB_VERSION : 'undefined'); ?>",
                        'product_id': "<?php echo $product_id; ?>",
                        'variants': <?php echo json_encode($product['variants']); ?>,
                        'cart_url': "<?php echo wc_get_cart_url(); ?>",
                        'customer': {
                            'id': "<?php echo $customer->ID ?? 'undefined'; ?>",
                            'email': "<?php echo $customer->user_email ?? 'undefined'; ?>"
                        },
                        'btn_label': "<?php echo $btn_label; ?>"
                    }
                </script>
                <button id="gang-sheet-builder-button" class="single_add_to_cart_button" type="button" style="
                            background-color: <?php echo $this->options['btn_bg_color'] ?>;
                            color: <?php echo $this->options['btn_fg_color'] ?>;
                            border-color: <?php echo $this->options['btn_bg_color'] ?>;
                        ">
                    <?php $btn_label = !empty($btn_label) ? $btn_label : $this->options['btn_text'];
                    echo $btn_label;
                    ?>
                </button>
                <?php

                $this->buttonRendered = true;
            } else {
                ?>
                <span style="color: red"> No available gang sheet sizes. </span>
                <?php
            }
        }
    }

    public function get_gang_sheet_preview($product_image, $cart_item, $cart_item_key)
    {
        if ($cart_item_key === 'gs_design_id' && !empty($cart_item['gs_design_id'])) {
            return '<img src="' . gs_get_thumbnail_url($cart_item['gs_design_id']) . '" />';
        }

        return $product_image;
    }
}

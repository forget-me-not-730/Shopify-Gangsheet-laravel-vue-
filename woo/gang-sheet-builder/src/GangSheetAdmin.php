<?php

namespace GangSheetBuilder;

class GangSheetAdmin
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_gang_sheet_menu']);
        add_action('admin_enqueue_scripts', [$this, 'gang_sheet_enqueue_scripts']);

        // remove admin notice
        if (($_GET["page"] ?? null) === 'gang-sheet') {
            add_action(
                'admin_notices',
                function () {
                    remove_all_actions('admin_notices');
                },
                0
            );
        }
    }

    function add_gang_sheet_menu()
    {
        add_menu_page(
            'Gang Sheet Settings',
            'Gang Sheet',
            'manage_options',
            'gang-sheet',
            [$this, 'render_gang_sheet_admin']
        );
    }

    function gang_sheet_enqueue_scripts()
    {
        $customer = wp_get_current_user();

        $order_id = $_GET['order_id']
            ?? $_GET['id']
            ?? $_GET['post_id']
            ?? $_GET['post']
            ?? null;

        ?>
        <script>
            window.gs_admin = true
            window.appEnv = "<?php echo gs_env(); ?>"
            window.gs_website = "<?php echo site_url(); ?>"
            window.gs_api_base_url = "<?php echo get_gs_api_url(); ?>"
            window.gs_access_token = "<?php echo get_gs_access_token(); ?>"
            window.GangSheetOptions = {
                shop_slug: "<?php echo get_gs_shop_slug(); ?>",
                shop_uuid: "<?php echo get_gs_shop_uuid(); ?>",
                rest_prefix: "<?php echo trailingslashit(rest_get_url_prefix()); ?>",
                wp_version: "<?php echo get_bloginfo('version'); ?>",
                wc_version: "<?php echo (defined('WC_VERSION') ? WC_VERSION : 'undefined'); ?>",
                gs_version: "<?php echo (defined('GSB_VERSION') ? GSB_VERSION : 'undefined'); ?>",
                order_id: <?php echo (empty($order_id) ? 'undefined' : $order_id); ?>,
                customer: {
                    id: "<?php echo $customer->ID ?? 'undefined'; ?>",
                    email: "<?php echo $customer->user_email ?? 'undefined'; ?>"
                }
            }
        </script>
        <?php
        $screen = get_current_screen();

        if (str_contains($screen->id, 'order')) {
            wp_enqueue_style('gang-sheet-product', gs_asset('css/gang-sheet-product.css'), false);
            wp_enqueue_script('gang-sheet-edit', gs_asset('scripts/gang-sheet-edit.js'), [], null);
        }

        if (str_contains($screen->id, 'gang-sheet')) {
            wp_enqueue_style('gang-sheet-admin', gs_asset('css/gang-sheet-admin.css'), [], null);
            wp_enqueue_script('gang-sheet-admin', gs_asset('scripts/gang-sheet-admin.js'), [], 10002, [
                'strategy' => 'defer'
            ]);
        }

        wp_enqueue_script('gang-sheet-login', gs_asset('scripts/gang-sheet-login.js'), [], null);
    }

    function render_gang_sheet_admin()
    {
        $data = gang_sheet_api_call('GET', 'shop');
        if (!empty($data['error'])) {
            $data = gs_shop_register();
        } else {
            $shop = $data['shop'];
            set_gs_shop_slug($shop['slug']);
        }

        if (empty($data['error'])) {
            $options = get_gs_options();
            ?>
            <script>
                window.gs_options = <?php echo json_encode($options); ?>;
                window.gs_shop = <?php echo json_encode($data['shop']); ?>;
                window.gs_latest_version = <?php echo json_encode($data['gs_latest_version']); ?>;
            </script>
            <div class="wrap" id="woo-gang-sheet-app"></div>
            <?php
        } else {
            ?>
            <div style="color: red; font-size: 1.25rem; font-weight: bold">Something went to wrong!</div>
            <?php
            ?>
            <div><?php echo $data['error']; ?></div>
            <?php
        }
    }
}

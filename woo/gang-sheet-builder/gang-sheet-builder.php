<?php
/**
 * Plugin Name: Build a gang sheet
 * Plugin URI: https://dripappsserver.com
 * Description: A WordPress plugin to build gang sheets.
 * Version: 1.4.4
 * Author: Justin Kicklighter<admin@dripapps.net>
 * Author URI: https://thedripapps.com
 * License: GPL v2 or later
 * Text Domain: gang-sheet-builder
 */

use GangSheetBuilder\GangSheetAdmin;
use GangSheetBuilder\GangSheetApi;
use GangSheetBuilder\GangSheetProduct;
use GangSheetBuilder\GangSheetCart;
use GangSheetBuilder\GangSheetWebhook;

if (!defined('ABSPATH')) {
    exit;
}

define('GSB_VERSION', '1.4.4');

define('GSB_DIR', plugin_dir_path(__FILE__));

require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/GangSheetWebhook.php';
require_once __DIR__ . '/src/GangSheetProduct.php';
require_once __DIR__ . '/src/GangSheetCart.php';
require_once __DIR__ . '/src/GangSheetApi.php';
require_once __DIR__ . '/src/GangSheetAdmin.php';
require_once __DIR__ . '/src/gs_helpers.php';

/**
 * The main plugin class
 */
final class GangSheetBuilder
{
    static public function activate()
    {
        if (is_plugin_active('woocommerce/woocommerce.php')) {
            $term = GANG_SHEET_ID;
            $taxonomy = 'product_tag';

            $existing_term = term_exists($term, $taxonomy);

            if ($existing_term !== 0 && $existing_term !== null) {
                $term_id = $existing_term['term_id'];
                wp_update_term($term_id, $taxonomy, array(
                    'name' => $term,
                    'slug' => sanitize_title(GANG_SHEET_ID),
                ));
            } else {
                // Term doesn't exist, insert it
                wp_insert_term($term, $taxonomy, array(
                    'slug' => sanitize_title(GANG_SHEET_ID),
                ));
            }
        }
    }

    static public function deactivate()
    {
        gs_remove_log();
        gang_sheet_api_call('DELETE', 'shop');
    }

    /**
     * Initialize the plugin
     *
     * @return void
     */
    static public function init_plugin()
    {
        new GangSheetWebhook();

        new GangSheetApi();
        new GangSheetCart();
        new GangSheetProduct();

        if (is_admin()) {
            new GangSheetAdmin();
        }
    }
}

register_activation_hook(__FILE__, [GangSheetBuilder::class, 'activate']);
register_deactivation_hook(__FILE__, [GangSheetBuilder::class, 'deactivate']);
add_action('plugins_loaded', [GangSheetBuilder::class, 'init_plugin']);

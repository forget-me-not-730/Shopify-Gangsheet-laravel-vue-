<?php

const GANG_SHEET_ID = 'Gang Sheet';
const KEY_GANG_SHEET_ACCESS_TOKEN = 'gang_sheet_access_token';
const KEY_GANG_SHEET_SHOP_UUID = 'gang_sheet_shop_uuid';
const KEY_GANG_SHEET_SHOP_SLUG = 'gang_sheet_shop_slug';
const KEY_GANG_SHEET_OPTIONS = 'gang_sheet_options';


if (!function_exists('gs_env')) {
    function gs_env($env = null)
    {
        $gsEnv = 'production';

        if (defined('GANG_SHEET_ENV')) {
            $gsEnv = GANG_SHEET_ENV;
        }

        if ($env) {
            return $env === $gsEnv;
        }

        return $gsEnv;
    }
}

if (!function_exists('get_gs_url')) {
    function get_gs_url($path = '')
    {
        $baseUrl = "https://app.buildagangsheet.com";
        if (defined('GANG_SHEET_BASE_URL')) {
            $baseUrl = GANG_SHEET_BASE_URL;
        }

        return $baseUrl . $path;
    }
}

if (!function_exists('get_gs_api_url')) {
    function get_gs_api_url($path = '')
    {
        return get_gs_url('/api/woo/' . $path);
    }
}

if (!function_exists('get_gs_access_token')) {
    function get_gs_access_token()
    {
        return get_option(KEY_GANG_SHEET_ACCESS_TOKEN);
    }
}
if (!function_exists('set_gs_access_token')) {
    function set_gs_access_token($token)
    {
        return update_option(KEY_GANG_SHEET_ACCESS_TOKEN, $token);
    }
}

if (!function_exists('get_gs_shop_uuid')) {
    function get_gs_shop_uuid()
    {
        return get_option(KEY_GANG_SHEET_SHOP_UUID);
    }
}
if (!function_exists('set_gs_shop_uuid')) {
    function set_gs_shop_uuid(string $uuid)
    {
        update_option(KEY_GANG_SHEET_SHOP_UUID, $uuid);
    }
}

if (!function_exists('get_gs_shop_slug')) {
    function get_gs_shop_slug()
    {
        return get_option(KEY_GANG_SHEET_SHOP_SLUG);
    }
}
if (!function_exists('set_gs_shop_slug')) {
    function set_gs_shop_slug($slug)
    {
        return update_option(KEY_GANG_SHEET_SHOP_SLUG, $slug);
    }
}

if (!function_exists('get_gs_options')) {
    function get_gs_options()
    {
        return get_option(KEY_GANG_SHEET_OPTIONS);
    }
}
if (!function_exists('set_gs_options')) {
    function set_gs_options(array $options)
    {
        return update_option(KEY_GANG_SHEET_OPTIONS, $options);
    }
}

if (!function_exists('gs_asset')) {
    function gs_asset($path)
    {
        if (gs_env('local')) {
            return "https://dev.buildagangsheet.test/assets/woo/" . $path . '?v=' . GSB_VERSION;
        } elseif (gs_env('development')) {
            return "https://dev.buildagangsheet.com/assets/woo/" . $path . '?v=' . GSB_VERSION;
        }

        return "https://app.buildagangsheet.com/assets/woo/" . $path . '?v=' . GSB_VERSION;
    }
}

if (!function_exists('write_log')) {
    function write_log($log)
    {
        if (is_null($log)) {
            $log = 'NULL';
        } else if (is_bool($log)) {
            $log = $log ? 'TRUE' : 'FALSE';
        } else if (is_array($log) || is_object($log)) {
            $log = print_r($log, true);
        }

        if (defined('WP_DEBUG')) {
            if (WP_DEBUG === true) {
                error_log($log);
            }
        }
        $log_path = ABSPATH . 'wp-content/gang-sheet.log';
        if (!file_exists($log_path)) {
            file_put_contents($log_path, $log_path);
            file_put_contents($log_path, "\r\n", FILE_APPEND);
        }
        file_put_contents($log_path, $log, FILE_APPEND);
        file_put_contents($log_path, "\r\n", FILE_APPEND);
    }
}

if (!function_exists('gs_remove_log')) {
    function gs_remove_log()
    {
        $log_path = ABSPATH . 'wp-content/gang-sheet.log';
        if (file_exists($log_path)) {
            unlink($log_path);
        }
    }
}


if (!function_exists('gang_sheet_api_call')) {
    function gang_sheet_api_call($method, $path, $params = [])
    {

        $api_url = get_gs_api_url($path);

        $headers = [
            'Content-Type' => 'application/json',
        ];

        $access_token = get_gs_access_token();

        if ($access_token) {
            $headers['Authorization'] = "Bearer $access_token";
        }

        $request_args = [
            'body' => $method === 'GET' ? $params : json_encode($params),
            'headers' => $headers,
            'method' => $method,
        ];

        if (gs_env('local')) {
            $request_args['sslverify'] = false;
        }

        // Fix curl timeout issue
        add_filter('http_request_timeout', function () {
            return 300;
        });

        $response = wp_remote_request($api_url, $request_args);

        if (is_wp_error($response)) {

            $error = $response->get_error_message();

            write_log($error);

            return [
                'error' => $error
            ];
        } else {
            // Get the response body
            $body = wp_remote_retrieve_body($response);

            return json_decode($body, true);
        }
    }
}

if (!function_exists('gs_report')) {
    function gs_report($message)
    {
        write_log($message);
        gang_sheet_api_call('POST', 'report', ['message' => json_encode($message)]);
    }
}

if (!function_exists('gs_shop_register')) {
    function gs_shop_register()
    {
        $uuid = get_gs_shop_uuid();
        if (empty($uuid)) {
            $uuid = wp_generate_uuid4();
            set_gs_shop_uuid($uuid);
        }

        $options = get_gs_options();
        if (empty($options)) {
            set_gs_options([
                'btn_text' => 'Build your own gang sheet'
            ]);
        }

        $current_user = wp_get_current_user();
        $post_data = [
            'username' => $current_user->user_login,
            'email' => $current_user->user_email,
            'first_name' => $current_user->first_name,
            'last_name' => $current_user->last_name,
            'uuid' => $uuid,
            'shop_name' => get_bloginfo('name'),
            'website' => site_url()
        ];

        $data = gang_sheet_api_call('POST', 'shop', $post_data);

        if (empty($data['error'])) {
            $shop = $data['shop'];
            set_gs_shop_uuid($shop['shop_uuid']);
            set_gs_access_token($shop['access_token']);
            set_gs_shop_slug($shop['slug']);

            return $data;
        } else {
            write_log($data);

            return $data;
        }
    }
}

if (!function_exists('gs_has_item_value')) {
    function gs_has_item_value($item_data, $key)
    {
        foreach ($item_data as $data) {
            if (($data['key'] ?? null) == $key && !empty($data['value'])) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('gs_get_thumbnail_url')) {
    function gs_get_thumbnail_url($design_id)
    {
        return get_gs_url('/thumbnail/' . $design_id . '.png');
    }
}

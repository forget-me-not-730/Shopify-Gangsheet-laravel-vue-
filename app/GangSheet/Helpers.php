<?php

use App\Models\User;
use Spatie\SlackAlerts\Facades\SlackAlert;
use Illuminate\Support\Facades\Mail;
use \Illuminate\Support\Facades\DB;

if (!function_exists('slack_report')) {
    function slack_report(array|string $messages): void
    {
        if (app()->environment('production')) {
            try {
                $blocks = [
                    [
                        'type' => 'section',
                        'text' => [
                            "type" => "mrkdwn",
                            "text" => "*Environment*: " . app()->environment()
                        ],
                    ],
                    [
                        'type' => 'section',
                        'text' => [
                            "type" => "mrkdwn",
                            "text" => "*Server*: " . config('app.server_name')
                        ],
                    ],
                    [
                        'type' => 'section',
                        'text' => [
                            "type" => "mrkdwn",
                            "text" => "*Request Path*: " . request()->path()
                        ],
                    ],
                    [
                        'type' => 'section',
                        'text' => [
                            "type" => "mrkdwn",
                            "text" => "*Request IP*: " . request()->ip()
                        ],
                    ],
                    [
                        'type' => 'section',
                        'text' => [
                            "type" => "mrkdwn",
                            "text" => "*Request Domain*: " . request()->getHost()
                        ],
                    ]
                ];

                if (is_array($messages)) {
                    foreach ($messages as $message) {
                        $blocks[] = [
                            'type' => 'section',
                            'text' => [
                                "type" => "mrkdwn",
                                "text" => $message
                            ],
                        ];
                    }
                } else {
                    $blocks[] = [
                        'type' => 'section',
                        'text' => [
                            "type" => "mrkdwn",
                            "text" => $messages
                        ],
                    ];
                }

                $blocks[] = [
                    'type' => 'divider',
                ];

                SlackAlert::to('default')->blocks($blocks);

                info(request()->path(), request()->all());
            } catch (\Exception $exception) {
                info($exception->getMessage());
            }
        } else {
            info(json_encode($messages));
        }
    }
}

if (!function_exists('slack_test_report')) {
    function slack_test_report(array $messages): void
    {
        try {
            $blocks = [
                [
                    'type' => 'section',
                    'text' => [
                        "type" => "mrkdwn",
                        "text" => "*Environment*: " . app()->environment()
                    ],
                ]
            ];

            foreach ($messages as $message) {
                $blocks[] = [
                    'type' => 'section',
                    'text' => [
                        "type" => "mrkdwn",
                        "text" => $message
                    ],
                ];
            }

            $blocks[] = [
                'type' => 'divider',
            ];

            SlackAlert::to('test')->blocks($blocks);
        } catch (\Exception $exception) {
            info($exception->getMessage());
        }
    }
}

if (!function_exists('email_report')) {
    function email_report(array $messages): void
    {
        try {
            Mail::to([
                'admin@dripapps.net',
                'sr.fullstack.geek@gmail.com',
                'darkinstar@outlook.com'
            ])->send(
                new \App\Mail\MailReport($messages)
            );
        } catch (\Exception $exception) {
            info($exception->getMessage());
        }
    }
}

if (!function_exists('hex8ToRgba')) {
    function hex8ToRgba(string $hex8): array
    {
        $hex = ltrim($hex8, '#');
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
            round(hexdec(substr($hex, 6, 2)) / 255, 2)
        ];
    }
}

if (!function_exists('isGangSheetItem')) {
    function isGangSheetItem($properties): bool
    {
        $gangSheetPropertyNames = [
            '_Has Low Resolution',
            '_Has Overlapping',
            '_Print Ready File'
        ];

        foreach ($properties as $property) {
            $isGangSheetItemProperty = (is_string($property['value']) && str_contains($property['value'], 'dripappsserver'))
                || in_array($property['name'], $gangSheetPropertyNames);

            if ($isGangSheetItemProperty) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('gs_shop')) {
    function gs_shop()
    {
        try {
            if (request()->get('store')) {
                return request()->get('store');
            }

            if (!request()->routeIs('builder.*')) {
                $shop = request()->user('web');
            }

            if (empty($shop)) {
                $shopName = request()->get('shop');

                if (!$shopName) {
                    $shopName = request()->header('x-shopify-shop-domain');
                }

                if ($shopName) {
                    $shop = User::where('name', $shopName)->first();
                }
            }

            if (empty($shop)) {
                $user_id = request()->get('user_id') ?? request()->get('shop_id');
                $shop = User::find($user_id);
            }

            if (empty($shop)) {
                $design_id = request()->get('design_id');

                if (!$design_id) {
                    $design_id = request()->route()->parameter('design_id');
                }

                if ($design_id) {
                    $design = DB::table('designs')->select('user_id')->find($design_id);
                    if ($design) {
                        $shop = User::find($design->user_id);
                    }
                }
            }

            if ($shop && empty(request()->get('store'))) {
                request()->attributes->set('store', $shop);
            }

            return $shop;
        } catch (\Exception $exception) {
            report($exception);

            return null;
        }
    }
}

if (!function_exists('spaces')) {
    function spaces(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return \Storage::disk('spaces');
    }
}

if (!function_exists('storage')) {
    function storage(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return \Storage::disk('public');
    }
}

if (!function_exists('clean_url')) {
    function clean_url(string $url): string
    {
        return preg_replace('#(?<!:)//+#', '/', $url);
    }
}

if (!function_exists('app_url')) {
    function app_url($path = ''): string
    {
        $appUrl = 'https://app.dripappsserver.com';

        if (!app()->environment('production')) {
            $appUrl = config('app.url');
        }

        if (empty($path)) {
            return $appUrl;
        }

        return clean_url($appUrl . '/' . $path);
    }
}

if (!function_exists('temp_path')) {
    function temp_path($fileName): string
    {
        $uploadMonth = now()->format('Y-m');
        return "temp/$uploadMonth/$fileName";
    }
}

if (!function_exists('color_contrast')) {
    function color_contrast($backgroundColor): string
    {
        // Remove "#" if present
        $backgroundColor = str_replace('#', '', $backgroundColor);

        // Convert the hex color to RGB
        $r = hexdec(substr($backgroundColor, 0, 2));
        $g = hexdec(substr($backgroundColor, 2, 2));
        $b = hexdec(substr($backgroundColor, 4, 2));

        // Calculate luminance according to the W3C formula
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        // Return black or white depending on the luminance
        return ($luminance > 0.8) ? '#333333' : '#eeeeee';
    }
}

if (!function_exists('round_up')) {
    function round_up($n, $x = 2): float
    {
        return ceil($n / $x) * $x;
    }
}

if (!function_exists('shopify_id')) {
    function shopify_id($id)
    {
        if (str_starts_with($id, 'gid://shopify/')) {
            $segments = explode('/', $id);
            return end($segments);
        } else {
            return $id;
        }
    }
}

/**
 * check if server is windows
 */

if (!function_exists('is_windows')) {
    function is_windows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }
}

if (!function_exists('isBagsEmail')) {
    function isBagsEmail($email): bool
    {
        return str_ends_with($email, "@buildagangsheet.com");
    }
}

if (!function_exists('merchant')) {
    function merchant()
    {
        try {
            $merchant = null;

            if (request()->get('shop')) {
                $merchant = request()->get('shop');
            }

            if ($merchant instanceof User) {
                return $merchant;
            }

            // Standalone Builder Route
            if (request()->routeIs('builder.*') || request()->routeIs('woo.builder.*')) {
                $host = request()->host();
                if ($host) {
                    //check if domain is using subdomain of app
                    if (str_ends_with($host, "." . config('app.domain'))) {
                        $subdomain = explode('.', $host)[0];
                        $merchant = User::where('slug', $subdomain)->first();
                        if ($merchant && $merchant->domain) {
                            $fullUrl = request()->fullUrl();
                            $mainDomainUrl = str_replace($host, $merchant->domain, $fullUrl);

                            redirect()->to($mainDomainUrl)->send();
                            exit;

                        }
                    } else {
                        $merchant = User::where('domain', $host)->first();
                    }
                }
            }

            if (empty($merchant)) {
                if ($shopId = request()->get('shop_id')) {
                    if (is_numeric($shopId)) {
                        $merchant = User::find($shopId);
                    } else if (is_string($shopId) && \Illuminate\Support\Str::isUuid($shopId)) {
                        $merchant = User::where('shop_uuid', $shopId)->first();
                    }
                }
            }

            if (empty($merchant)) {
                $shopUUID = request()->header('x-shop-uuid');
                if ($shopUUID) {
                    $merchant = User::where('shop_uuid', $shopUUID)->first();
                }
            }

            if (empty($merchant)) {
                $design_id = request()->route()->parameter('design_id');

                if (!$design_id) {
                    $design_id = request()->get('design_id');
                }

                if ($design_id) {
                    $design = DB::table('designs')->select('user_id')->find($design_id);
                    if ($design) {
                        $merchant = User::find($design->user_id);
                    }
                }
            }

            if (empty($merchant)) {
                $merchant = User::find(request()->get('user_id'));
            }

            // Merchant Route
            if (request()->routeIs('merchant.*')) {
                $merchant = request()->user('web');
            }


            if ($merchant && empty(request()->get('shop'))) {
                request()->merge(['shop' => $merchant]);
            }

            return $merchant;

        } catch (\Exception $exception) {
            report($exception);

            return null;
        }
    }
}

if (!function_exists('convert_dimension')) {
    function convert_dimension($value, $unitFrom, $unitTo = 'px', $resolution = 300): float
    {
        $value = (float)$value;

        if ($unitFrom === $unitTo) {
            return $value;
        }

        $pixelPerInch = $resolution;
        $pixelPerCm = $pixelPerInch / 2.54;
        $pixelPerMm = $pixelPerCm / 10;

        $value = match ($unitFrom) {
            'in' => $value * $pixelPerInch,
            'cm' => $value * $pixelPerCm,
            'mm' => $value * $pixelPerMm,
            default => $value,
        };

        return match ($unitTo) {
            'in' => $value / $pixelPerInch,
            'cm' => round($value / $pixelPerCm, 2),
            'mm' => round($value / $pixelPerMm, 2),
            default => round($value),
        };
    }
}

if (!function_exists('make_signature')) {
    function make_signature($params, $secret): string
    {
        ksort($params);
        $queryString = '';
        foreach ($params as $key => $value) {
            $queryString .= "$key=$value";
        }

        return hash_hmac('sha256', $queryString, $secret);
    }
}

if (!function_exists('proxy_signature')) {
    function proxy_signature($params): string
    {
        $secret = config('shopify-app.api_secret');

        return make_signature($params, $secret);
    }
}

if (!function_exists('app_signature')) {
    function app_signature($params): string
    {
        $secret = config('app.key');

        return make_signature($params, $secret);
    }
}

if (!function_exists('encode_url')) {
    function encode_url(string $url): string
    {
        // Parse the URL into components
        $parsed_url = parse_url($url);

        // Encode the path and file name
        $encoded_path = isset($parsed_url['path'])
            ? implode('/', array_map('rawurlencode', explode('/', $parsed_url['path'])))
            : '';

        // Reconstruct the URL
        $encoded_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];
        if (!empty($encoded_path)) {
            $encoded_url .= $encoded_path;
        }
        if (isset($parsed_url['query'])) {
            $encoded_url .= '?' . $parsed_url['query']; // Keep the query string intact
        }

        return $encoded_url;
    }
}

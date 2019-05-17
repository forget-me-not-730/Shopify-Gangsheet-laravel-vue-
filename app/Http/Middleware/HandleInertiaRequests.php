<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tightenco\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $sharedProps = [
            'auth' => [
                'user' => fn() => $request->user(),
                'customer' => fn() => auth('customer')->user(),
            ],
            'ziggy' => fn() => array_merge((new Ziggy)->toArray(), [
                'location' => $request->url(),
            ]),
            'recaptcha_key' => config('services.recaptcha.key'),
            'admin_id' => $request->session()->get('admin_id'),
            'hashtag' => $request->session()->get('hashtag')
        ];

        if ($merchant = merchant()) {
            $shopFonts = $merchant->getSetting('fonts', []);
            $defaultFont = $merchant->getSetting('defaultFont', $shopFonts[0] ?? null);

            if (empty($shopFonts)) {
                $shopFonts = option('fonts', []);
                $defaultFont = option('default_font', $shopFonts[0] ?? null);
            }

            $sharedProps['shopFonts'] = $shopFonts;
            $sharedProps['defaultFont'] = $defaultFont;
            $sharedProps['nameAndNumberFonts'] = option('name_and_number_fonts', []);
        }

        return array_merge(parent::share($request), $sharedProps, [
            'flash' => function () use ($request) {
                return [
                    'success' => $request->session()->get('success'),
                    'error' => $request->session()->get('error'),
                    'warning' => $request->session()->get('warning'),
                    'info' => $request->session()->get('info'),
                ];
            },
        ]);
    }

    public function handle(Request $request, Closure $next)
    {
        if ($request->routeIs('builder.*') ||
            $request->routeIs('woo.builder.*') ||
            $request->routeIs('merchant.order.design.*') ||
            $request->routeIs('merchant.product.pattern')) {
            $this->rootView = 'builder';
        } else if ($request->routeIs('gs.builder.*')) {
            $this->rootView = 'gsb';
        }

        return parent::handle($request, $next);
    }
}

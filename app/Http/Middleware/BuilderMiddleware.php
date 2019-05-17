<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BuilderMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if ($merchant = merchant()) {
            $fontName = $merchant->getSetting('builderFont', 'Oswald');
            $builderBgColor = $merchant->getSetting('builderBgColor');
            $builderTopBgColor = $merchant->getSetting('builderTopBgColor');
            $builderSideBgColor = $merchant->getSetting('builderSideBgColor');
            $builderPrimaryColor = $merchant->getSetting('builderPrimaryColor');
            $builderSecondaryColor = $merchant->getSetting('builderSecondaryColor');
            $builderFgColor = $merchant->getSetting('builderFgColor');
            $enableChat = $merchant->getSetting('enableChat', false);
            $chatScript = $merchant->getSetting('chatScript');

            view()->share('fontName', $fontName);
            view()->share('bgColor', $builderBgColor);
            view()->share('topBarBgColor', $builderTopBgColor);
            view()->share('sideBarBgColor', $builderSideBgColor);
            view()->share('primaryColor', $builderPrimaryColor);
            view()->share('secondaryColor', $builderSecondaryColor);
            view()->share('fgColor', $builderFgColor);
            if ($enableChat) {
                view()->share('chatScript', $chatScript);
            }
        } else {
            abort(403);
        }

        app()->singleton('shop', function () use ($merchant) {
            return $merchant;
        });

        view()->share('shop', $merchant);

        return $next($request);
    }
}

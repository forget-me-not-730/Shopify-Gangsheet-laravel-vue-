<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MerchantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if ($request->method() == 'GET') {
            $merchant = auth()->user();

            if ($merchant && !$merchant->isCustomStore() && (empty($merchant->company_name) || empty($merchant->slug))) {
                if ($request->route()->getName() == 'merchant.setting.index') {
                    return $next($request);
                }

                return to_route('merchant.setting.index')->with(['hashtag' => 'company'])->withErrors(['message' => 'Please complete your company profile.']);
            }
        }

        return $next($request);
    }
}

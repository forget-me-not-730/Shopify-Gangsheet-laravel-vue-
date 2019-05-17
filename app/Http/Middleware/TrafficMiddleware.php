<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TrafficMiddleware
{
    protected array $blockedIpAddresses = [
    ];

    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();
        $method = $request->method();
        $path = $request->path();

//        if (!Str::is('assets/*', $path)) {
//            Log::channel('traffic')->info("{$ip} - {$method} - {$path}");
//        }

        if ($this->isBlockedIp($request)) {
            abort(403, 'You are blocked from accessing this resource.');
        }

        return $next($request);
    }

    protected function isBlockedIp($request): bool
    {
        return in_array($request->ip(), $this->blockedIpAddresses);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DomainCheck
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        if (!str_ends_with($host, config('app.domain'))) {
            return redirect()->to(config('app.url'));
        }

        return $next($request);
    }
}

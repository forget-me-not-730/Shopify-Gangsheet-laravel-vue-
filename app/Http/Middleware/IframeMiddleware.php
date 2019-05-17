<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IframeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $domain = $request->get('domain');

        if ($domain) {

            $frameAncestors = [
                "https://$domain"
            ];

            if (str_starts_with($domain, 'www.')) {
                $domainWithoutWww = substr($domain, 4);
                $frameAncestors[] = "https://$domainWithoutWww";
            }

            $frameAncestors = array_merge(
                [
                    'http://127.0.0.1:*',
                    'http://localhost:*',
                    'https://localhost:*',
                    'http://wordpress.test:8088',
                ],
                $frameAncestors
            );

            if (count($frameAncestors)) {
                $frame_ancestors = implode(" ", $frameAncestors);

                $response->headers->set(
                    'Content-Security-Policy',
                    "frame-ancestors $frame_ancestors"
                );
            }
        }

        return $response;
    }
}

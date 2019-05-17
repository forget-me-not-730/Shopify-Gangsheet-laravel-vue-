<?php

namespace App\GangSheet\Middlewares;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $code = $request->get('code');
        $state = $request->get('state');

        if (empty($state) || empty($code)) {
            return response()->json([
                'error' => 'Invalid request'
            ], 400);
        }

        $params = json_decode(base64_decode($state), true);

        $signature = $params['signature'] ?? null;

        if (empty($signature)) {
            return response()->json([
                'error' => 'Invalid request'
            ], 400);
        }

        unset($params['signature']);

        if (!hash_equals(app_signature($params), $signature)) {
            return response()->json([
                'error' => 'Invalid request'
            ], 400);
        }

        $request->merge($params);

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;

class Authenticate extends Middleware
{
    public function handle($request, Closure $next, ...$guards)
    {
        try {

            $this->authenticate($request, $guards);

        } catch (\Exception $exception) {

            if($request->is('api*') and $exception instanceof AuthenticationException) {
                return response()->json([
                    'success' => false,
                    'error'   => 'Unauthorized.'
                ], 401);
            }

            $this->unauthenticated($request, $guards);
        }

        return $next($request);
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if($request->is('api/shop*')) {
            return null;
        }

        return $request->expectsJson() ? null : route('login');
    }
}

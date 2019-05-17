<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TrimStrings as Middleware;
use Illuminate\Support\Str;

class TrimStrings extends Middleware
{
    /**
     * The names of the attributes that should not be trimmed.
     *
     * @var array<int, string>
     */
    protected $except = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    protected function transform($key, $value)
    {
        if (Str::is('json.objects.*.text', $key)) {
            return $value;
        }

        return parent::transform($key, $value);
    }
}


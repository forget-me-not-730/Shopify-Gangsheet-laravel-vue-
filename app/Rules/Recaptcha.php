<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class Recaptcha implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (app()->environment('production')){
            $url = 'https://www.google.com/recaptcha/api/siteverify';

            $response = Http::asForm()->post($url, [
                'secret' => config('services.recaptcha.secret'),
                'response' => $value,
            ])->json();

            if($response['success'] && $response['score'] > 0.5) {
                return ;
            }
            $fail('Please verify that you are not a robot.');
        }
    }
}

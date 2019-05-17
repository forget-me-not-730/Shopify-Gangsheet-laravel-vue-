<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\Recaptcha;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:' . User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'captcha_token' => [new Recaptcha],
        ]);

        $user = User::create([
            'shop_uuid' => Str::uuid(),
            'type' => 'normal',
            'name' => $data['first_name'] . ' ' . $data['last_name'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('merchant.setting.index'))->with(['hashtag' => 'company']);
    }

    public function login(Request $request)
    {
        if (!$request->hasValidSignature()) {
            abort(403, 'Unauthorized access.');
        }

        $data = $request->validate([
            'user_id' => 'required',
            'page_url' => 'required'
        ]);

        $merchant = User::find($data['user_id']);

        auth()->login($merchant);

        return redirect()->to($data['page_url']);
    }
}

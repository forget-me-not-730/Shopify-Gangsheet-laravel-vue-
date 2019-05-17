<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\Process\Process;

class FrontController extends Controller
{
    public function home()
    {
        if (auth()->check()) {
            $user = auth()->user();

            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard.index');
            } else {
                return redirect()->route('merchant.dashboard.index');
            }
        }

        return redirect()->route('home');
    }

    public function index()
    {
        if (request()->host() === 'buildagangsheet.com') {
            return redirect()->to('https://thedripapps.com/');
        }

        if (auth()->check()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Front/Home');
    }

    public function policy()
    {
        return Inertia::render('Front/Policy');
    }
}

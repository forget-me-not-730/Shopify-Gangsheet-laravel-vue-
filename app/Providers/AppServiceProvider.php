<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
        Inertia::share('appDomain', config('app.domain'));

        Collection::macro('withoutAppends', function ($allowed = []) {
            return $this->map(function ($item) use ($allowed) {
                return $item->setAppends($allowed);
            });
        });

        Collection::macro('addAppends', function ($appends = []) {
            return $this->map(function ($item) use ($appends) {
                return $item->append($appends);
            });
        });
    }
}

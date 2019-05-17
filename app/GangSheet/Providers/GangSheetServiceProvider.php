<?php

namespace App\GangSheet\Providers;

use App\Extensions\Image\ImageManager;
use App\GangSheet\Services\CanvaService;
use App\GangSheet\Services\DripAppsService;
use App\GangSheet\Services\DropboxService;
use App\GangSheet\Services\GoogleService;
use Illuminate\Support\ServiceProvider;

class GangSheetServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('image', function () {
            return new ImageManager(config('image'));
        });

        $this->app->singleton('dripapps', function () {
            return new DripAppsService();
        });

        $this->app->singleton('canva', function () {
            return new CanvaService();
        });

        $this->app->singleton('google', function () {
            return new GoogleService();
        });

        $this->app->singleton('dropbox', function () {
            return new DropboxService();
        });
    }

    public function boot(): void
    {

    }
}

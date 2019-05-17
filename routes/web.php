<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [\App\Http\Controllers\FrontController::class, 'index'])->name('home');
Route::get('/home', [\App\Http\Controllers\FrontController::class, 'home'])->name('dashboard');
Route::get('/privacy-policy.html', [\App\Http\Controllers\FrontController::class, 'policy'])->name('policy');

Route::get('/doc/custom-integration', [\App\Http\Controllers\DocumentController::class, 'customIntegration'])->name('doc.custom-integration');

Route::get('/assets/thumbs/{image_name}', [\App\Http\Controllers\AssetController::class, 'getThumbImage'])->name('asset.thumb-image');

Route::get('mailable', function () {

    if (app()->environment('development')) {
        return new \App\Mail\SendEditRequest('c31bcd62-85c9-4aea-a65c-ec3baf975ea4');
    }

    abort(404);
});

require __DIR__ . '/auth.php';

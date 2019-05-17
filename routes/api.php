<?php

use App\Http\Controllers\Api\Woo\ShopController;
use App\Http\Controllers\Api\Woo\OrderController;
use App\Http\Controllers\Api\Woo\DesignController;
use App\Http\Controllers\Api\Woo\PaymentController;
use App\Http\Controllers\Api\Woo\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('woo/shop', [ShopController::class, 'register'])->name('shop.register');

Route::group(['middleware' => 'auth:sanctum', 'prefix' => 'woo', 'as' => 'woo.'], function () {

    Route::get('shop', [ShopController::class, 'shop'])->name('shop.get');
    Route::delete('shop', [ShopController::class, 'delete'])->name('shop.delete');
    Route::post('shop/settings', [ShopController::class, 'updateShopSettings'])->name('shop.settings');
    Route::post('shop/add-credit', [ShopController::class, 'addCredit'])->name('shop.add-credit');
    Route::post('report', [ShopController::class, 'report'])->name('shop.report');
    Route::get('shop/login-magiclink', [ShopController::class, 'loginMagicLink'])->name('shop.login-magiclink');

    Route::post('order', [OrderController::class, 'create'])->name('order.create');
    Route::get('orders', [OrderController::class, 'getOrders'])->name('order.all');

    Route::get('designs', [DesignController::class, 'getDesigns'])->name('designs');
    Route::get('design/{design_id}', [DesignController::class, 'getDesign'])->name('design.get');
    Route::get('design/{design_id}/status', [DesignController::class, 'getStatus'])->name('design.status');
    Route::get('design/{design_id}/download', [DesignController::class, 'download'])->name('design.download');
    Route::post('design/{design_id}/generate', [DesignController::class, 'generate'])->name('design.generate');

    Route::get('payments', [PaymentController::class, 'getPayments'])->name('payment.all');

    Route::get('products', [ProductController::class, 'getProducts'])->name('products.all');
    Route::post('product', [ProductController::class, 'updateProduct'])->name('product.update');
});

Route::group(['prefix' => 'v1', 'middleware' => 'auth:sanctum'], function () {
    Route::group(['prefix' => 'design'], function () {
        Route::get('{design_id}', [App\Http\Controllers\Api\V1\DesignController::class, 'getDesign']);
        Route::post('generate', [App\Http\Controllers\Api\V1\DesignController::class, 'generateDesign']);
    });
});

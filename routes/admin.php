<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MerchantController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\DesignController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\SettingController;
use \App\Http\Controllers\Admin;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'middleware' => ['auth', 'is_admin']], function () {
    Route::group(['prefix' => 'dashboard', 'as' => 'dashboard.'], function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        Route::get('/data', [DashboardController::class, 'data'])->name('data');
    });

    Route::group(['prefix' => 'merchants', 'as' => 'merchant.'], function () {
        Route::get('/', [MerchantController::class, 'index'])->name('index');
        Route::post('/', [MerchantController::class, 'create'])->name('create');
        Route::put('/update', [MerchantController::class, 'updateMerchant'])->name('update');
        Route::get('/{id}/products', [MerchantController::class, 'merchantProducts'])->name('products');
        Route::get('/impersonate/{merchant}', [MerchantController::class, 'impersonate'])->name('impersonate');
    });

    Route::group(['prefix' => 'orders', 'as' => 'order.'], function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/{order_id}/designs', [OrderController::class, 'designs'])->name('designs');
    });

    Route::group(['prefix' => 'designs', 'as' => 'design.'], function () {
        Route::get('/', [DesignController::class, 'index'])->name('index');
        Route::get('open/{design_id}', [DesignController::class, 'open'])->name('open');
        Route::get('preview/{design_id}', [DesignController::class, 'preview'])->name('preview');
        Route::post('/rebuild/{design_id}', [DesignController::class, 'rebuild'])->name('rebuild');
        Route::post('/add-note', [DesignController::class, 'addNote'])->name('add-note');
        Route::post('/get-log', [DesignController::class, 'getLog'])->name('get-log');
        Route::post('/clear-log', [DesignController::class, 'clearLog'])->name('clear-log');
    });

    Route::group(['as' => 'font.', 'prefix' => 'fonts'], function () {
        Route::get('/', [Admin\FontController::class, 'index'])->name('index');
        Route::post('/', [Admin\FontController::class, 'store'])->name('store');
        Route::post('/activate', [Admin\FontController::class, 'activate'])->name('activate');
        Route::post('/inactivate', [Admin\FontController::class, 'inactivate'])->name('inactivate');
        Route::post('/default', [Admin\FontController::class, 'default'])->name('default');
        Route::post('/updateType', [Admin\FontController::class, 'updateType'])->name('updateType');
    });

    Route::group(['prefix' => 'transactions', 'as' => 'transaction.'], function () {
        Route::get('/', [TransactionController::class, 'index'])->name('index');
    });

    Route::group(['prefix' => 'products', 'as' => 'product.'], function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
    });

    Route::group(['prefix' => 'settings', 'as' => 'setting.'], function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
    });
});

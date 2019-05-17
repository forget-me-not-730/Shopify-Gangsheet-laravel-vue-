<?php

use App\Http\Controllers\Merchant\ImageController;
use App\Http\Controllers\Merchant\CustomerController;
use App\Http\Controllers\Merchant\OrderController;
use App\Http\Controllers\Merchant\DashboardController;
use App\Http\Controllers\Merchant\PaymentController;
use App\Http\Controllers\Merchant\TransactionController;
use App\Http\Controllers\Merchant\ProductController;
use App\Http\Controllers\Merchant\SettingController;
use App\Http\Controllers\Merchant\DesignController;
use App\Http\Controllers\Merchant\FontController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

Route::get('merchant/login', [RegisteredUserController::class, 'login'])->middleware('signed')->name('merchant.login');

Route::group(['prefix' => 'merchant', 'as' => 'merchant.', 'middleware' => ['auth:web', 'merchant']], function () {

    Route::post('create-token', [DashboardController::class, 'createToken'])->name('create-token');
    Route::post('delete-token', [DashboardController::class, 'deleteToken'])->name('delete-token');
    Route::put('save-webhooks', [DashboardController::class, 'saveWebhooks'])->name('save-webhooks');

    Route::post('/upload-image', [DashboardController::class, 'uploadImage'])->name('upload-image');

    Route::group(['prefix' => 'dashboard', 'as' => 'dashboard.'], function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        Route::get('/api', [DashboardController::class, 'api'])->name('api');
        Route::get('/data', [DashboardController::class, 'data'])->name('data');
    });

    Route::group(['prefix' => 'orders', 'as' => 'order.'], function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::put('update', [OrderController::class, 'update'])->name('update');
        Route::get('/download/{id}', [OrderController::class, 'download'])->name('download');
        Route::get('/design/edit/{design}', [OrderController::class, 'editDesign'])->name('design.edit');
        Route::get('/design-status/{design}', [OrderController::class, 'getDesignStatus'])->name('design-status');
        Route::put('/archive', [OrderController::class, 'archive'])->name('archive');
    });

    Route::group(['prefix' => 'products', 'as' => 'product.'], function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/detail', [ProductController::class, 'detail'])->name('detail');
        Route::post('/save', [ProductController::class, 'save'])->name('save');
        Route::get('/pattern/{product_id}', [ProductController::class, 'pattern'])->middleware('builder')->name('pattern');
        Route::post('/pattern/{product_id}', [ProductController::class, 'savePattern'])->name('pattern.save');
    });

    Route::group(['prefix' => 'designs', 'as' => 'design.'], function () {
        Route::get('/', [DesignController::class, 'index'])->name('index');
        Route::get('/edit/{design_id}', [DesignController::class, 'editDesign'])->name('edit');
        Route::get('/download/{design_id}', [DesignController::class, 'download'])->name('download');
        Route::get('/pay-and-download/{design_id}', [DesignController::class, 'payAndDownload'])->name('pay-and-download');
        Route::get('/preview/{design_id}', [DesignController::class, 'preview'])->name('preview');
        Route::get('/thumbnail/{design_id}', [DesignController::class, 'thumbnail'])->name('thumbnail');
        Route::get('/status/{design_id}', [DesignController::class, 'status'])->name('status')->withoutMiddleware(['merchant', 'auth:web']);
        Route::get('/generate/{design_id}', [DesignController::class, 'generate'])->name('generate');
    });

    Route::group(['prefix' => 'images', 'as' => 'image.'], function () {
        Route::get('/', [ImageController::class, 'index'])->name('index');
        Route::post('/delete', [ImageController::class, 'deleteImages'])->name('delete');
        Route::post('/update-status', [ImageController::class, 'updateStatus'])->name('update-status');
        Route::post('reorder', [ImageController::class, 'reorderImages'])->name('reorder');

        Route::group(['prefix' => 'categories', 'as' => 'category.'], function () {
            Route::get('/images', [ImageController::class, 'getCategoryImages'])->name('images');
            Route::post('/create', [ImageController::class, 'createImageCategory'])->name('create');
            Route::post('/reorder', [ImageController::class, 'reorderImageCategory'])->name('reorder');
            Route::post('/update/{category_id}', [ImageController::class, 'updateImageCategory'])->name('update');
            Route::delete('/{category_id}', [ImageController::class, 'deleteImageCategory'])->name('delete');
        });

        Route::post('/move', [ImageController::class, 'moveGallery'])->name('move');
        Route::post('/update', [ImageController::class, 'updateImage'])->name('update');
        Route::get('/search', [ImageController::class, 'search'])->name('search');
        Route::get('/reload', [ImageController::class, 'reload'])->name('reload');
        Route::post('upload', [ImageController::class, 'uploadImage'])->name('upload');
        Route::put('/update/{image_id}', [ImageController::class, 'updateImageTitle'])->name('update-title');
        Route::post('/generate-watermark', [ImageController::class, 'generateWatermark'])->name('generate-watermark');

        Route::get('/tags', [ImageController::class, 'getUserTags'])->name('tags.get');
        Route::get('/tag/images', [ImageController::class, 'getTagImages'])->name('tag.images');
        Route::post('/tag', [ImageController::class, 'creatTagsAndCategory'])->name('tag.create');
        Route::post('/tags', [ImageController::class, 'deleteEmptyTags'])->name('tag.delete');
        Route::post('/add-tags', [ImageController::class, 'addImageTags'])->name('add-tags');
        Route::post('/remove-tags', [ImageController::class, 'removeTags'])->name('remove-tags');

        Route::get('/gallery', [ImageController::class, 'getGallery'])->name('gallery.get');
    });

    Route::group(['prefix' => 'customers', 'as' => 'customer.'], function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::post('/save', [CustomerController::class, 'saveCustomer'])->name('save');
    });

    Route::group(['prefix' => 'fonts', 'as' => 'font.'], function () {
        Route::get('/', [FontController::class, 'index'])->name('index');
        Route::post('/', [FontController::class, 'store'])->name('store');
        Route::get('/add', [FontController::class, 'add'])->name('add');
        Route::post('/delete', [FontController::class, 'delete'])->name('delete');
        Route::post('/default', [FontController::class, 'default'])->name('default');
    });

    Route::group(['prefix' => 'payments', 'as' => 'payment.'], function () {
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::get('/success', [PaymentController::class, 'paymentSuccess'])->name('success');
        Route::get('/credit', [PaymentController::class, 'getCredits'])->name('credit');
        Route::get('/credit-notification', [PaymentController::class, 'getCreditsNotification'])->name('credit-notification');
        Route::post('/add', [PaymentController::class, 'addCredits'])->name('add');
    });

    Route::group(['prefix' => 'transactions', 'as' => 'transaction.'], function () {
        Route::get('/', [TransactionController::class, 'index'])->name('index');
    });

    Route::group(['prefix' => 'settings', 'as' => 'setting.'], function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::post('/profile', [SettingController::class, 'updateProfile'])->name('profile');
        Route::get('/credit', [SettingController::class, 'getCredit'])->name('credit');
        Route::post('/credit', [SettingController::class, 'updateCredit'])->name('credit.update');
        Route::post('/company', [SettingController::class, 'updateCompany'])->name('company');
        Route::post('/builder', [SettingController::class, 'updateBuilder'])->name('builder');
        Route::post('/update-password', [SettingController::class, 'updatePassword'])->name('update-password');
        Route::get('/check-watermark-opacity-status', [SettingController::class, 'checkWatermarkOpacityStatus'])->name('check-watermark-opacity-status');
        Route::post('/apply-watermark-opacity', [SettingController::class, 'applyWatermarkOpacity'])->name('apply-watermark-opacity');

        Route::get('/dropbox', [SettingController::class, 'dropbox'])->name('dropbox');
        Route::post('/dropbox/revoke', [SettingController::class, 'revokeDropbox'])->name('revoke-dropbox');
    });

    Route::get('/support', [SettingController::class, 'support'])->name('support.index');
});

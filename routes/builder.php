<?php

use App\Http\Controllers\Customer\BuilderController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\ToolsController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'builder', 'as' => 'builder.', 'middleware' => ['builder']], function () {
    Route::group(['middleware' => 'throttle:5,1'], function () {
        Route::get('/view/{design_id}', [BuilderController::class, 'viewDesign'])->name('view');
    });

    Route::group(['middleware' => 'throttle:60,1'], function () {
        Route::get('/create/{slug}', [BuilderController::class, 'create'])->name('create');
        Route::get('/design/{design_id}', [BuilderController::class, 'design'])->name('design');

        Route::post('/upload-image', [BuilderController::class, 'uploadImage'])->name('upload-image');
        Route::post('/removebg-upload', [BuilderController::class, 'removeBackgroundAndUpload'])->name('removebg-upload');
        Route::get('/exported-canva-image', [BuilderController::class, 'getExportedCanvaImage'])->name('exported-canva-image');
        Route::post('/upload-canva-image', [BuilderController::class, 'uploadCanvaImage'])->name('upload-canva-image');
        Route::delete('/delete-canva-image', [BuilderController::class, 'deleteCanvaImage'])->name('delete-canva-image');
        Route::post('/save-design', [BuilderController::class, 'saveDesign'])->name('save-design');
        Route::post('/save-gang-sheet', [BuilderController::class, 'saveGangSheet'])->name('save-gang-sheet');
        Route::post('/save-draft-design', [BuilderController::class, 'saveDraftDesign'])->name('save-draft-design');
        Route::post('/upload-base64', [BuilderController::class, 'uploadBase64Image'])->name('upload-base64');

        Route::get('/shop/design/{design_id}', [BuilderController::class, 'getShopDesign'])->name('shop-design');
        Route::delete('/shop/design/{design_id}', [BuilderController::class, 'deleteDesign'])->name('delete-design');
        Route::post('/shop/design/{design_id}', [BuilderController::class, 'restoreDesign'])->name('restore-design');

        Route::post('/login', [BuilderController::class, 'login'])->name('login');
        Route::post('/register', [BuilderController::class, 'register'])->name('register');
        Route::post('/forgot-password', [BuilderController::class, 'forgotPassword'])->name('forgot-password');
        Route::get('/reset-password/{token}', [BuilderController::class, 'resetPassword'])->name('reset-password');
        Route::post('reset-password', [BuilderController::class, 'resetPasswordSave'])->name('reset-password-save');
        Route::post('/logout', [BuilderController::class, 'logout'])->name('logout');
        Route::get('/merchant-image-categories', [BuilderController::class, 'getMerchantImageCategories'])->name('shop-image-categories');
        Route::get('/merchant-images', [BuilderController::class, 'getMerchantImages'])->name('shop-images');

        Route::get('/images', [BuilderController::class, 'getCustomerImages'])->name('customer-images');
        Route::delete('/images', [BuilderController::class, 'deleteCustomerImages'])->name('delete-customer-images');
        Route::get('/designs', [BuilderController::class, 'getCustomerDesigns'])->name('customer-designs');
        Route::get('/customer/me/{customer_id}', [BuilderController::class, 'getCustomer'])->name('get-customer');
        Route::get('/customer/canva-designs', [BuilderController::class, 'getCustomerCanvaDesigns'])->name('customer-canva-designs');

        Route::post('/send-edit-request/{design}', [BuilderController::class, 'sendEditRequest'])->name('send-edit-request');
        Route::post('/approve-edit-request/{design}', [BuilderController::class, 'approveEditRequest'])->name('approve-edit-request');
        Route::post('/decline-edit-request/{design}', [BuilderController::class, 'declineEditRequest'])->name('decline-edit-request');

        Route::group(['prefix' => 'cart', 'as' => 'cart.', 'middleware' => 'builder'], function () {
            Route::post('/add', [CartController::class, 'add'])->name('add');
            Route::post('/update-quantity', [CartController::class, 'updateQuantity'])->name('update-quantity');
            Route::delete('/delete/{id}', [CartController::class, 'delete'])->name('delete');
            Route::post('/checkout', [CartController::class, 'checkout'])->name('checkout');
            Route::get('/{slug}', [CartController::class, 'index'])->name('index');
        });
    });
});

Route::group(['prefix' => 'builder', 'as' => 'builder.', 'middleware' => 'throttle:30,1'], function () {
    Route::post('/auto-nest', [ToolsController::class, 'autoNest'])->name('auto-nest');
    Route::post('/remove-background', [ToolsController::class, 'removeBackground'])->name('remove-background');
});

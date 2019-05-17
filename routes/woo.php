<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Woo\WooBuilderController;

Route::group(['prefix' => 'woo/builder', 'as' => 'woo.builder.', 'middleware' => ['builder', 'iframe']], function () {
    Route::get('/', [WooBuilderController::class, 'index'])->name('create');
    Route::post('/', [WooBuilderController::class, 'saveDesign'])->name('save-design');
    Route::get('/edit', [WooBuilderController::class, 'editDesign'])->name('edit');
});

Route::get('/gang-sheet-image/{design_id}/{file_name?}', [WooBuilderController::class, 'getGangSheetImage'])->middleware('signed')->name('woo.gang-sheet-image');
Route::get('/uploads/{fileName}', [WooBuilderController::class, 'getDesignImage'])->name('woo.get-design-image');
Route::get('/thumbnail/{fileName}', [WooBuilderController::class, 'getThumbnailImage'])->name('woo.get-thumbnail-image');
Route::get('/preview/{file_name}', [WooBuilderController::class, 'getPreviewImage'])->middleware('iframe')->name('woo.get-preview-image');

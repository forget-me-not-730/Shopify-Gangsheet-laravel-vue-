<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Gsb\BuilderController;

Route::group(['prefix' => 'gs/builder', 'as' => 'gs.builder.', 'middleware' => ['builder', 'iframe']], function () {
    Route::get('/', [BuilderController::class, 'index'])->name('create');
    Route::post('/', [BuilderController::class, 'saveDesign'])->name('save');
    Route::get('/edit', [BuilderController::class, 'editDesign'])->name('edit');

    Route::post('customer', [BuilderController::class, 'saveCustomer'])->name('customer.save');
});

Route::get('/gang-sheets/{fileName}', [BuilderController::class, 'getGangSheet'])->name('gs.gang-sheet');

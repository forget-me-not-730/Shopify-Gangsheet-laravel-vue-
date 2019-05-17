<?php

use App\Http\Controllers\Merchant\PaymentController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'webhook', 'as'=>'webhook.'], function (){
    Route::post('stripe', [PaymentController::class, 'webhook'])->name('stripe');
});

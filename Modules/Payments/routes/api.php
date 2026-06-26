<?php

use Illuminate\Support\Facades\Route;
use Modules\Payments\Http\Controllers\Api\PaymentController;

Route::prefix('v1')->group(function (): void {
    Route::middleware('auth:api')->group(function (): void {
        Route::get('payments', [PaymentController::class, 'index']);
        Route::post('payments/process', [PaymentController::class, 'process']);
    });
});

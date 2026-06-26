<?php

use Illuminate\Support\Facades\Route;
use Modules\Orders\Http\Controllers\Api\OrderController;

Route::prefix('v1')->group(function (): void {
    Route::middleware('auth:api')->group(function (): void {
        Route::get('orders', [OrderController::class, 'index']);
        Route::post('orders', [OrderController::class, 'store']);
        Route::put('orders/{id}', [OrderController::class, 'update']);
        Route::delete('orders/{id}', [OrderController::class, 'destroy']);
    });
});

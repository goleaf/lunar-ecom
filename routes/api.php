<?php

use App\Http\Controllers\CartController;
use Illuminate\Support\Facades\Route;

// Cart routes
Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'show']);
    Route::post('/add', [CartController::class, 'addItem']);
    Route::put('/line/{lineId}', [CartController::class, 'updateQuantity']);
    Route::delete('/line/{lineId}', [CartController::class, 'removeItem']);
    Route::post('/discount', [CartController::class, 'applyDiscount']);
    Route::delete('/discount', [CartController::class, 'removeDiscount']);
    Route::delete('/clear', [CartController::class, 'clear']);
    Route::get('/count', [CartController::class, 'getItemCount']);
    Route::get('/total', [CartController::class, 'getTotal']);
});
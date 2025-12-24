<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\CollectionController;
use App\Http\Controllers\Storefront\SearchController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CheckoutController;

Route::get('/', [ProductController::class, 'index'])->name('storefront.home');

Route::get('/products', [ProductController::class, 'index'])->name('storefront.products.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('storefront.products.show');

Route::get('/collections', [CollectionController::class, 'index'])->name('storefront.collections.index');
Route::get('/collections/{slug}', [CollectionController::class, 'show'])->name('storefront.collections.show');

Route::get('/search', [SearchController::class, 'index'])->name('storefront.search.index');

Route::prefix('cart')->name('storefront.cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/add', [CartController::class, 'add'])->name('add');
    Route::put('/{lineId}', [CartController::class, 'update'])->name('update');
    Route::delete('/{lineId}', [CartController::class, 'remove'])->name('remove');
    Route::delete('/', [CartController::class, 'clear'])->name('clear');
});

Route::prefix('checkout')->name('storefront.checkout.')->group(function () {
    Route::get('/', [CheckoutController::class, 'index'])->name('index');
    Route::post('/', [CheckoutController::class, 'store'])->name('store');
    Route::get('/confirmation/{order}', [CheckoutController::class, 'confirmation'])->name('confirmation');
});

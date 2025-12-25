<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\VariantManagementController;
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

// Variant management routes
Route::prefix('products/{product}/variants')->group(function () {
    Route::post('/generate', [VariantManagementController::class, 'generateVariants']);
    Route::post('/bulk-update', [VariantManagementController::class, 'bulkUpdate']);
    Route::get('/by-options', [VariantManagementController::class, 'getVariantByOptions']);
});

Route::prefix('variants/{variant}')->group(function () {
    Route::get('/price', [VariantManagementController::class, 'getPrice']);
    Route::get('/price-tiers', [VariantManagementController::class, 'getPriceTiers']);
    Route::get('/availability', [VariantManagementController::class, 'checkAvailability']);
});

// Attribute filtering routes
Route::prefix('filters')->group(function () {
    Route::get('/', [\App\Http\Controllers\AttributeFilterController::class, 'getFilters']);
    Route::post('/apply', [\App\Http\Controllers\AttributeFilterController::class, 'applyFilters']);
    Route::get('/count', [\App\Http\Controllers\AttributeFilterController::class, 'getFilterCount']);
});

// Pricing routes
Route::prefix('pricing')->name('api.pricing.')->group(function () {
    Route::post('/calculate', [\App\Http\Controllers\PricingController::class, 'calculate'])->name('calculate');
    Route::get('/tiers', [\App\Http\Controllers\PricingController::class, 'tiers'])->name('tiers');
    Route::get('/volume-discounts', [\App\Http\Controllers\PricingController::class, 'volumeDiscounts'])->name('volumeDiscounts');
});

// Pricing reports routes (admin)
Route::prefix('reports/pricing')->name('api.reports.pricing.')->middleware(['auth'])->group(function () {
    Route::get('/summary', [\App\Http\Controllers\PricingReportController::class, 'summary'])->name('summary');
    Route::get('/by-product', [\App\Http\Controllers\PricingReportController::class, 'byProduct'])->name('byProduct');
    Route::get('/by-customer-group', [\App\Http\Controllers\PricingReportController::class, 'byCustomerGroup'])->name('byCustomerGroup');
    Route::get('/by-region', [\App\Http\Controllers\PricingReportController::class, 'byRegion'])->name('byRegion');
    Route::get('/price-history', [\App\Http\Controllers\PricingReportController::class, 'priceHistory'])->name('priceHistory');
});

// Metrics and observability routes (admin)
Route::prefix('metrics')->name('api.metrics.')->middleware(['auth'])->group(function () {
    Route::get('/pricing', [\App\Http\Controllers\Api\MetricsController::class, 'pricing'])->name('pricing');
    Route::get('/cache-hit-ratio/{type?}', [\App\Http\Controllers\Api\MetricsController::class, 'cacheHitRatio'])->name('cacheHitRatio');
});

// Referral routes
Route::prefix('referrals')->name('api.referrals.')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/my-codes', [\App\Http\Controllers\Api\ReferralController::class, 'myCodes'])->name('myCodes');
    Route::get('/stats', [\App\Http\Controllers\Api\ReferralController::class, 'stats'])->name('stats');
    Route::get('/rewards', [\App\Http\Controllers\Api\ReferralController::class, 'rewards'])->name('rewards');
});

// Public referral routes
Route::prefix('referrals')->name('api.referrals.')->group(function () {
    Route::get('/code/{slug}', [\App\Http\Controllers\Api\ReferralController::class, 'getCode'])->name('getCode');
    Route::post('/track/{slug}', [\App\Http\Controllers\Api\ReferralController::class, 'trackClick'])->name('trackClick');
});
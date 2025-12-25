<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\CollectionController;
use App\Http\Controllers\Storefront\SearchController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\CurrencyController;
use App\Http\Controllers\Storefront\LanguageController;
use App\Http\Controllers\Storefront\BrandController;
use App\Http\Controllers\Storefront\MediaController;
use App\Http\Controllers\Storefront\VariantController;

// Dynamic robots.txt
Route::get('/robots.txt', [\App\Http\Controllers\Storefront\RobotsController::class, 'index'])->name('robots');

Route::get('/', [\App\Http\Controllers\Storefront\HomepageController::class, 'index'])->name('storefront.homepage');
Route::get('/home', [ProductController::class, 'index'])->name('storefront.home');

// Referral link tracking (must be before other routes to catch ref parameter)
Route::get('/ref/{ref}', function ($ref) {
    // Redirect to homepage with ref parameter
    return redirect()->route('storefront.homepage', ['ref' => $ref]);
})->name('referral.link');

Route::get('/products', [ProductController::class, 'index'])->name('storefront.products.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('storefront.products.show');

// Homepage
Route::get('/', [\App\Http\Controllers\Storefront\HomepageController::class, 'index'])->name('storefront.homepage');

Route::get('/collections', [CollectionController::class, 'index'])->name('storefront.collections.index');
Route::get('/collections/{collection}/filter', [\App\Http\Controllers\Storefront\CollectionFilterController::class, 'index'])->name('storefront.collections.filter');
Route::get('/collections/{slug}', [CollectionController::class, 'show'])->name('storefront.collections.show');

// Category routes with SEO-friendly URLs
Route::prefix('categories')->name('categories.')->group(function () {
    Route::get('/', [\App\Http\Controllers\CategoryController::class, 'roots'])->name('index');
    Route::get('/tree', [\App\Http\Controllers\CategoryController::class, 'tree'])->name('tree');
    Route::get('/flat', [\App\Http\Controllers\CategoryController::class, 'flatList'])->name('flat');
    Route::get('/navigation', [\App\Http\Controllers\CategoryController::class, 'navigation'])->name('navigation');
    Route::get('/{category}/breadcrumb', [\App\Http\Controllers\CategoryController::class, 'breadcrumb'])->name('breadcrumb');
    // SEO-friendly category URLs (supports nested paths like /categories/electronics/phones/smartphones)
    Route::get('/{path}', [\App\Http\Controllers\Storefront\CategoryController::class, 'show'])
        ->where('path', '.*')
        ->name('show');
});

Route::get('/search', [SearchController::class, 'index'])->name('storefront.search.index');
Route::get('/search/autocomplete', [SearchController::class, 'autocomplete'])->name('storefront.search.autocomplete');
Route::post('/search/track-click', [SearchController::class, 'trackClick'])->name('storefront.search.track-click');
Route::get('/search/popular', [SearchController::class, 'popularSearches'])->name('storefront.search.popular');
Route::get('/search/trending', [SearchController::class, 'trendingSearches'])->name('storefront.search.trending');

// Product Reviews
Route::prefix('products/{product}/reviews')->name('storefront.reviews.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Storefront\ReviewController::class, 'index'])->name('index');
    Route::post('/', [\App\Http\Controllers\Storefront\ReviewController::class, 'store'])->name('store')->middleware('auth');
    Route::get('/guidelines', [\App\Http\Controllers\Storefront\ReviewController::class, 'guidelines'])->name('guidelines');
});

Route::prefix('reviews/{review}')->name('storefront.reviews.')->group(function () {
    Route::post('/helpful', [\App\Http\Controllers\Storefront\ReviewController::class, 'markHelpful'])->name('helpful');
    Route::post('/report', [\App\Http\Controllers\Storefront\ReviewController::class, 'report'])->name('report');
});

// Admin Review Moderation
Route::prefix('admin/reviews')->name('admin.reviews.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/moderation', [\App\Http\Controllers\Admin\ReviewModerationController::class, 'index'])->name('moderation');
    Route::get('/{review}', [\App\Http\Controllers\Admin\ReviewModerationController::class, 'show'])->name('show');
    Route::post('/{review}/approve', [\App\Http\Controllers\Admin\ReviewModerationController::class, 'approve'])->name('approve');
    Route::post('/{review}/reject', [\App\Http\Controllers\Admin\ReviewModerationController::class, 'reject'])->name('reject');
    Route::post('/bulk-approve', [\App\Http\Controllers\Admin\ReviewModerationController::class, 'bulkApprove'])->name('bulk-approve');
    Route::post('/bulk-reject', [\App\Http\Controllers\Admin\ReviewModerationController::class, 'bulkReject'])->name('bulk-reject');
    Route::post('/{review}/response', [\App\Http\Controllers\Admin\ReviewModerationController::class, 'addResponse'])->name('add-response');
    Route::get('/statistics', [\App\Http\Controllers\Admin\ReviewModerationController::class, 'statistics'])->name('statistics');
});

// Admin Stock Management
Route::prefix('admin/stock')->name('admin.stock.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\StockManagementController::class, 'index'])->name('index');
    Route::get('/statistics', [\App\Http\Controllers\Admin\StockManagementController::class, 'statistics'])->name('statistics');
    Route::get('/movements', [\App\Http\Controllers\Admin\StockManagementController::class, 'movements'])->name('movements');
    Route::get('/variants/{variant}', [\App\Http\Controllers\Admin\StockManagementController::class, 'show'])->name('show');
    Route::post('/variants/{variant}/adjust', [\App\Http\Controllers\Admin\StockManagementController::class, 'adjustStock'])->name('adjust');
    Route::post('/variants/{variant}/transfer', [\App\Http\Controllers\Admin\StockManagementController::class, 'transferStock'])->name('transfer');
    Route::post('/alerts/{alert}/resolve', [\App\Http\Controllers\Admin\StockManagementController::class, 'resolveAlert'])->name('resolve-alert');
});

// Product Bundles
Route::prefix('bundles')->name('storefront.bundles.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Storefront\BundleController::class, 'index'])->name('index');
    Route::get('/{bundle:slug}', [\App\Http\Controllers\Storefront\BundleController::class, 'show'])->name('show');
    Route::post('/{bundle}/add-to-cart', [\App\Http\Controllers\Storefront\BundleController::class, 'addToCart'])->name('add-to-cart');
    Route::get('/{bundle}/calculate-price', [\App\Http\Controllers\Storefront\BundleController::class, 'calculatePrice'])->name('calculate-price');
});

// Product Comparison
Route::prefix('comparison')->name('storefront.comparison.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Storefront\ComparisonController::class, 'index'])->name('index');
    Route::get('/count', [\App\Http\Controllers\Storefront\ComparisonController::class, 'count'])->name('count');
    Route::post('/clear', [\App\Http\Controllers\Storefront\ComparisonController::class, 'clear'])->name('clear');
    Route::post('/products/{product}/add', [\App\Http\Controllers\Storefront\ComparisonController::class, 'add'])->name('add');
    Route::post('/products/{product}/remove', [\App\Http\Controllers\Storefront\ComparisonController::class, 'remove'])->name('remove');
    Route::get('/products/{product}/check', [\App\Http\Controllers\Storefront\ComparisonController::class, 'check'])->name('check');
});

// Stock Notifications
Route::prefix('stock-notifications')->name('storefront.stock-notifications.')->group(function () {
    Route::post('/variants/{variant}/subscribe', [\App\Http\Controllers\Storefront\StockNotificationController::class, 'subscribe'])->name('subscribe');
    Route::get('/variants/{variant}/check', [\App\Http\Controllers\Storefront\StockNotificationController::class, 'check'])->name('check');
    Route::get('/unsubscribe/{token}', [\App\Http\Controllers\Storefront\StockNotificationController::class, 'unsubscribe'])->name('unsubscribe');
    Route::get('/track/open/{metricId}', [\App\Http\Controllers\Storefront\StockNotificationTrackingController::class, 'trackOpen'])->name('track-open');
    Route::get('/track/click/{metricId}/{linkType}', [\App\Http\Controllers\Storefront\StockNotificationTrackingController::class, 'trackClick'])->name('track-click');
});

// Digital Product Downloads
Route::prefix('downloads')->name('storefront.downloads.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Storefront\DownloadController::class, 'index'])->name('index')->middleware('auth');
    Route::get('/{token}', [\App\Http\Controllers\Storefront\DownloadController::class, 'download'])->name('download');
    Route::get('/{token}/info', [\App\Http\Controllers\Storefront\DownloadController::class, 'info'])->name('info');
    Route::post('/{download}/resend-email', [\App\Http\Controllers\Storefront\DownloadController::class, 'resendEmail'])->name('resend-email')->middleware('auth')->where('download', '[0-9]+');
});

// Admin Product Import/Export
Route::prefix('admin/products')->name('admin.products.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/import-export', [\App\Http\Controllers\Admin\ProductImportController::class, 'index'])->name('import-export');
    Route::post('/import', [\App\Http\Controllers\Admin\ProductImportController::class, 'import'])->name('import');
    Route::get('/imports/{import}/status', [\App\Http\Controllers\Admin\ProductImportController::class, 'status'])->name('imports.status');
    Route::get('/imports/{import}/errors', [\App\Http\Controllers\Admin\ProductImportController::class, 'errors'])->name('imports.errors');
    Route::post('/imports/{import}/cancel', [\App\Http\Controllers\Admin\ProductImportController::class, 'cancel'])->name('imports.cancel');
    Route::post('/export', [\App\Http\Controllers\Admin\ProductImportController::class, 'export'])->name('export');
    Route::get('/import-template', [\App\Http\Controllers\Admin\ProductImportController::class, 'downloadTemplate'])->name('import-template');
});

// Admin Product Scheduling
Route::prefix('admin/products/{product}/schedules')->name('admin.products.schedules.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\ProductScheduleController::class, 'index'])->name('index');
    Route::post('/', [\App\Http\Controllers\Admin\ProductScheduleController::class, 'store'])->name('store');
    Route::put('/{schedule}', [\App\Http\Controllers\Admin\ProductScheduleController::class, 'update'])->name('update');
    Route::delete('/{schedule}', [\App\Http\Controllers\Admin\ProductScheduleController::class, 'destroy'])->name('destroy');
});

Route::prefix('admin/schedules')->name('admin.schedules.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/upcoming', [\App\Http\Controllers\Admin\ProductScheduleController::class, 'upcoming'])->name('upcoming');
    Route::get('/flash-sales', [\App\Http\Controllers\Admin\ProductScheduleController::class, 'activeFlashSales'])->name('flash-sales');
    Route::get('/calendar', [\App\Http\Controllers\Admin\ProductScheduleCalendarController::class, 'index'])->name('calendar');
    Route::get('/calendar/schedules', [\App\Http\Controllers\Admin\ProductScheduleCalendarController::class, 'getSchedules'])->name('calendar.schedules');
    Route::post('/bulk', [\App\Http\Controllers\Admin\BulkScheduleController::class, 'store'])->name('bulk.store');
    Route::get('/history', [\App\Http\Controllers\Admin\ProductScheduleController::class, 'history'])->name('history');
});

// Coming Soon Notifications
Route::prefix('coming-soon')->name('storefront.coming-soon.')->group(function () {
    Route::post('/products/{product}/subscribe', [\App\Http\Controllers\Storefront\ComingSoonController::class, 'subscribe'])->name('subscribe');
    Route::get('/unsubscribe/{token}', [\App\Http\Controllers\Storefront\ComingSoonController::class, 'unsubscribe'])->name('unsubscribe');
});

// Admin Product Badges
Route::prefix('admin/badges')->name('admin.badges.')->middleware(['auth', 'admin'])->group(function () {
    Route::resource('/', \App\Http\Controllers\Admin\ProductBadgeController::class)->parameters(['' => 'badge']);
    Route::post('/{badge}/preview', [\App\Http\Controllers\Admin\ProductBadgeController::class, 'preview'])->name('preview');
    Route::get('/{badge}/performance', [\App\Http\Controllers\Admin\ProductBadgeController::class, 'performance'])->name('performance');
    
    // Badge Rules
    Route::prefix('rules')->name('rules.')->group(function () {
        Route::resource('/', \App\Http\Controllers\Admin\ProductBadgeRuleController::class)->parameters(['' => 'rule']);
        Route::post('/{rule}/test', [\App\Http\Controllers\Admin\ProductBadgeRuleController::class, 'test'])->name('test');
    });
    
    // Product Badge Assignments
    Route::prefix('products/{product}')->name('products.')->group(function () {
        Route::post('/assign', [\App\Http\Controllers\Admin\ProductBadgeAssignmentController::class, 'assign'])->name('assign');
        Route::delete('/{badge}/remove', [\App\Http\Controllers\Admin\ProductBadgeAssignmentController::class, 'remove'])->name('remove');
        Route::get('/', [\App\Http\Controllers\Admin\ProductBadgeAssignmentController::class, 'index'])->name('index');
    });
});

// Admin Product Badges
Route::prefix('admin/products/badges')->name('admin.products.badges.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\ProductBadgeController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Admin\ProductBadgeController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Admin\ProductBadgeController::class, 'store'])->name('store');
    Route::get('/{badge}/edit', [\App\Http\Controllers\Admin\ProductBadgeController::class, 'edit'])->name('edit');
    Route::put('/{badge}', [\App\Http\Controllers\Admin\ProductBadgeController::class, 'update'])->name('update');
    Route::delete('/{badge}', [\App\Http\Controllers\Admin\ProductBadgeController::class, 'destroy'])->name('destroy');
    Route::post('/process-auto-assignment', [\App\Http\Controllers\Admin\ProductBadgeController::class, 'processAutoAssignment'])->name('process-auto-assignment');
});

// Product Badge Assignment
Route::prefix('admin/products/{product}/badges')->name('admin.products.product-badges.')->middleware(['auth', 'admin'])->group(function () {
    Route::post('/assign', [\App\Http\Controllers\Admin\ProductBadgeController::class, 'assignToProduct'])->name('assign');
    Route::post('/remove', [\App\Http\Controllers\Admin\ProductBadgeController::class, 'removeFromProduct'])->name('remove');
});

// Admin Collection Management
Route::prefix('admin/collections/{collection}')->name('admin.collections.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/manage', [\App\Http\Controllers\Admin\CollectionManagementController::class, 'show'])->name('manage');
    Route::put('/settings', [\App\Http\Controllers\Admin\CollectionManagementController::class, 'updateSettings'])->name('update-settings');
    Route::post('/products', [\App\Http\Controllers\Admin\CollectionManagementController::class, 'addProduct'])->name('add-product');
    Route::delete('/products/{product}', [\App\Http\Controllers\Admin\CollectionManagementController::class, 'removeProduct'])->name('remove-product');
    
    // Smart Collection Rules
    Route::get('/smart-rules', [\App\Http\Controllers\Admin\SmartCollectionRuleController::class, 'index'])->name('smart-rules');
    Route::post('/smart-rules', [\App\Http\Controllers\Admin\SmartCollectionRuleController::class, 'store'])->name('smart-rules.store');
    Route::put('/smart-rules/{rule}', [\App\Http\Controllers\Admin\SmartCollectionRuleController::class, 'update'])->name('smart-rules.update');
    Route::delete('/smart-rules/{rule}', [\App\Http\Controllers\Admin\SmartCollectionRuleController::class, 'destroy'])->name('smart-rules.destroy');
    Route::get('/smart-rules/preview', [\App\Http\Controllers\Admin\SmartCollectionRuleController::class, 'preview'])->name('smart-rules.preview');
    Route::post('/smart-rules/process', [\App\Http\Controllers\Admin\SmartCollectionRuleController::class, 'process'])->name('smart-rules.process');
    Route::post('/reorder', [\App\Http\Controllers\Admin\CollectionManagementController::class, 'reorderProducts'])->name('reorder');
    Route::post('/process-auto-assignment', [\App\Http\Controllers\Admin\CollectionManagementController::class, 'processAutoAssignment'])->name('process-auto-assignment');
    Route::get('/statistics', [\App\Http\Controllers\Admin\CollectionManagementController::class, 'statistics'])->name('statistics');
});


// Product Comparison
Route::prefix('comparison')->name('storefront.comparison.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Storefront\ComparisonController::class, 'index'])->name('index');
    Route::post('/add', [\App\Http\Controllers\Storefront\ComparisonController::class, 'add'])->name('add');
    Route::post('/remove', [\App\Http\Controllers\Storefront\ComparisonController::class, 'remove'])->name('remove');
    Route::post('/clear', [\App\Http\Controllers\Storefront\ComparisonController::class, 'clear'])->name('clear');
    Route::get('/count', [\App\Http\Controllers\Storefront\ComparisonController::class, 'count'])->name('count');
    Route::get('/check', [\App\Http\Controllers\Storefront\ComparisonController::class, 'check'])->name('check');
    Route::get('/products', [\App\Http\Controllers\Storefront\ComparisonController::class, 'products'])->name('products');
    Route::get('/export-pdf', [\App\Http\Controllers\Storefront\ComparisonController::class, 'exportPdf'])->name('export-pdf');
});

// Admin Bundle Management
Route::prefix('admin/bundles')->name('admin.bundles.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\BundleController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Admin\BundleController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Admin\BundleController::class, 'store'])->name('store');
    Route::get('/{bundle}/edit', [\App\Http\Controllers\Admin\BundleController::class, 'edit'])->name('edit');
    Route::put('/{bundle}', [\App\Http\Controllers\Admin\BundleController::class, 'update'])->name('update');
    Route::delete('/{bundle}', [\App\Http\Controllers\Admin\BundleController::class, 'destroy'])->name('destroy');
});

// Admin Order Status Management
Route::prefix('admin/orders')->name('admin.orders.')->middleware(['auth'])->group(function () {
    Route::get('/statuses', [\App\Http\Controllers\Admin\OrderStatusController::class, 'getAvailableStatuses'])->name('statuses');
    Route::get('/status/{status}', [\App\Http\Controllers\Admin\OrderStatusController::class, 'getOrdersByStatus'])->name('by-status');
    Route::post('/{order}/status', [\App\Http\Controllers\Admin\OrderStatusController::class, 'updateStatus'])->name('update-status');
    Route::get('/{order}/status-history', [\App\Http\Controllers\Admin\OrderStatusController::class, 'getStatusHistory'])->name('status-history');
    Route::get('/{order}/history', [\App\Http\Controllers\Admin\OrderStatusController::class, 'getOrderHistory'])->name('history');
});

// Admin Checkout Lock Management
Route::prefix('admin/checkout-locks')->name('admin.checkout-locks.')->middleware(['auth'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\CheckoutLockController::class, 'index'])->name('index');
    Route::get('/statistics', [\App\Http\Controllers\Admin\CheckoutLockController::class, 'statistics'])->name('statistics');
    Route::get('/{checkoutLock}', [\App\Http\Controllers\Admin\CheckoutLockController::class, 'show'])->name('show');
    Route::get('/{checkoutLock}/json', [\App\Http\Controllers\Admin\CheckoutLockController::class, 'showJson'])->name('show.json');
    Route::post('/{checkoutLock}/release', [\App\Http\Controllers\Admin\CheckoutLockController::class, 'release'])->name('release');
});

// Product Recommendations
Route::prefix('products/{product}/recommendations')->name('storefront.recommendations.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Storefront\RecommendationController::class, 'index'])->name('index');
    Route::post('/track-view', [\App\Http\Controllers\Storefront\RecommendationController::class, 'trackView'])->name('track-view');
    Route::get('/frequently-bought-together', [\App\Http\Controllers\Storefront\RecommendationController::class, 'frequentlyBoughtTogether'])->name('frequently-bought-together');
});

Route::prefix('recommendations')->name('storefront.recommendations.')->group(function () {
    Route::post('/track-click', [\App\Http\Controllers\Storefront\RecommendationController::class, 'trackClick'])->name('track-click');
    Route::get('/personalized', [\App\Http\Controllers\Storefront\RecommendationController::class, 'personalized'])->name('personalized');
});

// Stock Reservations (for checkout)
Route::prefix('stock-reservations')->name('storefront.stock-reservations.')->group(function () {
    Route::post('/reserve', [\App\Http\Controllers\Storefront\StockReservationController::class, 'reserve'])->name('reserve');
    Route::post('/release', [\App\Http\Controllers\Storefront\StockReservationController::class, 'release'])->name('release');
});

// Product Bundles
Route::prefix('bundles/{bundle}')->name('storefront.bundles.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Storefront\BundleController::class, 'show'])->name('show');
    Route::post('/calculate-price', [\App\Http\Controllers\Storefront\BundleController::class, 'calculatePrice'])->name('calculate-price');
    Route::post('/check-availability', [\App\Http\Controllers\Storefront\BundleController::class, 'checkAvailability'])->name('check-availability');
    Route::post('/validate-selection', [\App\Http\Controllers\Storefront\BundleController::class, 'validateSelection'])->name('validate-selection');
    Route::post('/add-to-cart', [\App\Http\Controllers\Storefront\BundleController::class, 'addToCart'])->name('add-to-cart');
    Route::get('/available-products', [\App\Http\Controllers\Storefront\BundleController::class, 'getAvailableProducts'])->name('available-products');
});

// Admin Inventory Management
Route::prefix('admin/inventory')->name('admin.inventory.')->middleware(['auth'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\InventoryController::class, 'index'])->name('index');
    Route::post('/adjust', [\App\Http\Controllers\Admin\InventoryController::class, 'adjust'])->name('adjust');
    Route::post('/transfer', [\App\Http\Controllers\Admin\InventoryController::class, 'transfer'])->name('transfer');
    Route::get('/check-availability', [\App\Http\Controllers\Admin\InventoryController::class, 'checkAvailability'])->name('check-availability');
    Route::get('/purchase-order-suggestions', [\App\Http\Controllers\Admin\InventoryController::class, 'purchaseOrderSuggestions'])->name('purchase-order-suggestions');
    Route::post('/barcode-scan', [\App\Http\Controllers\Admin\InventoryController::class, 'barcodeScan'])->name('barcode-scan');
});

// Admin Inventory Reports
Route::prefix('admin/inventory/reports')->name('admin.inventory.reports.')->middleware(['auth'])->group(function () {
    Route::get('/stock-valuation', [\App\Http\Controllers\Admin\InventoryReportController::class, 'stockValuation'])->name('stock-valuation');
    Route::get('/inventory-turnover', [\App\Http\Controllers\Admin\InventoryReportController::class, 'inventoryTurnover'])->name('inventory-turnover');
    Route::get('/dead-stock', [\App\Http\Controllers\Admin\InventoryReportController::class, 'deadStock'])->name('dead-stock');
    Route::get('/fast-moving-items', [\App\Http\Controllers\Admin\InventoryReportController::class, 'fastMovingItems'])->name('fast-moving-items');
});

// Admin Inventory Import/Export
Route::prefix('admin/inventory')->name('admin.inventory.')->middleware(['auth'])->group(function () {
    Route::get('/export', [\App\Http\Controllers\Admin\InventoryImportExportController::class, 'export'])->name('export');
    Route::post('/import', [\App\Http\Controllers\Admin\InventoryImportExportController::class, 'import'])->name('import');
});

// Admin Bundle Management
Route::prefix('admin/bundles')->name('admin.bundles.')->middleware(['auth'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\BundleController::class, 'index'])->name('index');
    Route::post('/', [\App\Http\Controllers\Admin\BundleController::class, 'store'])->name('store');
    Route::get('/{bundle}', [\App\Http\Controllers\Admin\BundleController::class, 'show'])->name('show');
    Route::put('/{bundle}', [\App\Http\Controllers\Admin\BundleController::class, 'update'])->name('update');
    Route::get('/{bundle}/analytics', [\App\Http\Controllers\Admin\BundleController::class, 'analytics'])->name('analytics');
    Route::post('/{bundle}/items', [\App\Http\Controllers\Admin\BundleController::class, 'addItem'])->name('items.store');
    Route::put('/{bundle}/items/{item}', [\App\Http\Controllers\Admin\BundleController::class, 'updateItem'])->name('items.update');
    Route::delete('/{bundle}/items/{item}', [\App\Http\Controllers\Admin\BundleController::class, 'removeItem'])->name('items.destroy');
});

// Admin Comparison Analytics
Route::prefix('admin/comparison-analytics')->name('admin.comparison-analytics.')->middleware(['auth'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\ComparisonAnalyticsController::class, 'index'])->name('index');
});

// Admin Stock Notifications
Route::prefix('admin/stock-notifications')->name('admin.stock-notifications.')->middleware(['auth'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\StockNotificationController::class, 'index'])->name('index');
    Route::get('/variants/{variant}/subscriptions', [\App\Http\Controllers\Admin\StockNotificationController::class, 'subscriptions'])->name('subscriptions');
    Route::get('/variants/{variant}/metrics', [\App\Http\Controllers\Admin\StockNotificationController::class, 'metrics'])->name('metrics');
});

// Search Analytics (admin/analytics endpoints)
Route::prefix('search-analytics')->name('storefront.search-analytics.')->group(function () {
    Route::get('/statistics', [\App\Http\Controllers\Storefront\SearchAnalyticsController::class, 'statistics'])->name('statistics');
    Route::get('/zero-results', [\App\Http\Controllers\Storefront\SearchAnalyticsController::class, 'zeroResults'])->name('zero-results');
    Route::get('/trends', [\App\Http\Controllers\Storefront\SearchAnalyticsController::class, 'trends'])->name('trends');
    Route::get('/most-clicked', [\App\Http\Controllers\Storefront\SearchAnalyticsController::class, 'mostClicked'])->name('most-clicked');
});

Route::prefix('cart')->name('storefront.cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::get('/summary', [CartController::class, 'summary'])->name('summary');
    Route::get('/pricing', [CartController::class, 'pricing'])->name('pricing');
    Route::post('/add', [CartController::class, 'add'])->name('add');
    Route::put('/{lineId}', [CartController::class, 'update'])->name('update');
    Route::delete('/{lineId}', [CartController::class, 'remove'])->name('remove');
    Route::delete('/', [CartController::class, 'clear'])->name('clear');
    Route::post('/discount/apply', [CartController::class, 'applyDiscount'])->name('discount.apply');
    Route::post('/discount/remove', [CartController::class, 'removeDiscount'])->name('discount.remove');
});

Route::prefix('checkout')->name('storefront.checkout.')
    ->middleware(['throttle.checkout'])
    ->group(function () {
        Route::get('/', [CheckoutController::class, 'index'])->name('index');
        Route::post('/', [CheckoutController::class, 'store'])->name('store');
        Route::get('/confirmation/{order}', [CheckoutController::class, 'confirmation'])->name('confirmation');
        Route::get('/status', [\App\Http\Controllers\Storefront\CheckoutStatusController::class, 'status'])->name('status');
        Route::post('/cancel', [\App\Http\Controllers\Storefront\CheckoutStatusController::class, 'cancel'])->name('cancel');
    });

Route::prefix('currency')->name('storefront.currency.')->group(function () {
    Route::get('/', [CurrencyController::class, 'index'])->name('index');
    Route::post('/switch', [CurrencyController::class, 'switch'])->name('switch');
    Route::get('/current', [CurrencyController::class, 'current'])->name('current');
});

Route::prefix('addresses')->name('storefront.addresses.')->middleware('auth')->group(function () {
    Route::get('/', [\App\Http\Controllers\Storefront\AddressController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Storefront\AddressController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Storefront\AddressController::class, 'store'])->name('store');
    Route::get('/{address}/edit', [\App\Http\Controllers\Storefront\AddressController::class, 'edit'])->name('edit');
    Route::put('/{address}', [\App\Http\Controllers\Storefront\AddressController::class, 'update'])->name('update');
    Route::delete('/{address}', [\App\Http\Controllers\Storefront\AddressController::class, 'destroy'])->name('destroy');
    Route::post('/{address}/default-shipping', [\App\Http\Controllers\Storefront\AddressController::class, 'setDefaultShipping'])->name('set-default-shipping');
    Route::post('/{address}/default-billing', [\App\Http\Controllers\Storefront\AddressController::class, 'setDefaultBilling'])->name('set-default-billing');
});

Route::prefix('language')->name('storefront.language.')->group(function () {
    Route::get('/', [LanguageController::class, 'index'])->name('index');
    Route::post('/switch', [LanguageController::class, 'switch'])->name('switch');
    Route::get('/current', [LanguageController::class, 'current'])->name('current');
});

Route::prefix('brands')->name('storefront.brands.')->group(function () {
    Route::get('/', [BrandController::class, 'index'])->name('index');
    Route::get('/api', [BrandController::class, 'api'])->name('api');
    Route::get('/{slug}', [BrandController::class, 'show'])->name('show');
});

Route::prefix('media')->name('storefront.media.')->middleware(['web'])->group(function () {
    Route::post('/product/{productId}/upload', [MediaController::class, 'uploadProductImages'])->name('product.upload');
    Route::post('/collection/{collectionId}/upload', [MediaController::class, 'uploadCollectionImages'])->name('collection.upload');
    Route::post('/brand/{brandId}/logo', [MediaController::class, 'uploadBrandLogo'])->name('brand.logo');
    Route::delete('/{modelType}/{modelId}/{mediaId}', [MediaController::class, 'deleteMedia'])->name('delete');
    Route::post('/{modelType}/{modelId}/reorder', [MediaController::class, 'reorderMedia'])->name('reorder');
});

Route::prefix('products/{product}/variants')->name('storefront.variants.')->middleware(['web'])->group(function () {
    Route::get('/', [VariantController::class, 'index'])->name('index');
    Route::post('/generate', [VariantController::class, 'generate'])->name('generate');
    Route::post('/', [VariantController::class, 'store'])->name('store');
});

Route::prefix('variants')->name('storefront.variants.')->middleware(['web'])->group(function () {
    Route::get('/{variant}', [VariantController::class, 'show'])->name('show');
    Route::put('/{variant}', [VariantController::class, 'update'])->name('update');
    Route::delete('/{variant}', [VariantController::class, 'destroy'])->name('destroy');
    Route::post('/{variant}/stock', [VariantController::class, 'updateStock'])->name('stock.update');
    Route::post('/{variant}/images', [VariantController::class, 'attachImage'])->name('images.attach');
    Route::delete('/{variant}/images/{mediaId}', [VariantController::class, 'detachImage'])->name('images.detach');
    Route::post('/{variant}/images/primary', [VariantController::class, 'setPrimaryImage'])->name('images.primary');
});

// GDPR Compliance Routes
Route::prefix('gdpr')->name('gdpr.')->group(function () {
    // Cookie Consent
    Route::prefix('cookie-consent')->name('cookie-consent.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Gdpr\CookieConsentController::class, 'show'])->name('show');
        Route::post('/', [\App\Http\Controllers\Gdpr\CookieConsentController::class, 'store'])->name('store');
        Route::put('/', [\App\Http\Controllers\Gdpr\CookieConsentController::class, 'update'])->name('update');
    });

    // Privacy Policy
    Route::prefix('privacy-policy')->name('privacy-policy.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Gdpr\PrivacyPolicyController::class, 'show'])->name('show');
        Route::get('/versions', [\App\Http\Controllers\Gdpr\PrivacyPolicyController::class, 'index'])->name('index');
        Route::get('/version/{version}', [\App\Http\Controllers\Gdpr\PrivacyPolicyController::class, 'version'])->name('version');
    });

    // Privacy Settings (requires authentication)
    Route::prefix('privacy-settings')->name('privacy-settings.')->middleware('auth')->group(function () {
        Route::get('/', [\App\Http\Controllers\Gdpr\PrivacySettingsController::class, 'index'])->name('index');
        Route::put('/', [\App\Http\Controllers\Gdpr\PrivacySettingsController::class, 'update'])->name('update');
    });

    // GDPR Requests
    Route::prefix('request')->name('request.')->group(function () {
        Route::get('/create', function () {
            $type = request()->get('type', 'export');
            return view('gdpr.request-form', ['type' => $type]);
        })->name('create');
        Route::post('/', [\App\Http\Controllers\Gdpr\GdprRequestController::class, 'store'])->name('store');
        Route::get('/verify/{token}', [\App\Http\Controllers\Gdpr\GdprRequestController::class, 'verify'])->name('verify');
        Route::get('/download/{token}', [\App\Http\Controllers\Gdpr\GdprRequestController::class, 'download'])->name('download');
    });
});

// Admin Stock Notifications
Route::prefix('admin/stock-notifications')->name('admin.stock-notifications.')->middleware(['auth:staff'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\StockNotificationController::class, 'index'])->name('index');
    Route::get('/variants/{variant}/subscriptions', [\App\Http\Controllers\Admin\StockNotificationController::class, 'subscriptions'])->name('variant.subscriptions');
    Route::get('/variants/{variant}/metrics', [\App\Http\Controllers\Admin\StockNotificationController::class, 'metrics'])->name('variant.metrics');
});

// Admin Product Import/Export
Route::prefix('admin/products/import')->name('admin.products.import.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\ProductImportController::class, 'index'])->name('index');
    Route::post('/preview', [\App\Http\Controllers\Admin\ProductImportController::class, 'preview'])->name('preview');
    Route::post('/import', [\App\Http\Controllers\Admin\ProductImportController::class, 'import'])->name('import');
    Route::get('/{id}/status', [\App\Http\Controllers\Admin\ProductImportController::class, 'status'])->name('status');
    Route::get('/{id}/report', [\App\Http\Controllers\Admin\ProductImportController::class, 'report'])->name('report');
    Route::post('/{id}/rollback', [\App\Http\Controllers\Admin\ProductImportController::class, 'rollback'])->name('rollback');
    Route::get('/template/download', [\App\Http\Controllers\Admin\ProductImportController::class, 'downloadTemplate'])->name('template.download');
});

Route::prefix('admin/products/export')->name('admin.products.export.')->middleware(['auth', 'admin'])->group(function () {
    Route::post('/', [\App\Http\Controllers\Admin\ProductExportController::class, 'export'])->name('export');
    Route::get('/columns', [\App\Http\Controllers\Admin\ProductExportController::class, 'columns'])->name('columns');
});

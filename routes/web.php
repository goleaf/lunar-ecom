<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Frontend\ProductIndex;
use App\Livewire\Frontend\ProductShow;
use App\Livewire\Frontend\Pages\Homepage;
use App\Livewire\Frontend\Pages\CollectionsIndex;
use App\Livewire\Frontend\Pages\CollectionShow;
use App\Livewire\Frontend\Pages\CategoriesIndex;
use App\Livewire\Frontend\Pages\CategoryShow;
use App\Livewire\Frontend\Pages\BrandsIndex;
use App\Livewire\Frontend\Pages\BrandShow;
use App\Livewire\Frontend\Pages\SearchIndex;
use App\Livewire\Frontend\Pages\ProductReviewsIndex;
use App\Livewire\Frontend\Pages\ReviewGuidelines;
use App\Livewire\Frontend\Pages\BundlesIndex;
use App\Livewire\Frontend\Pages\BundleShow;
use App\Livewire\Frontend\Pages\CartIndex;
use App\Livewire\Frontend\Pages\CheckoutIndex;
use App\Livewire\Frontend\Pages\CheckoutConfirmation;
use App\Livewire\Frontend\Pages\DownloadsIndex;
use App\Livewire\Frontend\Pages\ProductQuestionsIndex;
use App\Livewire\Frontend\Pages\StockNotificationUnsubscribe;
use App\Livewire\Frontend\Pages\ComingSoonUnsubscribed;
use App\Livewire\Frontend\Pages\AddressesIndex;
use App\Livewire\Frontend\Pages\AddressCreate;
use App\Livewire\Frontend\Pages\AddressEdit;
use App\Livewire\Frontend\Pages\Auth\Login as FrontendLogin;
use App\Livewire\Frontend\Pages\Gdpr\PrivacyPolicyShow;
use App\Livewire\Frontend\Pages\Gdpr\PrivacyPolicyVersions;
use App\Livewire\Frontend\Pages\Gdpr\PrivacyPolicyVersion;
use App\Livewire\Frontend\Pages\Gdpr\PrivacySettings;
use App\Livewire\Frontend\Pages\Gdpr\RequestCreate as GdprRequestCreate;
use App\Http\Controllers\Frontend\CollectionController;
use App\Http\Controllers\Frontend\SearchController;
use App\Http\Controllers\Frontend\CartController;
use App\Http\Controllers\Frontend\CheckoutController;
use App\Http\Controllers\Frontend\CurrencyController;
use App\Http\Controllers\Frontend\LanguageController;
use App\Http\Controllers\Frontend\ReferralLandingController;
use App\Http\Controllers\Frontend\BrandController;
use App\Http\Controllers\Frontend\MediaController;
use App\Http\Controllers\Frontend\VariantController;
use App\Livewire\Frontend\ReferralLandingPage;

// Legacy admin URLs redirect to `/admin/*`.
Route::redirect('/lunar', '/admin', 301);
Route::redirect('/lunar/login', '/admin/login', 301);
Route::get('/lunar/{any}', function (string $any) {
    return redirect('/admin/'.ltrim($any, '/'), 301);
})->where('any', '.*');

// Dynamic robots.txt
Route::get('/robots.txt', [\App\Http\Controllers\Frontend\RobotsController::class, 'index'])->name('robots');

// Customer login (required for auth middleware redirects)
Route::get('/login', FrontendLogin::class)->name('login');

Route::get('/', Homepage::class)->name('frontend.homepage');
Route::get('/home', ProductIndex::class)->name('frontend.home');

// Referral link tracking (must be before other routes to catch ref parameter)
Route::get('/ref/{ref}', function ($ref) {
    // Backward compatible short link -> localized landing page
    return redirect()->route('frontend.referrals.landing', [
        'locale' => app()->getLocale(),
        'code' => $ref,
    ] + request()->query());
})->name('referral.link');

// Localized referral landing page
Route::get('/r/{code}', [ReferralLandingController::class, 'redirectToLocalized'])
    ->where('code', '[A-Za-z0-9_-]+')
    ->name('frontend.referrals.redirect');

Route::get('/{locale}/r/{code}', ReferralLandingPage::class)
    ->where([
        'locale' => '[a-zA-Z]{2}',
        'code' => '[A-Za-z0-9_-]+',
    ])
    ->name('frontend.referrals.landing');

Route::get('/products', ProductIndex::class)->name('frontend.products.index');
Route::get('/products/{slug}', ProductShow::class)
    ->middleware('canonical.product-slug')
    ->name('frontend.products.show');

Route::get('/collections', CollectionsIndex::class)->name('frontend.collections.index');
Route::get('/collections/{collection}/filter', [\App\Http\Controllers\Frontend\CollectionFilterController::class, 'index'])->name('frontend.collections.filter');
Route::get('/collections/{slug}', CollectionShow::class)
    ->middleware('canonical.collection-slug')
    ->name('frontend.collections.show');

// Category routes with SEO-friendly URLs
Route::prefix('categories')->name('categories.')->group(function () {
    Route::get('/', CategoriesIndex::class)->name('index');
    Route::get('/tree', [\App\Http\Controllers\CategoryController::class, 'tree'])->name('tree');
    Route::get('/flat', [\App\Http\Controllers\CategoryController::class, 'flatList'])->name('flat');
    Route::get('/navigation', [\App\Http\Controllers\CategoryController::class, 'navigation'])->name('navigation');
    Route::get('/{category}/breadcrumb', [\App\Http\Controllers\CategoryController::class, 'breadcrumb'])->name('breadcrumb');
    // SEO-friendly category URLs (supports nested paths like /categories/electronics/phones/smartphones)
    Route::get('/{path}', CategoryShow::class)
        ->where('path', '.*')
        ->name('show');
});

Route::get('/search', SearchIndex::class)->name('frontend.search.index');
Route::get('/search/autocomplete', [SearchController::class, 'autocomplete'])->name('frontend.search.autocomplete');
Route::post('/search/track-click', [SearchController::class, 'trackClick'])->name('frontend.search.track-click');
Route::get('/search/popular', [SearchController::class, 'popularSearches'])->name('frontend.search.popular');
Route::get('/search/trending', [SearchController::class, 'trendingSearches'])->name('frontend.search.trending');

// Product Reviews
Route::prefix('products/{product}/reviews')->name('frontend.reviews.')->group(function () {
    Route::get('/', ProductReviewsIndex::class)->name('index');
    Route::post('/', [\App\Http\Controllers\Frontend\ReviewController::class, 'store'])->name('store')->middleware('auth');
    Route::get('/guidelines', ReviewGuidelines::class)->name('guidelines');
});

Route::prefix('reviews/{review}')->name('frontend.reviews.')->group(function () {
    Route::post('/helpful', [\App\Http\Controllers\Frontend\ReviewController::class, 'markHelpful'])->name('helpful');
    Route::post('/report', [\App\Http\Controllers\Frontend\ReviewController::class, 'report'])->name('report');
});

// Admin Review Moderation
Route::prefix('admin/reviews')->name('admin.reviews.')->middleware(['admin'])->group(function () {
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
Route::prefix('admin/stock')->name('admin.stock.')->middleware(['admin'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\StockManagementController::class, 'index'])->name('index');
    Route::get('/statistics', [\App\Http\Controllers\Admin\StockManagementController::class, 'statistics'])->name('statistics');
    Route::get('/movements', [\App\Http\Controllers\Admin\StockManagementController::class, 'movements'])->name('movements');
    Route::get('/variants/{variant}', [\App\Http\Controllers\Admin\StockManagementController::class, 'show'])->name('show');
    Route::post('/variants/{variant}/adjust', [\App\Http\Controllers\Admin\StockManagementController::class, 'adjustStock'])->name('adjust');
    Route::post('/variants/{variant}/transfer', [\App\Http\Controllers\Admin\StockManagementController::class, 'transferStock'])->name('transfer');
    Route::post('/alerts/{alert}/resolve', [\App\Http\Controllers\Admin\StockManagementController::class, 'resolveAlert'])->name('resolve-alert');
});

// Product Bundles
Route::prefix('bundles')->name('frontend.bundles.')->group(function () {
    Route::get('/', BundlesIndex::class)->name('index');
    Route::get('/{bundle:slug}', BundleShow::class)->name('show');
    Route::post('/{bundle}/add-to-cart', [\App\Http\Controllers\Frontend\BundleController::class, 'addToCart'])->name('add-to-cart');
    Route::match(['get', 'post'], '/{bundle}/calculate-price', [\App\Http\Controllers\Frontend\BundleController::class, 'calculatePrice'])->name('calculate-price');
    Route::post('/{bundle}/check-availability', [\App\Http\Controllers\Frontend\BundleController::class, 'checkAvailability'])->name('check-availability');
    Route::post('/{bundle}/validate-selection', [\App\Http\Controllers\Frontend\BundleController::class, 'validateSelection'])->name('validate-selection');
    Route::get('/{bundle}/available-products', [\App\Http\Controllers\Frontend\BundleController::class, 'getAvailableProducts'])->name('available-products');
});

// Stock Notifications
Route::prefix('stock-notifications')->name('frontend.stock-notifications.')->group(function () {
    Route::post('/variants/{variant}/subscribe', [\App\Http\Controllers\Frontend\StockNotificationController::class, 'subscribe'])->name('subscribe');
    Route::get('/variants/{variant}/check', [\App\Http\Controllers\Frontend\StockNotificationController::class, 'check'])->name('check');
    Route::get('/unsubscribe/{token}', StockNotificationUnsubscribe::class)->name('unsubscribe');
    Route::get('/track/open/{metricId}', [\App\Http\Controllers\Frontend\StockNotificationTrackingController::class, 'trackOpen'])->name('track-open');
    Route::get('/track/click/{metricId}/{linkType}', [\App\Http\Controllers\Frontend\StockNotificationTrackingController::class, 'trackClick'])->name('track-click');
});

// Digital Product Downloads
Route::prefix('downloads')->name('frontend.downloads.')->group(function () {
    Route::get('/', DownloadsIndex::class)->name('index')->middleware('auth');
    Route::get('/{token}', [\App\Http\Controllers\Frontend\DownloadController::class, 'download'])->name('download');
    Route::get('/{token}/info', [\App\Http\Controllers\Frontend\DownloadController::class, 'info'])->name('info');
    Route::post('/{download}/resend-email', [\App\Http\Controllers\Frontend\DownloadController::class, 'resendEmail'])->name('resend-email')->middleware('auth')->where('download', '[0-9]+');
});

// Admin Product Import/Export
Route::prefix('admin/products')->name('admin.products.')->middleware(['admin'])->group(function () {
    Route::get('/import-export', [\App\Http\Controllers\Admin\ProductImportController::class, 'index'])->name('import-export');
    Route::post('/import', [\App\Http\Controllers\Admin\ProductImportController::class, 'import'])->name('import');
    Route::get('/imports/{import}/status', [\App\Http\Controllers\Admin\ProductImportController::class, 'status'])->name('imports.status');
    Route::get('/imports/{import}/errors', [\App\Http\Controllers\Admin\ProductImportController::class, 'errors'])->name('imports.errors');
    Route::post('/imports/{import}/cancel', [\App\Http\Controllers\Admin\ProductImportController::class, 'cancel'])->name('imports.cancel');
    Route::post('/export', [\App\Http\Controllers\Admin\ProductImportController::class, 'export'])->name('export');
    Route::get('/import-template', [\App\Http\Controllers\Admin\ProductImportController::class, 'downloadTemplate'])->name('import-template');
});

// Admin Product Scheduling
Route::prefix('admin/products/{product}/schedules')->name('admin.products.schedules.')->middleware(['admin'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\ProductScheduleController::class, 'index'])->name('index');
    Route::post('/', [\App\Http\Controllers\Admin\ProductScheduleController::class, 'store'])->name('store');
    Route::put('/{schedule}', [\App\Http\Controllers\Admin\ProductScheduleController::class, 'update'])->name('update');
    Route::delete('/{schedule}', [\App\Http\Controllers\Admin\ProductScheduleController::class, 'destroy'])->name('destroy');
});

Route::prefix('admin/schedules')->name('admin.schedules.')->middleware(['admin'])->group(function () {
    Route::get('/upcoming', [\App\Http\Controllers\Admin\ProductScheduleController::class, 'upcoming'])->name('upcoming');
    Route::get('/flash-sales', [\App\Http\Controllers\Admin\ProductScheduleController::class, 'activeFlashSales'])->name('flash-sales');
    Route::get('/calendar', [\App\Http\Controllers\Admin\ProductScheduleCalendarController::class, 'index'])->name('calendar');
    Route::get('/calendar/schedules', [\App\Http\Controllers\Admin\ProductScheduleCalendarController::class, 'getSchedules'])->name('calendar.schedules');
    Route::post('/bulk', [\App\Http\Controllers\Admin\BulkScheduleController::class, 'store'])->name('bulk.store');
    Route::get('/history', [\App\Http\Controllers\Admin\ProductScheduleController::class, 'history'])->name('history');
});

// Coming Soon Notifications
Route::prefix('coming-soon')->name('frontend.coming-soon.')->group(function () {
    Route::post('/products/{product}/subscribe', [\App\Http\Controllers\Frontend\ComingSoonController::class, 'subscribe'])->name('subscribe');
    Route::get('/unsubscribe/{token}', ComingSoonUnsubscribed::class)->name('unsubscribe');
});

// Product Questions & Answers
Route::prefix('products/{product}/questions')->name('frontend.products.questions.')->group(function () {
    Route::get('/', ProductQuestionsIndex::class)->name('index');
    Route::post('/', [\App\Http\Controllers\Frontend\ProductQuestionController::class, 'store'])->name('store');
    Route::get('/search', [\App\Http\Controllers\Frontend\ProductQuestionController::class, 'search'])->name('search');
    Route::post('/{question}/answer', [\App\Http\Controllers\Frontend\ProductQuestionController::class, 'answer'])->name('answer');
    Route::post('/{question}/helpful', [\App\Http\Controllers\Frontend\ProductQuestionController::class, 'markHelpful'])->name('helpful');
    Route::post('/{question}/answers/{answer}/helpful', [\App\Http\Controllers\Frontend\ProductQuestionController::class, 'markAnswerHelpful'])->name('answer.helpful');
    Route::post('/{question}/view', [\App\Http\Controllers\Frontend\ProductQuestionController::class, 'view'])->name('view');
});

// Admin Product Questions & Answers
Route::prefix('admin/products/questions')->name('admin.products.questions.')->middleware(['admin'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\ProductQuestionController::class, 'index'])->name('index');
    Route::get('/{question}', [\App\Http\Controllers\Admin\ProductQuestionController::class, 'show'])->name('show');
    Route::post('/{question}/moderate', [\App\Http\Controllers\Admin\ProductQuestionController::class, 'moderate'])->name('moderate');
    Route::post('/{question}/answer', [\App\Http\Controllers\Admin\ProductQuestionController::class, 'answer'])->name('answer');
    Route::post('/{question}/answers/{answer}/moderate', [\App\Http\Controllers\Admin\ProductQuestionController::class, 'moderateAnswer'])->name('answer.moderate');
    Route::post('/bulk-moderate', [\App\Http\Controllers\Admin\ProductQuestionController::class, 'bulkModerate'])->name('bulk-moderate');
    Route::get('/products/{product}/metrics', [\App\Http\Controllers\Admin\ProductQuestionController::class, 'metrics'])->name('metrics');
});

// Frontend Size Guide & Fit Finder
Route::prefix('products/{product}/size-guide')->name('frontend.products.size-guide.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Frontend\SizeGuideController::class, 'show'])->name('show');
    Route::post('/recommend', [\App\Http\Controllers\Frontend\SizeGuideController::class, 'recommend'])->name('recommend');
    Route::get('/fit-statistics', [\App\Http\Controllers\Frontend\SizeGuideController::class, 'fitStatistics'])->name('fit-statistics');
    Route::post('/fit-review', [\App\Http\Controllers\Frontend\SizeGuideController::class, 'submitFitReview'])->name('fit-review');
});

// Admin Size Guides are managed via Filament (SizeGuideResource) under the admin panel.

// Frontend Pricing
Route::prefix('pricing')->name('frontend.pricing.')->group(function () {
    Route::get('/variants/{variant}', [\App\Http\Controllers\Frontend\PricingController::class, 'getPrice'])->name('variant');
    Route::get('/variants/{variant}/tiers', [\App\Http\Controllers\Frontend\PricingController::class, 'getTieredPricing'])->name('tiers');
    Route::get('/products/{product}/volume-discounts', [\App\Http\Controllers\Frontend\PricingController::class, 'getVolumeDiscounts'])->name('volume-discounts');
});

// Admin Price Matrices
Route::prefix('admin/products/{product}/pricing')->name('admin.products.pricing.')->middleware(['admin'])->group(function () {
    Route::get('/matrices', [\App\Http\Controllers\Admin\PriceMatrixController::class, 'index'])->name('matrices.index');
    Route::post('/matrices', [\App\Http\Controllers\Admin\PriceMatrixController::class, 'store'])->name('matrices.store');
    Route::put('/matrices/{matrix}', [\App\Http\Controllers\Admin\PriceMatrixController::class, 'update'])->name('matrices.update');
    Route::delete('/matrices/{matrix}', [\App\Http\Controllers\Admin\PriceMatrixController::class, 'destroy'])->name('matrices.destroy');
    Route::post('/matrices/{matrix}/tiers', [\App\Http\Controllers\Admin\PriceMatrixController::class, 'addTier'])->name('matrices.tiers.store');
    Route::put('/matrices/{matrix}/tiers/{tier}', [\App\Http\Controllers\Admin\PriceMatrixController::class, 'updateTier'])->name('matrices.tiers.update');
    Route::post('/matrices/{matrix}/approve', [\App\Http\Controllers\Admin\PriceMatrixController::class, 'approve'])->name('matrices.approve');
    Route::get('/import', [\App\Http\Controllers\Admin\PricingImportController::class, 'index'])->name('import.index');
    Route::post('/import', [\App\Http\Controllers\Admin\PricingImportController::class, 'import'])->name('import');
    Route::get('/export', [\App\Http\Controllers\Admin\PricingExportController::class, 'export'])->name('export');
    Route::get('/report', [\App\Http\Controllers\Admin\PriceMatrixController::class, 'report'])->name('report');
    Route::get('/history', [\App\Http\Controllers\Admin\PricingHistoryController::class, 'index'])->name('history');
});

// Frontend Product Availability
Route::prefix('products/{product}/availability')->name('frontend.products.availability.')->group(function () {
    Route::post('/check', [\App\Http\Controllers\Frontend\AvailabilityController::class, 'checkAvailability'])->name('check');
    Route::get('/dates', [\App\Http\Controllers\Frontend\AvailabilityController::class, 'getAvailableDates'])->name('dates');
    Route::post('/pricing', [\App\Http\Controllers\Frontend\AvailabilityController::class, 'calculatePricing'])->name('pricing');
    Route::post('/reserve', [\App\Http\Controllers\Frontend\AvailabilityController::class, 'reserveDate'])->name('reserve');
});

// Admin Product Availability
Route::prefix('admin/products/{product}/availability')->name('admin.products.availability.')->middleware(['admin'])->group(function () {
    Route::get('/calendar', [\App\Http\Controllers\Admin\ProductAvailabilityController::class, 'calendar'])->name('calendar');
    Route::post('/', [\App\Http\Controllers\Admin\ProductAvailabilityController::class, 'store'])->name('store');
    Route::put('/{availability}', [\App\Http\Controllers\Admin\ProductAvailabilityController::class, 'update'])->name('update');
    Route::post('/rules', [\App\Http\Controllers\Admin\ProductAvailabilityController::class, 'storeRule'])->name('rules.store');
    Route::get('/bookings', [\App\Http\Controllers\Admin\ProductAvailabilityController::class, 'bookings'])->name('bookings');
});

// Frontend Product Customization
Route::prefix('products/{product}/customizations')->name('frontend.products.customizations.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Frontend\ProductCustomizationController::class, 'index'])->name('index');
    Route::post('/validate', [\App\Http\Controllers\Frontend\ProductCustomizationController::class, 'validate'])->name('validate');
    Route::post('/preview', [\App\Http\Controllers\Frontend\ProductCustomizationController::class, 'preview'])->name('preview');
    Route::post('/upload-image', [\App\Http\Controllers\Frontend\ProductCustomizationController::class, 'uploadImage'])->name('upload-image');
    Route::get('/templates', [\App\Http\Controllers\Frontend\ProductCustomizationController::class, 'templates'])->name('templates');
    Route::get('/examples', [\App\Http\Controllers\Frontend\ProductCustomizationController::class, 'examples'])->name('examples');
});

// Admin Product Customizations
Route::prefix('admin/products/{product}/customizations')->name('admin.products.customizations.')->middleware(['admin'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\ProductCustomizationController::class, 'index'])->name('index');
    Route::post('/', [\App\Http\Controllers\Admin\ProductCustomizationController::class, 'store'])->name('store');
    Route::put('/{customization}', [\App\Http\Controllers\Admin\ProductCustomizationController::class, 'update'])->name('update');
    Route::delete('/{customization}', [\App\Http\Controllers\Admin\ProductCustomizationController::class, 'destroy'])->name('destroy');
    Route::get('/examples', [\App\Http\Controllers\Admin\ProductCustomizationController::class, 'examples'])->name('examples');
    Route::post('/examples', [\App\Http\Controllers\Admin\ProductCustomizationController::class, 'storeExample'])->name('examples.store');
});

Route::prefix('admin/customizations')->name('admin.customizations.')->middleware(['admin'])->group(function () {
    Route::get('/templates', [\App\Http\Controllers\Admin\ProductCustomizationController::class, 'templates'])->name('templates');
    Route::post('/templates', [\App\Http\Controllers\Admin\ProductCustomizationController::class, 'storeTemplate'])->name('templates.store');
});

// Admin Product Badges
Route::prefix('admin/badges')->name('admin.badges.')->middleware(['admin'])->group(function () {
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
Route::prefix('admin/products/badges')->name('admin.products.badges.')->middleware(['admin'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\ProductBadgeController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Admin\ProductBadgeController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Admin\ProductBadgeController::class, 'store'])->name('store');
    Route::get('/{badge}/edit', [\App\Http\Controllers\Admin\ProductBadgeController::class, 'edit'])->name('edit');
    Route::put('/{badge}', [\App\Http\Controllers\Admin\ProductBadgeController::class, 'update'])->name('update');
    Route::delete('/{badge}', [\App\Http\Controllers\Admin\ProductBadgeController::class, 'destroy'])->name('destroy');
    Route::post('/process-auto-assignment', [\App\Http\Controllers\Admin\ProductBadgeController::class, 'processAutoAssignment'])->name('process-auto-assignment');
});

// Product Badge Assignment
Route::prefix('admin/products/{product}/badges')->name('admin.products.product-badges.')->middleware(['admin'])->group(function () {
    Route::post('/assign', [\App\Http\Controllers\Admin\ProductBadgeController::class, 'assignToProduct'])->name('assign');
    Route::post('/remove', [\App\Http\Controllers\Admin\ProductBadgeController::class, 'removeFromProduct'])->name('remove');
});

// Admin Collection Management
Route::prefix('admin/collections/{collection}')->name('admin.collections.')->middleware(['admin'])->group(function () {
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


// Admin Bundle Management
Route::prefix('admin/bundles')->name('admin.bundles.')->middleware(['admin'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\BundleController::class, 'index'])->name('index');
    Route::get('/create', [\App\Http\Controllers\Admin\BundleController::class, 'create'])->name('create');
    Route::post('/', [\App\Http\Controllers\Admin\BundleController::class, 'store'])->name('store');
    Route::get('/{bundle}/edit', [\App\Http\Controllers\Admin\BundleController::class, 'edit'])->name('edit');
    Route::put('/{bundle}', [\App\Http\Controllers\Admin\BundleController::class, 'update'])->name('update');
    Route::delete('/{bundle}', [\App\Http\Controllers\Admin\BundleController::class, 'destroy'])->name('destroy');
});

// Admin Order Status Management
Route::prefix('admin/orders')->name('admin.orders.')->middleware(['admin'])->group(function () {
    Route::get('/statuses', [\App\Http\Controllers\Admin\OrderStatusController::class, 'getAvailableStatuses'])->name('statuses');
    Route::get('/status/{status}', [\App\Http\Controllers\Admin\OrderStatusController::class, 'getOrdersByStatus'])->name('by-status');
    Route::post('/{order}/status', [\App\Http\Controllers\Admin\OrderStatusController::class, 'updateStatus'])->name('update-status');
    Route::get('/{order}/status-history', [\App\Http\Controllers\Admin\OrderStatusController::class, 'getStatusHistory'])->name('status-history');
    Route::get('/{order}/history', [\App\Http\Controllers\Admin\OrderStatusController::class, 'getOrderHistory'])->name('history');
});

// Admin Checkout Lock Management
Route::prefix('admin/checkout-locks')->name('admin.checkout-locks.')->middleware(['admin'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\CheckoutLockController::class, 'index'])->name('index');
    Route::get('/statistics', [\App\Http\Controllers\Admin\CheckoutLockController::class, 'statistics'])->name('statistics');
    Route::get('/{checkoutLock}', [\App\Http\Controllers\Admin\CheckoutLockController::class, 'show'])->name('show');
    Route::get('/{checkoutLock}/json', [\App\Http\Controllers\Admin\CheckoutLockController::class, 'showJson'])->name('show.json');
    Route::post('/{checkoutLock}/release', [\App\Http\Controllers\Admin\CheckoutLockController::class, 'release'])->name('release');
});

// Product Recommendations
Route::prefix('products/{product}/recommendations')->name('frontend.recommendations.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Frontend\RecommendationController::class, 'index'])->name('index');
    Route::post('/track-view', [\App\Http\Controllers\Frontend\RecommendationController::class, 'trackView'])->name('track-view');
    Route::get('/frequently-bought-together', [\App\Http\Controllers\Frontend\RecommendationController::class, 'frequentlyBoughtTogether'])->name('frequently-bought-together');
});

Route::prefix('recommendations')->name('frontend.recommendations.')->group(function () {
    Route::post('/track-click', [\App\Http\Controllers\Frontend\RecommendationController::class, 'trackClick'])->name('track-click');
    Route::get('/personalized', [\App\Http\Controllers\Frontend\RecommendationController::class, 'personalized'])->name('personalized');
});

// Stock Reservations (for checkout)
Route::prefix('stock-reservations')->name('frontend.stock-reservations.')->group(function () {
    Route::post('/reserve', [\App\Http\Controllers\Frontend\StockReservationController::class, 'reserve'])->name('reserve');
    Route::post('/release', [\App\Http\Controllers\Frontend\StockReservationController::class, 'release'])->name('release');
});

// NOTE: Duplicate bundles/{bundle} group removed (handled under the main "bundles" prefix group above).
// Admin Inventory Management
Route::prefix('admin/inventory')->name('admin.inventory.')->middleware(['admin'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\InventoryController::class, 'index'])->name('index');
    Route::post('/adjust', [\App\Http\Controllers\Admin\InventoryController::class, 'adjust'])->name('adjust');
    Route::post('/transfer', [\App\Http\Controllers\Admin\InventoryController::class, 'transfer'])->name('transfer');
    Route::get('/check-availability', [\App\Http\Controllers\Admin\InventoryController::class, 'checkAvailability'])->name('check-availability');
    Route::get('/purchase-order-suggestions', [\App\Http\Controllers\Admin\InventoryController::class, 'purchaseOrderSuggestions'])->name('purchase-order-suggestions');
    Route::post('/barcode-scan', [\App\Http\Controllers\Admin\InventoryController::class, 'barcodeScan'])->name('barcode-scan');
});

// Admin Inventory Reports
Route::prefix('admin/inventory/reports')->name('admin.inventory.reports.')->middleware(['admin'])->group(function () {
    Route::get('/stock-valuation', [\App\Http\Controllers\Admin\InventoryReportController::class, 'stockValuation'])->name('stock-valuation');
    Route::get('/inventory-turnover', [\App\Http\Controllers\Admin\InventoryReportController::class, 'inventoryTurnover'])->name('inventory-turnover');
    Route::get('/dead-stock', [\App\Http\Controllers\Admin\InventoryReportController::class, 'deadStock'])->name('dead-stock');
    Route::get('/fast-moving-items', [\App\Http\Controllers\Admin\InventoryReportController::class, 'fastMovingItems'])->name('fast-moving-items');
});

// Admin Inventory Import/Export
Route::prefix('admin/inventory')->name('admin.inventory.')->middleware(['admin'])->group(function () {
    Route::get('/export', [\App\Http\Controllers\Admin\InventoryImportExportController::class, 'export'])->name('export');
    Route::post('/import', [\App\Http\Controllers\Admin\InventoryImportExportController::class, 'import'])->name('import');
});

// Admin Bundle Management
Route::prefix('admin/bundles')->name('admin.bundles.')->middleware(['admin'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\BundleController::class, 'index'])->name('index');
    Route::post('/', [\App\Http\Controllers\Admin\BundleController::class, 'store'])->name('store');
    Route::get('/{bundle}', [\App\Http\Controllers\Admin\BundleController::class, 'show'])->name('show');
    Route::put('/{bundle}', [\App\Http\Controllers\Admin\BundleController::class, 'update'])->name('update');
    Route::get('/{bundle}/analytics', [\App\Http\Controllers\Admin\BundleController::class, 'analytics'])->name('analytics');
    Route::post('/{bundle}/items', [\App\Http\Controllers\Admin\BundleController::class, 'addItem'])->name('items.store');
    Route::put('/{bundle}/items/{item}', [\App\Http\Controllers\Admin\BundleController::class, 'updateItem'])->name('items.update');
    Route::delete('/{bundle}/items/{item}', [\App\Http\Controllers\Admin\BundleController::class, 'removeItem'])->name('items.destroy');
});

// Admin Stock Notifications
Route::prefix('admin/stock-notifications')->name('admin.stock-notifications.')->middleware(['admin'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\StockNotificationController::class, 'index'])->name('index');
    Route::get('/variants/{variant}/subscriptions', [\App\Http\Controllers\Admin\StockNotificationController::class, 'subscriptions'])->name('subscriptions');
    Route::get('/variants/{variant}/metrics', [\App\Http\Controllers\Admin\StockNotificationController::class, 'metrics'])->name('metrics');
});

// Search Analytics (admin/analytics endpoints)
Route::prefix('search-analytics')->name('frontend.search-analytics.')->group(function () {
    Route::get('/statistics', [\App\Http\Controllers\Frontend\SearchAnalyticsController::class, 'statistics'])->name('statistics');
    Route::get('/zero-results', [\App\Http\Controllers\Frontend\SearchAnalyticsController::class, 'zeroResults'])->name('zero-results');
    Route::get('/trends', [\App\Http\Controllers\Frontend\SearchAnalyticsController::class, 'trends'])->name('trends');
    Route::get('/most-clicked', [\App\Http\Controllers\Frontend\SearchAnalyticsController::class, 'mostClicked'])->name('most-clicked');
});

Route::prefix('cart')->name('frontend.cart.')->group(function () {
    Route::get('/', CartIndex::class)->name('index');
    Route::get('/summary', [CartController::class, 'summary'])->name('summary');
    Route::get('/pricing', [CartController::class, 'pricing'])->name('pricing');
    Route::post('/add', [CartController::class, 'add'])->name('add');
    Route::put('/{lineId}', [CartController::class, 'update'])->name('update');
    Route::delete('/{lineId}', [CartController::class, 'remove'])->name('remove');
    Route::delete('/', [CartController::class, 'clear'])->name('clear');
    Route::post('/discount/apply', [CartController::class, 'applyDiscount'])->name('discount.apply');
    Route::post('/discount/remove', [CartController::class, 'removeDiscount'])->name('discount.remove');
});

Route::prefix('checkout')->name('frontend.checkout.')
    ->middleware(['throttle.checkout'])
    ->group(function () {
        Route::get('/', CheckoutIndex::class)->name('index');
        Route::post('/', [CheckoutController::class, 'store'])->name('store');
        Route::get('/confirmation/{order}', CheckoutConfirmation::class)->name('confirmation');
        Route::get('/status', [\App\Http\Controllers\Frontend\CheckoutStatusController::class, 'status'])->name('status');
        Route::post('/cancel', [\App\Http\Controllers\Frontend\CheckoutStatusController::class, 'cancel'])->name('cancel');
    });

Route::prefix('currency')->name('frontend.currency.')->group(function () {
    Route::get('/', [CurrencyController::class, 'index'])->name('index');
    Route::post('/switch', [CurrencyController::class, 'switch'])->name('switch');
    Route::get('/current', [CurrencyController::class, 'current'])->name('current');
});

Route::prefix('addresses')->name('frontend.addresses.')->middleware('auth')->group(function () {
    Route::get('/', AddressesIndex::class)->name('index');
    Route::get('/create', AddressCreate::class)->name('create');
    Route::post('/', [\App\Http\Controllers\Frontend\AddressController::class, 'store'])->name('store');
    Route::get('/{address}/edit', AddressEdit::class)->name('edit');
    Route::put('/{address}', [\App\Http\Controllers\Frontend\AddressController::class, 'update'])->name('update');
    Route::delete('/{address}', [\App\Http\Controllers\Frontend\AddressController::class, 'destroy'])->name('destroy');
    Route::post('/{address}/default-shipping', [\App\Http\Controllers\Frontend\AddressController::class, 'setDefaultShipping'])->name('set-default-shipping');
    Route::post('/{address}/default-billing', [\App\Http\Controllers\Frontend\AddressController::class, 'setDefaultBilling'])->name('set-default-billing');
});

Route::prefix('language')->name('frontend.language.')->group(function () {
    Route::get('/', [LanguageController::class, 'index'])->name('index');
    Route::post('/switch', [LanguageController::class, 'switch'])->name('switch');
    Route::get('/current', [LanguageController::class, 'current'])->name('current');
});

Route::prefix('brands')->name('frontend.brands.')->group(function () {
    Route::get('/', BrandsIndex::class)->name('index');
    Route::get('/api', [BrandController::class, 'api'])->name('api');
    Route::get('/{slug}', BrandShow::class)->name('show');
});

Route::prefix('media')->name('frontend.media.')->middleware(['web'])->group(function () {
    Route::post('/product/{productId}/upload', [MediaController::class, 'uploadProductImages'])->name('product.upload');
    Route::post('/collection/{collectionId}/upload', [MediaController::class, 'uploadCollectionImages'])->name('collection.upload');
    Route::post('/brand/{brandId}/logo', [MediaController::class, 'uploadBrandLogo'])->name('brand.logo');
    Route::delete('/{modelType}/{modelId}/{mediaId}', [MediaController::class, 'deleteMedia'])->name('delete');
    Route::post('/{modelType}/{modelId}/reorder', [MediaController::class, 'reorderMedia'])->name('reorder');
});

Route::prefix('products/{product}/variants')->name('frontend.variants.')->middleware(['web'])->group(function () {
    Route::get('/', [VariantController::class, 'index'])->name('index');
    Route::post('/generate', [VariantController::class, 'generate'])->name('generate');
    Route::post('/', [VariantController::class, 'store'])->name('store');
});

Route::prefix('variants')->name('frontend.variants.')->middleware(['web'])->group(function () {
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
        Route::get('/', PrivacyPolicyShow::class)->name('show');
        Route::get('/versions', PrivacyPolicyVersions::class)->name('index');
        Route::get('/version/{version}', PrivacyPolicyVersion::class)->name('version');
    });

    // Privacy Settings (requires authentication)
    Route::prefix('privacy-settings')->name('privacy-settings.')->middleware('auth')->group(function () {
        Route::get('/', PrivacySettings::class)->name('index');
        Route::put('/', [\App\Http\Controllers\Gdpr\PrivacySettingsController::class, 'update'])->name('update');
    });

    // GDPR Requests
    Route::prefix('request')->name('request.')->group(function () {
        Route::get('/create', GdprRequestCreate::class)->name('create');
        Route::post('/', [\App\Http\Controllers\Gdpr\GdprRequestController::class, 'store'])->name('store');
        Route::get('/verify/{token}', [\App\Http\Controllers\Gdpr\GdprRequestController::class, 'verify'])->name('verify');
        Route::get('/download/{token}', [\App\Http\Controllers\Gdpr\GdprRequestController::class, 'download'])->name('download');
    });
});

// Admin Stock Notifications
Route::prefix('admin/stock-notifications')->name('admin.stock-notifications.')->middleware(['admin'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\StockNotificationController::class, 'index'])->name('index');
    Route::get('/variants/{variant}/subscriptions', [\App\Http\Controllers\Admin\StockNotificationController::class, 'subscriptions'])->name('variant.subscriptions');
    Route::get('/variants/{variant}/metrics', [\App\Http\Controllers\Admin\StockNotificationController::class, 'metrics'])->name('variant.metrics');
});

// Admin Product Import/Export
Route::prefix('admin/products/import')->name('admin.products.import.')->middleware(['admin'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\ProductImportController::class, 'index'])->name('index');
    Route::post('/preview', [\App\Http\Controllers\Admin\ProductImportController::class, 'preview'])->name('preview');
    Route::post('/import', [\App\Http\Controllers\Admin\ProductImportController::class, 'import'])->name('import');
    Route::get('/{id}/status', [\App\Http\Controllers\Admin\ProductImportController::class, 'status'])->name('status');
    Route::get('/{id}/report', [\App\Http\Controllers\Admin\ProductImportController::class, 'report'])->name('report');
    Route::post('/{id}/rollback', [\App\Http\Controllers\Admin\ProductImportController::class, 'rollback'])->name('rollback');
    Route::get('/template/download', [\App\Http\Controllers\Admin\ProductImportController::class, 'downloadTemplate'])->name('template.download');
});

Route::prefix('admin/products/export')->name('admin.products.export.')->middleware(['admin'])->group(function () {
    Route::post('/', [\App\Http\Controllers\Admin\ProductExportController::class, 'export'])->name('export');
    Route::get('/columns', [\App\Http\Controllers\Admin\ProductExportController::class, 'columns'])->name('columns');
});



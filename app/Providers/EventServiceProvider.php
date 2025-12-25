<?php

namespace App\Providers;

use App\Events\CartAddressChanged;
use App\Events\CartCurrencyChanged;
use App\Events\CartCustomerChanged;
use App\Events\CartQuantityChanged;
use App\Events\CartRepricingEvent;
use App\Events\CartVariantChanged;
use App\Events\CheckoutCompleted;
use App\Events\CheckoutFailed;
use App\Events\CheckoutStarted;
use App\Events\ContractValidityChanged;
use App\Events\PromotionActivated;
use App\Events\PromotionExpired;
use App\Events\StockChanged;
use App\Events\ReferralCodeClicked;
use App\Events\ReferralSignup;
use App\Events\ReferralPurchase;
use App\Listeners\CartRepricingListener;
use App\Listeners\ProcessReferralClick;
use App\Listeners\ProcessReferralSignup;
use App\Listeners\ProcessReferralPurchase;
use App\Listeners\ProcessUserRegistration;
use App\Listeners\ProcessOrderCompletion;
use App\Listeners\Cache\InvalidateContractCache;
use App\Listeners\Cache\InvalidateCurrencyCache;
use App\Listeners\Cache\InvalidatePriceCache;
use App\Listeners\Cache\InvalidatePromotionCache;
use App\Listeners\Cache\InvalidateStockCache;
use App\Listeners\ClearCartOnLogin;
use App\Listeners\CreateDigitalProductDownloads;
use App\Listeners\DeliverDigitalProducts;
use App\Listeners\MergeCartOnLogin;
use App\Listeners\NotifyCheckoutFailure;
use App\Listeners\RemoveStockNotificationOnPurchase;
use App\Listeners\SendOrderConfirmation;
use App\Listeners\TrackStockNotificationConversion;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Lunar\Events\OrderStatusChanged;
use Lunar\Models\Discount;
use Lunar\Models\Price;
use Lunar\Models\Currency;
use Lunar\Models\ProductVariant;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Login::class => [
            MergeCartOnLogin::class,
        ],
        Registered::class => [
            ProcessUserRegistration::class,
        ],
        Logout::class => [
            ClearCartOnLogout::class,
        ],
        OrderStatusChanged::class => [
            DeliverDigitalProducts::class,
            RemoveStockNotificationOnPurchase::class,
            TrackStockNotificationConversion::class,
        ],
        // Cart repricing events
        CartQuantityChanged::class => [
            CartRepricingListener::class,
        ],
        CartVariantChanged::class => [
            CartRepricingListener::class,
        ],
        CartCustomerChanged::class => [
            CartRepricingListener::class,
        ],
        CartAddressChanged::class => [
            CartRepricingListener::class,
        ],
        CartCurrencyChanged::class => [
            CartRepricingListener::class,
        ],
        PromotionActivated::class => [
            CartRepricingListener::class,
            InvalidatePromotionCache::class . '@handlePromotionActivated',
        ],
        PromotionExpired::class => [
            CartRepricingListener::class,
            InvalidatePromotionCache::class . '@handlePromotionExpired',
        ],
        StockChanged::class => [
            CartRepricingListener::class,
            InvalidateStockCache::class,
        ],
        ContractValidityChanged::class => [
            CartRepricingListener::class,
            InvalidateContractCache::class,
        ],
        // Checkout events
        CheckoutStarted::class => [
            // Add listeners here for checkout started events
            // Example: TrackCheckoutStart::class,
        ],
        CheckoutCompleted::class => [
            SendOrderConfirmation::class,
            ProcessOrderCompletion::class,
            // Add additional listeners here
        ],
        CheckoutFailed::class => [
            NotifyCheckoutFailure::class,
            // Add additional listeners here
        ],
        // Referral events
        ReferralCodeClicked::class => [
            ProcessReferralClick::class,
        ],
        ReferralSignup::class => [
            ProcessReferralSignup::class,
        ],
        ReferralPurchase::class => [
            ProcessReferralPurchase::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Register model observers for cache invalidation
        Price::observe(InvalidatePriceCache::class);
        Discount::observe(InvalidatePromotionCache::class);
        Currency::observe(InvalidateCurrencyCache::class);
        ProductVariant::observe(InvalidateStockCache::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
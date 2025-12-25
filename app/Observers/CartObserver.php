<?php

namespace App\Observers;

use App\Events\CartAddressChanged;
use App\Events\CartCurrencyChanged;
use App\Events\CartCustomerChanged;
use App\Events\PromotionActivated;
use App\Events\PromotionExpired;
use App\Services\AbandonedCartService;
use Lunar\Models\Cart;

/**
 * Observer for Cart model to track abandoned carts and trigger repricing events.
 */
class CartObserver
{
    /**
     * Handle the Cart "updated" event.
     *
     * @param  Cart  $cart
     * @return void
     */
    public function updated(Cart $cart): void
    {
        // Abandoned cart tracking
        // If cart was converted to order, mark as converted
        if ($cart->order_id) {
            $service = app(AbandonedCartService::class);
            $service->markAsConverted($cart);
        }
        
        // Track abandoned cart if cart hasn't been updated recently
        // This would be handled by scheduled command, but can also track here
        if ($cart->wasChanged('updated_at') && $cart->lines->isNotEmpty()) {
            // Cart was updated, could mark as recovered if it was abandoned
            $service = app(AbandonedCartService::class);
            $service->markAsRecovered($cart);
        }
        
        // Cart repricing triggers
        $changes = $cart->getChanges();
        
        // Currency changed
        if (isset($changes['currency_id'])) {
            event(new CartCurrencyChanged($cart));
        }
        
        // Customer changed
        if (isset($changes['customer_id'])) {
            event(new CartCustomerChanged($cart));
        }
        
        // Coupon code changed (promotion)
        if (isset($changes['coupon_code'])) {
            if ($changes['coupon_code']) {
                event(new PromotionActivated($cart));
            } else {
                event(new PromotionExpired($cart));
            }
        }
    }

    /**
     * Handle the Cart "deleted" event.
     *
     * @param  Cart  $cart
     * @return void
     */
    public function deleted(Cart $cart): void
    {
        // Cart was deleted, could mark as expired
        // Implementation depends on your needs
    }
}

<?php

namespace App\Traits;

use App\Services\CheckoutService;
use Lunar\Facades\CartSession;

/**
 * Trait for controllers that need to check checkout locks.
 */
trait ChecksCheckoutLock
{
    /**
     * Check if cart is locked and throw exception if so.
     */
    protected function ensureCartNotLocked(): void
    {
        $cart = CartSession::current();
        
        if (!$cart) {
            return;
        }

        $checkoutService = app(CheckoutService::class);
        
        if ($checkoutService->isCartLocked($cart)) {
            throw new \Exception('Cart cannot be modified during checkout. Please complete or cancel your checkout first.');
        }
    }

    /**
     * Get checkout status for current cart.
     */
    protected function getCheckoutStatus(): array
    {
        $cart = CartSession::current();
        
        if (!$cart) {
            return ['locked' => false, 'can_checkout' => false];
        }

        $checkoutService = app(CheckoutService::class);
        
        return $checkoutService->getCheckoutStatus($cart);
    }
}


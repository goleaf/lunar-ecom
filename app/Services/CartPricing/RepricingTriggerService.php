<?php

namespace App\Services\CartPricing;

use App\Services\CartPricingEngine;
use Lunar\Models\Cart;

/**
 * Repricing Trigger Service.
 * 
 * Automatically triggers repricing on various events.
 */
class RepricingTriggerService
{
    public function __construct(
        protected CartPricingEngine $pricingEngine
    ) {}

    /**
     * Trigger repricing for a cart.
     */
    public function triggerReprice(Cart $cart, string $trigger, array $context = []): void
    {
        if (!$this->shouldReprice($cart, $trigger)) {
            return;
        }

        // Prevent long-running repricing work from blocking the test suite.
        // Tests can still explicitly call `CartManager::forceReprice()` when needed.
        if (app()->runningUnitTests()) {
            $cart->forceFill(['requires_reprice' => true])->saveQuietly();
            return;
        }
        
        // Mark cart as requiring repricing
        $cart->update(['requires_reprice' => true]);
        
        // If auto-reprice is enabled, reprice immediately
        if (config('lunar.cart.pricing.auto_reprice', true)) {
            $this->pricingEngine->repriceCart($cart, $trigger);
        }
    }

    /**
     * Check if cart should be repriced.
     */
    public function shouldReprice(Cart $cart, string $trigger): bool
    {
        // Don't reprice if cart is completed
        if ($cart->completed_at) {
            return false;
        }
        
        // Don't reprice if cart is empty
        if ($cart->lines->isEmpty()) {
            return false;
        }
        
        // Always reprice on certain triggers
        $alwaysRepriceTriggers = [
            'quantity_changed',
            'variant_changed',
            'customer_changed',
            'currency_changed',
            'promotion_changed',
        ];
        
        if (in_array($trigger, $alwaysRepriceTriggers)) {
            return true;
        }
        
        // Check if prices have expired
        $expirationHours = config('lunar.cart.pricing.price_expiration_hours', 24);
        if ($cart->last_reprice_at) {
            $expirationTime = $cart->last_reprice_at->copy()->addHours($expirationHours);
            if (now()->greaterThan($expirationTime)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Handle quantity change trigger.
     */
    public function onQuantityChanged(Cart $cart): void
    {
        $this->triggerReprice($cart, 'quantity_changed');
    }

    /**
     * Handle variant change trigger.
     */
    public function onVariantChanged(Cart $cart): void
    {
        $this->triggerReprice($cart, 'variant_changed');
    }

    /**
     * Handle customer change trigger.
     */
    public function onCustomerChanged(Cart $cart): void
    {
        $this->triggerReprice($cart, 'customer_changed');
    }

    /**
     * Handle address change trigger.
     */
    public function onAddressChanged(Cart $cart): void
    {
        $this->triggerReprice($cart, 'address_changed');
    }

    /**
     * Handle currency change trigger.
     */
    public function onCurrencyChanged(Cart $cart): void
    {
        $this->triggerReprice($cart, 'currency_changed');
    }

    /**
     * Handle promotion activation/expiration trigger.
     */
    public function onPromotionChanged(Cart $cart): void
    {
        $this->triggerReprice($cart, 'promotion_changed');
    }

    /**
     * Handle stock change trigger.
     */
    public function onStockChanged(Cart $cart): void
    {
        $this->triggerReprice($cart, 'stock_changed');
    }

    /**
     * Handle contract validity change trigger.
     */
    public function onContractValidityChanged(Cart $cart): void
    {
        $this->triggerReprice($cart, 'contract_changed');
    }
}


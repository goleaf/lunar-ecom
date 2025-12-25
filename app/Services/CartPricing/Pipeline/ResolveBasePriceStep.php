<?php

namespace App\Services\CartPricing\Pipeline;

use App\Services\AdvancedPricingService;
use Lunar\Models\Cart;
use Lunar\Models\CartLine;
use Lunar\Models\ProductVariant;

/**
 * Step 1: Resolve variant base prices.
 * 
 * Uses AdvancedPricingService to get the base price for a variant,
 * considering currency, channel, and customer group.
 */
class ResolveBasePriceStep
{
    public function __construct(
        protected AdvancedPricingService $pricingService
    ) {}

    /**
     * Resolve base price for a cart line.
     */
    public function handle(array $data, Cart $cart, callable $next): array
    {
        $line = $data['line'] ?? null;
        
        if (!$line instanceof CartLine) {
            return $next($data);
        }
        
        $purchasable = $line->purchasable;
        
        if (!$purchasable instanceof ProductVariant) {
            // For non-variant purchasables, use a default price resolver
            $basePrice = $this->resolveNonVariantPrice($purchasable, $cart);
        } else {
            // Get customer group from cart
            $customerGroup = $cart->customer?->customerGroups->first();
            
            // Use AdvancedPricingService to get base price
            $priceData = $this->pricingService->calculatePrice(
                $purchasable,
                $line->quantity,
                $cart->currency,
                $customerGroup,
                $cart->channel,
                false // Tax excluded for base price
            );
            
            $basePrice = $priceData['price'] ?? 0;
        }

        // Store base price snapshot
        $data['base_price'] = $basePrice;
        $data['current_price'] = $basePrice;
        $data['price_source'] = 'base';
        $data['applied_rules'] = $data['applied_rules'] ?? [];

        return $next($data);
    }

    /**
     * Resolve price for non-variant purchasables.
     */
    protected function resolveNonVariantPrice($purchasable, Cart $cart): int
    {
        // Default implementation - can be extended for other purchasable types
        // For now, return 0 if not a ProductVariant
        return 0;
    }
}


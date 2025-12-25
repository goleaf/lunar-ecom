<?php

namespace App\Services\CartPricing\Pipeline;

use App\Services\MatrixPricingService;
use Lunar\Models\Cart;
use Lunar\Models\CartLine;
use Lunar\Models\ProductVariant;

/**
 * Step 3: Apply quantity (tier) pricing.
 * 
 * Uses MatrixPricingService to apply quantity-based tier pricing.
 */
class ApplyQuantityTierStep
{
    public function __construct(
        protected MatrixPricingService $matrixPricingService
    ) {}

    /**
     * Apply quantity tier pricing.
     */
    public function handle(array $data, Cart $cart, callable $next): array
    {
        $line = $data['line'] ?? null;
        $currentPrice = $data['current_price'] ?? 0;
        
        if (!$line instanceof CartLine) {
            return $next($data);
        }

        $purchasable = $line->purchasable;
        
        if (!$purchasable instanceof ProductVariant) {
            return $next($data);
        }

        // Get customer group
        $customerGroup = $cart->customer?->customerGroups->first();
        
        // Get tier pricing from MatrixPricingService
        $tierPricing = $this->matrixPricingService->calculatePrice(
            $purchasable,
            $line->quantity,
            $cart->currency,
            $customerGroup
        );

        // Check if tier pricing applies and is better than current price
        if (isset($tierPricing['price']) && $tierPricing['price'] < $currentPrice) {
            $data['current_price'] = $tierPricing['price'];
            $data['tier_price'] = $tierPricing['price'];
            $data['tier_name'] = $tierPricing['tier_name'] ?? "Quantity {$line->quantity}+";
            $data['price_source'] = $data['price_source'] === 'contract' ? 'contract' : 'matrix';
            
            // Store tier information
            $data['applied_rules'][] = [
                'type' => 'quantity_tier',
                'quantity' => $line->quantity,
                'tier_price' => $tierPricing['price'],
                'matrix_id' => $tierPricing['matrix_id'] ?? null,
            ];
        }

        return $next($data);
    }
}


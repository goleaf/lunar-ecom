<?php

namespace App\Services\CartPricing\Pipeline;

use App\Services\CartPricing\DTOs\ShippingCost;
use Lunar\Facades\ShippingManifest;
use Lunar\Models\Cart;

/**
 * Step 6: Calculate shipping cost.
 * 
 * Uses Lunar's shipping modifiers/calculators to calculate shipping cost.
 */
class CalculateShippingStep
{
    /**
     * Calculate shipping cost.
     */
    public function handle(array $data, Cart $cart, callable $next): array
    {
        // Lunar's shipping calculation happens via modifiers
        // The shipping total is already calculated by Lunar's cart calculation
        $shippingTotal = $cart->shippingTotal?->value ?? 0;
        $shippingSubTotal = $cart->shippingSubTotal?->value ?? 0;
        
        // Get selected shipping option if available
        $shippingOption = $this->getSelectedShippingOption($cart);
        
        $shippingCost = new ShippingCost(
            amount: $shippingTotal,
            shippingOptionId: $shippingOption['identifier'] ?? null,
            shippingOptionName: $shippingOption['name'] ?? null,
            shippingOptionDescription: $shippingOption['description'] ?? null,
            taxAmount: $shippingTotal - $shippingSubTotal, // Tax on shipping
            taxRate: $shippingSubTotal > 0 ? ($shippingTotal - $shippingSubTotal) / $shippingSubTotal : null,
        );
        
        $data['shipping_cost'] = $shippingCost;
        $data['shipping_total'] = $shippingTotal;

        return $next($data);
    }

    /**
     * Get selected shipping option.
     */
    protected function getSelectedShippingOption(Cart $cart): ?array
    {
        // Check if cart has a selected shipping option in meta
        $meta = $cart->meta ?? [];
        
        if (isset($meta['shipping_option'])) {
            return $meta['shipping_option'];
        }
        
        // Try to get from shipping manifest
        $options = ShippingManifest::getOptions($cart);
        
        if ($options->isNotEmpty()) {
            // Return first available option (or selected one if available)
            return [
                'identifier' => $options->first()?->getIdentifier(),
                'name' => $options->first()?->getName(),
                'description' => $options->first()?->getDescription(),
            ];
        }
        
        return null;
    }
}


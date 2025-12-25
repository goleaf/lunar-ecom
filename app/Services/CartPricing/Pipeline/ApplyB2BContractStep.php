<?php

namespace App\Services\CartPricing\Pipeline;

use Lunar\Models\Cart;
use Lunar\Models\CartLine;
use Lunar\Models\ProductVariant;

/**
 * Step 2: Apply B2B contract / price list overrides.
 * 
 * Checks for active B2B contracts for the customer and applies
 * contract-specific pricing if available.
 */
class ApplyB2BContractStep
{
    /**
     * Apply B2B contract pricing if available.
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

        // Check for B2B contract pricing
        $contractPrice = $this->getContractPrice($line, $cart);
        
        if ($contractPrice !== null && isset($contractPrice['price']) && $contractPrice['price'] < $currentPrice) {
            // Contract price overrides base price
            $data['current_price'] = $contractPrice['price'];
            $data['price_source'] = 'contract';
            $data['contract_id'] = $contractPrice['contract_id'] ?? null;
            $data['applied_rules'][] = [
                'type' => 'b2b_contract',
                'contract_id' => $contractPrice['contract_id'] ?? null,
                'version' => $contractPrice['version'] ?? '1.0',
            ];
        }

        return $next($data);
    }

    /**
     * Get contract price for a line item.
     * 
     * This method checks for active B2B contracts. If B2B contracts exist
     * in the system, integrate with them here. Otherwise, returns null.
     */
    protected function getContractPrice(CartLine $line, Cart $cart): ?array
    {
        // TODO: Integrate with existing B2B contract system
        // For now, check if customer has a customer group that might have contract pricing
        $customer = $cart->customer;
        
        if (!$customer) {
            return null;
        }

        // Check customer groups for contract pricing
        // This is a placeholder - replace with actual B2B contract lookup
                $customerGroup = $customer->customerGroups?->first();
        
        if (!$customerGroup) {
            return null;
        }

        // If B2B contracts exist, query them here:
        // $contract = B2BContract::where('customer_id', $customer->id)
        //     ->where('product_variant_id', $line->purchasable_id)
        //     ->where('valid_from', '<=', now())
        //     ->where(function($q) {
        //         $q->whereNull('valid_to')->orWhere('valid_to', '>=', now());
        //     })
        //     ->first();
        
        // For now, return null (no contract pricing)
        return null;
    }
}


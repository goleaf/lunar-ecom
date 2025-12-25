<?php

namespace App\Services\CartPricing\Pipeline;

use App\Services\PriceListService;
use Lunar\Models\Cart;
use Lunar\Models\CartLine;
use Lunar\Models\ProductVariant;

/**
 * Step 2: Apply B2B contract / price list overrides.
 * 
 * Checks for active B2B contracts for the customer and applies
 * contract-specific pricing if available.
 * 
 * Contract prices override base prices and promotions.
 */
class ApplyB2BContractStep
{
    public function __construct(
        protected PriceListService $priceListService
    ) {}

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

        $customer = $cart->customer;
        
        if (!$customer) {
            return $next($data);
        }

        // Get contract price for this variant
        $contractPrice = $this->priceListService->getContractPrice(
            $purchasable,
            $customer,
            $line->quantity,
            $currentPrice
        );
        
        if ($contractPrice !== null && isset($contractPrice['price'])) {
            // Contract price overrides base price (even if higher)
            // This ensures contract pricing is always applied
            $data['current_price'] = $contractPrice['price'];
            $data['price_source'] = 'contract';
            $data['contract_id'] = $contractPrice['contract_id'] ?? null;
            $data['price_list_id'] = $contractPrice['price_list_id'] ?? null;
            $data['applied_rules'][] = [
                'type' => 'b2b_contract',
                'contract_id' => $contractPrice['contract_id'] ?? null,
                'price_list_id' => $contractPrice['price_list_id'] ?? null,
                'version' => $contractPrice['version'] ?? '1.0',
            ];
        }

        return $next($data);
    }
}


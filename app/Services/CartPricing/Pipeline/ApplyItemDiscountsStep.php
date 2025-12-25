<?php

namespace App\Services\CartPricing\Pipeline;

use App\Services\CartPricing\DTOs\DiscountBreakdown;
use App\Services\CartPricing\DTOs\ItemDiscount;
use Illuminate\Support\Collection;
use Lunar\Models\Cart;
use Lunar\Models\CartLine;
use Lunar\Models\Discount;
use Lunar\Models\ProductVariant;

/**
 * Step 4: Apply item-level discounts.
 * 
 * Applies product/variant-specific discounts using Lunar's discount system.
 */
class ApplyItemDiscountsStep
{
    /**
     * Apply item-level discounts.
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

        // Get applicable discounts for this item
        $discounts = $this->getApplicableDiscounts($line, $cart);
        
        $itemDiscounts = collect();
        $totalDiscountAmount = 0;
        $priceAfterDiscounts = $currentPrice;

        foreach ($discounts as $discount) {
            $discountAmount = $this->calculateDiscountAmount($discount, $priceAfterDiscounts, $line);
            
            if ($discountAmount > 0) {
                $itemDiscounts->push(new ItemDiscount(
                    cartLineId: $line->id,
                    discountId: (string) $discount->id,
                    discountVersion: $this->getDiscountVersion($discount),
                    discountName: $discount->name ?? 'Item Discount',
                    amount: $discountAmount,
                    type: $this->getDiscountType($discount),
                ));
                
                $totalDiscountAmount += $discountAmount;
                $priceAfterDiscounts -= $discountAmount;
                
                // Store discount in applied rules
                $data['applied_rules'][] = [
                    'type' => 'item_discount',
                    'discount_id' => $discount->id,
                    'discount_version' => $this->getDiscountVersion($discount),
                    'amount' => $discountAmount,
                ];
            }
        }

        // Ensure price doesn't go negative
        $priceAfterDiscounts = max(0, $priceAfterDiscounts);
        
        $data['current_price'] = $priceAfterDiscounts;
        $data['item_discounts'] = $itemDiscounts;
        $data['item_discount_total'] = $totalDiscountAmount;

        return $next($data);
    }

    /**
     * Get applicable discounts for a cart line.
     */
    protected function getApplicableDiscounts(CartLine $line, Cart $cart): Collection
    {
        $purchasable = $line->purchasable;
        
        if (!$purchasable instanceof ProductVariant) {
            return collect();
        }

        // Get active discounts that apply to this product/variant
        $discounts = Discount::where('active', true)
            ->where(function($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->get()
            ->filter(function($discount) use ($purchasable, $cart) {
                // Check if discount applies to this purchasable
                return $this->discountAppliesToPurchasable($discount, $purchasable, $cart);
            });

        return $discounts;
    }

    /**
     * Check if discount applies to purchasable.
     */
    protected function discountAppliesToPurchasable(Discount $discount, ProductVariant $purchasable, Cart $cart): bool
    {
        // Check if discount has purchasables (product/variant specific)
        // Lunar uses purchasables() relationship method, check for 'reward' type
        $discountPurchasables = $discount->purchasables()
            ->where('type', 'reward')
            ->get();
        
        if ($discountPurchasables->isEmpty()) {
            return false; // Not an item-level discount
        }

        // Check if this variant is in the discount's purchasables
        return $discountPurchasables->contains(function($p) use ($purchasable) {
            return $p->purchasable_type === get_class($purchasable) 
                && $p->purchasable_id === $purchasable->id;
        });
    }

    /**
     * Calculate discount amount for a discount.
     */
    protected function calculateDiscountAmount(Discount $discount, int $price, CartLine $line): int
    {
        $data = $discount->data ?? [];
        
        // Percentage discount
        if (isset($data['percentage'])) {
            $amount = (int) round($price * ($data['percentage'] / 100));
            
            // Check max discount cap
            if (isset($data['max_discount_amount']) && $amount > $data['max_discount_amount']) {
                $amount = $data['max_discount_amount'];
            }
            
            return $amount;
        }
        
        // Fixed amount discount
        if (isset($data['fixed_amount'])) {
            return min($data['fixed_amount'], $price);
        }
        
        return 0;
    }

    /**
     * Get discount type.
     */
    protected function getDiscountType(Discount $discount): string
    {
        $data = $discount->data ?? [];
        
        if (isset($data['percentage'])) {
            return 'percentage';
        }
        
        if (isset($data['fixed_amount'])) {
            return 'fixed';
        }
        
        return 'unknown';
    }

    /**
     * Get discount version (for audit trail).
     */
    protected function getDiscountVersion(Discount $discount): string
    {
        // Use updated_at timestamp as version identifier
        return $discount->updated_at?->timestamp ?? '1.0';
    }
}


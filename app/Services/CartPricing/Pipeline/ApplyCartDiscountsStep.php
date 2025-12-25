<?php

namespace App\Services\CartPricing\Pipeline;

use App\Services\CartPricing\DTOs\CartDiscount;
use Illuminate\Support\Collection;
use Lunar\Models\Cart;
use Lunar\Models\Discount;

/**
 * Step 5: Apply cart-level discounts.
 * 
 * Applies cart-wide discounts (coupon codes, etc.) and distributes
 * them proportionally across line items.
 */
class ApplyCartDiscountsStep
{
    /**
     * Apply cart-level discounts.
     */
    public function handle(array $data, Cart $cart, callable $next): array
    {
        $lineItems = $data['line_items'] ?? [];
        $cartSubtotal = $data['cart_subtotal'] ?? 0;
        
        // Get cart-level discounts
        $cartDiscounts = $this->getCartDiscounts($cart);
        
        $appliedCartDiscounts = collect();
        $totalCartDiscount = 0;

        foreach ($cartDiscounts as $discount) {
            $discountAmount = $this->calculateCartDiscountAmount($discount, $cartSubtotal, $cart);
            
            if ($discountAmount > 0) {
                // Distribute discount across line items proportionally
                $distribution = $this->distributeDiscount($discountAmount, $lineItems, $cartSubtotal);
                
                $appliedCartDiscounts->push(new CartDiscount(
                    discountId: (string) $discount->id,
                    discountVersion: $this->getDiscountVersion($discount),
                    discountName: $discount->name ?? 'Cart Discount',
                    amount: $discountAmount,
                    type: $this->getDiscountType($discount),
                    distribution: $distribution,
                ));
                
                $totalCartDiscount += $discountAmount;
                
                // Update line items with distributed discount
                foreach ($distribution as $lineId => $lineDiscount) {
                    if (isset($lineItems[$lineId])) {
                        $lineItems[$lineId]['cart_discount'] = ($lineItems[$lineId]['cart_discount'] ?? 0) + $lineDiscount;
                        $lineItems[$lineId]['current_price'] -= $lineDiscount;
                        $lineItems[$lineId]['current_price'] = max(0, $lineItems[$lineId]['current_price']);
                    }
                }
                
                // Store in applied rules
                $data['applied_rules'][] = [
                    'type' => 'cart_discount',
                    'discount_id' => $discount->id,
                    'discount_version' => $this->getDiscountVersion($discount),
                    'amount' => $discountAmount,
                ];
            }
        }

        $data['cart_discounts'] = $appliedCartDiscounts;
        $data['cart_discount_total'] = $totalCartDiscount;
        $data['line_items'] = $lineItems;

        return $next($data);
    }

    /**
     * Get cart-level discounts.
     */
    protected function getCartDiscounts(Cart $cart): Collection
    {
        $discounts = collect();
        
        // Check for coupon code
        if ($cart->coupon_code) {
            $discount = Discount::where('coupon', $cart->coupon_code)
                ->where('active', true)
                ->where(function($query) {
                    $query->whereNull('starts_at')
                        ->orWhere('starts_at', '<=', now());
                })
                ->where(function($query) {
                    $query->whereNull('ends_at')
                        ->orWhere('ends_at', '>=', now());
                })
                ->first();
            
            if ($discount && $this->discountAppliesToCart($discount, $cart)) {
                $discounts->push($discount);
            }
        }
        
        // Get other cart-level discounts (not tied to coupon codes)
        $cartLevelDiscounts = Discount::where('active', true)
            ->whereNull('coupon') // Not coupon-based
            ->where(function($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->get()
            ->filter(function($discount) use ($cart) {
                return $this->discountAppliesToCart($discount, $cart);
            });
        
        return $discounts->merge($cartLevelDiscounts);
    }

    /**
     * Check if discount applies to cart.
     */
    protected function discountAppliesToCart(Discount $discount, Cart $cart): bool
    {
        $data = $discount->data ?? [];
        
        // Check minimum cart value
        if (isset($data['min_cart_value'])) {
            $cartSubtotal = $cart->subTotal?->value ?? 0;
            if ($cartSubtotal < $data['min_cart_value']) {
                return false;
            }
        }
        
        // Check customer group restrictions
        if ($cart->customer) {
            $customerGroups = $cart->customer?->customerGroups?->pluck('id') ?? collect();
            $discountGroups = $discount->customerGroups()->pluck('id');
            
            if ($discountGroups->isNotEmpty() && $customerGroups->intersect($discountGroups)->isEmpty()) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Calculate cart discount amount.
     */
    protected function calculateCartDiscountAmount(Discount $discount, int $cartSubtotal, Cart $cart): int
    {
        $data = $discount->data ?? [];
        
        // Percentage discount
        if (isset($data['percentage'])) {
            $amount = (int) round($cartSubtotal * ($data['percentage'] / 100));
            
            // Check max discount cap
            if (isset($data['max_discount_amount']) && $amount > $data['max_discount_amount']) {
                $amount = $data['max_discount_amount'];
            }
            
            return $amount;
        }
        
        // Fixed amount discount
        if (isset($data['fixed_amount'])) {
            return min($data['fixed_amount'], $cartSubtotal);
        }
        
        return 0;
    }

    /**
     * Distribute discount proportionally across line items.
     */
    protected function distributeDiscount(int $discountAmount, array $lineItems, int $cartSubtotal): array
    {
        if ($cartSubtotal <= 0) {
            return [];
        }

        $distribution = [];
        $distributed = 0;

        foreach ($lineItems as $lineId => $lineData) {
            $lineTotal = ($lineData['current_price'] ?? 0) * ($lineData['quantity'] ?? 1);
            $proportion = $lineTotal / $cartSubtotal;
            $lineDiscount = (int) round($discountAmount * $proportion);
            
            $distribution[$lineId] = $lineDiscount;
            $distributed += $lineDiscount;
        }

        // Adjust for rounding differences
        $difference = $discountAmount - $distributed;
        if ($difference !== 0 && !empty($distribution)) {
            $firstLineId = array_key_first($distribution);
            $distribution[$firstLineId] += $difference;
        }

        return $distribution;
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
     * Get discount version.
     */
    protected function getDiscountVersion(Discount $discount): string
    {
        return $discount->updated_at?->timestamp ?? '1.0';
    }
}


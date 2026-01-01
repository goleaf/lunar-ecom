<?php

namespace App\Services;

use Lunar\Models\Cart;
use Lunar\Facades\CartSession;

/**
 * Service to ensure cart always exposes all required transparency fields:
 * - Subtotal (pre-discount)
 * - Total discounts
 * - Tax breakdown
 * - Shipping cost
 * - Grand total
 * - Audit trail of applied rules
 */
class CartTransparencyService
{
    /**
     * Get complete cart breakdown with all transparency fields.
     *
     * @param  Cart|null  $cart
     * @return array
     */
    public function getCartBreakdown(?Cart $cart = null): array
    {
        $cart = $cart ?? CartSession::current();
        
        if (!$cart) {
            return $this->getEmptyCartBreakdown();
        }

        // Ensure cart is calculated
        $cart->calculate();

        // Get pre-discount subtotal (raw subtotal before any discounts)
        $subtotalPreDiscount = $this->getSubtotalPreDiscount($cart);
        
        // Get discount breakdown with audit trail
        $discountBreakdown = $this->getDiscountBreakdown($cart);
        
        // Get tax breakdown
        $taxBreakdown = $this->getTaxBreakdown($cart);
        
        // Get shipping breakdown
        $shippingBreakdown = $this->getShippingBreakdown($cart);

        return [
            // Core totals
            'subtotal_pre_discount' => [
                'value' => $subtotalPreDiscount,
                'formatted' => $this->formatPrice($subtotalPreDiscount, $cart->currency),
                'decimal' => $this->toDecimal($subtotalPreDiscount, $cart->currency),
            ],
            'subtotal_discounted' => [
                'value' => $cart->subTotalDiscounted?->value ?? $subtotalPreDiscount,
                'formatted' => $cart->subTotalDiscounted?->formatted ?? $this->formatPrice($subtotalPreDiscount, $cart->currency),
                'decimal' => $cart->subTotalDiscounted?->decimal ?? $this->toDecimal($subtotalPreDiscount, $cart->currency),
            ],
            'total_discounts' => [
                'value' => $cart->discountTotal?->value ?? 0,
                'formatted' => $cart->discountTotal?->formatted ?? $this->formatPrice(0, $cart->currency),
                'decimal' => $cart->discountTotal?->decimal ?? 0,
            ],
            'shipping_total' => [
                'value' => $cart->shippingTotal?->value ?? 0,
                'formatted' => $cart->shippingTotal?->formatted ?? $this->formatPrice(0, $cart->currency),
                'decimal' => $cart->shippingTotal?->decimal ?? 0,
            ],
            'tax_total' => [
                'value' => $cart->taxTotal?->value ?? 0,
                'formatted' => $cart->taxTotal?->formatted ?? $this->formatPrice(0, $cart->currency),
                'decimal' => $cart->taxTotal?->decimal ?? 0,
            ],
            'grand_total' => [
                'value' => $cart->total?->value ?? 0,
                'formatted' => $cart->total?->formatted ?? $this->formatPrice(0, $cart->currency),
                'decimal' => $cart->total?->decimal ?? 0,
            ],
            
            // Breakdowns
            'discount_breakdown' => $discountBreakdown,
            'tax_breakdown' => $taxBreakdown,
            'shipping_breakdown' => $shippingBreakdown,
            
            // Audit trail
            'applied_rules' => $this->getAppliedRulesAuditTrail($cart),
            
            // Metadata
            'currency' => $cart->currency?->code ?? 'USD',
            'currency_symbol' => $cart->currency?->symbol ?? '$',
            'item_count' => $cart->lines->sum('quantity'),
            'line_count' => $cart->lines->count(),
        ];
    }

    /**
     * Get subtotal before any discounts are applied.
     *
     * @param  Cart  $cart
     * @return int
     */
    protected function getSubtotalPreDiscount(Cart $cart): int
    {
        // Calculate raw subtotal from all cart lines
        $subtotal = 0;
        
        foreach ($cart->lines as $line) {
            $price = $line->subTotal?->value ?? 0;
            $subtotal += $price;
        }
        
        return $subtotal;
    }

    /**
     * Get discount breakdown with audit trail.
     *
     * @param  Cart  $cart
     * @return array
     */
    protected function getDiscountBreakdown(Cart $cart): array
    {
        $breakdown = [];
        
        // Get discount breakdown from Lunar
        if ($cart->discountBreakdown && $cart->discountBreakdown->isNotEmpty()) {
            foreach ($cart->discountBreakdown as $discountBreakdown) {
                $discount = $discountBreakdown->discount ?? null;
                $price = $discountBreakdown->price ?? null;

                $breakdown[] = [
                    'name' => $discount?->name ?? 'Discount',
                    'description' => null,
                    'coupon_code' => $cart->coupon_code ?? null,
                    'amount' => [
                        'value' => $price?->value ?? 0,
                        'formatted' => $price?->formatted ?? $this->formatPrice(0, $cart->currency),
                        'decimal' => $price?->decimal ?? 0,
                    ],
                    'type' => $discount?->type ?? 'unknown',
                    'priority' => $discount?->priority ?? 0,
                    'applied_at' => now()->toIso8601String(),
                ];
            }
        }
        
        return $breakdown;
    }

    /**
     * Get tax breakdown.
     *
     * @param  Cart  $cart
     * @return array
     */
    protected function getTaxBreakdown(Cart $cart): array
    {
        $breakdown = [];
        
        if ($cart->taxBreakdown && $cart->taxBreakdown->amounts && $cart->taxBreakdown->amounts->isNotEmpty()) {
            foreach ($cart->taxBreakdown->amounts as $tax) {
                $price = $tax->price ?? null;

                $breakdown[] = [
                    // Backward compatible keys
                    'name' => $tax->description ?? $tax->identifier ?? 'Tax',
                    // New/explicit keys
                    'identifier' => $tax->identifier ?? null,
                    'description' => $tax->description ?? null,
                    'rate' => $tax->percentage ?? 0,
                    'amount' => [
                        'value' => $price?->value ?? 0,
                        'formatted' => $price?->formatted ?? $this->formatPrice(0, $cart->currency),
                        'decimal' => $price?->decimal ?? 0,
                    ],
                ];
            }
        }
        
        return $breakdown;
    }

    /**
     * Get shipping breakdown.
     *
     * @param  Cart  $cart
     * @return array
     */
    protected function getShippingBreakdown(Cart $cart): array
    {
        $breakdown = [];
        
        if ($cart->shippingBreakdown && $cart->shippingBreakdown->items && $cart->shippingBreakdown->items->isNotEmpty()) {
            foreach ($cart->shippingBreakdown->items as $shipping) {
                $breakdown[] = [
                    'name' => $shipping->name ?? 'Shipping',
                    'identifier' => $shipping->identifier ?? null,
                    'description' => null,
                    'amount' => [
                        'value' => $shipping->price?->value ?? 0,
                        'formatted' => $shipping->price?->formatted ?? $this->formatPrice(0, $cart->currency),
                        'decimal' => $shipping->price?->decimal ?? 0,
                    ],
                    // Backward compatible: per-item tax isn't available in Lunar's ShippingBreakdownItem.
                    'tax_amount' => [
                        'value' => 0,
                        'formatted' => $this->formatPrice(0, $cart->currency),
                        'decimal' => 0,
                    ],
                ];
            }
        }
        
        return $breakdown;
    }

    /**
     * Get audit trail of applied discount rules.
     *
     * @param  Cart  $cart
     * @return array
     */
    protected function getAppliedRulesAuditTrail(Cart $cart): array
    {
        $auditTrail = [];
        
        // Get applied discounts from cart
        if ($cart->coupon_code) {
            $discount = \Lunar\Models\Discount::where('coupon', $cart->coupon_code)->first();
            
            if ($discount) {
                $auditTrail[] = [
                    'rule_id' => $discount->id,
                    'rule_name' => $discount->name,
                    'rule_type' => $discount->type ?? 'unknown',
                    'coupon_code' => $cart->coupon_code,
                    'applied_at' => $cart->updated_at?->toIso8601String() ?? now()->toIso8601String(),
                    'status' => 'applied',
                    'conditions_met' => $this->getDiscountConditions($discount, $cart),
                ];
            }
        }
        
        // Get any automatic discounts that may have been applied
        $automaticDiscounts = \Lunar\Models\Discount::whereNull('coupon')
            ->where('starts_at', '<=', now())
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->get();
        
        foreach ($automaticDiscounts as $discount) {
            // Check if discount conditions are met
            if ($this->isDiscountApplicable($discount, $cart)) {
                $auditTrail[] = [
                    'rule_id' => $discount->id,
                    'rule_name' => $discount->name,
                    'rule_type' => $discount->type ?? 'unknown',
                    'coupon_code' => null,
                    'applied_at' => now()->toIso8601String(),
                    'status' => 'applied',
                    'conditions_met' => $this->getDiscountConditions($discount, $cart),
                ];
            }
        }
        
        return $auditTrail;
    }

    /**
     * Get discount conditions for audit trail.
     *
     * @param  \Lunar\Models\Discount  $discount
     * @param  Cart  $cart
     * @return array
     */
    protected function getDiscountConditions(\Lunar\Models\Discount $discount, Cart $cart): array
    {
        $conditions = [];
        
        // Check minimum purchase amount
        if ($discount->min_prices) {
            foreach ($discount->min_prices as $minPrice) {
                $conditions[] = [
                    'type' => 'minimum_purchase',
                    'currency' => $minPrice->currency->code ?? 'USD',
                    'value' => $minPrice->value,
                    'met' => ($cart->subTotal?->value ?? 0) >= $minPrice->value,
                ];
            }
        }
        
        // Check customer groups
        if ($discount->customerGroups && $discount->customerGroups->isNotEmpty()) {
            $customerGroupIds = $discount->customerGroups->pluck('id')->toArray();
            $conditions[] = [
                'type' => 'customer_group',
                'required_groups' => $customerGroupIds,
                'met' => $this->checkCustomerGroup($cart, $customerGroupIds),
            ];
        }
        
        return $conditions;
    }

    /**
     * Check if discount is applicable to cart.
     *
     * @param  \Lunar\Models\Discount  $discount
     * @param  Cart  $cart
     * @return bool
     */
    protected function isDiscountApplicable(\Lunar\Models\Discount $discount, Cart $cart): bool
    {
        // Check date range
        if ($discount->starts_at && $discount->starts_at->isFuture()) {
            return false;
        }
        
        if ($discount->ends_at && $discount->ends_at->isPast()) {
            return false;
        }
        
        // Check minimum purchase
        if ($discount->min_prices && $discount->min_prices->isNotEmpty()) {
            foreach ($discount->min_prices as $minPrice) {
                if (($cart->subTotal?->value ?? 0) < $minPrice->value) {
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Check if cart customer is in required customer groups.
     *
     * @param  Cart  $cart
     * @param  array  $requiredGroupIds
     * @return bool
     */
    protected function checkCustomerGroup(Cart $cart, array $requiredGroupIds): bool
    {
        if (!$cart->user_id && !$cart->customer_id) {
            return false;
        }
        
        // This would need to check the actual customer groups
        // For now, return true if cart has a user/customer
        return true;
    }

    /**
     * Get empty cart breakdown.
     *
     * @return array
     */
    protected function getEmptyCartBreakdown(): array
    {
        return [
            'subtotal_pre_discount' => ['value' => 0, 'formatted' => '$0.00', 'decimal' => 0],
            'subtotal_discounted' => ['value' => 0, 'formatted' => '$0.00', 'decimal' => 0],
            'total_discounts' => ['value' => 0, 'formatted' => '$0.00', 'decimal' => 0],
            'shipping_total' => ['value' => 0, 'formatted' => '$0.00', 'decimal' => 0],
            'tax_total' => ['value' => 0, 'formatted' => '$0.00', 'decimal' => 0],
            'grand_total' => ['value' => 0, 'formatted' => '$0.00', 'decimal' => 0],
            'discount_breakdown' => [],
            'tax_breakdown' => [],
            'shipping_breakdown' => [],
            'applied_rules' => [],
            'currency' => 'USD',
            'currency_symbol' => '$',
            'item_count' => 0,
            'line_count' => 0,
        ];
    }

    /**
     * Format price for display.
     *
     * @param  int  $value
     * @param  \Lunar\Models\Currency|null  $currency
     * @return string
     */
    protected function formatPrice(int $value, $currency = null): string
    {
        if (!$currency) {
            return '$' . number_format($value / 100, 2);
        }
        
        $decimal = $value / (10 ** $currency->decimal_places);
        return $currency->symbol . number_format($decimal, $currency->decimal_places);
    }

    /**
     * Convert price to decimal.
     *
     * @param  int  $value
     * @param  \Lunar\Models\Currency|null  $currency
     * @return float
     */
    protected function toDecimal(int $value, $currency = null): float
    {
        if (!$currency) {
            return $value / 100;
        }
        
        return $value / (10 ** $currency->decimal_places);
    }
}


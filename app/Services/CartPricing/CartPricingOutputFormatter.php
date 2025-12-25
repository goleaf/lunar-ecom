<?php

namespace App\Services\CartPricing;

use App\Services\CartPricing\DTOs\CartPricingResult;
use Lunar\Models\Cart;

/**
 * Cart Pricing Output Formatter.
 * 
 * Formats pricing results for API responses with complete audit trail.
 */
class CartPricingOutputFormatter
{
    /**
     * Format cart pricing for API response.
     */
    public function formatCartPricing(Cart $cart): array
    {
        // Get pricing snapshot if available
        $snapshot = $cart->pricing_snapshot ?? [];
        
        if (empty($snapshot)) {
            // Return basic cart totals if no snapshot
            return $this->formatBasicCart($cart);
        }
        
        return [
            'subtotal' => $snapshot['subtotal'] ?? ($cart->subTotal?->value ?? 0),
            'subtotal_decimal' => ($snapshot['subtotal'] ?? ($cart->subTotal?->value ?? 0)) / 100,
            'total_discounts' => $snapshot['total_discounts'] ?? ($cart->discountTotal?->value ?? 0),
            'total_discounts_decimal' => ($snapshot['total_discounts'] ?? ($cart->discountTotal?->value ?? 0)) / 100,
            'discount_breakdown' => $snapshot['discount_breakdown'] ?? [],
            'tax_breakdown' => $snapshot['tax_breakdown'] ?? [],
            'tax_total' => $snapshot['tax_total'] ?? ($cart->taxTotal?->value ?? 0),
            'tax_total_decimal' => ($snapshot['tax_total'] ?? ($cart->taxTotal?->value ?? 0)) / 100,
            'shipping_cost' => $snapshot['shipping_cost'] ?? [],
            'shipping_total' => $snapshot['shipping_total'] ?? ($cart->shippingTotal?->value ?? 0),
            'shipping_total_decimal' => ($snapshot['shipping_total'] ?? ($cart->shippingTotal?->value ?? 0)) / 100,
            'grand_total' => $snapshot['grand_total'] ?? ($cart->total?->value ?? 0),
            'grand_total_decimal' => ($snapshot['grand_total'] ?? ($cart->total?->value ?? 0)) / 100,
            'audit_trail' => [
                'calculated_at' => $cart->last_reprice_at?->toIso8601String(),
                'pricing_version' => $cart->pricing_version ?? 0,
                'applied_rules' => $snapshot['applied_rules'] ?? [],
                'price_hash' => $snapshot['price_hash'] ?? null,
                'requires_reprice' => $cart->requires_reprice ?? false,
            ],
            'line_items' => $snapshot['line_items'] ?? [],
        ];
    }

    /**
     * Format CartPricingResult DTO for API response.
     */
    public function formatPricingResult(CartPricingResult $result): array
    {
        return $result->toArray();
    }

    /**
     * Format basic cart (when no pricing snapshot available).
     */
    protected function formatBasicCart(Cart $cart): array
    {
        return [
            'subtotal' => $cart->subTotal?->value ?? 0,
            'subtotal_decimal' => ($cart->subTotal?->value ?? 0) / 100,
            'total_discounts' => $cart->discountTotal?->value ?? 0,
            'total_discounts_decimal' => ($cart->discountTotal?->value ?? 0) / 100,
            'discount_breakdown' => [],
            'tax_breakdown' => [],
            'tax_total' => $cart->taxTotal?->value ?? 0,
            'tax_total_decimal' => ($cart->taxTotal?->value ?? 0) / 100,
            'shipping_cost' => [],
            'shipping_total' => $cart->shippingTotal?->value ?? 0,
            'shipping_total_decimal' => ($cart->shippingTotal?->value ?? 0) / 100,
            'grand_total' => $cart->total?->value ?? 0,
            'grand_total_decimal' => ($cart->total?->value ?? 0) / 100,
            'audit_trail' => [
                'calculated_at' => null,
                'pricing_version' => 0,
                'applied_rules' => [],
                'price_hash' => null,
                'requires_reprice' => true,
            ],
            'line_items' => [],
        ];
    }
}


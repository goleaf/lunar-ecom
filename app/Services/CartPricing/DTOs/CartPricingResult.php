<?php

namespace App\Services\CartPricing\DTOs;

use Illuminate\Support\Collection;

/**
 * Complete cart pricing result DTO.
 */
class CartPricingResult
{
    public function __construct(
        public readonly int $subtotal, // Pre-discount subtotal
        public readonly int $totalDiscounts, // Total discounts applied
        public readonly int $taxTotal, // Total tax amount
        public readonly int $shippingTotal, // Shipping cost
        public readonly int $grandTotal, // Final total
        public readonly Collection $lineItems, // Collection of LineItemPricing
        public readonly DiscountBreakdown $discountBreakdown, // Cart-level discount breakdown
        public readonly TaxBreakdown $taxBreakdown, // Tax breakdown
        public readonly ShippingCost $shippingCost, // Shipping details
        public readonly array $appliedRules, // All applied rule IDs and versions
        public readonly string $priceHash, // Hash for tamper detection
        public readonly \Carbon\Carbon $calculatedAt, // When pricing was calculated
        public readonly int $pricingVersion, // Pricing version number
    ) {}

    /**
     * Convert to array for API responses.
     */
    public function toArray(): array
    {
        return [
            'subtotal' => $this->subtotal,
            'subtotal_decimal' => $this->subtotal / 100,
            'total_discounts' => $this->totalDiscounts,
            'total_discounts_decimal' => $this->totalDiscounts / 100,
            'tax_total' => $this->taxTotal,
            'tax_total_decimal' => $this->taxTotal / 100,
            'shipping_total' => $this->shippingTotal,
            'shipping_total_decimal' => $this->shippingTotal / 100,
            'grand_total' => $this->grandTotal,
            'grand_total_decimal' => $this->grandTotal / 100,
            'line_items' => $this->lineItems->map(fn($item) => $item->toArray())->toArray(),
            'discount_breakdown' => $this->discountBreakdown->toArray(),
            'tax_breakdown' => $this->taxBreakdown->toArray(),
            'shipping_cost' => $this->shippingCost->toArray(),
            'applied_rules' => $this->appliedRules,
            'price_hash' => $this->priceHash,
            'calculated_at' => $this->calculatedAt->toIso8601String(),
            'pricing_version' => $this->pricingVersion,
        ];
    }
}


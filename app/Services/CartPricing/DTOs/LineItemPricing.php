<?php

namespace App\Services\CartPricing\DTOs;

/**
 * Individual line item pricing DTO.
 */
class LineItemPricing
{
    public function __construct(
        public readonly int $cartLineId,
        public readonly int $originalUnitPrice, // Base price before modifications
        public readonly int $finalUnitPrice, // Final price after all calculations
        public readonly int $quantity,
        public readonly int $lineTotal, // finalUnitPrice * quantity
        public readonly DiscountBreakdown $discountBreakdown, // Item-level discounts
        public readonly int $taxBase, // Price used for tax calculation
        public readonly int $taxAmount, // Tax amount for this line
        public readonly array $appliedRules, // Rule IDs and versions applied
        public readonly string $priceSource, // 'base', 'contract', 'promo', 'matrix'
        public readonly ?int $tierPrice = null, // Quantity tier price if applicable
        public readonly ?string $tierName = null, // Tier name/description
    ) {}

    /**
     * Convert to array for API responses.
     */
    public function toArray(): array
    {
        return [
            'cart_line_id' => $this->cartLineId,
            'original_unit_price' => $this->originalUnitPrice,
            'original_unit_price_decimal' => $this->originalUnitPrice / 100,
            'final_unit_price' => $this->finalUnitPrice,
            'final_unit_price_decimal' => $this->finalUnitPrice / 100,
            'quantity' => $this->quantity,
            'line_total' => $this->lineTotal,
            'line_total_decimal' => $this->lineTotal / 100,
            'discount_breakdown' => $this->discountBreakdown->toArray(),
            'tax_base' => $this->taxBase,
            'tax_base_decimal' => $this->taxBase / 100,
            'tax_amount' => $this->taxAmount,
            'tax_amount_decimal' => $this->taxAmount / 100,
            'applied_rules' => $this->appliedRules,
            'price_source' => $this->priceSource,
            'tier_price' => $this->tierPrice,
            'tier_price_decimal' => $this->tierPrice ? $this->tierPrice / 100 : null,
            'tier_name' => $this->tierName,
        ];
    }
}


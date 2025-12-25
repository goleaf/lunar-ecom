<?php

namespace App\Services\CartPricing\DTOs;

use Illuminate\Support\Collection;

/**
 * Tax breakdown DTO.
 */
class TaxBreakdown
{
    public function __construct(
        public readonly int $totalAmount, // Total tax amount in cents
        public readonly Collection $lineItemTaxes, // Collection of LineItemTax
        public readonly Collection $taxRates, // Collection of TaxRate
    ) {}

    /**
     * Convert to array for API responses.
     */
    public function toArray(): array
    {
        return [
            'total_amount' => $this->totalAmount,
            'total_amount_decimal' => $this->totalAmount / 100,
            'line_item_taxes' => $this->lineItemTaxes->map(fn($tax) => $tax->toArray())->toArray(),
            'tax_rates' => $this->taxRates->map(fn($rate) => $rate->toArray())->toArray(),
        ];
    }
}

/**
 * Line item tax DTO.
 */
class LineItemTax
{
    public function __construct(
        public readonly int $cartLineId,
        public readonly int $taxBase, // Taxable amount
        public readonly int $taxAmount, // Tax amount
        public readonly float $taxRate, // Tax rate (e.g., 0.20 for 20%)
        public readonly string $taxClass, // Tax class name
    ) {}

    public function toArray(): array
    {
        return [
            'cart_line_id' => $this->cartLineId,
            'tax_base' => $this->taxBase,
            'tax_base_decimal' => $this->taxBase / 100,
            'tax_amount' => $this->taxAmount,
            'tax_amount_decimal' => $this->taxAmount / 100,
            'tax_rate' => $this->taxRate,
            'tax_rate_percent' => $this->taxRate * 100,
            'tax_class' => $this->taxClass,
        ];
    }
}

/**
 * Tax rate summary DTO.
 */
class TaxRate
{
    public function __construct(
        public readonly float $rate, // Tax rate (e.g., 0.20 for 20%)
        public readonly string $name, // Tax name (e.g., "VAT", "Sales Tax")
        public readonly int $amount, // Total tax amount at this rate
    ) {}

    public function toArray(): array
    {
        return [
            'rate' => $this->rate,
            'rate_percent' => $this->rate * 100,
            'name' => $this->name,
            'amount' => $this->amount,
            'amount_decimal' => $this->amount / 100,
        ];
    }
}


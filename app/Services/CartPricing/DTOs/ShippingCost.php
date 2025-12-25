<?php

namespace App\Services\CartPricing\DTOs;

/**
 * Shipping cost DTO.
 */
class ShippingCost
{
    public function __construct(
        public readonly int $amount, // Shipping cost in cents
        public readonly ?string $shippingOptionId = null, // Shipping option identifier
        public readonly ?string $shippingOptionName = null, // Shipping option name
        public readonly ?string $shippingOptionDescription = null, // Shipping option description
        public readonly ?int $taxAmount = null, // Tax on shipping
        public readonly ?float $taxRate = null, // Tax rate on shipping
    ) {}

    /**
     * Convert to array for API responses.
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'amount_decimal' => $this->amount / 100,
            'shipping_option_id' => $this->shippingOptionId,
            'shipping_option_name' => $this->shippingOptionName,
            'shipping_option_description' => $this->shippingOptionDescription,
            'tax_amount' => $this->taxAmount,
            'tax_amount_decimal' => $this->taxAmount ? $this->taxAmount / 100 : null,
            'tax_rate' => $this->taxRate,
            'tax_rate_percent' => $this->taxRate ? $this->taxRate * 100 : null,
        ];
    }
}


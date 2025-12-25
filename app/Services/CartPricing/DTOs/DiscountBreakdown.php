<?php

namespace App\Services\CartPricing\DTOs;

use Illuminate\Support\Collection;

/**
 * Discount breakdown DTO.
 */
class DiscountBreakdown
{
    public function __construct(
        public readonly int $totalAmount, // Total discount amount in cents
        public readonly Collection $itemDiscounts, // Collection of ItemDiscount
        public readonly Collection $cartDiscounts, // Collection of CartDiscount
    ) {}

    /**
     * Convert to array for API responses.
     */
    public function toArray(): array
    {
        return [
            'total_amount' => $this->totalAmount,
            'total_amount_decimal' => $this->totalAmount / 100,
            'item_discounts' => $this->itemDiscounts->map(fn($discount) => $discount->toArray())->toArray(),
            'cart_discounts' => $this->cartDiscounts->map(fn($discount) => $discount->toArray())->toArray(),
        ];
    }
}

/**
 * Individual item discount DTO.
 */
class ItemDiscount
{
    public function __construct(
        public readonly int $cartLineId,
        public readonly string $discountId, // Discount rule ID
        public readonly string $discountVersion, // Discount rule version
        public readonly string $discountName, // Discount name/description
        public readonly int $amount, // Discount amount in cents
        public readonly string $type, // 'percentage', 'fixed', 'bogo', etc.
    ) {}

    public function toArray(): array
    {
        return [
            'cart_line_id' => $this->cartLineId,
            'discount_id' => $this->discountId,
            'discount_version' => $this->discountVersion,
            'discount_name' => $this->discountName,
            'amount' => $this->amount,
            'amount_decimal' => $this->amount / 100,
            'type' => $this->type,
        ];
    }
}

/**
 * Cart-level discount DTO.
 */
class CartDiscount
{
    public function __construct(
        public readonly string $discountId, // Discount rule ID
        public readonly string $discountVersion, // Discount rule version
        public readonly string $discountName, // Discount name/description
        public readonly int $amount, // Discount amount in cents
        public readonly string $type, // 'percentage', 'fixed', etc.
        public readonly array $distribution, // How discount is distributed across line items
    ) {}

    public function toArray(): array
    {
        return [
            'discount_id' => $this->discountId,
            'discount_version' => $this->discountVersion,
            'discount_name' => $this->discountName,
            'amount' => $this->amount,
            'amount_decimal' => $this->amount / 100,
            'type' => $this->type,
            'distribution' => $this->distribution, // ['line_id' => amount]
        ];
    }
}


<?php

namespace App\Services\DiscountStacking\DTOs;

use App\Enums\DiscountType;
use Lunar\Models\Discount;

/**
 * Discount Application DTO
 * 
 * Represents a single discount application with metadata.
 */
readonly class DiscountApplication
{
    public function __construct(
        public Discount $discount,
        public int $amount,
        public DiscountType $type,
        public string $reason,
    ) {}

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'discount_id' => $this->discount->id,
            'discount_name' => $this->discount->name,
            'amount' => $this->amount,
            'type' => $this->type->value,
            'reason' => $this->reason,
        ];
    }
}



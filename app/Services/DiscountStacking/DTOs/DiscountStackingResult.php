<?php

namespace App\Services\DiscountStacking\DTOs;

use Illuminate\Support\Collection;

/**
 * Discount Stacking Result DTO
 * 
 * Contains the result of applying discount stacking rules.
 */
readonly class DiscountStackingResult
{
    public function __construct(
        public Collection $applications,
        public int $totalDiscount,
        public int $remainingAmount,
        public int $baseAmount,
        public array $appliedRules,
    ) {}

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'applications' => $this->applications->map(fn($app) => $app->toArray())->toArray(),
            'total_discount' => $this->totalDiscount,
            'remaining_amount' => $this->remainingAmount,
            'base_amount' => $this->baseAmount,
            'applied_rules' => $this->appliedRules,
        ];
    }
}



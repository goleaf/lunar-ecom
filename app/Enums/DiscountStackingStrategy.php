<?php

namespace App\Enums;

use Osama\LaravelEnums\Concerns\EnumTranslatable;

/**
 * Discount Stacking Strategies
 * 
 * Defines the strategy used when multiple discounts are applicable.
 */
enum DiscountStackingStrategy: string
{
    use EnumTranslatable;

    case BEST_OF = 'best_of';
    case PRIORITY_FIRST = 'priority_first';
    case CUMULATIVE = 'cumulative';
    case EXCLUSIVE_OVERRIDE = 'exclusive_override';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return $this->trans();
    }

    /**
     * Get description
     */
    public function description(): string
    {
        return match($this) {
            self::BEST_OF => 'Selects the single highest discount amount',
            self::PRIORITY_FIRST => 'Applies discounts in priority order until one is exclusive',
            self::CUMULATIVE => 'Applies all applicable discounts together',
            self::EXCLUSIVE_OVERRIDE => 'Exclusive discounts override all others',
        };
    }
}



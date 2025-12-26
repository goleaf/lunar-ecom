<?php

namespace App\Enums;

/**
 * Discount Stacking Strategies
 * 
 * Defines the strategy used when multiple discounts are applicable.
 */
enum DiscountStackingStrategy: string
{
    case BEST_OF = 'best_of';
    case PRIORITY_FIRST = 'priority_first';
    case CUMULATIVE = 'cumulative';
    case EXCLUSIVE_OVERRIDE = 'exclusive_override';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::BEST_OF => 'Best Of (Choose Max Discount)',
            self::PRIORITY_FIRST => 'Priority First',
            self::CUMULATIVE => 'Cumulative',
            self::EXCLUSIVE_OVERRIDE => 'Exclusive Override',
        };
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



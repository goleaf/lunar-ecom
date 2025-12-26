<?php

namespace App\Enums;

/**
 * Discount Stacking Modes
 * 
 * Defines how discounts can be combined with other discounts.
 */
enum DiscountStackingMode: string
{
    case STACKABLE = 'stackable';
    case NON_STACKABLE = 'non_stackable';
    case EXCLUSIVE = 'exclusive';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return match($this) {
            self::STACKABLE => 'Stackable',
            self::NON_STACKABLE => 'Non-Stackable',
            self::EXCLUSIVE => 'Exclusive',
        };
    }

    /**
     * Check if this mode allows stacking
     */
    public function allowsStacking(): bool
    {
        return $this === self::STACKABLE;
    }

    /**
     * Check if this mode is exclusive
     */
    public function isExclusive(): bool
    {
        return $this === self::EXCLUSIVE;
    }
}



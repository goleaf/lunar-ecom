<?php

namespace App\Enums;

use Osama\LaravelEnums\Concerns\EnumTranslatable;

/**
 * Discount Stacking Modes
 * 
 * Defines how discounts can be combined with other discounts.
 */
enum DiscountStackingMode: string
{
    use EnumTranslatable;

    case STACKABLE = 'stackable';
    case NON_STACKABLE = 'non_stackable';
    case EXCLUSIVE = 'exclusive';

    /**
     * Get human-readable label
     */
    public function label(): string
    {
        return $this->trans();
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



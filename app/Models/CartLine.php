<?php

namespace App\Models;

use Lunar\Base\ValueObjects\Cart\TaxBreakdown;
use Lunar\Models\CartLine as LunarCartLine;

/**
 * Custom CartLine model to patch Lunar's typed property defaults.
 *
 * Lunar's CachesProperties::refresh() resets cachable properties to their default
 * values via Reflection. The upstream CartLine::$taxBreakdown has no default and
 * is non-nullable, which can cause a TypeError when refreshing.
 */
class CartLine extends LunarCartLine
{
    /**
     * Ensure the property is initialized for PHP versions that don't support
     * `new` in property initializers.
     */
    public TaxBreakdown $taxBreakdown;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Lunar's base CartLine leaves this uninitialized, but typed non-nullable.
        $this->taxBreakdown = new TaxBreakdown();
    }

    /**
     * Override Lunar's CachesProperties::refresh() behavior for typed properties
     * without defaults (notably $taxBreakdown).
     */
    public function refresh()
    {
        // Call Eloquent's base refresh (Lunar overrides refresh via trait).
        (new \ReflectionMethod(\Illuminate\Database\Eloquent\Model::class, 'refresh'))
            ->invoke($this);

        $ro = new \ReflectionClass($this);

        foreach ($this->cachableProperties as $property) {
            $defaultValue = $ro->getProperty($property)->getDefaultValue();

            if ($property === 'taxBreakdown' && $defaultValue === null) {
                $defaultValue = new TaxBreakdown();
            }

            $this->{$property} = $defaultValue;
        }

        return $this;
    }
}



<?php

namespace App\Lunar\Taxation\TaxCalculators;

use Lunar\Base\TaxCalculatorInterface;
use Lunar\DataTypes\Price;
use Lunar\Models\Cart;
use Lunar\Models\Currency;

/**
 * Example standard tax calculator (e.g., VAT, Sales Tax).
 * 
 * To use this, register it in config/lunar/taxes.php:
 * 
 * 'calculators' => [
 *     'standard' => StandardTaxCalculator::class,
 * ]
 */
class StandardTaxCalculator implements TaxCalculatorInterface
{
    /**
     * Tax rate (e.g., 0.20 for 20% VAT)
     */
    protected float $taxRate = 0.20;

    /**
     * Calculate tax for the cart.
     */
    public function calculate(Cart $cart, Currency $currency): Price
    {
        // Calculate tax on subtotal
        $taxAmount = (int) ($cart->subTotal->value * $this->taxRate);
        
        return new Price($taxAmount, $currency, 1);
    }

    /**
     * Set the tax rate.
     */
    public function setTaxRate(float $rate): self
    {
        $this->taxRate = $rate;
        return $this;
    }
}


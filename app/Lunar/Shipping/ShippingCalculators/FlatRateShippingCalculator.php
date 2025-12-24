<?php

namespace App\Lunar\Shipping\ShippingCalculators;

use Lunar\Base\ShippingCalculatorInterface;
use Lunar\DataTypes\Price;
use Lunar\Models\Cart;
use Lunar\Models\Currency;

/**
 * Example flat-rate shipping calculator.
 * 
 * To use this, register it in config/lunar/shipping.php:
 * 
 * 'calculators' => [
 *     'flat_rate' => FlatRateShippingCalculator::class,
 * ]
 */
class FlatRateShippingCalculator implements ShippingCalculatorInterface
{
    /**
     * Calculate shipping cost for the cart.
     */
    public function calculate(Cart $cart, Currency $currency): Price
    {
        // Flat rate shipping: $10.00
        $shippingAmount = 1000; // 1000 = $10.00 in cents
        
        return new Price($shippingAmount, $currency, 1);
    }
}


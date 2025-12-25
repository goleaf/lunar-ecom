<?php

namespace App\Services\CartPricing\Pipeline;

use App\Services\AdvancedPricingService;
use Lunar\Models\Cart;

/**
 * Step 8: Apply final rounding.
 * 
 * Applies currency-specific rounding rules to ensure final totals
 * match currency precision requirements.
 */
class ApplyRoundingStep
{
    public function __construct(
        protected AdvancedPricingService $pricingService
    ) {}

    /**
     * Apply rounding to final totals.
     */
    public function handle(array $data, Cart $cart, callable $next): array
    {
        $currency = $cart->currency;
        
        // Round line item prices
        $lineItems = $data['line_items'] ?? [];
        foreach ($lineItems as $lineId => $lineData) {
            $currentPrice = $lineData['current_price'] ?? 0;
            $roundedPrice = $this->roundPrice($currentPrice, $currency);
            $lineItems[$lineId]['current_price'] = $roundedPrice;
            $lineItems[$lineId]['final_unit_price'] = $roundedPrice;
        }
        
        // Round cart totals
        $subtotal = $data['cart_subtotal'] ?? 0;
        $discountTotal = $data['cart_discount_total'] ?? 0;
        $itemDiscountTotal = $data['item_discount_total'] ?? 0;
        $taxTotal = $data['tax_total'] ?? 0;
        $shippingTotal = $data['shipping_total'] ?? 0;
        
        $roundedSubtotal = $this->roundPrice($subtotal, $currency);
        $roundedDiscountTotal = $this->roundPrice($discountTotal + $itemDiscountTotal, $currency);
        $roundedTaxTotal = $this->roundPrice($taxTotal, $currency);
        $roundedShippingTotal = $this->roundPrice($shippingTotal, $currency);
        
        // Calculate grand total
        $grandTotal = $roundedSubtotal - $roundedDiscountTotal + $roundedTaxTotal + $roundedShippingTotal;
        $roundedGrandTotal = $this->roundPrice($grandTotal, $currency);
        
        $data['line_items'] = $lineItems;
        $data['cart_subtotal'] = $roundedSubtotal;
        $data['cart_discount_total'] = $roundedDiscountTotal;
        $data['tax_total'] = $roundedTaxTotal;
        $data['shipping_total'] = $roundedShippingTotal;
        $data['grand_total'] = $roundedGrandTotal;

        return $next($data);
    }

    /**
     * Round price according to currency rounding rules.
     */
    protected function roundPrice(int $price, $currency): int
    {
        $priceDecimal = $price / 100;
        $precision = (float) ($currency->decimal_places ?? 2);
        
        // Use currency rounding mode if available
        $roundingMode = $currency->rounding_mode ?? 'nearest';
        
        $rounded = match ($roundingMode) {
            'none' => $priceDecimal,
            'up' => ceil($priceDecimal * pow(10, $precision)) / pow(10, $precision),
            'down' => floor($priceDecimal * pow(10, $precision)) / pow(10, $precision),
            'nearest' => round($priceDecimal, $precision),
            default => round($priceDecimal, $precision),
        };

        return (int) round($rounded * 100);
    }
}


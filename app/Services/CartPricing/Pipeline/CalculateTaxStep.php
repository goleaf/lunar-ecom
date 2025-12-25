<?php

namespace App\Services\CartPricing\Pipeline;

use App\Services\CartPricing\DTOs\LineItemTax;
use App\Services\CartPricing\DTOs\TaxBreakdown;
use App\Services\CartPricing\DTOs\TaxRate;
use Illuminate\Support\Collection;
use Lunar\Models\Cart;
use Lunar\Models\CartLine;
use Lunar\Models\ProductVariant;

/**
 * Step 7: Calculate taxes.
 * 
 * Uses Lunar's tax calculators to calculate tax per line item and cart total.
 */
class CalculateTaxStep
{
    /**
     * Calculate taxes.
     */
    public function handle(array $data, Cart $cart, callable $next): array
    {
        $lineItems = $data['line_items'] ?? [];
        $lineItemTaxes = collect();
        $taxRates = [];
        $totalTax = 0;

        // Calculate tax for each line item
        foreach ($lineItems as $lineId => $lineData) {
            $line = $lineData['line'] ?? null;
            
            if (!$line instanceof CartLine) {
                continue;
            }

            $purchasable = $line->purchasable;
            
            if (!$purchasable instanceof ProductVariant) {
                continue;
            }

            $taxBase = $lineData['current_price'] ?? 0;
            $quantity = $lineData['quantity'] ?? 1;
            
            // Get tax class and rate
            $taxClass = $purchasable->taxClass;
            $taxRate = $this->getTaxRate($taxClass, $cart);
            
            if ($taxRate > 0) {
                $lineTaxAmount = (int) round($taxBase * $taxRate * $quantity);
                
                $lineItemTaxes->push(new LineItemTax(
                    cartLineId: $line->id,
                    taxBase: $taxBase * $quantity,
                    taxAmount: $lineTaxAmount,
                    taxRate: $taxRate,
                    taxClass: $taxClass?->name ?? 'Standard',
                ));
                
                // Track tax rates
                $rateKey = (string) ($taxRate * 100);
                if (!isset($taxRates[$rateKey])) {
                    $taxRates[$rateKey] = [
                        'rate' => $taxRate,
                        'name' => $taxClass?->name ?? 'Standard',
                        'amount' => 0,
                    ];
                }
                $taxRates[$rateKey]['amount'] += $lineTaxAmount;
                
                $totalTax += $lineTaxAmount;
                
                // Store tax in line data
                $lineItems[$lineId]['tax_amount'] = $lineTaxAmount;
                $lineItems[$lineId]['tax_base'] = $taxBase * $quantity;
            }
        }

        // Convert tax rates to DTOs
        $taxRateDTOs = collect($taxRates)->map(function($rate) {
            return new TaxRate(
                rate: $rate['rate'],
                name: $rate['name'],
                amount: $rate['amount'],
            );
        });

        $taxBreakdown = new TaxBreakdown(
            totalAmount: $totalTax,
            lineItemTaxes: $lineItemTaxes,
            taxRates: $taxRateDTOs,
        );
        
        $data['tax_breakdown'] = $taxBreakdown;
        $data['tax_total'] = $totalTax;
        $data['line_items'] = $lineItems;

        return $next($data);
    }

    /**
     * Get tax rate for a tax class.
     */
    protected function getTaxRate($taxClass, Cart $cart): float
    {
        if (!$taxClass) {
            return 0.0;
        }

        // Use Lunar's tax calculation
        // For now, use a simplified approach - get the first tax rate
        $taxZone = $this->getTaxZone($cart);
        
        if (!$taxZone) {
            return 0.0;
        }

        $taxRate = $taxZone->taxRates()
            ->where('tax_class_id', $taxClass->id)
            ->where('active', true)
            ->first();
        
        if (!$taxRate) {
            return 0.0;
        }

        // Get the rate amount for the cart's currency
        $rateAmount = $taxRate->taxRateAmounts()
            ->where('currency_id', $cart->currency_id)
            ->first();
        
        return $rateAmount?->percentage / 100 ?? 0.0;
    }

    /**
     * Get tax zone for cart based on shipping address.
     */
    protected function getTaxZone(Cart $cart)
    {
        $shippingAddress = $cart->shippingAddress;
        
        if (!$shippingAddress) {
            return null;
        }

        // Use Lunar's tax zone resolution
        // This is simplified - Lunar has more complex tax zone resolution
        return \Lunar\Models\TaxZone::whereHas('countries', function($query) use ($shippingAddress) {
            $query->where('country_id', $shippingAddress->country_id);
        })->first();
    }
}


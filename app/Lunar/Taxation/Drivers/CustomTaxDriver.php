<?php

namespace App\Lunar\Taxation\Drivers;

use Illuminate\Support\Collection;
use Lunar\Base\Purchasable;
use Lunar\Base\TaxDriver;
use Lunar\DataTypes\Address;
use Lunar\Models\Currency;

/**
 * Example custom tax driver.
 * 
 * This is a scaffolding example. For production use, refer to:
 * https://docs.lunarphp.com/1.x/extending/taxation
 * 
 * Tax drivers implement custom tax calculation logic, which is useful for:
 * - Integrating with external tax services (e.g., TaxJar, Avalara)
 * - Implementing complex tax rules not covered by Lunar's built-in system
 * - Custom tax calculations based on your business requirements
 */
class CustomTaxDriver implements TaxDriver
{
    /**
     * The shipping address.
     */
    protected ?Address $shippingAddress = null;

    /**
     * The billing address.
     */
    protected ?Address $billingAddress = null;

    /**
     * The currency.
     */
    protected ?Currency $currency = null;

    /**
     * The purchasable item.
     */
    protected ?Purchasable $purchasable = null;

    /**
     * Set the shipping address.
     *
     * @param Address|null $address
     * @return self
     */
    public function setShippingAddress(?Address $address): self
    {
        $this->shippingAddress = $address;
        return $this;
    }

    /**
     * Set the currency.
     *
     * @param Currency $currency
     * @return self
     */
    public function setCurrency(Currency $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * Set the billing address.
     *
     * @param Address|null $address
     * @return self
     */
    public function setBillingAddress(?Address $address): self
    {
        $this->billingAddress = $address;
        return $this;
    }

    /**
     * Set the purchasable item.
     *
     * @param Purchasable|null $purchasable
     * @return self
     */
    public function setPurchasable(?Purchasable $purchasable): self
    {
        $this->purchasable = $purchasable;
        return $this;
    }

    /**
     * Return the tax breakdown from a given sub total.
     *
     * This method should return a collection of tax breakdown items.
     * Each item typically includes the tax rate, description, and amount.
     *
     * @param int $subTotal The subtotal amount in the smallest currency unit (cents)
     * @return Collection
     */
    public function getBreakdown(int $subTotal): Collection
    {
        // Example: Simple flat tax rate calculation
        // In a real implementation, you might:
        // - Call an external tax API (e.g., TaxJar, Avalara)
        // - Look up tax rates based on the address
        // - Apply complex tax rules based on your business logic
        
        $taxRate = 0.20; // Example: 20% tax rate
        $taxAmount = (int) ($subTotal * $taxRate);

        return collect([
            [
                'description' => 'Sales Tax',
                'identifier' => 'sales_tax',
                'percentage' => $taxRate * 100, // 20.0
                'value' => $taxAmount,
            ],
        ]);

        // Example: More complex breakdown with multiple tax types
        // return collect([
        //     [
        //         'description' => 'State Sales Tax',
        //         'identifier' => 'state_tax',
        //         'percentage' => 6.5,
        //         'value' => (int) ($subTotal * 0.065),
        //     ],
        //     [
        //         'description' => 'Local Tax',
        //         'identifier' => 'local_tax',
        //         'percentage' => 2.0,
        //         'value' => (int) ($subTotal * 0.02),
        //     ],
        // ]);
    }
}



<?php

namespace App\Lunar\StorefrontSession;

use Illuminate\Support\Collection;
use Lunar\Facades\StorefrontSession;
use Lunar\Models\Channel;
use Lunar\Models\Currency;
use Lunar\Models\Customer;
use Lunar\Models\CustomerGroup;

/**
 * Helper class for working with Lunar Storefront Session.
 * 
 * Provides convenience methods for managing storefront session state
 * (channel, currency, customer groups, and customer).
 * See: https://docs.lunarphp.com/1.x/storefront-utils/storefront-session
 */
class StorefrontSessionHelper
{
    /**
     * Initialize the channel.
     * 
     * Sets the channel based on what's been previously set, otherwise uses the default.
     * This is automatically called when using the StorefrontSession facade.
     * 
     * @return Channel|null
     */
    public static function initChannel(): ?Channel
    {
        StorefrontSession::initChannel();
        return static::getChannel();
    }

    /**
     * Set the channel.
     * 
     * @param Channel|string $channel Channel instance or handle (e.g., 'webstore')
     * @return void
     */
    public static function setChannel(Channel|string $channel): void
    {
        StorefrontSession::setChannel($channel);
    }

    /**
     * Get the current channel.
     * 
     * @return Channel|null
     */
    public static function getChannel(): ?Channel
    {
        return StorefrontSession::getChannel();
    }

    /**
     * Initialize customer groups.
     * 
     * Sets customer groups based on what's been previously set (from the session),
     * otherwise uses the default record.
     * This is automatically called when using the StorefrontSession facade.
     * 
     * @return Collection<CustomerGroup>
     */
    public static function initCustomerGroups(): Collection
    {
        StorefrontSession::initCustomerGroups();
        return static::getCustomerGroups();
    }

    /**
     * Set customer groups.
     * 
     * @param Collection|array|CustomerGroup $customerGroups Customer groups collection, array, or single group
     * @return void
     */
    public static function setCustomerGroups(Collection|array|CustomerGroup $customerGroups): void
    {
        if (!($customerGroups instanceof Collection)) {
            if (is_array($customerGroups)) {
                $customerGroups = collect($customerGroups);
            } else {
                // Single customer group
                $customerGroups = collect([$customerGroups]);
            }
        }

        StorefrontSession::setCustomerGroups($customerGroups);
    }

    /**
     * Set a single customer group.
     * 
     * @param CustomerGroup $customerGroup
     * @return void
     */
    public static function setCustomerGroup(CustomerGroup $customerGroup): void
    {
        StorefrontSession::setCustomerGroup($customerGroup);
    }

    /**
     * Get the current customer groups.
     * 
     * @return Collection<CustomerGroup>
     */
    public static function getCustomerGroups(): Collection
    {
        return StorefrontSession::getCustomerGroups();
    }

    /**
     * Initialize the customer.
     * 
     * Sets the customer based on what's been previously set (from the session),
     * otherwise retrieves the latest customer attached with the logged-in user.
     * This is automatically called when using the StorefrontSession facade.
     * 
     * @return Customer|null
     */
    public static function initCustomer(): ?Customer
    {
        StorefrontSession::initCustomer();
        return static::getCustomer();
    }

    /**
     * Set the customer.
     * 
     * @param Customer $customer
     * @return void
     */
    public static function setCustomer(Customer $customer): void
    {
        StorefrontSession::setCustomer($customer);
    }

    /**
     * Get the current customer.
     * 
     * @return Customer|null
     */
    public static function getCustomer(): ?Customer
    {
        return StorefrontSession::getCustomer();
    }

    /**
     * Initialize the currency.
     * 
     * Sets the currency based on what's been previously set (from the session),
     * otherwise uses the default record.
     * This is automatically called when using the StorefrontSession facade.
     * 
     * @return Currency|null
     */
    public static function initCurrency(): ?Currency
    {
        StorefrontSession::initCurrency();
        return static::getCurrency();
    }

    /**
     * Set the currency.
     * 
     * @param Currency|string $currency Currency instance or code (e.g., 'USD')
     * @return void
     */
    public static function setCurrency(Currency|string $currency): void
    {
        StorefrontSession::setCurrency($currency);
    }

    /**
     * Get the current currency.
     * 
     * @return Currency|null
     */
    public static function getCurrency(): ?Currency
    {
        return StorefrontSession::getCurrency();
    }

    /**
     * Initialize all storefront session components.
     * 
     * This initializes channel, currency, customer groups, and customer.
     * Useful for setting up the entire storefront session at once.
     * 
     * @return array Array containing initialized components
     */
    public static function initAll(): array
    {
        return [
            'channel' => static::initChannel(),
            'currency' => static::initCurrency(),
            'customerGroups' => static::initCustomerGroups(),
            'customer' => static::initCustomer(),
        ];
    }
}



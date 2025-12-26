<?php

namespace App\Lunar\FrontendSession;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Lunar\Facades\StorefrontSession;
use Lunar\Models\Channel;
use Lunar\Models\Currency;
use Lunar\Models\Customer;
use Lunar\Models\CustomerGroup;
use Lunar\Models\Language;

/**
 * Helper class for working with the frontend session.
 *
 * Wraps Lunar's session utilities (channel, currency, customer groups, customer, language)
 * behind project naming that avoids "Storefront" terminology.
 */
class FrontendSessionHelper
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
     * Initialize the language.
     * 
     * Sets the language based on what's been previously set (from the session),
     * otherwise uses the default language.
     * 
     * @return Language|null
     */
    public static function initLanguage(): ?Language
    {
        $languageCode = session('frontend_language');
        
        if ($languageCode) {
            $language = Language::where('code', $languageCode)->first();
            if ($language) {
                App::setLocale($language->code);
                return $language;
            }
        }

        // Use default language
        $defaultLanguage = Language::getDefault();
        if ($defaultLanguage) {
            App::setLocale($defaultLanguage->code);
            session(['frontend_language' => $defaultLanguage->code]);
            return $defaultLanguage;
        }

        // Fallback to 'en' if no default language is set
        App::setLocale('en');
        return null;
    }

    /**
     * Set the language.
     * 
     * @param Language|string $language Language instance or code (e.g., 'en', 'fr')
     * @return void
     */
    public static function setLanguage(Language|string $language): void
    {
        if (is_string($language)) {
            $language = Language::where('code', $language)->first();
            if (!$language) {
                throw new \InvalidArgumentException("Language with code '{$language}' not found");
            }
        }

        session(['frontend_language' => $language->code]);
        App::setLocale($language->code);
    }

    /**
     * Get the current language.
     * 
     * @return Language|null
     */
    public static function getLanguage(): ?Language
    {
        $languageCode = session('frontend_language');
        
        if ($languageCode) {
            return Language::where('code', $languageCode)->first();
        }

        return Language::getDefault();
    }

    /**
     * Initialize all frontend session components.
     *
     * This initializes channel, currency, customer groups, customer, and language.
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
            'language' => static::initLanguage(),
        ];
    }
}



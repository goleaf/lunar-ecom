<?php

namespace App\Lunar\Currencies;

use Illuminate\Support\Collection;
use Lunar\Models\Currency;

/**
 * Helper class for working with Lunar Currencies.
 * 
 * Provides convenience methods for managing currencies and exchange rates.
 * See: https://docs.lunarphp.com/1.x/reference/currencies
 */
class CurrencyHelper
{
    /**
     * Get the default currency.
     * 
     * @return Currency|null
     */
    public static function getDefault(): ?Currency
    {
        return Currency::getDefault();
    }

    /**
     * Get all enabled currencies.
     * 
     * @return Collection<Currency>
     */
    public static function getEnabled(): Collection
    {
        return Currency::where('enabled', true)->get();
    }

    /**
     * Get all currencies.
     * 
     * @return Collection<Currency>
     */
    public static function getAll(): Collection
    {
        return Currency::all();
    }

    /**
     * Find a currency by ID.
     * 
     * @param int $id
     * @return Currency|null
     */
    public static function find(int $id): ?Currency
    {
        return Currency::find($id);
    }

    /**
     * Find a currency by ISO 4217 code.
     * 
     * @param string $code ISO 4217 currency code (e.g., 'GBP', 'USD', 'EUR')
     * @return Currency|null
     */
    public static function findByCode(string $code): ?Currency
    {
        return Currency::where('code', $code)->first();
    }

    /**
     * Create a new currency.
     * 
     * @param string $code ISO 4217 currency code (e.g., 'GBP', 'USD', 'EUR')
     * @param string $name Currency name (e.g., 'British Pound', 'US Dollar')
     * @param float $exchangeRate Exchange rate relative to default currency (default currency should be 1.0000)
     * @param int $decimalPlaces Number of decimal places (typically 2)
     * @param bool $enabled Whether the currency is enabled
     * @param bool $default Whether this is the default currency
     * @return Currency
     */
    public static function create(
        string $code,
        string $name,
        float $exchangeRate = 1.0000,
        int $decimalPlaces = 2,
        bool $enabled = true,
        bool $default = false
    ): Currency {
        return Currency::create([
            'code' => $code,
            'name' => $name,
            'exchange_rate' => $exchangeRate,
            'decimal_places' => $decimalPlaces,
            'enabled' => $enabled,
            'default' => $default,
        ]);
    }

    /**
     * Update exchange rate for a currency.
     * 
     * Exchange rates are relative to the default currency.
     * For example, if GBP is default (1.0) and EUR rate is 1.17, 
     * then EUR exchange_rate should be 1 / 1.17 = 0.8547.
     * 
     * @param Currency|string $currency Currency instance or code
     * @param float $exchangeRate New exchange rate
     * @return Currency
     */
    public static function updateExchangeRate(Currency|string $currency, float $exchangeRate): Currency
    {
        if (is_string($currency)) {
            $currency = static::findByCode($currency);
            if (!$currency) {
                throw new \InvalidArgumentException("Currency with code '{$currency}' not found");
            }
        }

        $currency->update(['exchange_rate' => $exchangeRate]);
        
        return $currency->fresh();
    }

    /**
     * Convert an amount from one currency to another.
     * 
     * @param float|int $amount Amount to convert
     * @param Currency|string $fromCurrency Source currency (instance or code)
     * @param Currency|string $toCurrency Target currency (instance or code)
     * @return float Converted amount
     */
    public static function convert(
        float|int $amount,
        Currency|string $fromCurrency,
        Currency|string $toCurrency
    ): float {
        if (is_string($fromCurrency)) {
            $fromCurrency = static::findByCode($fromCurrency);
            if (!$fromCurrency) {
                throw new \InvalidArgumentException("Source currency '{$fromCurrency}' not found");
            }
        }

        if (is_string($toCurrency)) {
            $toCurrency = static::findByCode($toCurrency);
            if (!$toCurrency) {
                throw new \InvalidArgumentException("Target currency '{$toCurrency}' not found");
            }
        }

        // Convert to default currency first, then to target currency
        // Amount in default currency = amount / from_exchange_rate
        // Amount in target currency = amount_in_default * to_exchange_rate
        $amountInDefault = $amount / $fromCurrency->exchange_rate;
        $convertedAmount = $amountInDefault * $toCurrency->exchange_rate;

        return $convertedAmount;
    }

    /**
     * Enable a currency.
     * 
     * @param Currency|string $currency Currency instance or code
     * @return Currency
     */
    public static function enable(Currency|string $currency): Currency
    {
        if (is_string($currency)) {
            $currency = static::findByCode($currency);
            if (!$currency) {
                throw new \InvalidArgumentException("Currency with code '{$currency}' not found");
            }
        }

        $currency->update(['enabled' => true]);
        
        return $currency->fresh();
    }

    /**
     * Disable a currency.
     * 
     * @param Currency|string $currency Currency instance or code
     * @return Currency
     */
    public static function disable(Currency|string $currency): Currency
    {
        if (is_string($currency)) {
            $currency = static::findByCode($currency);
            if (!$currency) {
                throw new \InvalidArgumentException("Currency with code '{$currency}' not found");
            }
        }

        $currency->update(['enabled' => false]);
        
        return $currency->fresh();
    }

    /**
     * Set a currency as the default.
     * 
     * This will automatically unset any existing default currency.
     * 
     * @param Currency|string $currency Currency instance or code
     * @return Currency
     */
    public static function setDefault(Currency|string $currency): Currency
    {
        if (is_string($currency)) {
            $currency = static::findByCode($currency);
            if (!$currency) {
                throw new \InvalidArgumentException("Currency with code '{$currency}' not found");
            }
        }

        // Unset existing default
        Currency::where('default', true)->update(['default' => false]);

        // Set new default
        $currency->update([
            'default' => true,
            'exchange_rate' => 1.0000, // Default currency should always have exchange_rate of 1.0
        ]);
        
        return $currency->fresh();
    }

    /**
     * Check if a currency is enabled.
     * 
     * @param Currency $currency
     * @return bool
     */
    public static function isEnabled(Currency $currency): bool
    {
        return $currency->enabled;
    }

    /**
     * Check if a currency is the default.
     * 
     * @param Currency $currency
     * @return bool
     */
    public static function isDefault(Currency $currency): bool
    {
        return $currency->default;
    }
}



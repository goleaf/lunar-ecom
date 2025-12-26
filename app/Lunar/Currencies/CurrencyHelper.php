<?php

namespace App\Lunar\Currencies;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
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
     * @param string|null $format Currency format string (e.g., '{symbol}{value}' or '{value} {symbol}')
     * @param string|null $decimalPoint Decimal point character (default: '.')
     * @param string|null $thousandPoint Thousand separator character (default: ',')
     * @return Currency
     */
    public static function create(
        string $code,
        string $name,
        float $exchangeRate = 1.0000,
        int $decimalPlaces = 2,
        bool $enabled = true,
        bool $default = false,
        ?string $format = null,
        ?string $decimalPoint = null,
        ?string $thousandPoint = null
    ): Currency {
        $attributes = [
            'code' => $code,
            'name' => $name,
            'exchange_rate' => $exchangeRate,
            'decimal_places' => $decimalPlaces,
            'enabled' => $enabled,
            'default' => $default,
        ];

        // This project removes currency formatting columns (see 2022_03_11_100000_* migration),
        // but keep compatibility if they're present in some environments.
        $table = config('lunar.database.table_prefix', 'lunar_') . 'currencies';
        if (Schema::hasColumn($table, 'format')) {
            $attributes['format'] = $format ?? '{symbol}{value}';
        }
        if (Schema::hasColumn($table, 'decimal_point')) {
            $attributes['decimal_point'] = $decimalPoint ?? '.';
        }
        if (Schema::hasColumn($table, 'thousand_point')) {
            $attributes['thousand_point'] = $thousandPoint ?? ',';
        }

        return Currency::create($attributes);
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
        $fromCurrencyCode = is_string($fromCurrency) ? $fromCurrency : $fromCurrency->code;
        $toCurrencyCode = is_string($toCurrency) ? $toCurrency : $toCurrency->code;

        if (is_string($fromCurrency)) {
            $fromCurrency = static::findByCode($fromCurrency);
            if (!$fromCurrency) {
                throw new \InvalidArgumentException("Source currency '{$fromCurrencyCode}' not found");
            }
        }

        if (is_string($toCurrency)) {
            $toCurrency = static::findByCode($toCurrency);
            if (!$toCurrency) {
                throw new \InvalidArgumentException("Target currency '{$toCurrencyCode}' not found");
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

    /**
     * Round a price according to currency rounding rules.
     * 
     * @param float|int $amount Amount to round
     * @param Currency $currency Currency instance
     * @return float Rounded amount
     */
    public static function roundPrice(float|int $amount, Currency $currency): float
    {
        $precision = (float) ($currency->rounding_precision ?? 0.01);

        if ($precision <= 0) {
            return (float) $amount; // No rounding
        }

        $mode = $currency->rounding_mode ?? 'nearest';

        return match ($mode) {
            'none' => (float) $amount,
            'up' => ceil($amount / $precision) * $precision,
            'down' => floor($amount / $precision) * $precision,
            'nearest' => round($amount / $precision) * $precision,
            'nearest_up' => $amount % $precision == 0 
                ? (float) $amount 
                : ceil($amount / $precision) * $precision,
            'nearest_down' => $amount % $precision == 0 
                ? (float) $amount 
                : floor($amount / $precision) * $precision,
            default => round($amount / $precision) * $precision,
        };
    }

    /**
     * Round a price in integer format (smallest currency unit).
     * 
     * @param int $price Price in smallest currency unit (cents)
     * @param Currency $currency Currency instance
     * @return int Rounded price in smallest currency unit
     */
    public static function roundPriceInteger(int $price, Currency $currency): int
    {
        $priceDecimal = $price / 100;
        $rounded = static::roundPrice($priceDecimal, $currency);
        return (int) round($rounded * 100);
    }

    /**
     * Convert and round a price from one currency to another.
     * 
     * @param float|int $amount Amount to convert
     * @param Currency|string $fromCurrency Source currency
     * @param Currency|string $toCurrency Target currency
     * @param bool $round Whether to apply rounding rules (default: true)
     * @return float Converted and rounded amount
     */
    public static function convertAndRound(
        float|int $amount,
        Currency|string $fromCurrency,
        Currency|string $toCurrency,
        bool $round = true
    ): float {
        $converted = static::convert($amount, $fromCurrency, $toCurrency);
        
        if ($round) {
            $toCurrencyInstance = is_string($toCurrency) 
                ? static::findByCode($toCurrency) 
                : $toCurrency;
            
            if ($toCurrencyInstance) {
                return static::roundPrice($converted, $toCurrencyInstance);
            }
        }
        
        return $converted;
    }
}



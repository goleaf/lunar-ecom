# Multi-Currency Configuration

This document describes the multi-currency setup for the Lunar e-commerce application.

## Overview

The application supports multiple currencies (USD, EUR, GBP, JPY, AUD) with:
- Currency exchange rates
- Default currency (USD)
- Currency formatting
- Automatic currency conversion
- Frontend currency selector

## Setup

### 1. Seed Currencies

Run the currency seeder to create and configure all currencies:

```bash
php artisan currencies:seed
```

Or use the seeder directly:

```bash
php artisan db:seed --class=CurrencySeeder
```

### 2. Configured Currencies

The following currencies are configured:

| Code | Name | Exchange Rate (vs USD) | Decimal Places | Format |
|------|------|------------------------|----------------|--------|
| USD  | US Dollar | 1.0000 (default) | 2 | `{symbol}{value}` |
| EUR  | Euro | 0.9200 | 2 | `{value} {symbol}` |
| GBP  | British Pound | 0.7900 | 2 | `{symbol}{value}` |
| JPY  | Japanese Yen | 150.0000 | 0 | `{symbol}{value}` |
| AUD  | Australian Dollar | 1.5200 | 2 | `{symbol}{value}` |

**Note:** Exchange rates are relative to USD (the default currency). Update them as needed to reflect current market rates.

## Usage

### Backend

#### Get Current Currency

```php
use App\Lunar\StorefrontSession\StorefrontSessionHelper;

$currency = StorefrontSessionHelper::getCurrency();
```

#### Switch Currency

```php
use App\Lunar\StorefrontSession\StorefrontSessionHelper;

// By code
StorefrontSessionHelper::setCurrency('EUR');

// By currency instance
$currency = CurrencyHelper::findByCode('GBP');
StorefrontSessionHelper::setCurrency($currency);
```

#### Get Enabled Currencies

```php
use App\Lunar\Currencies\CurrencyHelper;

$currencies = CurrencyHelper::getEnabled();
```

#### Convert Amounts Between Currencies

```php
use App\Lunar\Currencies\CurrencyHelper;

// Convert 100 USD to EUR
$amountInEur = CurrencyHelper::convert(100, 'USD', 'EUR');

// Convert using currency instances
$gbp = CurrencyHelper::findByCode('GBP');
$eur = CurrencyHelper::findByCode('EUR');
$converted = CurrencyHelper::convert(100, $gbp, $eur);
```

#### Update Exchange Rates

```php
use App\Lunar\Currencies\CurrencyHelper;

// Update EUR exchange rate
CurrencyHelper::updateExchangeRate('EUR', 0.9500);
```

### Frontend

#### Currency Selector

The currency selector is automatically included in the navigation bar. Users can:
- View all enabled currencies
- See the current currency
- Switch currencies with a single click
- See prices automatically update after switching

#### API Endpoints

**Get all enabled currencies:**
```
GET /currency
```

**Get current currency:**
```
GET /currency/current
```

**Switch currency:**
```
POST /currency/switch
Content-Type: application/json

{
    "currency": "EUR"
}
```

## How It Works

1. **Currency Initialization**: The `StorefrontSessionMiddleware` initializes the currency on every request, using the currency from the session or defaulting to USD.

2. **Price Display**: All prices are displayed using Lunar's `PriceDataType` which automatically formats prices according to the current currency's settings (decimal places, format, etc.).

3. **Currency Switching**: When a user switches currency:
   - The frontend sends a POST request to `/currency/switch`
   - The `CurrencyController` sets the currency in the frontend session
   - The page reloads to show all prices in the new currency

4. **Automatic Conversion**: Lunar automatically handles currency conversion when displaying prices. Prices stored in the database are in the default currency (USD), and are converted on-the-fly based on the current currency's exchange rate.

## Files Created/Modified

### New Files
- `database/seeders/CurrencySeeder.php` - Currency seeder
- `app/Http/Controllers/Frontend/CurrencyController.php` - Currency API controller
- `resources/views/frontend/components/currency-selector.blade.php` - Frontend currency selector
- `app/Console/Commands/SeedCurrencies.php` - Artisan command for seeding currencies

### Modified Files
- `app/Lunar/Currencies/CurrencyHelper.php` - Added support for format fields
- `routes/web.php` - Added currency routes
- `resources/views/frontend/layout.blade.php` - Added currency selector to navigation

## Updating Exchange Rates

Exchange rates should be updated periodically to reflect current market rates. You can update them programmatically:

```php
use App\Lunar\Currencies\CurrencyHelper;

// Update EUR rate (example: 1 USD = 0.95 EUR)
CurrencyHelper::updateExchangeRate('EUR', 0.9500);

// Update GBP rate (example: 1 USD = 0.80 GBP)
CurrencyHelper::updateExchangeRate('GBP', 0.8000);
```

Or manually via the database:

```sql
UPDATE lunar_currencies 
SET exchange_rate = 0.9500 
WHERE code = 'EUR';
```

## Notes

- The default currency (USD) always has an exchange_rate of 1.0000
- Exchange rates are relative to the default currency
- JPY has 0 decimal places (as is standard for Japanese Yen)
- All prices in the database are stored in the default currency
- Currency conversion happens automatically when displaying prices
- The currency selector uses Alpine.js for interactivity


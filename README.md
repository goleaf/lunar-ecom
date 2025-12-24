# Lunar E-commerce Store

A Laravel 12 application powered by [Lunar PHP](https://docs.lunarphp.com/) e-commerce framework.

## Requirements

- PHP >= 8.2
- Laravel 12
- MySQL 8.0+ or PostgreSQL 9.4+
- Required PHP extensions: exif, intl, bcmath, GD

## Installation

1. Install dependencies:
```bash
composer install
npm install
```

2. Configure your environment:
```bash
cp .env.example .env
php artisan key:generate
```

3. Configure your database connection in `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lunar_ecom
DB_USERNAME=root
DB_PASSWORD=
```

4. Run migrations:
```bash
php artisan migrate
```

5. Seed demo data:
```bash
php artisan db:seed
```

## Accessing the Admin Panel

The Lunar admin panel is available at: `http://localhost/lunar`

Default admin user (if created during installation):
- Email: Check your seeder or create one via the admin panel

## Storefront Routes

- Home: `/`
- Products: `/products`
- Product Detail: `/products/{slug}`
- Collections: `/collections`
- Collection Detail: `/collections/{slug}`
- Search: `/search?q=query`
- Cart: `/cart`
- Checkout: `/checkout`

## Extension Points

This project includes scaffolding for extending Lunar's functionality:

### Cart Extensions
- **Location**: `app/Lunar/Cart/Pipelines/CartLine/`
- **Example**: `ValidateCartLineStock.php` - Custom cart line validation
- **Registration**: Add to `config/lunar/cart.php` under the `pipeline` array

### Discount Extensions
- **Location**: `app/Lunar/Discounts/DiscountTypes/`
- **Example**: `CustomPercentageDiscount.php` - Custom discount type
- **Registration**: Register in `AppServiceProvider::boot()` using `DiscountManager::extend()`

### Payment Extensions
- **Location**: `app/Lunar/Payments/PaymentProviders/`
- **Example**: `DummyPaymentProvider.php` - Dummy payment for development
- **Registration**: Add to `config/lunar/payments.php` under `providers`

### Shipping Extensions
- **Location**: `app/Lunar/Shipping/ShippingCalculators/`
- **Example**: `FlatRateShippingCalculator.php` - Flat-rate shipping calculator
- **Registration**: Add to `config/lunar/shipping.php` under `calculators`

### Taxation Extensions
- **Location**: `app/Lunar/Taxation/TaxCalculators/`
- **Example**: `StandardTaxCalculator.php` - Standard tax calculator
- **Registration**: Add to `config/lunar/taxes.php` under `calculators`

### Search Extensions
- **Location**: `app/Lunar/Search/SearchDrivers/`
- **Example**: `CustomSearchDriver.php` - Custom search driver
- **Registration**: Configure in `config/lunar/search.php`

### Order Extensions
- **Location**: `app/Lunar/Orders/Pipelines/OrderCreation/`
- **Example**: `ValidateOrderStock.php` - Order validation hook
- **Registration**: Add to `config/lunar/orders.php` under the `pipeline` array

## Adding Real Payment Providers

To add a real payment provider (e.g., Stripe):

1. Install the Lunar Stripe package (if available):
```bash
composer require lunarphp/stripe
```

2. Or create your own provider by extending `AbstractPayment`:
```php
use Lunar\Base\PaymentTypes\AbstractPayment;

class StripePaymentProvider extends AbstractPayment
{
    public function authorize(): bool
    {
        // Implement Stripe authorization
    }
    
    public function refund(?int $amount = null): bool
    {
        // Implement Stripe refund
    }
}
```

3. Register it in `config/lunar/payments.php`

## Demo Data

The `LunarDemoSeeder` creates:
- Channels, Currencies, Languages
- Attribute Groups and Attributes
- Product Types
- Collections
- Products with variants, prices, and URLs
- Tags
- Product Associations (cross-sell, up-sell, alternate)

## Development

Run the development server:
```bash
php artisan serve
```

Run code style checks:
```bash
./vendor/bin/pint
./vendor/bin/pint --test
```

Run tests:
```bash
php artisan test
```

## Documentation

- [Lunar PHP Documentation](https://docs.lunarphp.com/)
- [Laravel Documentation](https://laravel.com/docs)

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

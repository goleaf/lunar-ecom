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

## Attributes

This project implements attributes following the [Lunar Attributes documentation](https://docs.lunarphp.com/1.x/reference/attributes):

- **Field Types**: Text, Number, TranslatedText (with more available)
- **Attribute Groups**: Logical grouping (e.g., "Product" and "SEO" groups)
- **Attribute Data**: Stored using proper FieldType objects (e.g., `new \Lunar\FieldTypes\Text('value')`)
- **Accessing Data**: Use `$product->translateAttribute('handle')` to retrieve attribute values

The `AttributeHelper` class provides convenience methods for working with attributes programmatically.

## Media

This project implements media handling following the [Lunar Media documentation](https://docs.lunarphp.com/1.x/reference/media):

- **Media Library**: Uses Spatie Laravel Media Library package
- **Supported Models**: Products and Collections support media
- **Custom Media Definitions**: Custom conversions and collections defined in `CustomMediaDefinitions`
- **Fallback Images**: Configured via `lunar/media` config or `.env` (FALLBACK_IMAGE_URL, FALLBACK_IMAGE_PATH)
- **Media Collections**: Uses 'images' collection by default
- **Conversions**: Supports 'small', 'thumb', 'medium', 'large', 'zoom' conversions

The `MediaHelper` class provides convenience methods for working with media programmatically.

Example usage:
```php
use App\Lunar\Media\MediaHelper;

// Get images
$images = MediaHelper::getImages($product);

// Get first image URL
$imageUrl = MediaHelper::getFirstImageUrl($product, 'images', 'large');

// Add image
MediaHelper::addImage($product, $request->file('image'));

// Or use directly with Spatie Media Library
$product->addMedia($request->file('image'))->toMediaCollection('images');
$product->getMedia('images');
$product->getFirstMediaUrl('images', 'large');
```

## Collections

This project implements collections following the [Lunar Collections documentation](https://docs.lunarphp.com/1.x/reference/collections):

- **Collection Groups**: Collections belong to collection groups (e.g., "Main Catalogue")
- **Collections**: Created with `attribute_data` using FieldType objects (e.g., `new \Lunar\FieldTypes\TranslatedText(...)`)
- **Nested Collections**: Child collections using nested sets (`appendNode()`)
- **Adding Products**: Products added with positions using `sync()` method
- **Sorting Products**: Collections support multiple sort types:
  - `min_price:asc` / `min_price:desc` - Sort by minimum variant price
  - `sku:asc` / `sku:desc` - Sort by SKU
  - `custom` - Manual position ordering (default)

The `CollectionHelper` class provides convenience methods for working with collections programmatically.

Example usage:
```php
use App\Lunar\Collections\CollectionHelper;

// Get sorted products from a collection
$products = CollectionHelper::getSortedProducts($collection);

// Add products with positions
CollectionHelper::addProducts($collection, [
    1 => ['position' => 1],
    2 => ['position' => 2],
]);

// Create child collection
$child = Collection::create([/*...*/]);
CollectionHelper::addChildCollection($parent, $child);
```

## Product Associations

This project implements product associations as described in the [Lunar Associations documentation](https://docs.lunarphp.com/1.x/reference/associations):

- **Cross-sell**: Complementary products (e.g., headphones with smartphones)
- **Up-sell**: Higher value alternatives (e.g., premium versions)
- **Alternate**: Alternative product options

The storefront displays associations on product detail pages. Associations are managed via:
- `AssociationManager` class for synchronous operations (seeders, commands)
- `Product::associate()` method for asynchronous operations (queued jobs)
- `ProductAssociationController` for API management

Example usage:
```php
use App\Lunar\Associations\AssociationManager;
use Lunar\Base\Enums\ProductAssociation as ProductAssociationEnum;

$manager = new AssociationManager();
$manager->associate($product, $targetProduct, ProductAssociationEnum::CROSS_SELL);
```

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
- Attribute Groups and Attributes (with proper FieldType objects)
- Product Types
- Collections
- Products with variants, prices, and URLs
- Tags
- Product Associations (cross-sell, up-sell, alternate)

### Attributes

The seeder demonstrates proper attribute usage following the [Lunar Attributes documentation](https://docs.lunarphp.com/1.x/reference/attributes):

- **Product attributes**: Name (TranslatedText), Description (Text), Material (Text), Weight (Number), Meta Title/Description (Text)
- **Variant attributes**: Size (Text), Color (Text) - filterable for faceted search
- **Attribute groups**: Main product group and SEO group

Example of accessing attributes:
```php
// Get translated name
$product->translateAttribute('name'); // Returns current locale
$product->translateAttribute('name', 'fr'); // Returns French translation

// Get other attributes
$product->translateAttribute('description');
$product->translateAttribute('weight'); // Number field type
```

See the [Lunar Attributes documentation](https://docs.lunarphp.com/1.x/reference/attributes) for complete details.

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

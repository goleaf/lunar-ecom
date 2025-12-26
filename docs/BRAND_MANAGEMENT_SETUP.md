# Brand Management System

This document describes the comprehensive brand management system for the Lunar e-commerce application.

## Overview

The brand management system provides:
- Brand pages with logos and descriptions
- A-Z brand directory
- Brand-specific product filtering
- Brand logos and media support
- Multi-language brand descriptions
- Brand website links

## Setup

### 1. Seed Brands

Run the brand seeder to create sample brands:

```bash
php artisan brands:seed
```

Or use the seeder directly:

```bash
php artisan db:seed --class=BrandSeeder
```

### 2. Add Brand Logos

Brand logos can be added via:
- **Media Upload**: Use Lunar's media system to upload logos to brands
- **Attribute Data**: Store logo URLs in brand attribute_data

To upload a logo via media:
```php
use Lunar\Models\Brand;

$brand = Brand::find(1);
$brand->addMediaFromUrl('https://example.com/logo.png')
      ->toMediaCollection('logo');
```

## Features

### Brand Pages

**Brand Index (A-Z Directory)**
- URL: `/brands`
- Shows all brands grouped alphabetically
- A-Z navigation for quick access
- Filter by letter: `/brands?letter=A`

**Brand Show Page**
- URL: `/brands/{id}` or `/brands/{slug}`
- Displays brand logo, name, and description
- Lists all products for the brand
- Shows product count
- Link to brand website (if available)

### Brand Filtering

**Product Listings**
- Filter products by brand on the products page
- Dropdown selector for brand filtering
- URL parameter: `/products?brand_id=1`

**Brand-Specific Product Pages**
- View all products for a specific brand
- Paginated product listings
- Integrated with product card component

### Brand Helper Methods

```php
use App\Lunar\Brands\BrandHelper;

// Get all brands
$brands = BrandHelper::getAll();

// Get brands grouped by letter (A-Z)
$grouped = BrandHelper::getGroupedByLetter();

// Get brands for specific letter
$brandsA = BrandHelper::getByLetter('A');

// Get available letters
$letters = BrandHelper::getAvailableLetters();

// Get brand logo URL
$logoUrl = BrandHelper::getLogoUrl($brand);

// Get brand description
$description = BrandHelper::getDescription($brand, 'en');

// Get brand website URL
$websiteUrl = BrandHelper::getWebsiteUrl($brand);

// Get products for a brand
$products = BrandHelper::getProducts($brand, 10);

// Get product count
$count = BrandHelper::getProductCount($brand);
```

## Database Structure

### Brands Table
- `id` - Primary key
- `name` - Brand name (required)
- `attribute_data` - JSON field for brand attributes (description, website_url, etc.)
- `created_at`, `updated_at` - Timestamps

### Products Table
- `brand_id` - Foreign key to brands table (nullable)

### Brand Attributes

Brands support the following attributes (stored in `attribute_data`):
- `description` - TranslatedText field for multi-language descriptions
- `website_url` - Text field for brand website URL
- `logo_url` - Text field for logo URL (alternative to media)

## Routes

```
GET  /brands              - Brand directory (A-Z)
GET  /brands?letter=A     - Brands starting with letter A
GET  /brands/{id}         - Brand show page
GET  /brands/api           - JSON API for brands
GET  /products?brand_id=1 - Products filtered by brand
```

## Views

### Brand Index (`resources/views/storefront/brands/index.blade.php`)
- A-Z navigation
- Brand cards grouped by letter
- Responsive grid layout

### Brand Show (`resources/views/storefront/brands/show.blade.php`)
- Brand header with logo and description
- Product grid
- Pagination
- Back to directory link

### Brand Card (`resources/views/storefront/brands/_brand-card.blade.php`)
- Reusable brand card component
- Logo display
- Product count
- Link to brand page

## Integration with Products

### Assigning Brands to Products

```php
use Lunar\Models\Product;
use Lunar\Models\Brand;

$brand = Brand::find(1);
$product = Product::find(1);

$product->update(['brand_id' => $brand->id]);
```

### Filtering Products by Brand

```php
use Lunar\Models\Product;

// Get products for a brand
$products = Product::where('brand_id', $brandId)
    ->where('status', 'published')
    ->get();

// Get products with brand relationship
$products = Product::with('brand')
    ->where('status', 'published')
    ->get();
```

## Files Created/Modified

### New Files
- `app/Lunar/Brands/BrandHelper.php` - Brand helper utilities
- `app/Http/Controllers/Storefront/BrandController.php` - Brand controller
- `resources/views/storefront/brands/index.blade.php` - Brand directory page
- `resources/views/storefront/brands/show.blade.php` - Brand show page
- `resources/views/storefront/brands/_brand-card.blade.php` - Brand card component
- `database/seeders/BrandSeeder.php` - Brand seeder
- `app/Console/Commands/SeedBrands.php` - Artisan command for seeding brands

### Modified Files
- `routes/web.php` - Added brand routes
- `app/Http/Controllers/Storefront/ProductController.php` - Added brand filtering
- `resources/views/storefront/products/index.blade.php` - Added brand filter dropdown
- `resources/views/storefront/layout.blade.php` - Added brands link to navigation

## Usage Examples

### Creating a Brand with Description

```php
use Lunar\Models\Brand;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;

$brand = Brand::create([
    'name' => 'Apple',
    'attribute_data' => collect([
        'description' => new TranslatedText(collect([
            'en' => new Text('Apple Inc. is a technology company.'),
            'fr' => new Text('Apple Inc. est une entreprise technologique.'),
        ])),
        'website_url' => new Text('https://www.apple.com'),
    ]),
]);
```

### Displaying Brand Logo

```blade
@php
    use App\Lunar\Brands\BrandHelper;
    $logoUrl = BrandHelper::getLogoUrl($brand);
@endphp

@if($logoUrl)
    <img src="{{ $logoUrl }}" alt="{{ $brand->name }}">
@endif
```

### Filtering Products by Brand

```blade
<form method="GET" action="{{ route('frontend.products.index') }}">
    <select name="brand_id" onchange="this.form.submit()">
        <option value="">All Brands</option>
        @foreach($brands as $brand)
            <option value="{{ $brand->id }}" {{ request('brand_id') == $brand->id ? 'selected' : '' }}>
                {{ $brand->name }}
            </option>
        @endforeach
    </select>
</form>
```

## Notes

- Brands support media uploads via Lunar's media system (SpatieHasMedia trait)
- Brand descriptions support multi-language translations
- The A-Z directory automatically groups brands by first letter
- Non-alphabetic brand names are grouped under '#'
- Brand logos can be stored as media or as URL in attribute_data
- Product filtering by brand is integrated into the product listing page
- Brand pages show all published products for that brand



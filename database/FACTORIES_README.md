# Factories and Seeders Guide

This document explains how to use the factories and seeders for the Lunar e-commerce project.

## Factories

### ProductFactory

Creates products with proper Lunar attribute data structure.

**Basic Usage:**
```php
// Create a single product
$product = Product::factory()->create();

// Create a published product
$product = Product::factory()->published()->create();

// Create a draft product
$product = Product::factory()->draft()->create();

// Create product with custom brand
$product = Product::factory()->withBrand('Nike')->create();

// Create product with custom attributes
$product = Product::factory()
    ->withAttributes([
        'material' => 'Cotton',
        'weight' => '500g',
    ])
    ->create();
```

**With Relationships:**
```php
// Create product with variants
$product = Product::factory()
    ->has(ProductVariant::factory()->count(3), 'variants')
    ->create();

// Create product and attach to collections
$product = Product::factory()->create();
$product->collections()->attach([1, 2, 3]);
```

### ProductVariantFactory

Creates product variants with SKUs, stock, and dimensions.

**Basic Usage:**
```php
// Create a variant
$variant = ProductVariant::factory()->create();

// Create variant with specific stock
$variant = ProductVariant::factory()->inStock(100)->create();

// Create out of stock variant
$variant = ProductVariant::factory()->outOfStock()->create();

// Create low stock variant
$variant = ProductVariant::factory()->lowStock(5)->create();

// Create variant with custom SKU
$variant = ProductVariant::factory()
    ->withSku('CUSTOM-SKU-001')
    ->create();

// Create variant with custom dimensions
$variant = ProductVariant::factory()
    ->withDimensions(
        weight: 1.5,
        height: 10,
        width: 20,
        length: 30
    )
    ->create();

// Create variant with custom attributes
$variant = ProductVariant::factory()
    ->withAttributes([
        'size' => 'XL',
        'color' => 'Blue',
    ])
    ->create();
```

**With Relationships:**
```php
// Create variant for a product
$variant = ProductVariant::factory()
    ->for(Product::factory(), 'product')
    ->create();

// Variants automatically get prices created via configure() method
```

### CollectionFactory

Creates collections with proper attribute data.

**Basic Usage:**
```php
// Create a collection
$collection = Collection::factory()->create();

// Create collection with custom position
$collection = Collection::factory()
    ->withPosition(10)
    ->create();

// Create collection with custom attributes
$collection = Collection::factory()
    ->withAttributes([
        'description' => 'Summer collection 2024',
    ])
    ->create();
```

### AttributeFactory

Creates attributes for products or collections.

**Basic Usage:**
```php
// Create an attribute
$attribute = Attribute::factory()->create();

// Create required attribute
$attribute = Attribute::factory()->required()->create();

// Create filterable attribute
$attribute = Attribute::factory()->filterable()->create();

// Create system attribute
$attribute = Attribute::factory()->system()->create();

// Create attribute with specific type
$attribute = Attribute::factory()
    ->type('number')
    ->create();
```

### ProductTypeFactory

Creates product types.

**Basic Usage:**
```php
// Create a product type
$productType = ProductType::factory()->create();
```

## Seeders

### FactorySeeder

Main seeder that sets up everything using factories.

**Usage:**
```bash
php artisan db:seed --class=FactorySeeder
```

This seeder:
- Creates essential Lunar setup (channels, currencies, languages, etc.)
- Creates 5 attributes
- Creates 3 product types
- Creates 5 collections
- Creates 20 products with variants and prices
- Attaches products to channels and collections

### ProductSeeder

Creates products with variants and prices.

**Usage:**
```bash
php artisan db:seed --class=ProductSeeder
```

**Note:** Requires FactorySeeder to be run first for dependencies.

### CollectionSeeder

Creates collections.

**Usage:**
```bash
php artisan db:seed --class=CollectionSeeder
```

## Advanced Examples

### Create a Complete Product Catalog

```php
// Create product types
$clothingType = ProductType::factory()->create(['name' => 'Clothing']);
$electronicsType = ProductType::factory()->create(['name' => 'Electronics']);

// Create collections
$summerCollection = Collection::factory()
    ->withAttributes(['name' => 'Summer 2024'])
    ->create();

// Create products with variants
$products = Product::factory()
    ->count(10)
    ->published()
    ->create([
        'product_type_id' => $clothingType->id,
    ]);

foreach ($products as $product) {
    // Attach to collection
    $product->collections()->attach($summerCollection->id);
    
    // Create variants
    $variants = ProductVariant::factory()
        ->count(3)
        ->inStock(50)
        ->create([
            'product_id' => $product->id,
        ]);
    
    // Create prices for variants
    foreach ($variants as $variant) {
        Price::create([
            'price' => fake()->randomFloat(2, 20, 200),
            'currency_id' => Currency::where('default', true)->first()->id,
            'priceable_type' => ProductVariant::class,
            'priceable_id' => $variant->id,
        ]);
    }
}
```

### Create Products with Specific Attributes

```php
$product = Product::factory()
    ->withAttributes([
        'name' => 'Premium T-Shirt',
        'description' => 'High quality cotton t-shirt',
        'material' => '100% Organic Cotton',
        'weight' => '200g',
    ])
    ->withBrand('EcoWear')
    ->published()
    ->create();
```

### Create Variants with Different Stock Levels

```php
$product = Product::factory()->create();

// Create variants with different stock levels
ProductVariant::factory()
    ->inStock(100)
    ->create(['product_id' => $product->id]);

ProductVariant::factory()
    ->lowStock(5)
    ->create(['product_id' => $product->id]);

ProductVariant::factory()
    ->outOfStock()
    ->create(['product_id' => $product->id]);
```

## Tips

1. **Always run FactorySeeder first** to set up Lunar dependencies
2. **Use factory relationships** for cleaner code: `Product::factory()->has(ProductVariant::factory()->count(3), 'variants')`
3. **Factories auto-create dependencies** like TaxClass, CollectionGroup, etc.
4. **Use state methods** for common scenarios: `published()`, `inStock()`, etc.
5. **Configure factories** are automatically called to set up relationships (prices, channels)

## Testing

Factories can be used in tests:

```php
use App\Models\Product;
use App\Models\ProductVariant;

// In your test
$product = Product::factory()->published()->create();
$variant = ProductVariant::factory()->inStock(10)->create([
    'product_id' => $product->id,
]);
```


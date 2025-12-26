# Categories, Collections & Relations - Complete Implementation

This document describes the complete implementation of the Categories, Collections & Relations system with all required features.

## Overview

The Categories, Collections & Relations system provides comprehensive product organization with hierarchical categories, manual and rule-based collections, scheduled collections, and various product relation types.

## Features

### ✅ Categories

#### Hierarchical Category Tree

Categories support unlimited depth using nested set pattern:

```php
use App\Models\Category;
use App\Lunar\Categories\CategoryHelper;

// Create root category
$electronics = Category::create([
    'name' => ['en' => 'Electronics'],
    'slug' => 'electronics',
    'is_active' => true,
    'show_in_navigation' => true,
]);

// Create child category
$phones = Category::create([
    'name' => ['en' => 'Phones'],
    'slug' => 'phones',
    'parent_id' => $electronics->id,
]);

// Create grandchild category
$smartphones = Category::create([
    'name' => ['en' => 'Smartphones'],
    'slug' => 'smartphones',
    'parent_id' => $phones->id,
]);

// Get category tree
$tree = CategoryHelper::getTree($electronics, depth: 3);

// Get all descendants
$descendants = $electronics->descendants;

// Get breadcrumbs
$breadcrumbs = $smartphones->getBreadcrumbPath();
```

#### Multiple Categories Per Product

Products can belong to multiple categories:

```php
use App\Models\Product;
use App\Models\Category;

$product = Product::find(1);

// Add product to categories with position
$product->categories()->attach([
    1 => ['position' => 1], // Electronics
    2 => ['position' => 2], // Phones
    5 => ['position' => 3], // Featured
]);

// Get all categories for product
$categories = $product->categories;

// Get products in category (ordered by position)
$products = $category->products;
```

#### Category Ordering

Categories can be ordered using `display_order`:

```php
use App\Models\Category;

// Set display order
$category->display_order = 5;
$category->save();

// Get ordered categories
$ordered = Category::ordered()->get();

// Products in category are ordered by pivot position
$products = $category->products; // Already ordered by position
```

#### Category-Specific Visibility

Categories can have different visibility per channel and language:

```php
use App\Models\Category;
use Lunar\Models\Channel;
use Lunar\Models\Language;

$category = Category::find(1);
$webChannel = Channel::where('handle', 'webstore')->first();
$mobileChannel = Channel::where('handle', 'mobile')->first();

// Set visibility per channel
$category->channels()->attach([
    $webChannel->id => [
        'is_visible' => true,
        'is_in_navigation' => true,
    ],
    $mobileChannel->id => [
        'is_visible' => true,
        'is_in_navigation' => false, // Not in mobile nav
    ],
]);

// Set visibility per language
$english = Language::where('code', 'en')->first();
$french = Language::where('code', 'fr')->first();

$category->languages()->attach([
    $english->id => [
        'is_visible' => true,
        'is_in_navigation' => true,
    ],
    $french->id => [
        'is_visible' => false, // Hidden in French
        'is_in_navigation' => false,
    ],
]);
```

### ✅ Collections

#### Manual Collections

Manually curated collections:

```php
use App\Services\ComprehensiveCollectionService;
use App\Models\Collection;
use App\Enums\CollectionType;

$service = app(ComprehensiveCollectionService::class);

// Create manual collection
$collection = $service->createManualCollection([
    'collection_group_id' => 1,
    'collection_type' => CollectionType::STANDARD->value,
    'sort' => 0,
]);

// Add products manually
$collection->products()->attach([1, 2, 3, 4, 5]);

// Get products
$products = $service->getCollectionProducts($collection);
```

#### Rule-Based (Dynamic) Collections

Collections that automatically include products based on conditions:

```php
use App\Services\ComprehensiveCollectionService;

$service = app(ComprehensiveCollectionService::class);

// Create rule-based collection
$collection = $service->createRuleBasedCollection([
    'collection_group_id' => 1,
    'collection_type' => CollectionType::STANDARD->value,
], [
    [
        'conditions' => [
            'category_id' => 1, // Electronics
            'price_min' => 10000, // $100.00
            'price_max' => 50000, // $500.00
            'stock_status' => 'in_stock',
        ],
        'logic' => 'and', // All conditions must match
        'priority' => 10,
        'product_limit' => 50,
        'sort_by' => 'price',
        'sort_direction' => 'asc',
    ],
    [
        'conditions' => [
            'brand_id' => 5,
            'attributes' => [
                'color' => 'red',
            ],
        ],
        'logic' => 'and',
        'priority' => 5,
    ],
]);

// Refresh collection (re-evaluate rules)
$productCount = $service->refreshRuleBasedCollection($collection);

// Get products (automatically evaluated)
$products = $service->getCollectionProducts($collection);
```

**Supported Rule Conditions:**

- `category_id` - Single category
- `category_ids` - Multiple categories
- `brand_id` - Brand filter
- `price_min` / `price_max` - Price range
- `attributes` - Attribute filters (e.g., `{"color": "red", "size": "large"}`)
- `tags` - Tag filters
- `stock_status` - in_stock, out_of_stock
- `created_after` / `created_before` - Date range
- `status` - Product status

**Rule Logic:**

- `and` - All conditions must match (default)
- `or` - Any condition matches

#### Scheduled Collections

Collections that publish/unpublish automatically:

```php
use App\Services\ComprehensiveCollectionService;
use Carbon\Carbon;

$service = app(ComprehensiveCollectionService::class);

// Create scheduled collection
$collection = $service->createScheduledCollection(
    data: [
        'collection_group_id' => 1,
        'collection_type' => CollectionType::STANDARD->value,
    ],
    publishAt: Carbon::parse('2024-12-01 00:00:00'),
    unpublishAt: Carbon::parse('2024-12-31 23:59:59')
);

// Or schedule existing collection
$collection->schedulePublish(Carbon::parse('2024-12-01'));
$collection->scheduleUnpublish(Carbon::parse('2024-12-31'));

// Process scheduled collections (run via cron)
$result = $service->processScheduledCollections();
// Returns: ['published' => 5, 'unpublished' => 2]
```

#### Cross-Sell / Up-Sell Collections

Collections specifically for cross-selling and up-selling:

```php
use App\Services\ComprehensiveCollectionService;
use App\Enums\CollectionType;

$service = app(ComprehensiveCollectionService::class);

// Create cross-sell collection
$crossSellCollection = $service->createCrossSellCollection([
    'collection_group_id' => 1,
]);

// Create up-sell collection
$upSellCollection = $service->createUpSellCollection([
    'collection_group_id' => 1,
]);

// Get cross-sell products for a product
$crossSellProducts = $service->getCrossSellProducts($product, limit: 10);

// Get up-sell products for a product
$upSellProducts = $service->getUpSellProducts($product, limit: 10);
```

### ✅ Relations

#### Related Products

Products manually or algorithmically related:

```php
use App\Services\ProductRelationService;
use App\Models\Product;

$service = app(ProductRelationService::class);
$product = Product::find(1);

// Get related products
$related = $service->getRelated($product, limit: 10);

// Add related products
$service->addRelated($product, [2, 3, 4]);
```

#### Accessories

Complementary products (uses cross-sell type):

```php
use App\Services\ProductRelationService;

$service = app(ProductRelationService::class);

// Get accessories
$accessories = $service->getAccessories($product, limit: 10);

// Add accessories
$service->addAccessories($product, [5, 6, 7]);
```

#### "Customers Also Bought"

Products frequently bought together:

```php
use App\Services\ProductRelationService;

$service = app(ProductRelationService::class);

// Get "customers also bought" products
$alsoBought = $service->getCustomersAlsoBought($product, limit: 10);
```

#### Replacement / Alternative Products

Alternative products when original is unavailable:

```php
use App\Services\ProductRelationService;

$service = app(ProductRelationService::class);

// Get replacements/alternatives
$replacements = $service->getReplacements($product, limit: 10);

// Add replacements
$service->addReplacements($product, [8, 9, 10]);
```

#### Bundled Products

Products included in bundles:

```php
use App\Services\ProductRelationService;
use App\Models\Product;

$service = app(ProductRelationService::class);
$bundleProduct = Product::find(1);

// Get bundled products (products in this bundle)
$bundledProducts = $service->getBundledProducts($bundleProduct);

// Get bundles that include this product
$bundles = $service->getBundledIn($product);

// Check if product is a bundle
if ($product->isBundle()) {
    $bundle = $product->bundle;
    $items = $bundle->items; // BundleItem models
}
```

## Database Schema

### Categories Table

- `parent_id` - Parent category (nested set)
- `_lft`, `_rgt` - Nested set boundaries
- `name` (JSON) - Translatable name
- `slug` - URL-friendly slug
- `display_order` - Order for display
- `is_active` - Active status
- `show_in_navigation` - Show in navigation
- `product_count` - Cached product count

### Category-Product Pivot Table

- `category_id` - Foreign key to categories
- `product_id` - Foreign key to products
- `position` - Product position in category

### Category-Channel Pivot Table

- `category_id` - Foreign key to categories
- `channel_id` - Foreign key to channels
- `is_visible` - Visibility per channel
- `is_in_navigation` - Show in navigation per channel

### Category-Language Pivot Table

- `category_id` - Foreign key to categories
- `language_id` - Foreign key to languages
- `is_visible` - Visibility per language
- `is_in_navigation` - Show in navigation per language

### Collections Table

- `collection_group_id` - Collection group
- `type` - 'static' (manual) or 'dynamic' (rule-based)
- `collection_type` - standard, cross_sell, up_sell, related, bundle
- `scheduled_publish_at` - Scheduled publish date
- `scheduled_unpublish_at` - Scheduled unpublish date
- `auto_publish_products` - Auto-publish products when collection publishes

### Collection Rules Table

- `collection_id` - Foreign key to collections
- `conditions` (JSON) - Rule conditions
- `logic` - 'and' or 'or'
- `priority` - Rule priority
- `is_active` - Active status
- `product_limit` - Max products
- `sort_by` - Sort field
- `sort_direction` - Sort direction

### Product Associations Table

- `product_parent_id` - Source product
- `product_target_id` - Target product
- `type` - Association type (cross-sell, up-sell, alternate, related, etc.)

## Services

### ComprehensiveCollectionService

Complete collection management:

- `createManualCollection()` - Create manual collection
- `createRuleBasedCollection()` - Create rule-based collection
- `addRuleToCollection()` - Add rule to collection
- `getCollectionProducts()` - Get products (manual or rule-based)
- `refreshRuleBasedCollection()` - Refresh rule-based collection
- `createScheduledCollection()` - Create scheduled collection
- `createCrossSellCollection()` - Create cross-sell collection
- `createUpSellCollection()` - Create up-sell collection
- `getCrossSellProducts()` - Get cross-sell products
- `getUpSellProducts()` - Get up-sell products
- `processScheduledCollections()` - Process scheduled collections

### ProductRelationService

Product relation management:

- `getRelated()` - Get related products
- `getAccessories()` - Get accessories
- `getReplacements()` - Get replacements/alternatives
- `getCrossSell()` - Get cross-sell products
- `getUpSell()` - Get up-sell products
- `getCustomersAlsoBought()` - Get "customers also bought"
- `getBundledProducts()` - Get bundled products
- `getBundledIn()` - Get bundles containing product
- `getAllRelations()` - Get all relations grouped by type
- `addRelated()` - Add related products
- `addAccessories()` - Add accessories
- `addCrossSell()` - Add cross-sell products
- `addUpSell()` - Add up-sell products
- `addReplacements()` - Add replacements

### CollectionService

Basic collection operations:

- `createCollection()` - Create collection
- `addProducts()` - Add products
- `removeProducts()` - Remove products
- `getProducts()` - Get products
- `getCollectionsByType()` - Get collections by type

## Usage Examples

### Create Rule-Based Collection

```php
use App\Services\ComprehensiveCollectionService;
use App\Enums\CollectionType;

$service = app(ComprehensiveCollectionService::class);

$collection = $service->createRuleBasedCollection([
    'collection_group_id' => 1,
    'collection_type' => CollectionType::STANDARD->value,
], [
    [
        'conditions' => [
            'category_id' => 1,
            'price_min' => 10000,
            'price_max' => 50000,
            'stock_status' => 'in_stock',
        ],
        'logic' => 'and',
        'priority' => 10,
        'product_limit' => 50,
        'sort_by' => 'price',
        'sort_direction' => 'asc',
    ],
]);
```

### Get All Product Relations

```php
use App\Services\ProductRelationService;
use App\Models\Product;

$service = app(ProductRelationService::class);
$product = Product::find(1);

$relations = $service->getAllRelations($product, limitPerType: 10);

// Returns:
// [
//     'related' => [...],
//     'accessories' => [...],
//     'replacements' => [...],
//     'cross_sell' => [...],
//     'up_sell' => [...],
//     'customers_also_bought' => [...],
// ]
```

### Category Tree Navigation

```php
use App\Lunar\Categories\CategoryHelper;

// Get full category tree
$tree = CategoryHelper::getTree();

// Get category with children
$category = Category::find(1);
$category->load('children');

// Get breadcrumbs
$breadcrumbs = $category->getBreadcrumbPath();
```

### Scheduled Collection Management

```php
use App\Services\ComprehensiveCollectionService;
use Carbon\Carbon;

$service = app(ComprehensiveCollectionService::class);

// Create scheduled collection
$collection = $service->createScheduledCollection(
    data: ['collection_group_id' => 1],
    publishAt: Carbon::now()->addDays(7),
    unpublishAt: Carbon::now()->addDays(30)
);

// Process scheduled collections (run via cron)
$result = $service->processScheduledCollections();
```

## Summary

✅ Hierarchical category tree (nested set pattern)
✅ Multiple categories per product (pivot with position)
✅ Category ordering (display_order + pivot position)
✅ Category-specific visibility (channels + languages)
✅ Manual collections (static type)
✅ Rule-based (dynamic) collections (CollectionRule model)
✅ Scheduled collections (scheduled_publish_at/unpublish_at)
✅ Cross-sell / up-sell collections (CollectionType enum)
✅ Related products (ProductAssociation)
✅ Accessories (cross-sell type)
✅ "Customers also bought" (RecommendationService)
✅ Replacement / alternative products (alternate type)
✅ Bundled products (Bundle model)

The Categories, Collections & Relations system is now complete with all required features.



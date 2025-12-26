# Product Organization System Enhancement

This document describes the enhancements made to the product organization system, including categories, collections, and product relations.

## Overview

The product organization system has been enhanced with:
1. **Per-Channel/Locale Category Visibility** - Control category visibility per channel and language
2. **Scheduled Collections** - Schedule collections to publish/unpublish automatically
3. **Collection Types** - Explicit types for cross-sell, up-sell, related, and bundle collections
4. **Enhanced Relation Management** - Centralized service for managing product relations

## Features

### 1. Category Per-Channel/Locale Visibility

**Purpose**: Control category visibility and navigation display per channel and language/locale.

**Database Tables**:
- `lunar_category_channels` - Stores channel-specific visibility settings
- `lunar_category_languages` - Stores language-specific visibility settings

**Key Features**:
- Per-channel visibility control (`is_visible`, `is_in_navigation`)
- Per-language visibility control (`is_visible`, `is_in_navigation`)
- Falls back to global settings if no channel/language-specific setting exists
- Scopes: `visibleInChannel()`, `visibleInLanguage()`, `inNavigationForChannel()`, `inNavigationForLanguage()`

**Usage**:
```php
use App\Services\CategoryVisibilityService;
use App\Models\Category;
use Lunar\Models\Channel;
use Lunar\Models\Language;

$service = app(CategoryVisibilityService::class);
$category = Category::find(1);
$channel = Channel::find(1);
$language = Language::where('code', 'en')->first();

// Set channel visibility
$service->setChannelVisibility($category, $channel, true, true);

// Set language visibility
$service->setLanguageVisibility($category, $language, false, false);

// Query categories visible in channel
$categories = Category::visibleInChannel($channel)->get();

// Query categories visible in language
$categories = Category::visibleInLanguage($language)->get();
```

### 2. Scheduled Collections

**Purpose**: Automatically publish/unpublish collections and their products at scheduled times.

**Database Fields** (added to `lunar_collections`):
- `scheduled_publish_at` - When to publish the collection
- `scheduled_unpublish_at` - When to unpublish the collection
- `auto_publish_products` - Whether to auto-publish products in collection

**Key Features**:
- Schedule collections for future publish/unpublish
- Auto-publish products when collection publishes (optional)
- Auto-unpublish products when collection unpublishes (optional)
- Scheduled command: `php artisan collections:process-scheduled`
- Runs every minute via Laravel scheduler

**Usage**:
```php
use App\Models\Collection;
use App\Services\CollectionSchedulingService;
use Carbon\Carbon;

$collection = Collection::find(1);
$service = app(CollectionSchedulingService::class);

// Schedule for publish
$service->schedulePublish($collection, Carbon::now()->addDays(7), true);

// Schedule for unpublish
$service->scheduleUnpublish($collection, Carbon::now()->addDays(30), true);

// Or use model methods directly
$collection->schedulePublish(Carbon::now()->addDays(7));
$collection->scheduleUnpublish(Carbon::now()->addDays(30));

// Check if scheduled
if ($collection->isScheduledForPublish()) {
    // Collection is scheduled
}
```

**Scheduled Command**:
Add to `app/Console/Kernel.php` or `routes/console.php`:
```php
Schedule::command('collections:process-scheduled')->everyMinute();
```

### 3. Collection Types

**Purpose**: Categorize collections by their purpose (cross-sell, up-sell, related, bundle).

**Database Field** (added to `lunar_collections`):
- `collection_type` - Enum: `standard`, `cross_sell`, `up_sell`, `related`, `bundle`

**Key Features**:
- Explicit collection types for better organization
- Scopes: `crossSell()`, `upSell()`, `related()`, `bundles()`, `ofType()`
- CollectionType enum with labels and descriptions
- Filter collections by type in admin panel

**Usage**:
```php
use App\Models\Collection;
use App\Enums\CollectionType;
use App\Services\CollectionService;

// Create collection with type
$collection = Collection::create([
    'collection_group_id' => 1,
    'collection_type' => CollectionType::CROSS_SELL->value,
]);

// Query by type
$crossSellCollections = Collection::crossSell()->get();
$upSellCollections = Collection::upSell()->get();

// Using service
$service = app(CollectionService::class);
$crossSell = $service->getCrossSellCollections();
```

### 4. Enhanced Relation Management

**Purpose**: Centralized service for managing product relations (accessories, replacements, related products, etc.).

**Key Features**:
- ProductRelationService for centralized relation management
- Helper methods on Product model: `getAccessories()`, `getReplacements()`, `getAllRelations()`
- Uses existing ProductAssociation types:
  - Accessories → `cross-sell` type
  - Replacements → `alternate` type
- Integration with RecommendationService for "customers also bought"

**Usage**:
```php
use App\Models\Product;
use App\Services\ProductRelationService;

$product = Product::find(1);
$service = app(ProductRelationService::class);

// Get accessories (cross-sell)
$accessories = $service->getAccessories($product, 10);

// Get replacements (alternate)
$replacements = $service->getReplacements($product, 10);

// Get all relations grouped by type
$allRelations = $service->getAllRelations($product, 10);

// Or use helper methods on Product model
$accessories = $product->getAccessories(10);
$replacements = $product->getReplacements(10);
$allRelations = $product->getAllRelations(10);
```

## Database Migrations

Run the following migrations:
```bash
php artisan migrate
```

Migrations created:
1. `2025_12_26_100100_create_category_channels_table.php`
2. `2025_12_26_100200_create_category_languages_table.php`
3. `2025_12_26_100300_add_collection_type_to_collections_table.php`
4. `2025_12_26_100400_add_scheduling_to_collections_table.php`

## Services

### CategoryVisibilityService
- `setChannelVisibility()` - Set visibility for a channel
- `setLanguageVisibility()` - Set visibility for a language
- `setMultipleChannelVisibility()` - Set visibility for multiple channels
- `setMultipleLanguageVisibility()` - Set visibility for multiple languages
- `getChannelVisibility()` - Get all channel visibility settings
- `getLanguageVisibility()` - Get all language visibility settings

### CollectionSchedulingService
- `schedulePublish()` - Schedule collection for publish
- `scheduleUnpublish()` - Schedule collection for unpublish
- `processScheduledPublishes()` - Process collections ready to publish
- `processScheduledUnpublishes()` - Process collections ready to unpublish
- `validateScheduling()` - Validate scheduling dates

### ProductRelationService
- `getAccessories()` - Get accessories (cross-sell)
- `getReplacements()` - Get replacements (alternate)
- `getRelated()` - Get related products
- `getCrossSell()` - Get cross-sell products
- `getUpSell()` - Get up-sell products
- `getCustomersAlsoBought()` - Get frequently bought together
- `getAllRelations()` - Get all relations grouped by type

## Testing

Run tests:
```bash
php artisan test --filter CategoryVisibilityTest
php artisan test --filter ScheduledCollectionsTest
php artisan test --filter CollectionTypesTest
```

## Backward Compatibility

All new features are backward compatible:
- Categories default to global visibility (no channel/language-specific settings)
- Collections default to `standard` type
- Collections have no scheduling by default
- Existing product associations continue to work

## Admin Panel Integration

See `ADMIN_PANEL_ENHANCEMENTS.md` for details on integrating these features into the Lunar admin panel.

## Future Enhancements

1. Bulk operations for category visibility
2. Collection scheduling templates
3. Relation analytics and reporting
4. Automated relation suggestions based on purchase patterns



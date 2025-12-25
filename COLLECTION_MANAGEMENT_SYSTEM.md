# Collection Management System

## Overview

A comprehensive collection management system for grouping products with manual and automatic assignment rules, sorting options, and display settings.

## Features

### Core Features

1. **Collection Types**
   - Manual: Manually curated collections
   - Bestsellers: Top-selling products
   - New Arrivals: Recently added products
   - Featured: Featured/highlighted products
   - Seasonal: Season-based collections
   - Custom: Custom rule-based collections

2. **Automatic Assignment**
   - Rule-based product assignment
   - Collection type shortcuts (bestsellers, new arrivals, etc.)
   - Custom assignment rules
   - Scheduled processing
   - Max products limit

3. **Sorting Options**
   - Created date
   - Price
   - Name
   - Popularity
   - Sales count
   - Rating
   - Ascending/Descending

4. **Display Settings**
   - Homepage display
   - Homepage position
   - Display style (grid, list, carousel)
   - Products per row
   - Time-based visibility

5. **Product Management**
   - Manual product assignment
   - Product position/ordering
   - Assignment expiration
   - Auto vs manual assignment tracking
   - Product metadata

## Models

### Collection (Extended)
- **Location**: `app/Models/Collection.php`
- **Extends**: `Lunar\Models\Collection`
- **Key Fields**:
  - `collection_type`: Type of collection
  - `auto_assign`: Auto-assignment enabled
  - `assignment_rules`: JSON rules for assignment
  - `max_products`: Maximum products limit
  - `sort_by`: Sort field
  - `sort_direction`: Sort direction
  - `show_on_homepage`: Show on homepage
  - `homepage_position`: Homepage position
  - `display_style`: Display style
  - `products_per_row`: Products per row
  - `starts_at`, `ends_at`: Time-based visibility
  - `product_count`: Product count
  - `last_updated_at`: Last update timestamp

### CollectionProductMetadata
- **Location**: `app/Models/CollectionProductMetadata.php`
- **Table**: `lunar_collection_product_metadata`
- **Key Fields**:
  - `collection_id`, `product_id`
  - `is_auto_assigned`: Auto-assigned flag
  - `position`: Manual sorting position
  - `assigned_at`: Assignment timestamp
  - `expires_at`: Assignment expiration
  - `metadata`: Additional metadata (JSON)

## Services

### CollectionManagementService
- **Location**: `app/Services/CollectionManagementService.php`
- **Methods**:
  - `assignProduct()`: Assign product to collection
  - `removeProduct()`: Remove product from collection
  - `processAutoAssignment()`: Process auto-assignment for collection
  - `getProductsMatchingRules()`: Get products matching rules
  - `getProductsByType()`: Get products by collection type
  - `getBestsellers()`: Get bestseller products
  - `getNewArrivals()`: Get new arrival products
  - `getFeatured()`: Get featured products
  - `getSeasonal()`: Get seasonal products
  - `reorderProducts()`: Reorder products in collection
  - `updateProductCount()`: Update product count
  - `processAllAutoAssignments()`: Process all collections
  - `removeExpiredAssignments()`: Remove expired assignments

## Controllers

### Admin\CollectionManagementController
- **Location**: `app/Http/Controllers/Admin/CollectionManagementController.php`
- **Methods**:
  - `show()`: Display collection management page
  - `updateSettings()`: Update collection settings
  - `addProduct()`: Add product to collection
  - `removeProduct()`: Remove product from collection
  - `reorderProducts()`: Reorder products
  - `processAutoAssignment()`: Process auto-assignment
  - `statistics()`: Get collection statistics

## Commands

### ProcessCollectionAssignments
- **Location**: `app/Console/Commands/ProcessCollectionAssignments.php`
- **Signature**: `collections:process-assignments`
- **Options**: `--collection=ID` (process specific collection)
- **Schedule**: Hourly
- **Function**: Process auto-assignments and remove expired assignments

## Routes

```php
// Admin Collection Management
Route::prefix('admin/collections/{collection}')->name('admin.collections.')->group(function () {
    Route::get('/manage', [CollectionManagementController::class, 'show'])->name('manage');
    Route::put('/settings', [CollectionManagementController::class, 'updateSettings'])->name('update-settings');
    Route::post('/products', [CollectionManagementController::class, 'addProduct'])->name('add-product');
    Route::delete('/products/{product}', [CollectionManagementController::class, 'removeProduct'])->name('remove-product');
    Route::post('/reorder', [CollectionManagementController::class, 'reorderProducts'])->name('reorder');
    Route::post('/process-auto-assignment', [CollectionManagementController::class, 'processAutoAssignment'])->name('process-auto-assignment');
    Route::get('/statistics', [CollectionManagementController::class, 'statistics'])->name('statistics');
});
```

## Automatic Assignment Rules

### Collection Type Shortcuts

#### Bestsellers
```php
[
    'type' => 'bestsellers',
    'limit' => 20,
    'days' => 30  // Last 30 days
]
```

#### New Arrivals
```php
[
    'type' => 'new_arrivals',
    'limit' => 20,
    'days' => 30  // Products created in last 30 days
]
```

#### Featured
```php
[
    'type' => 'featured',
    'limit' => 20
]
// Products with rating >= 4.5 or reviews >= 10
```

#### Seasonal
```php
[
    'type' => 'seasonal',
    'limit' => 20,
    'season' => 'winter'  // Optional, defaults to current season
]
```

### Custom Rules
```php
[
    ['field' => 'status', 'operator' => 'equals', 'value' => 'published'],
    ['field' => 'average_rating', 'operator' => 'greater_than', 'value' => 4.0],
    ['field' => 'created_at', 'operator' => 'greater_than', 'value' => '2024-01-01']
]
```

### Supported Operators
- `equals`: Exact match
- `not_equals`: Not equal
- `greater_than`: Greater than
- `less_than`: Less than
- `greater_or_equal`: Greater or equal
- `less_or_equal`: Less or equal
- `contains`: String contains
- `in`: Value in array
- `not_in`: Value not in array
- `is_null`: Field is null
- `is_not_null`: Field is not null

## Usage Examples

### Create Bestsellers Collection
```php
use App\Models\Collection;

$collection = Collection::create([
    'name' => 'Bestsellers',
    'collection_type' => 'bestsellers',
    'auto_assign' => true,
    'assignment_rules' => [
        'type' => 'bestsellers',
        'limit' => 20,
        'days' => 30
    ],
    'sort_by' => 'sales_count',
    'sort_direction' => 'desc',
    'show_on_homepage' => true,
    'homepage_position' => 1,
]);
```

### Create New Arrivals Collection
```php
$collection = Collection::create([
    'name' => 'New Arrivals',
    'collection_type' => 'new_arrivals',
    'auto_assign' => true,
    'assignment_rules' => [
        'type' => 'new_arrivals',
        'limit' => 20,
        'days' => 30
    ],
    'sort_by' => 'created_at',
    'sort_direction' => 'desc',
]);
```

### Manually Assign Product
```php
use App\Services\CollectionManagementService;

$service = app(CollectionManagementService::class);
$service->assignProduct($collection, $product, [
    'position' => 0,
    'expires_at' => now()->addDays(30),
]);
```

### Process Auto-Assignment
```php
$service = app(CollectionManagementService::class);
$assigned = $service->processAutoAssignment($collection);
// Returns number of products assigned
```

## Automatic Processing

The system automatically processes collections via Laravel's task scheduler:

```php
// In routes/console.php
Schedule::command('collections:process-assignments')->hourly();
```

This command:
1. Processes all auto-assign collections
2. Removes expired product assignments
3. Updates product counts
4. Applies assignment rules

## Best Practices

1. **Collection Types**
   - Use type shortcuts for common collections (bestsellers, new arrivals)
   - Use custom rules for complex requirements
   - Set appropriate max_products limits

2. **Sorting**
   - Use sales_count for bestsellers
   - Use created_at for new arrivals
   - Use rating for featured products
   - Consider performance for large collections

3. **Auto-Assignment**
   - Test rules before enabling auto-assign
   - Monitor assignment results
   - Set expiration dates for time-sensitive collections
   - Use max_products to limit collection size

4. **Performance**
   - Index collection_type and auto_assign fields
   - Cache collection products when possible
   - Process auto-assignment during off-peak hours
   - Limit max_products for large collections

5. **Display Settings**
   - Use homepage_position for ordering
   - Choose appropriate display_style
   - Set products_per_row based on design
   - Use time-based visibility for seasonal collections

## Future Enhancements

1. **Advanced Rules**
   - AND/OR logic operators
   - Nested rule groups
   - Custom rule functions
   - Rule templates

2. **Collection Templates**
   - Pre-designed collection templates
   - Template library
   - Quick apply templates
   - Template customization

3. **Analytics**
   - Collection performance tracking
   - Product performance within collections
   - Conversion impact
   - A/B testing

4. **Dynamic Collections**
   - Real-time updates
   - Event-based assignment
   - Customer-specific collections
   - Personalized collections

5. **Collection Groups**
   - Collection grouping
   - Group-based rules
   - Group templates
   - Bulk operations


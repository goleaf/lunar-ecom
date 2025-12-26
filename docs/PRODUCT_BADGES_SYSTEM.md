# Product Badges & Labels System

## Overview

A comprehensive system for product badges and labels with customizable styling, display rules, and automatic badge assignment based on product conditions.

## Features

### Core Features

1. **Badge Types**
   - New: For newly added products
   - Sale: For products on sale
   - Hot: For trending/popular products
   - Limited: For limited edition products
   - Exclusive: For exclusive products
   - Custom: Custom badge types

2. **Customizable Styling**
   - Text color
   - Background color
   - Border color
   - Font size
   - Padding (X and Y)
   - Border radius
   - Style (rounded, square, pill, custom)
   - Icons support
   - Animations (pulse, bounce, flash, shake)

3. **Display Rules**
   - Position (top-left, top-right, bottom-left, bottom-right, center)
   - Priority (higher priority shown first)
   - Maximum display count per product
   - Time-based visibility (starts_at, ends_at)
   - Active/inactive status

4. **Automatic Assignment**
   - Rule-based auto-assignment
   - Multiple condition operators
   - Product field evaluation
   - Automatic badge assignment on product save
   - Scheduled processing

5. **Product-Specific Settings**
   - Override badge position per product
   - Override priority per product
   - Expiration dates per assignment
   - Manual assignment/removal

## Models

### ProductBadge
- **Location**: `app/Models/ProductBadge.php`
- **Table**: `lunar_product_badges`
- **Key Fields**:
  - `name`: Badge name
  - `handle`: Unique handle (slug)
  - `type`: Badge type (new, sale, hot, limited, exclusive, custom)
  - `label`: Display text (overrides name)
  - `color`: Text color
  - `background_color`: Background color
  - `border_color`: Border color
  - `icon`: Icon class or SVG
  - `position`: Display position
  - `style`: Badge style
  - `font_size`, `padding_x`, `padding_y`, `border_radius`: Styling
  - `show_icon`: Show icon flag
  - `animated`: Animated flag
  - `animation_type`: Animation type
  - `is_active`: Active status
  - `priority`: Display priority
  - `max_display_count`: Max badges per product
  - `auto_assign`: Auto-assign flag
  - `assignment_rules`: JSON rules for auto-assignment
  - `display_conditions`: JSON display conditions
  - `starts_at`, `ends_at`: Time-based visibility

### Product-Badge Pivot
- **Table**: `lunar_product_badge_product`
- **Key Fields**:
  - `product_badge_id`, `product_id`
  - `is_auto_assigned`: Auto-assigned flag
  - `assigned_at`: Assignment timestamp
  - `expires_at`: Assignment expiration
  - `position`: Override position
  - `priority`: Override priority

## Services

### ProductBadgeService
- **Location**: `app/Services/ProductBadgeService.php`
- **Methods**:
  - `assignBadge()`: Assign badge to product
  - `removeBadge()`: Remove badge from product
  - `autoAssignBadges()`: Auto-assign badges based on rules
  - `matchesRules()`: Check if product matches rules
  - `evaluateRule()`: Evaluate single rule
  - `getProductValue()`: Get product field value
  - `isOnSale()`: Check if product is on sale
  - `getProductBadges()`: Get badges for product
  - `processAutoAssignment()`: Process all products
  - `removeExpiredAssignments()`: Remove expired assignments

## Controllers

### Admin\ProductBadgeController
- **Location**: `app/Http/Controllers/Admin/ProductBadgeController.php`
- **Methods**:
  - `index()`: Display badges list
  - `create()`: Show creation form
  - `store()`: Create new badge
  - `edit()`: Show edit form
  - `update()`: Update badge
  - `destroy()`: Delete badge
  - `assignToProduct()`: Assign badge to product
  - `removeFromProduct()`: Remove badge from product
  - `processAutoAssignment()`: Process auto-assignment

## Commands

### ProcessProductBadges
- **Location**: `app/Console/Commands/ProcessProductBadges.php`
- **Signature**: `products:process-badges`
- **Schedule**: Daily
- **Function**: Process auto-assignment and remove expired assignments

## Routes

```php
// Admin Badge Management
Route::prefix('admin/products/badges')->name('admin.products.badges.')->group(function () {
    Route::get('/', [ProductBadgeController::class, 'index'])->name('index');
    Route::get('/create', [ProductBadgeController::class, 'create'])->name('create');
    Route::post('/', [ProductBadgeController::class, 'store'])->name('store');
    Route::get('/{badge}/edit', [ProductBadgeController::class, 'edit'])->name('edit');
    Route::put('/{badge}', [ProductBadgeController::class, 'update'])->name('update');
    Route::delete('/{badge}', [ProductBadgeController::class, 'destroy'])->name('destroy');
    Route::post('/process-auto-assignment', [ProductBadgeController::class, 'processAutoAssignment'])->name('process-auto-assignment');
});

// Product Badge Assignment
Route::prefix('admin/products/{product}/badges')->name('admin.products.product-badges.')->group(function () {
    Route::post('/assign', [ProductBadgeController::class, 'assignToProduct'])->name('assign');
    Route::post('/remove', [ProductBadgeController::class, 'removeFromProduct'])->name('remove');
});
```

## Frontend Components

### Product Badges Component
- **Location**: `resources/views/storefront/components/product-badges.blade.php`
- **Usage**: `<x-frontend.product-badges :product="$product" :limit="3" />`
- **Features**:
  - Displays badges with custom styling
  - Position-based rendering
  - Priority ordering
  - Limit support

### Admin Badges Index
- **Location**: `resources/views/admin/products/badges/index.blade.php`
- **Features**:
  - Badge list with preview
  - Type, position, priority display
  - Auto-assign status
  - Edit/delete actions
  - Process auto-assignment button

### Admin Badge Create/Edit
- **Location**: `resources/views/admin/products/badges/create.blade.php`
- **Features**:
  - Full badge configuration form
  - Color pickers
  - Style options
  - Animation settings
  - Auto-assign rules (future enhancement)

## Auto-Assignment Rules

### Rule Structure
```json
{
    "field": "created_days_ago",
    "operator": "less_than",
    "value": 30
}
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

### Supported Fields
- Product fields: `name`, `sku`, `status`, `weight`, `length`, `width`, `height`, etc.
- Special fields:
  - `created_days_ago`: Days since creation
  - `updated_days_ago`: Days since update
  - `published_days_ago`: Days since publish
  - `stock_total`: Total stock across variants
  - `has_low_stock`: Has low stock (<= 10)
  - `is_on_sale`: Product is on sale
  - `has_reviews`: Has reviews
  - `average_rating`: Average rating
- Nested fields: `variant.stock`, `brand.name`, `category.name`

## Usage Examples

### Create "New" Badge
```php
use App\Models\ProductBadge;

$badge = ProductBadge::create([
    'name' => 'New',
    'type' => 'new',
    'color' => '#FFFFFF',
    'background_color' => '#10B981',
    'position' => 'top-left',
    'priority' => 100,
    'auto_assign' => true,
    'assignment_rules' => [
        ['field' => 'created_days_ago', 'operator' => 'less_than', 'value' => 30]
    ],
]);
```

### Create "Sale" Badge
```php
$badge = ProductBadge::create([
    'name' => 'Sale',
    'type' => 'sale',
    'color' => '#FFFFFF',
    'background_color' => '#EF4444',
    'position' => 'top-right',
    'priority' => 90,
    'auto_assign' => true,
    'assignment_rules' => [
        ['field' => 'is_on_sale', 'operator' => 'equals', 'value' => true]
    ],
]);
```

### Create "Hot" Badge
```php
$badge = ProductBadge::create([
    'name' => 'Hot',
    'type' => 'hot',
    'color' => '#FFFFFF',
    'background_color' => '#F59E0B',
    'position' => 'top-left',
    'priority' => 80,
    'auto_assign' => true,
    'assignment_rules' => [
        ['field' => 'average_rating', 'operator' => 'greater_than', 'value' => 4.5],
        ['field' => 'has_reviews', 'operator' => 'equals', 'value' => true]
    ],
]);
```

### Manually Assign Badge
```php
use App\Services\ProductBadgeService;

$service = app(ProductBadgeService::class);
$service->assignBadge($product, $badge, [
    'expires_at' => now()->addDays(7),
    'priority' => 50,
]);
```

## Automatic Processing

The system automatically processes badges via Laravel's task scheduler:

```php
// In routes/console.php
Schedule::command('products:process-badges')->daily();
```

This command:
1. Processes all products for auto-assignment
2. Removes expired badge assignments
3. Applies assignment rules
4. Updates badge assignments

## Best Practices

1. **Badge Design**
   - Use contrasting colors for visibility
   - Keep text short and clear
   - Use appropriate font sizes
   - Test on different backgrounds

2. **Priority Management**
   - Use high priority (90-100) for important badges
   - Use medium priority (50-89) for standard badges
   - Use low priority (0-49) for secondary badges

3. **Auto-Assignment Rules**
   - Keep rules simple and specific
   - Test rules before enabling auto-assign
   - Monitor assignment results
   - Use multiple rules for complex conditions

4. **Performance**
   - Limit max_display_count per product
   - Use indexes on priority and active fields
   - Cache badge assignments when possible
   - Process auto-assignment during off-peak hours

5. **Positioning**
   - Avoid overlapping badges
   - Use different positions for different badge types
   - Consider mobile responsiveness
   - Test on various screen sizes

## Future Enhancements

1. **Advanced Rules**
   - AND/OR logic operators
   - Nested rule groups
   - Custom rule functions
   - Rule templates

2. **Badge Templates**
   - Pre-designed badge templates
   - Template library
   - Quick apply templates
   - Template customization

3. **Analytics**
   - Badge performance tracking
   - Click-through rates
   - Conversion impact
   - A/B testing

4. **Dynamic Badges**
   - Countdown timers
   - Stock countdown
   - Percentage off display
   - Real-time updates

5. **Badge Categories**
   - Badge grouping
   - Category-based rules
   - Category templates
   - Bulk operations



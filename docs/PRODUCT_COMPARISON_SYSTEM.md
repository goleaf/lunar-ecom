# Product Comparison Feature

## Overview

A comprehensive product comparison tool that allows customers to compare up to 4 products side-by-side, displaying specifications, prices, features, and attributes in a clear comparison table.

## Features

### Core Features

1. **Add to Comparison**
   - Add products to comparison from product pages or product cards
   - Maximum of 4 products can be compared at once
   - Visual feedback when products are added/removed
   - Session-based storage (no login required)

2. **Comparison Table**
   - Side-by-side product comparison
   - Sticky first column for specifications
   - Responsive horizontal scrolling
   - Clear visual separation between products

3. **Comparison Data**
   - **Price**: Current price and compare-at price
   - **Brand**: Product brand
   - **Rating**: Average rating and review count
   - **Availability**: Stock status
   - **Description**: Product description
   - **Specifications**: Custom product fields (SKU, barcode, weight, dimensions, etc.)
   - **Attributes**: Product attributes (color, size, material, etc.)

4. **User Interface**
   - Comparison button on product pages and cards
   - Comparison bar at bottom of page (when items added)
   - Comparison count badge in navigation
   - Quick actions (view details, add to cart)

## Models

No database models required - uses session storage for comparison items.

## Services

### ComparisonService
- **Location**: `app/Services/ComparisonService.php`
- **Methods**:
  - `addProduct()`: Add product to comparison
  - `removeProduct()`: Remove product from comparison
  - `clearComparison()`: Clear all comparison items
  - `getComparisonItems()`: Get comparison item IDs
  - `getComparisonProducts()`: Get full product data for comparison
  - `getComparisonCount()`: Get number of items in comparison
  - `isInComparison()`: Check if product is in comparison
  - `isFull()`: Check if comparison is full (4 items)
  - `getComparisonData()`: Get formatted comparison data

## Controllers

### Storefront\ComparisonController
- `index()`: Display comparison page
- `add()`: Add product to comparison (AJAX)
- `remove()`: Remove product from comparison (AJAX)
- `clear()`: Clear comparison (AJAX)
- `count()`: Get comparison count (AJAX)
- `check()`: Check if product is in comparison (AJAX)

## Routes

```php
Route::prefix('comparison')->name('frontend.comparison.')->group(function () {
    Route::get('/', [ComparisonController::class, 'index'])->name('index');
    Route::get('/count', [ComparisonController::class, 'count'])->name('count');
    Route::post('/clear', [ComparisonController::class, 'clear'])->name('clear');
    Route::post('/products/{product}/add', [ComparisonController::class, 'add'])->name('add');
    Route::post('/products/{product}/remove', [ComparisonController::class, 'remove'])->name('remove');
    Route::get('/products/{product}/check', [ComparisonController::class, 'check'])->name('check');
});
```

## Frontend Components

### Compare Button
- **Location**: `resources/views/storefront/components/compare-button.blade.php`
- **Usage**: `<x-frontend.compare-button :product="$product" />`
- **Features**:
  - Toggle add/remove from comparison
  - Visual state indication (green when added)
  - Disabled state when comparison is full
  - AJAX updates without page reload

### Comparison Bar
- **Location**: `resources/views/storefront/components/comparison-bar.blade.php`
- **Usage**: Included in layout automatically
- **Features**:
  - Fixed bottom bar when items in comparison
  - Shows item count
  - Quick link to comparison page
  - Clear all button

### Comparison Page
- **Location**: `resources/views/storefront/comparison/index.blade.php`
- **Features**:
  - Side-by-side comparison table
  - Sticky first column
  - Responsive design
  - Remove products from comparison
  - Quick actions (view details, add to cart)

## Usage Examples

### Add Product to Comparison
```javascript
fetch('/comparison/products/1/add', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest'
    }
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        // Update UI
        updateComparisonButton(data.count);
    }
});
```

### Get Comparison Count
```javascript
fetch('/comparison/count')
.then(response => response.json())
.then(data => {
    console.log(`${data.count} items in comparison`);
});
```

### Using ComparisonService in PHP
```php
use App\Services\ComparisonService;

$comparisonService = app(ComparisonService::class);

// Add product
$comparisonService->addProduct($product);

// Get comparison products
$products = $comparisonService->getComparisonProducts();

// Get comparison data
$data = $comparisonService->getComparisonData();
```

## Comparison Data Structure

### Specifications
- SKU
- Barcode
- Weight (formatted as kg)
- Dimensions (length × width × height in cm)
- Manufacturer
- Warranty Period (in months)
- Condition
- Origin Country

### Attributes
- All product attributes are displayed
- Attribute values are shown for each product
- "N/A" shown if attribute not available for product

### Pricing
- Current price (formatted with currency)
- Compare-at price (if available)
- "N/A" if price not available

### Rating
- Star rating (1-5 stars)
- Review count
- "No ratings yet" if no reviews

## Session Storage

Comparison items are stored in session:
- **Key**: `product_comparison`
- **Structure**: Array of items with `id` and `added_at`
- **Persistence**: Session-based (cleared on logout or session expiry)

## Integration Points

### Product Show Page
- Comparison button added below "Add to Cart" button
- Allows adding product to comparison

### Product Cards
- Comparison button added next to "View Details" button
- Quick add to comparison from product listings

### Navigation
- Comparison link with count badge
- Shows number of items in comparison
- Links to comparison page

### Layout
- Comparison bar at bottom of page
- Appears when items are in comparison
- Fixed position for easy access

## Best Practices

1. **Limit Management**
   - Maximum 4 products prevents overwhelming comparison
   - Clear messaging when limit reached
   - Suggest removing items before adding new ones

2. **User Experience**
   - Visual feedback on add/remove actions
   - Comparison bar for easy access
   - Quick actions (view, add to cart) in comparison table

3. **Performance**
   - Lazy load product data only when viewing comparison
   - Cache comparison count in session
   - Efficient queries with eager loading

4. **Responsive Design**
   - Horizontal scrolling on mobile
   - Sticky first column for context
   - Touch-friendly buttons and links

## Future Enhancements

1. **Save Comparison**
   - Save comparisons to user account
   - Share comparison links
   - Email comparison to user

2. **Comparison Filters**
   - Filter by attributes
   - Highlight differences
   - Show only differences

3. **Export Comparison**
   - Export to PDF
   - Print comparison
   - Email comparison

4. **Smart Recommendations**
   - Suggest products to add based on comparison
   - Show similar products
   - Price alerts for compared products



# Collection Sorting & Filtering System

## Overview

A comprehensive sorting and filtering system for collection pages with AJAX loading, supporting price range, attributes, availability, and multiple sorting options.

## Features

### Sorting Options

1. **Price Sorting**
   - Price: Low to High
   - Price: High to Low

2. **Date Sorting**
   - Newest (most recently created)
   - Oldest (oldest first)

3. **Popularity Sorting**
   - Based on sales count and product views

4. **Rating Sorting**
   - Highest rated products first

5. **Name Sorting**
   - A-Z (alphabetical ascending)
   - Z-A (alphabetical descending)

6. **Default**
   - Collection's default sort order

### Filtering Options

1. **Price Range**
   - Minimum price filter
   - Maximum price filter
   - Dynamic price range display

2. **Availability**
   - In Stock
   - Low Stock (≤10 items)
   - Out of Stock

3. **Brands**
   - Multi-select brand filter
   - Product count per brand

4. **Categories**
   - Multi-select category filter
   - Product count per category

5. **Attributes**
   - Dynamic attribute filters
   - Multi-select attribute values
   - Product count per value

6. **Rating**
   - Minimum rating filter (1-5 stars)

7. **Search**
   - Text search within collection
   - Searches product name and description

## Controllers

### Storefront\CollectionFilterController
- **Location**: `app/Http/Controllers/Storefront/CollectionFilterController.php`
- **Methods**:
  - `index()`: Main filtering/sorting endpoint (AJAX and regular)
  - `applyFilters()`: Apply all filters to query
  - `applySorting()`: Apply sorting to query
  - `filterByPriceRange()`: Filter by price range
  - `filterByAvailability()`: Filter by stock availability
  - `filterByAttribute()`: Filter by product attributes
  - `sortByPrice()`: Sort by price
  - `sortByPopularity()`: Sort by popularity
  - `getFilterOptions()`: Get available filter options
  - `getPriceRange()`: Get min/max prices
  - `getAvailableBrands()`: Get available brands with counts
  - `getAvailableCategories()`: Get available categories with counts
  - `getAvailableAttributes()`: Get available attributes with counts
  - `getAvailabilityCounts()`: Get availability counts

## Views

### Collection Show Page
- **Location**: `resources/views/storefront/collections/show.blade.php`
- **Features**:
  - Filter sidebar
  - Sort dropdown
  - Active filters display
  - Products grid
  - Pagination
  - Loading indicators

### Product Grid Partial
- **Location**: `resources/views/storefront/collections/_product-grid.blade.php`
- **Features**:
  - Reusable product grid
  - Empty state handling
  - Product cards

## JavaScript

### CollectionFilters Class
- **Location**: `resources/js/collection-filters.js`
- **Features**:
  - AJAX filtering and sorting
  - URL parameter management
  - Active filter tracking
  - Dynamic filter option updates
  - Pagination handling
  - Debounced input handling
  - Loading states

## CSS

### Collection Filters Styles
- **Location**: `resources/css/collection-filters.css`
- **Features**:
  - Filter sidebar styling
  - Active filter badges
  - Loading states
  - Responsive design
  - Scrollable filter sections

## Routes

```php
// Collection filtering endpoint
Route::get('/collections/{collection}/filter', [CollectionFilterController::class, 'index'])
    ->name('frontend.collections.filter');
```

## Usage Examples

### Filter by Price Range
```javascript
// Set price range
collectionFilters.filters.min_price = 10;
collectionFilters.filters.max_price = 100;
collectionFilters.applyFilters();
```

### Filter by Availability
```javascript
// Filter in-stock products
collectionFilters.filters.availability = 'in_stock';
collectionFilters.applyFilters();
```

### Filter by Brand
```javascript
// Filter by multiple brands
collectionFilters.filters.brands = [1, 2, 3];
collectionFilters.applyFilters();
```

### Filter by Attributes
```javascript
// Filter by color attribute
collectionFilters.filters.attributes = {
    'color': [1, 2, 3] // Attribute value IDs
};
collectionFilters.applyFilters();
```

### Sort Products
```javascript
// Sort by price low to high
collectionFilters.sortBy = 'price_low';
collectionFilters.applyFilters();
```

## AJAX Response Format

```json
{
    "products": [...],
    "pagination": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 24,
        "total": 120
    },
    "filter_options": {
        "price_range": {
            "min": 10.00,
            "max": 500.00
        },
        "brands": [
            {
                "id": 1,
                "name": "Brand Name",
                "count": 15
            }
        ],
        "categories": [...],
        "attributes": [...],
        "availability": {
            "in_stock": 100,
            "out_of_stock": 10,
            "low_stock": 5
        }
    },
    "html": "<div>...</div>"
}
```

## Performance Optimizations

### Query Optimization
- Eager loading relationships
- Indexed database queries
- Efficient joins for price sorting
- Cached filter options

### Frontend Optimization
- Debounced input handling
- AJAX loading with loading states
- URL state management
- Efficient DOM updates

### Caching
- Filter option counts can be cached
- Price ranges cached per collection
- Attribute lists cached

## Best Practices

1. **Filter Performance**
   - Limit number of active filters
   - Use indexes on filtered columns
   - Cache filter option counts
   - Optimize attribute queries

2. **User Experience**
   - Show loading indicators
   - Update URL without reload
   - Maintain scroll position (optional)
   - Clear visual feedback

3. **Accessibility**
   - Keyboard navigation support
   - Screen reader announcements
   - Focus management
   - ARIA labels

4. **Mobile Optimization**
   - Collapsible filter sidebar
   - Touch-friendly controls
   - Responsive grid layout
   - Optimized for small screens

## Future Enhancements

1. **Advanced Filters**
   - Date range filters
   - Weight/dimension filters
   - Custom attribute ranges
   - Saved filter presets

2. **Performance**
   - Infinite scroll option
   - Virtual scrolling
   - Service worker caching
   - GraphQL API

3. **Analytics**
   - Filter usage tracking
   - Popular filter combinations
   - Conversion tracking
   - A/B testing

4. **User Features**
   - Save favorite filters
   - Filter history
   - Quick filter buttons
   - Filter recommendations



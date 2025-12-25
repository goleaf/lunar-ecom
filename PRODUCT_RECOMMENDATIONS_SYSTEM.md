# Product Recommendations System

## Overview

A comprehensive product recommendation engine that provides multiple types of recommendations:
- **Related Products**: Based on category, tags, attributes, and brand
- **Frequently Bought Together**: Using association rules and order history analysis
- **Customers Also Viewed**: Based on browsing history and personalized data
- **Personalized Recommendations**: Based on user behavior, wishlist, and purchase history

## Features

### Recommendation Algorithms

1. **Related Products** (`getRelatedProducts`)
   - Same category products (40% weight)
   - Same tags/products (30% weight)
   - Similar attributes (20% weight)
   - Same brand (10% weight)

2. **Frequently Bought Together** (`getFrequentlyBoughtTogether`)
   - Uses `ProductPurchaseAssociation` model with confidence, support, and lift metrics
   - Falls back to order history analysis if associations are insufficient
   - Minimum confidence threshold: 0.2

3. **Personalized Recommendations** (`getPersonalizedRecommendations`)
   - Based on browsing history (40% weight)
   - Based on wishlist (30% weight)
   - Based on past purchases (30% weight)

4. **Collaborative Filtering** (`getCollaborativeFilteringRecommendations`)
   - Finds users who viewed/bought the product
   - Recommends products those users also viewed/bought

5. **Hybrid Recommendations** (`getHybridRecommendations`)
   - Combines multiple algorithms with weighted scores
   - Personalized: 30%
   - Related: 30%
   - Frequently Bought Together: 20%
   - Collaborative: 20%

### Tracking

- **Product Views**: Tracked via `ProductView` model and `TrackProductView` middleware
- **Recommendation Clicks**: Tracked via `RecommendationClick` model
- **Purchase Associations**: Updated via `updatePurchaseAssociations()` method (should run periodically via cron)

### Models

1. **ProductView** (`app/Models/ProductView.php`)
   - Tracks product views with user_id, session_id, IP, user agent, referrer
   - Scopes: `recent()`, `forUserOrSession()`

2. **ProductPurchaseAssociation** (`app/Models/ProductPurchaseAssociation.php`)
   - Stores co-purchase patterns with confidence, support, and lift metrics
   - Scopes: `topAssociations()`, `highConfidence()`

3. **RecommendationClick** (`app/Models/RecommendationClick.php`)
   - Tracks recommendation clicks and conversions
   - Scopes: `converted()`, `byType()`, `byLocation()`

4. **RecommendationRule** (`app/Models/RecommendationRule.php`)
   - Manual recommendation rules with priority and A/B testing
   - Tracks display count, click count, and conversion rate

### Services

**RecommendationService** (`app/Services/RecommendationService.php`)
- Main service for generating recommendations
- Methods:
  - `getRecommendations()`: Main method that combines manual rules and algorithms
  - `getRelatedProducts()`: Related products based on similarity
  - `getFrequentlyBoughtTogether()`: Co-purchase recommendations
  - `getPersonalizedRecommendations()`: User-specific recommendations
  - `getCollaborativeFilteringRecommendations()`: Collaborative filtering
  - `getHybridRecommendations()`: Combined algorithm
  - `trackView()`: Track product views
  - `trackClick()`: Track recommendation clicks
  - `updatePurchaseAssociations()`: Update association rules from orders

### Controllers

**RecommendationController** (`app/Http/Controllers/Storefront/RecommendationController.php`)
- `index()`: Get recommendations for a product
- `trackView()`: Track product view
- `trackClick()`: Track recommendation click
- `frequentlyBoughtTogether()`: Get frequently bought together products
- `personalized()`: Get personalized recommendations

### Frontend Components

1. **product-recommendations.blade.php**
   - Generic component for displaying recommendations
   - Supports multiple types: related, frequently_bought_together, customers_also_viewed, personalized
   - Automatically tracks clicks

2. **frequently-bought-together.blade.php**
   - Specialized component for "Frequently Bought Together" section
   - Compact layout with product thumbnails and prices

3. **customers-also-viewed.blade.php**
   - Displays products based on browsing history
   - Filters out current product

### Middleware

**TrackProductView** (`app/Http/Middleware/TrackProductView.php`)
- Automatically tracks product views on product show pages
- Runs asynchronously to avoid blocking the response

### Routes

```php
// Product Recommendations
Route::prefix('products/{product}/recommendations')->name('storefront.recommendations.')->group(function () {
    Route::get('/', [RecommendationController::class, 'index'])->name('index');
    Route::post('/track-view', [RecommendationController::class, 'trackView'])->name('track-view');
    Route::get('/frequently-bought-together', [RecommendationController::class, 'frequentlyBoughtTogether'])->name('frequently-bought-together');
});

Route::prefix('recommendations')->name('storefront.recommendations.')->group(function () {
    Route::post('/track-click', [RecommendationController::class, 'trackClick'])->name('track-click');
    Route::get('/personalized', [RecommendationController::class, 'personalized'])->name('personalized');
});
```

### Usage

#### In Blade Templates

```blade
{{-- Related Products --}}
<x-storefront.product-recommendations 
    :product="$product" 
    type="related" 
    :limit="8" 
    location="product_page" />

{{-- Frequently Bought Together --}}
<x-storefront.frequently-bought-together 
    :product="$product" 
    :limit="5" />

{{-- Customers Also Viewed --}}
<x-storefront.customers-also-viewed 
    :product="$product" 
    :limit="8" />
```

#### In Controllers

```php
use App\Services\RecommendationService;

$service = app(RecommendationService::class);

// Get related products
$related = $service->getRelatedProducts($product, 10);

// Get frequently bought together
$frequentlyBought = $service->getFrequentlyBoughtTogether($product, 5);

// Get personalized recommendations
$personalized = $service->getPersonalizedRecommendations($userId, $sessionId, 10);
```

### Caching

Recommendations are cached to improve performance:
- Related products: 6 hours
- Frequently bought together: 12 hours
- Personalized: 2 hours
- Collaborative filtering: 6 hours

Cache keys follow the pattern: `recommendations:{type}:{product_id}:{limit}`

### Periodic Tasks

**Update Purchase Associations**
Run this daily via cron to update association rules:

```php
$service = app(RecommendationService::class);
$service->updatePurchaseAssociations();
```

This analyzes order history and calculates confidence, support, and lift metrics for product associations.

### A/B Testing

The system supports A/B testing of recommendation algorithms:
- Variants are assigned based on user_id or session_id
- Variants are cached for 30 days
- Different algorithms can be tested for different variants

### Performance Considerations

1. **Caching**: All recommendations are cached to reduce database load
2. **Async Tracking**: Product views are tracked asynchronously
3. **Indexes**: Database indexes on frequently queried fields
4. **Lazy Loading**: Relationships are eager loaded where needed

### Future Enhancements

1. Machine learning integration for better recommendations
2. Real-time recommendation updates
3. More sophisticated collaborative filtering
4. Seasonal and trending product recommendations
5. Recommendation analytics dashboard


# Product Search with Laravel Scout

This document describes the integration of Laravel Scout with Algolia or Meilisearch for advanced product search, including full-text search, faceted search, search suggestions, typo tolerance, and search analytics.

## Overview

The search system provides:
- **Full-Text Search**: Fast, indexed search across product names, descriptions, SKUs, and attributes
- **Faceted Search**: Filter search results by categories, brands, price ranges, and more
- **Search Suggestions**: Autocomplete with product suggestions and popular searches
- **Typo Tolerance**: Handles common typos and misspellings automatically
- **Search Analytics**: Track searches, zero-result queries, click-through rates, and trends
- **Multiple Engines**: Support for Meilisearch (open-source) and Algolia (cloud)

## Installation

### Meilisearch

```bash
# Install Meilisearch (Docker)
docker run -d -p 7700:7700 getmeili/meilisearch:latest

# Or install via Homebrew (macOS)
brew install meilisearch
meilisearch
```

### Algolia

```bash
# Install Algolia PHP SDK
composer require algolia/algoliasearch-client-php
```

## Configuration

### Environment Variables

Add to `.env`:

```env
# Scout Configuration
SCOUT_DRIVER=meilisearch  # or 'algolia'
SCOUT_SOFT_DELETE=true    # Required by Lunar
SCOUT_PREFIX=
SCOUT_QUEUE=false

# Meilisearch Configuration
MEILISEARCH_HOST=http://localhost:7700
MEILISEARCH_KEY=  # Optional master key

# Algolia Configuration
ALGOLIA_APP_ID=your_app_id
ALGOLIA_SECRET=your_secret_key
```

### Scout Configuration

The `config/scout.php` file is already configured with:
- Meilisearch settings with typo tolerance
- Algolia settings structure
- Index-specific configurations

### Lunar Search Configuration

In `config/lunar/search.php`:
- Products use Meilisearch by default
- CustomProductIndexer is registered
- Engine mapping allows different engines per model

## Indexing Products

### Initial Indexing

```bash
# Index all products
php artisan scout:import "Lunar\Models\Product"

# Or use Lunar's command
php artisan lunar:search:index
```

### Re-indexing

```bash
# Re-index all products
php artisan scout:flush "Lunar\Models\Product"
php artisan scout:import "Lunar\Models\Product"

# Or use Lunar's command
php artisan lunar:search:index
```

### Auto-Indexing

Products are automatically indexed when:
- Created
- Updated
- Restored from soft delete

## Searchable Data

The `CustomProductIndexer` indexes:

**Searchable Fields:**
- `name` - Product name (translatable)
- `description` - Product description (HTML stripped)
- `sku` - Product SKU
- `brand_name` - Brand name
- `category_names` - Array of category names
- `attribute_values` - Array of attribute values
- `skus` - Array of variant SKUs

**Filterable Fields:**
- `status` - Product status
- `brand_id` - Brand ID
- `category_ids` - Array of category IDs
- `product_type_id` - Product type ID
- `price_min` - Minimum price
- `price_max` - Maximum price
- `in_stock` - Stock availability

**Sortable Fields:**
- `created_at` - Creation date
- `updated_at` - Update date
- `price_min` - Minimum price
- `price_max` - Maximum price

## Basic Search

### Using Scout Directly

```php
use Lunar\Models\Product;

// Simple search
$products = Product::search('laptop')
    ->where('status', 'published')
    ->paginate(12);

// Search with filters
$products = Product::search('laptop')
    ->where('status', 'published')
    ->where('brand_id', 5)
    ->where('price_min', '>=', 1000)
    ->paginate(12);

// Search with sorting
$products = Product::search('laptop')
    ->where('status', 'published')
    ->orderBy('price_min', 'asc')
    ->paginate(12);
```

### Using SearchService

```php
use App\Services\SearchService;

$searchService = app(SearchService::class);

// Basic search
$products = $searchService->search('laptop', [
    'per_page' => 24,
    'page' => 1,
]);

// Search with filters (faceted search)
$result = $searchService->searchWithFilters('laptop', [
    'brand_id' => 5,
    'category_ids' => [1, 2, 3],
    'price_min' => 1000,
    'price_max' => 5000,
], [
    'per_page' => 24,
    'sort' => 'price_asc',
]);

$products = $result['results'];
$facets = $result['facets'];
```

## Faceted Search

### Getting Facets

Facets are automatically included in `searchWithFilters()`:

```php
$result = $searchService->searchWithFilters('laptop', $filters);
$facets = $result['facets'];

// Facets structure:
// [
//     'categories' => [...],
//     'brands' => [...],
//     'price_ranges' => [...],
//     'in_stock' => [...],
// ]
```

### Filtering by Facets

```php
// Category filter
$filters['category_ids'] = [1, 2, 3]; // Multiple categories

// Brand filter
$filters['brand_id'] = 5; // Single brand

// Price range
$filters['price_min'] = 1000; // In cents
$filters['price_max'] = 5000; // In cents

// Stock filter
$filters['in_stock'] = true;
```

## Search Suggestions (Autocomplete)

### Getting Suggestions

```php
use App\Services\SearchService;

$searchService = app(SearchService::class);

// Get autocomplete suggestions
$suggestions = $searchService->searchSuggestions('lap', 10);

// Returns:
// [
//     ['type' => 'product', 'id' => 1, 'text' => 'Laptop Pro', 'url' => '...'],
//     ['type' => 'search', 'text' => 'laptop bag', 'url' => '...'],
// ]
```

### Search History

```php
// Get user's search history
$history = $searchService->getSearchHistory(10);
```

### Popular Searches

```php
// Get popular searches
$popular = $searchService->popularSearches(10, 'week');

// Get trending searches (last 24 hours)
$trending = $searchService->trendingSearches(10);
```

## Typo Tolerance

### Meilisearch Typo Tolerance

Meilisearch automatically handles typos with:
- **Min word size for 1 typo**: 4 characters
- **Min word size for 2 typos**: 8 characters

Configure via command:
```bash
php artisan lunar:configure-search-index --driver=meilisearch
```

### Algolia Typo Tolerance

Algolia also handles typos automatically:
- **Min word size for 1 typo**: 4 characters
- **Min word size for 2 typos**: 8 characters

Configure via command:
```bash
php artisan lunar:configure-search-index --driver=algolia
```

## Search Analytics

### Tracking Searches

Searches are automatically tracked when using `SearchService`:

```php
$searchService->search('laptop'); // Automatically tracked
```

### Getting Analytics

```php
use App\Lunar\Search\SearchAnalyticsHelper;

// Get statistics
$stats = SearchAnalyticsHelper::getStatistics('week');
// Returns: total_searches, zero_result_rate, click_through_rate, etc.

// Get zero-result queries
$zeroResults = SearchAnalyticsHelper::getZeroResultQueries(20, 'week');

// Get search trends
$trends = SearchAnalyticsHelper::getSearchTrends('week', 'day');

// Get most clicked products
$mostClicked = SearchAnalyticsHelper::getMostClickedProducts(10, 'week');
```

### Tracking Clicks

```php
// Track when user clicks a product from search results
$searchService->trackClick('laptop', $productId);
```

## Search Synonyms

### Creating Synonyms

```php
use App\Models\SearchSynonym;

SearchSynonym::create([
    'term' => 'laptop',
    'synonyms' => ['notebook', 'computer', 'pc'],
    'is_active' => true,
    'priority' => 1,
]);
```

### Updating Synonyms in Search Engine

```bash
# Configure index (updates synonyms)
php artisan lunar:configure-search-index
```

Or programmatically:
```php
$searchService->configureMeilisearchIndex();
```

## Frontend Integration

### Search Autocomplete Component

The search autocomplete component is included in the layout:

```blade
@include('frontend.components.search-autocomplete')
```

Features:
- Real-time suggestions as you type
- Product suggestions
- Popular searches
- Search history
- Keyboard navigation

### Search Results Page

The search results page (`resources/views/frontend/search/index.blade.php`) includes:
- Faceted search filters
- Product grid
- Pagination
- Click tracking

## API Endpoints

### Search Endpoints

**GET** `/search?q=laptop` - Search products
- Query params: `q`, `category_id[]`, `brand_id[]`, `price_min`, `price_max`, `sort`, `page`, `per_page`

**GET** `/search/autocomplete?q=lap` - Get autocomplete suggestions
- Query params: `q`, `limit`

**POST** `/search/track-click` - Track product click
- Body: `{ "query": "laptop", "product_id": 1 }`

**GET** `/search/popular` - Get popular searches
- Query params: `period`, `limit`

**GET** `/search/trending` - Get trending searches
- Query params: `limit`

### Analytics Endpoints

**GET** `/search-analytics/statistics` - Get search statistics
- Query params: `period`

**GET** `/search-analytics/zero-results` - Get zero-result queries
- Query params: `period`, `limit`

**GET** `/search-analytics/trends` - Get search trends
- Query params: `period`, `interval`

**GET** `/search-analytics/most-clicked` - Get most clicked products
- Query params: `period`, `limit`

## Configuration Commands

### Configure Search Index

```bash
# Configure Meilisearch
php artisan lunar:configure-search-index --driver=meilisearch

# Configure Algolia
php artisan lunar:configure-search-index --driver=algolia
```

This command:
- Updates ranking rules
- Configures typo tolerance
- Updates stop words
- Syncs synonyms from database

## Best Practices

1. **Always Set `soft_delete` to `true`**: Prevents soft-deleted products from appearing
2. **Index Regularly**: Re-index after bulk updates
3. **Use Facets**: Leverage faceted search for better filtering
4. **Track Analytics**: Monitor zero-result queries to improve search
5. **Configure Synonyms**: Add synonyms for common terms
6. **Optimize Index**: Use `makeAllSearchableUsing()` for efficient eager loading
7. **Cache Suggestions**: Autocomplete suggestions are cached for performance
8. **Monitor Performance**: Track search analytics to optimize

## Files

### Services
- `app/Services/SearchService.php` - Main search service with faceted search
- `app/Lunar/Search/SearchAnalyticsHelper.php` - Analytics helper

### Controllers
- `app/Http/Controllers/Frontend/SearchController.php` - Search endpoints
- `app/Http/Controllers/Frontend/SearchAnalyticsController.php` - Analytics endpoints

### Indexers
- `app/Lunar/Search/Indexers/CustomProductIndexer.php` - Product indexer

### Models
- `app/Models/SearchAnalytic.php` - Search analytics tracking
- `app/Models/SearchSynonym.php` - Search synonyms

### Views
- `resources/views/frontend/search/index.blade.php` - Search results page
- `resources/views/frontend/components/search-autocomplete.blade.php` - Autocomplete component

### Commands
- `app/Console/Commands/ConfigureMeilisearchIndex.php` - Index configuration command

### Configuration
- `config/scout.php` - Scout configuration
- `config/lunar/search.php` - Lunar search configuration

## Example Usage

### Complete Search Flow

```php
use App\Services\SearchService;

$searchService = app(SearchService::class);

// 1. Perform search with filters
$result = $searchService->searchWithFilters('laptop', [
    'brand_id' => 5,
    'category_ids' => [1, 2],
    'price_min' => 1000,
], [
    'per_page' => 24,
    'sort' => 'price_asc',
]);

$products = $result['results'];
$facets = $result['facets'];

// 2. Get autocomplete suggestions
$suggestions = $searchService->searchSuggestions('lap', 10);

// 3. Track click
$searchService->trackClick('laptop', $productId);

// 4. Get analytics
$stats = \App\Lunar\Search\SearchAnalyticsHelper::getStatistics('week');
```

### Frontend Search

```blade
{{-- In layout --}}
@include('frontend.components.search-autocomplete')

{{-- In search results --}}
@foreach($products as $product)
    <a href="{{ route('frontend.products.show', $product->slug) }}" 
       x-data="{ trackClick: function() {
           fetch('{{ route('frontend.search.track-click') }}', {
               method: 'POST',
               headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
               body: JSON.stringify({ query: '{{ $query }}', product_id: {{ $product->id }} })
           });
       }}"
       @click="trackClick()">
        {{ $product->name }}
    </a>
@endforeach
```

## Troubleshooting

### Products Not Appearing in Search

1. Check if products are indexed:
   ```bash
   php artisan scout:import "Lunar\Models\Product"
   ```

2. Verify product status is 'published'

3. Check `shouldBeSearchable()` in CustomProductIndexer

### Typo Tolerance Not Working

1. Configure index settings:
   ```bash
   php artisan lunar:configure-search-index
   ```

2. Verify typo tolerance is enabled in search engine

### Facets Not Showing

1. Ensure facets are configured in index settings
2. Check if search engine supports faceting (Meilisearch/Algolia)
3. Verify filterable fields are set in indexer

### Slow Search Performance

1. Ensure indexes are properly configured
2. Use caching for suggestions
3. Optimize `makeAllSearchableUsing()` eager loading
4. Consider using queue for indexing



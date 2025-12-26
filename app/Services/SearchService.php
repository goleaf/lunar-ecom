<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SearchAnalytic;
use App\Models\SearchSynonym;
use App\Models\Category;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

/**
 * Advanced search service with faceted search, analytics, and autocomplete.
 * Uses Laravel Scout with Meilisearch/Algolia for search, with database fallback.
 */
class SearchService
{
    /**
     * Add JSON "LIKE" conditions for the given JSON column and paths in a
     * database-driver-aware way (works with SQLite json_extract).
     *
     * Note: For PostgreSQL we fall back to casting JSON to text and doing ILIKE.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  string  $column
     * @param  array<int, string>  $paths  JSON paths (e.g. '$.name', '$.name.en', '$."lt"')
     * @param  string  $term
     */
    protected function whereJsonLike($query, string $column, array $paths, string $term): void
    {
        $driver = DB::connection()->getDriverName();
        $like = '%' . $term . '%';

        if ($driver === 'pgsql') {
            // Broad fallback: search JSON text representation.
            $query->whereRaw("{$column}::text ILIKE ?", [$like]);
            return;
        }

        $first = true;
        foreach ($paths as $path) {
            if ($driver === 'sqlite') {
                $sql = "json_extract({$column}, ?) LIKE ?";
            } else {
                // MySQL/MariaDB (and compatible)
                $sql = "JSON_UNQUOTE(JSON_EXTRACT({$column}, ?)) LIKE ?";
            }

            if ($first) {
                $query->whereRaw($sql, [$path, $like]);
                $first = false;
            } else {
                $query->orWhereRaw($sql, [$path, $like]);
            }
        }
    }

    /**
     * Check if Scout is configured with a search engine (not database).
     *
     * @return bool
     */
    protected function isScoutConfigured(): bool
    {
        $driver = config('scout.driver', 'database');
        return $driver !== 'database' && $driver !== 'collection' && $driver !== 'null';
    }

    /**
     * Perform a basic search.
     *
     * @param  string  $query
     * @param  array  $options
     * @return LengthAwarePaginator
     */
    public function search(string $query, array $options = []): LengthAwarePaginator
    {
        $perPage = $options['per_page'] ?? 24;
        $page = $options['page'] ?? 1;

        // Apply synonyms
        $query = $this->applySynonyms($query);

        // Use Scout if configured, otherwise fall back to database
        if ($this->isScoutConfigured() && !empty(trim($query))) {
            try {
                $results = $this->searchWithScout($query, $options['filters'] ?? [], $options);
            } catch (\Exception $e) {
                // Fall back to database search on error
                \Log::warning('Scout search failed, falling back to database: ' . $e->getMessage());
                $results = $this->searchWithDatabase($query, $options);
            }
        } else {
            // Use database search
            $results = $this->searchWithDatabase($query, $options);
        }

        // Track search analytics
        $this->trackSearch($query, $results->total(), $options['filters'] ?? []);

        return $results;
    }

    /**
     * Perform search with filters (faceted search).
     *
     * @param  string  $query
     * @param  array  $filters
     * @param  array  $options
     * @return array
     */
    public function searchWithFilters(string $query, array $filters = [], array $options = []): array
    {
        $perPage = $options['per_page'] ?? 24;
        $page = $options['page'] ?? 1;

        // Apply synonyms
        $query = $this->applySynonyms($query);

        // Use Scout if configured, otherwise fall back to database
        if ($this->isScoutConfigured() && !empty(trim($query))) {
            try {
                $results = $this->searchWithScout($query, $filters, $options);
                $facets = $this->getFacetsFromScout($query, $filters);
            } catch (\Exception $e) {
                // Fall back to database search on error
                \Log::warning('Scout search failed, falling back to database: ' . $e->getMessage());
                $results = $this->searchWithDatabase($query, array_merge($options, ['filters' => $filters]));
                $facets = $this->getFacets($query, $filters);
            }
        } else {
            // Use database search
            $results = $this->searchWithDatabase($query, array_merge($options, ['filters' => $filters]));
            $facets = $this->getFacets($query, $filters);
        }

        // Track search
        $this->trackSearch($query, $results->total(), $filters);

        return [
            'results' => $results,
            'facets' => $facets,
        ];
    }

    /**
     * Search using Laravel Scout.
     *
     * @param  string  $query
     * @param  array  $filters
     * @param  array  $options
     * @return LengthAwarePaginator
     */
    protected function searchWithScout(string $query, array $filters = [], array $options = []): LengthAwarePaginator
    {
        $perPage = $options['per_page'] ?? 24;
        $page = $options['page'] ?? 1;

        // Start Scout search
        $scoutQuery = Product::search($query);

        // Apply filters
        $scoutQuery = $this->applyScoutFilters($scoutQuery, $filters);

        // Apply sorting
        if (isset($options['sort'])) {
            $scoutQuery = $this->applyScoutSort($scoutQuery, $options['sort']);
        }

        // Get paginated results
        return $scoutQuery->paginate($perPage, 'page', $page);
    }

    /**
     * Search using database queries (fallback).
     *
     * @param  string  $query
     * @param  array  $options
     * @return LengthAwarePaginator
     */
    protected function searchWithDatabase(string $query, array $options = []): LengthAwarePaginator
    {
        $perPage = $options['per_page'] ?? 24;
        $page = $options['page'] ?? 1;

        // Build database query
        $searchQuery = $this->buildSearchQuery($query, $options['filters'] ?? []);

        // Apply sorting
        if (isset($options['sort'])) {
            $this->applySortToQuery($searchQuery, $options['sort']);
        }

        // Get results with pagination
        return $searchQuery->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Apply filters to Scout query.
     *
     * @param  \Laravel\Scout\Builder  $query
     * @param  array  $filters
     * @return \Laravel\Scout\Builder
     */
    protected function applyScoutFilters($query, array $filters): \Laravel\Scout\Builder
    {
        // Always filter by published status
        $query->published();

        foreach ($filters as $field => $value) {
            if (empty($value)) {
                continue;
            }

            if ($field === 'brand_id') {
                $query->where('brand_id', $value);
            } elseif ($field === 'category_ids' && is_array($value) && !empty($value)) {
                // For array filters in Scout, we need to check if any category ID is in the array
                // Since category_ids is stored as an array in the index, we use whereIn
                // Note: This may need adjustment based on the search engine
                foreach ($value as $categoryId) {
                    $query->where('category_ids', $categoryId);
                }
            } elseif ($field === 'price_min') {
                // Price filtering - values are stored in cents
                $query->where('price_min', '>=', (int) $value);
            } elseif ($field === 'price_max') {
                $query->where('price_max', '<=', (int) $value);
            } elseif ($field === 'in_stock' && $value) {
                $query->where('in_stock', true);
            }
        }

        return $query;
    }

    /**
     * Apply sorting to Scout query.
     *
     * @param  \Laravel\Scout\Builder  $query
     * @param  string  $sort
     * @return \Laravel\Scout\Builder
     */
    protected function applyScoutSort($query, string $sort): \Laravel\Scout\Builder
    {
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price_min', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price_min', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'relevance':
            default:
                // Relevance is the default for Scout searches
                break;
        }

        return $query;
    }

    /**
     * Get facets from Scout search engine.
     *
     * @param  string  $query
     * @param  array  $currentFilters
     * @return array
     */
    protected function getFacetsFromScout(string $query, array $currentFilters = []): array
    {
        // For now, fall back to database facets
        // In the future, this could query Meilisearch/Algolia directly for facets
        return $this->getFacets($query, $currentFilters);
    }

    /**
     * Get search suggestions (autocomplete).
     *
     * @param  string  $query
     * @param  int  $limit
     * @return Collection
     */
    public function searchSuggestions(string $query, int $limit = 10): Collection
    {
        if (strlen($query) < 2) {
            return collect();
        }

        $cacheKey = "search.suggestions." . md5($query . $limit);

        return Cache::remember($cacheKey, 300, function () use ($query, $limit) {
            // Apply synonyms for better suggestions.
            $query = $this->applySynonyms($query);

            // Use the database query builder for suggestions (works with SQLite + no Scout engine required).
            $products = $this->buildSearchQuery($query, [])
                ->with(['urls'])
                ->limit($limit)
                ->get();

            $suggestions = $products->map(function ($product) {
                $slug = $product->urls->first()?->slug ?? $product->id;
                return [
                    'type' => 'product',
                    'id' => $product->id,
                    'text' => $product->translateAttribute('name'),
                    'url' => route('frontend.products.show', $slug),
                ];
            });

            // Add popular searches that match query
            $popular = $this->popularSearches($limit, 'week');
            $popular->each(function ($term) use (&$suggestions, $query) {
                $searchTerm = is_object($term) ? $term->search_term : ($term['search_term'] ?? '');
                if (!empty($searchTerm) && stripos($searchTerm, $query) !== false) {
                    $suggestions->push([
                        'type' => 'search',
                        'text' => $searchTerm,
                        'url' => route('frontend.search.index', ['q' => $searchTerm]),
                    ]);
                }
            });

            // Add query suggestions from analytics
            $querySuggestions = \App\Lunar\Search\SearchAnalyticsHelper::getQuerySuggestions($query, 3);
            $querySuggestions->each(function ($suggestion) use (&$suggestions) {
                $suggestions->push([
                    'type' => 'search',
                    'text' => $suggestion,
                    'url' => route('frontend.search.index', ['q' => $suggestion]),
                ]);
            });

            return $suggestions->unique('text')->take($limit);
        });
    }

    /**
     * Mega-menu autocomplete suggestions for the frontend search (Products + Brands + Categories).
     *
     * Returns arrays ready for UI rendering (Livewire).
     *
     * @param  string  $query
     * @param  array{categories?:int,brands?:int,products?:int}  $limits
     * @return array{categories:array<int,array>,brands:array<int,array>,products:array<int,array>}
     */
    public function megaMenuAutocomplete(string $query, array $limits = []): array
    {
        $query = trim($query);

        if (mb_strlen($query) < 2) {
            return [
                'categories' => [],
                'brands' => [],
                'products' => [],
            ];
        }

        $query = $this->applySynonyms($query);

        $catLimit = (int) ($limits['categories'] ?? 5);
        $brandLimit = (int) ($limits['brands'] ?? 5);
        $productLimit = (int) ($limits['products'] ?? 6);

        $like = '%' . addcslashes($query, "%_\\") . '%';

        $locale = app()->getLocale();
        $fallbackLocale = config('app.fallback_locale');

        $categories = Category::query()
            ->active()
            ->ordered()
            ->with(['media'])
            ->where(function ($q) use ($query, $like, $locale, $fallbackLocale) {
                $q->where('slug', 'LIKE', $like)
                    ->orWhere(function ($json) use ($query, $locale, $fallbackLocale) {
                        // Category name is a JSON object keyed by locale (casts to array).
                        $this->whereJsonLike($json, 'name', [
                            '$."' . $locale . '"',
                            '$."' . $fallbackLocale . '"',
                        ], $query);
                    });
            })
            ->limit($catLimit)
            ->get();

        $brands = \Lunar\Models\Brand::query()
            ->with(['media'])
            ->where('name', 'LIKE', $like)
            ->limit($brandLimit)
            ->get();

        $products = $this->buildSearchQuery($query, [])
            ->with(['media', 'urls', 'brand'])
            ->limit($productLimit)
            ->get();

        return [
            'categories' => $categories->map(function (Category $category) {
                return [
                    'key' => 'category:' . $category->id,
                    'title' => $category->getName(),
                    'subtitle' => $category->product_count ? ($category->product_count . ' ' . __('frontend.products')) : null,
                    'image_url' => $category->getImageUrl('small') ?? $category->getImageUrl('thumb'),
                    'url' => route('categories.show', $category->getFullPath()),
                ];
            })->values()->all(),
            'brands' => $brands->map(function ($brand) {
                $logo = $brand->getFirstMedia('logo');
                return [
                    'key' => 'brand:' . $brand->id,
                    'title' => $brand->name,
                    'subtitle' => null,
                    'image_url' => $logo ? ($logo->getUrl('small') ?? $logo->getUrl('thumb')) : null,
                    'url' => route('frontend.brands.show', $brand->id),
                ];
            })->values()->all(),
            'products' => $products->map(function (Product $product) {
                $slug = $product->urls->first()?->slug ?? $product->id;
                $image = $product->getFirstMedia('images');
                return [
                    'key' => 'product:' . $product->id,
                    'title' => $product->translateAttribute('name'),
                    'subtitle' => $product->brand?->name,
                    'image_url' => $image ? $image->getUrl('thumb') : null,
                    'url' => route('frontend.products.show', $slug),
                ];
            })->values()->all(),
        ];
    }

    /**
     * Get popular searches.
     *
     * @param  int  $limit
     * @param  string  $period  'day', 'week', 'month', 'all'
     * @return Collection
     */
    public function popularSearches(int $limit = 10, string $period = 'week'): Collection
    {
        $cacheKey = "search.popular.{$period}.{$limit}";

        return Cache::remember($cacheKey, 3600, function () use ($limit, $period) {
            $query = SearchAnalytic::select('search_term')
                ->selectRaw('COUNT(*) as search_count')
                ->selectRaw('SUM(CASE WHEN zero_results = 0 THEN 1 ELSE 0 END) as success_count')
                ->where('zero_results', false) // Only successful searches
                ->groupBy('search_term')
                ->orderByDesc('search_count')
                ->limit($limit);

            // Filter by period
            switch ($period) {
                case 'day':
                    $query->where('searched_at', '>=', now()->subDay());
                    break;
                case 'week':
                    $query->where('searched_at', '>=', now()->subWeek());
                    break;
                case 'month':
                    $query->where('searched_at', '>=', now()->subMonth());
                    break;
            }

            return $query->get();
        });
    }

    /**
     * Get trending searches (recent popular searches).
     *
     * @param  int  $limit
     * @return Collection
     */
    public function trendingSearches(int $limit = 10): Collection
    {
        $cacheKey = "search.trending.{$limit}";

        return Cache::remember($cacheKey, 1800, function () use ($limit) {
            return SearchAnalytic::select('search_term')
                ->selectRaw('COUNT(*) as search_count')
                ->where('searched_at', '>=', now()->subHours(24))
                ->where('zero_results', false)
                ->groupBy('search_term')
                ->orderByDesc('search_count')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get search history for current session/user.
     *
     * @param  int  $limit
     * @return Collection
     */
    public function getSearchHistory(int $limit = 10): Collection
    {
        $sessionId = Session::getId();
        $userId = Auth::id();

        $query = SearchAnalytic::where(function ($q) use ($sessionId, $userId) {
            if ($userId) {
                $q->where('user_id', $userId);
            } else {
                $q->where('session_id', $sessionId);
            }
        })
        ->whereNotNull('search_term')
        ->where('search_term', '!=', '')
        ->select('search_term')
        ->distinct()
        ->orderByDesc('searched_at')
        ->limit($limit);

        return $query->pluck('search_term');
    }

    /**
     * Track a search query.
     *
     * @param  string  $query
     * @param  int  $resultCount
     * @param  array  $filters
     * @return void
     */
    public function trackSearch(string $query, int $resultCount, array $filters = []): void
    {
        if (empty(trim($query))) {
            return;
        }

        SearchAnalytic::create([
            'search_term' => trim($query),
            'result_count' => $resultCount,
            'zero_results' => $resultCount === 0,
            'user_id' => Auth::id(),
            'session_id' => Session::getId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'filters' => $filters,
            'searched_at' => now(),
        ]);

        // Clear popular searches cache
        Cache::forget('search.popular.week.10');
        Cache::forget('search.trending.10');
    }

    /**
     * Track a product click from search results.
     *
     * @param  string  $query
     * @param  int  $productId
     * @return void
     */
    public function trackClick(string $query, int $productId): void
    {
        SearchAnalytic::where('search_term', $query)
            ->where('session_id', Session::getId())
            ->whereNull('clicked_product_id')
            ->latest('searched_at')
            ->first()
            ?->update(['clicked_product_id' => $productId]);
    }

    /**
     * Build a database search query.
     *
     * @param  string  $query
     * @param  array  $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildSearchQuery(string $query, array $filters = []): \Illuminate\Database\Eloquent\Builder
    {
        $searchQuery = Product::query()
            ->published();

        // Apply text search
        if (!empty(trim($query))) {
            $searchTerms = $this->parseSearchTerms($query);
            $searchQuery->where(function ($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    $q->where(function ($subQuery) use ($term) {
                        // Search in SKU (direct field)
                        $subQuery->where('sku', 'LIKE', "%{$term}%")
                            // Search in manufacturer name
                            ->orWhere('manufacturer_name', 'LIKE', "%{$term}%")
                            // Search in brand name (via relationship)
                            ->orWhereHas('brand', function ($brandQuery) use ($term) {
                                $brandQuery->where('name', 'LIKE', "%{$term}%");
                            })
                            // Search in category names (via relationship)
                            ->orWhereHas('categories', function ($catQuery) use ($term) {
                                $like = "%{$term}%";
                                $locale = app()->getLocale();
                                $fallbackLocale = config('app.fallback_locale');

                                $catQuery->where(function ($catSub) use ($term, $like, $locale, $fallbackLocale) {
                                    $catSub->where('slug', 'LIKE', $like)
                                        ->orWhere(function ($nameQuery) use ($term, $locale, $fallbackLocale) {
                                            $this->whereJsonLike($nameQuery, 'name', [
                                                '$."' . $locale . '"',
                                                '$."' . $fallbackLocale . '"',
                                            ], $term);
                                        });
                                });
                            })
                            // Search in variant SKUs
                            ->orWhereHas('variants', function ($variantQuery) use ($term) {
                                $variantQuery->where('sku', 'LIKE', "%{$term}%");
                            })
                            // Search in attribute values (text_value field)
                            ->orWhereHas('attributeValues', function ($attrQuery) use ($term) {
                                $attrQuery->where('text_value', 'LIKE', "%{$term}%");
                            })
                            // Search in product attribute_data JSON (name and description)
                            ->orWhere(function ($jsonQuery) use ($term) {
                                $locale = app()->getLocale();
                                $fallbackLocale = config('app.fallback_locale');

                                $jsonQuery->where(function ($nameQuery) use ($term, $locale, $fallbackLocale) {
                                    $this->whereJsonLike($nameQuery, 'attribute_data', [
                                        '$.name',
                                        '$.name.' . $locale,
                                        '$.name.' . $fallbackLocale,
                                    ], $term);
                                })->orWhere(function ($descQuery) use ($term, $locale, $fallbackLocale) {
                                    $this->whereJsonLike($descQuery, 'attribute_data', [
                                        '$.description',
                                        '$.description.' . $locale,
                                        '$.description.' . $fallbackLocale,
                                    ], $term);
                                });
                            });
                    });
                }
            });
        }

        // Apply filters
        $this->applyFiltersToQuery($searchQuery, $filters);

        return $searchQuery;
    }

    /**
     * Parse search terms from query string.
     *
     * @param  string  $query
     * @return array
     */
    protected function parseSearchTerms(string $query): array
    {
        // Split by spaces and filter out empty strings
        $terms = array_filter(explode(' ', trim($query)));
        return array_values($terms);
    }

    /**
     * Apply filters to database query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array  $filters
     * @return void
     */
    protected function applyFiltersToQuery($query, array $filters): void
    {
        foreach ($filters as $field => $value) {
            if (is_array($value)) {
                if ($field === 'category_ids') {
                    $query->whereHas('categories', function ($q) use ($value) {
                        $q->whereIn('lunar_categories.id', $value);
                    });
                } else {
                    $query->whereIn($field, $value);
                }
            } elseif ($field === 'brand_id') {
                $query->where('brand_id', $value);
            } elseif ($field === 'price_min') {
                // Price filtering requires joining with variants and prices
                $query->whereHas('variants.prices', function ($q) use ($value) {
                    $q->where('price', '>=', $value);
                });
            } elseif ($field === 'price_max') {
                $query->whereHas('variants.prices', function ($q) use ($value) {
                    $q->where('price', '<=', $value);
                });
            } elseif ($field === 'in_stock') {
                if ($value) {
                    $query->whereHas('variants', function ($q) {
                        $q->where(function ($stockQuery) {
                            $stockQuery->where('stock', '>', 0)
                                ->orWhere('backorder', true);
                        });
                    });
                } else {
                    $query->whereDoesntHave('variants', function ($q) {
                        $q->where(function ($stockQuery) {
                            $stockQuery->where('stock', '>', 0)
                                ->orWhere('backorder', true);
                        });
                    });
                }
            } elseif (str_contains($field, '_min')) {
                $baseField = str_replace('_min', '', $field);
                $query->where($baseField, '>=', $value);
            } elseif (str_contains($field, '_max')) {
                $baseField = str_replace('_max', '', $field);
                $query->where($baseField, '<=', $value);
            } else {
                $query->where($field, $value);
            }
        }
    }

    /**
     * Apply sorting to database query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $sort
     * @return void
     */
    protected function applySortToQuery($query, string $sort): void
    {
        switch ($sort) {
            case 'price_asc':
                $query->withMin('variants.prices', 'price')
                    ->orderBy('variants_prices_min_price', 'asc');
                break;
            case 'price_desc':
                $query->withMax('variants.prices', 'price')
                    ->orderBy('variants_prices_max_price', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'relevance':
            default:
                // Default relevance sorting - order by created_at desc as fallback
                $query->orderBy('created_at', 'desc');
                break;
        }
    }

    /**
     * Get facets for faceted search.
     *
     * @param  string  $query
     * @param  array  $currentFilters
     * @return array
     */
    protected function getFacets(string $query, array $currentFilters = []): array
    {
        return [
            'categories' => $this->getCategoryFacets($query, $currentFilters),
            'brands' => $this->getBrandFacets($query, $currentFilters),
            'price_ranges' => $this->getPriceRangeFacets($query, $currentFilters),
        ];
    }

    /**
     * Format category facets.
     *
     * @param  array  $facetData
     * @return Collection
     */
    protected function formatCategoryFacets(array $facetData): Collection
    {
        if (empty($facetData)) {
            return collect();
        }

        $categoryIds = array_keys($facetData);
        $categories = Category::whereIn('id', $categoryIds)->get();

        return $categories->map(function ($category) use ($facetData) {
            return [
                'id' => $category->id,
                'name' => $category->getName(),
                'count' => $facetData[$category->id] ?? 0,
            ];
        })->sortByDesc('count')->values();
    }

    /**
     * Format brand facets.
     *
     * @param  array  $facetData
     * @return Collection
     */
    protected function formatBrandFacets(array $facetData): Collection
    {
        if (empty($facetData)) {
            return collect();
        }

        $brandIds = array_keys($facetData);
        $brands = \Lunar\Models\Brand::whereIn('id', $brandIds)->get();

        return $brands->map(function ($brand) use ($facetData) {
            return [
                'id' => $brand->id,
                'name' => $brand->name,
                'count' => $facetData[$brand->id] ?? 0,
            ];
        })->sortByDesc('count')->values();
    }

    /**
     * Get category facets.
     *
     * @param  string  $query
     * @param  array  $currentFilters
     * @return Collection
     */
    protected function getCategoryFacets(string $query, array $currentFilters): Collection
    {
        // Build base query matching the search
        $baseQuery = $this->buildSearchQuery($query, $currentFilters);
        
        // Get product IDs from search
        $productIds = $baseQuery->pluck('id');
        
        if ($productIds->isEmpty()) {
            return collect();
        }

        // Get categories with counts
        return Category::whereHas('products', function ($q) use ($productIds) {
            $q->whereIn('lunar_products.id', $productIds);
        })
        ->withCount(['products' => function ($q) use ($productIds) {
            $q->whereIn('lunar_products.id', $productIds);
        }])
        ->orderBy('products_count', 'desc')
        ->limit(20)
        ->get()
        ->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->getName(),
                'count' => $category->products_count,
            ];
        });
    }

    /**
     * Get brand facets.
     *
     * @param  string  $query
     * @param  array  $currentFilters
     * @return Collection
     */
    protected function getBrandFacets(string $query, array $currentFilters): Collection
    {
        // Build base query matching the search
        $baseQuery = $this->buildSearchQuery($query, $currentFilters);
        
        // Get product IDs from search
        $productIds = $baseQuery->pluck('id');
        
        if ($productIds->isEmpty()) {
            return collect();
        }

        // Get brands with counts
        return \Lunar\Models\Brand::whereHas('products', function ($q) use ($productIds) {
            $q->whereIn('lunar_products.id', $productIds);
        })
        ->withCount(['products' => function ($q) use ($productIds) {
            $q->whereIn('lunar_products.id', $productIds);
        }])
        ->orderBy('products_count', 'desc')
        ->limit(20)
        ->get()
        ->map(function ($brand) {
            return [
                'id' => $brand->id,
                'name' => $brand->name,
                'count' => $brand->products_count,
            ];
        });
    }

    /**
     * Get price range facets.
     *
     * @param  string  $query
     * @param  array  $currentFilters
     * @return array
     */
    protected function getPriceRangeFacets(string $query, array $currentFilters): array
    {
        // Define price ranges
        return [
            ['min' => 0, 'max' => 50, 'label' => 'Under $50'],
            ['min' => 50, 'max' => 100, 'label' => '$50 - $100'],
            ['min' => 100, 'max' => 250, 'label' => '$100 - $250'],
            ['min' => 250, 'max' => 500, 'label' => '$250 - $500'],
            ['min' => 500, 'max' => null, 'label' => 'Over $500'],
        ];
    }

    /**
     * Apply synonyms to search query.
     *
     * @param  string  $query
     * @return string
     */
    protected function applySynonyms(string $query): string
    {
        $synonyms = SearchSynonym::active()
            ->ordered()
            ->get();

        foreach ($synonyms as $synonym) {
            if (stripos($query, $synonym->term) !== false) {
                // Replace term with synonyms
                $synonymTerms = implode(' ', $synonym->synonyms);
                $query = str_ireplace($synonym->term, "{$synonym->term} {$synonymTerms}", $query);
            }
        }

        return $query;
    }

}


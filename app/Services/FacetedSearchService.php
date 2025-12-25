<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Lunar\Models\Product;
use App\Models\Category;
use Lunar\Models\Brand;

/**
 * Service for faceted search functionality.
 * 
 * Provides faceted search capabilities for filtering products by:
 * - Brand
 * - Category
 * - Price range
 * - Stock availability
 * - Rating
 */
class FacetedSearchService
{
    /**
     * Get available facets for search results.
     *
     * @param  array  $filters  Current active filters
     * @param  string|null  $searchQuery  Search query
     * @return array
     */
    public function getFacets(array $filters = [], ?string $searchQuery = null): array
    {
        $baseQuery = Product::published();

        // Apply search query if provided
        if ($searchQuery) {
            $baseQuery->where(function ($q) use ($searchQuery) {
                $q->where('attribute_data->name', 'like', "%{$searchQuery}%")
                  ->orWhere('attribute_data->description', 'like', "%{$searchQuery}%")
                  ->orWhere('sku', 'like', "%{$searchQuery}%");
            });
        }

        // Apply existing filters (excluding the facet being calculated)
        $baseQuery = $this->applyFilters($baseQuery, $filters);

        return [
            'brands' => $this->getBrandFacets($baseQuery, $filters),
            'categories' => $this->getCategoryFacets($baseQuery, $filters),
            'price_ranges' => $this->getPriceRangeFacets($baseQuery, $filters),
            'stock_status' => $this->getStockStatusFacets($baseQuery, $filters),
            'ratings' => $this->getRatingFacets($baseQuery, $filters),
        ];
    }

    /**
     * Get brand facets.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $baseQuery
     * @param  array  $filters
     * @return array
     */
    protected function getBrandFacets($baseQuery, array $filters): array
    {
        $query = clone $baseQuery;
        
        // Exclude brand filter when calculating brand facets
        unset($filters['brand_id']);
        $query = $this->applyFilters($query, $filters);

        return Brand::whereHas('products', function ($q) use ($query) {
                $q->whereIn('id', $query->pluck('id'));
            })
            ->withCount(['products' => function ($q) use ($query) {
                $q->whereIn('id', $query->pluck('id'));
            }])
            ->having('products_count', '>', 0)
            ->orderBy('products_count', 'desc')
            ->get()
            ->map(function ($brand) {
                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'count' => $brand->products_count,
                ];
            })
            ->toArray();
    }

    /**
     * Get category facets.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $baseQuery
     * @param  array  $filters
     * @return array
     */
    protected function getCategoryFacets($baseQuery, array $filters): array
    {
        $query = clone $baseQuery;
        
        // Exclude category filter when calculating category facets
        unset($filters['category_ids']);
        $query = $this->applyFilters($query, $filters);

        return Category::whereHas('products', function ($q) use ($query) {
                $q->whereIn('id', $query->pluck('id'));
            })
            ->withCount(['products' => function ($q) use ($query) {
                $q->whereIn('id', $query->pluck('id'));
            }])
            ->having('products_count', '>', 0)
            ->orderBy('products_count', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->getName(),
                    'count' => $category->products_count,
                ];
            })
            ->toArray();
    }

    /**
     * Get price range facets.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $baseQuery
     * @param  array  $filters
     * @return array
     */
    protected function getPriceRangeFacets($baseQuery, array $filters): array
    {
        $query = clone $baseQuery;
        unset($filters['price_min'], $filters['price_max']);
        $query = $this->applyFilters($query, $filters);

        // Get price ranges from variants
        $priceRanges = [
            ['min' => 0, 'max' => 25, 'label' => 'Under $25', 'count' => 0],
            ['min' => 25, 'max' => 50, 'label' => '$25 - $50', 'count' => 0],
            ['min' => 50, 'max' => 100, 'label' => '$50 - $100', 'count' => 0],
            ['min' => 100, 'max' => 200, 'label' => '$100 - $200', 'count' => 0],
            ['min' => 200, 'max' => null, 'label' => 'Over $200', 'count' => 0],
        ];

        // Count products in each range
        foreach ($priceRanges as &$range) {
            $rangeQuery = clone $query;
            $rangeQuery->whereHas('variants.prices', function ($q) use ($range) {
                if ($range['max']) {
                    $q->whereBetween('price', [$range['min'] * 100, $range['max'] * 100]);
                } else {
                    $q->where('price', '>=', $range['min'] * 100);
                }
            });
            $range['count'] = $rangeQuery->count();
        }

        return array_filter($priceRanges, fn($range) => $range['count'] > 0);
    }

    /**
     * Get stock status facets.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $baseQuery
     * @param  array  $filters
     * @return array
     */
    protected function getStockStatusFacets($baseQuery, array $filters): array
    {
        $query = clone $baseQuery;
        unset($filters['in_stock']);
        $query = $this->applyFilters($query, $filters);

        return [
            [
                'value' => true,
                'label' => 'In Stock',
                'count' => (clone $query)->whereHas('variants', function ($q) {
                    $q->where('stock', '>', 0);
                })->count(),
            ],
            [
                'value' => false,
                'label' => 'Out of Stock',
                'count' => (clone $query)->whereDoesntHave('variants', function ($q) {
                    $q->where('stock', '>', 0);
                })->count(),
            ],
        ];
    }

    /**
     * Get rating facets.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $baseQuery
     * @param  array  $filters
     * @return array
     */
    protected function getRatingFacets($baseQuery, array $filters): array
    {
        $query = clone $baseQuery;
        unset($filters['average_rating']);
        $query = $this->applyFilters($query, $filters);

        $ratings = [
            ['min' => 4.5, 'label' => '4.5+ Stars', 'count' => 0],
            ['min' => 4.0, 'max' => 4.5, 'label' => '4.0 - 4.5 Stars', 'count' => 0],
            ['min' => 3.5, 'max' => 4.0, 'label' => '3.5 - 4.0 Stars', 'count' => 0],
            ['min' => 3.0, 'max' => 3.5, 'label' => '3.0 - 3.5 Stars', 'count' => 0],
        ];

        foreach ($ratings as &$rating) {
            $ratingQuery = clone $query;
            if (isset($rating['max'])) {
                $ratingQuery->whereBetween('average_rating', [$rating['min'], $rating['max']]);
            } else {
                $ratingQuery->where('average_rating', '>=', $rating['min']);
            }
            $rating['count'] = $ratingQuery->count();
        }

        return array_filter($ratings, fn($rating) => $rating['count'] > 0);
    }

    /**
     * Apply filters to query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array  $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyFilters($query, array $filters)
    {
        if (isset($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }

        if (isset($filters['category_ids']) && is_array($filters['category_ids'])) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->whereIn('id', $filters['category_ids']);
            });
        }

        if (isset($filters['price_min'])) {
            $query->whereHas('variants.prices', function ($q) use ($filters) {
                $q->where('price', '>=', $filters['price_min'] * 100);
            });
        }

        if (isset($filters['price_max'])) {
            $query->whereHas('variants.prices', function ($q) use ($filters) {
                $q->where('price', '<=', $filters['price_max'] * 100);
            });
        }

        if (isset($filters['in_stock'])) {
            if ($filters['in_stock']) {
                $query->whereHas('variants', function ($q) {
                    $q->where('stock', '>', 0);
                });
            } else {
                $query->whereDoesntHave('variants', function ($q) {
                    $q->where('stock', '>', 0);
                });
            }
        }

        if (isset($filters['average_rating'])) {
            $query->where('average_rating', '>=', $filters['average_rating']);
        }

        return $query;
    }
}


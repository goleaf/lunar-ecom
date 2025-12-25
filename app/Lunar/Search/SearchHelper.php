<?php

namespace App\Lunar\Search;

use Illuminate\Pagination\LengthAwarePaginator;
use Lunar\Models\Product;

/**
 * Helper class for working with Lunar Search.
 * 
 * Provides convenience methods for searching products and other models using Laravel Scout.
 * See: https://docs.lunarphp.com/1.x/reference/search
 */
class SearchHelper
{
    /**
     * Search products using Laravel Scout.
     * 
     * @param string $query
     * @param int $perPage
     * @param int $page
     * @return LengthAwarePaginator
     */
    public static function searchProducts(string $query, int $perPage = 12, int $page = 1): LengthAwarePaginator
    {
        if (empty($query)) {
            return new LengthAwarePaginator(
                collect(),
                0,
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        // Use Laravel Scout for search
        // Products use the Searchable trait from Lunar
        $results = Product::search($query)
            ->where('status', 'published')
            ->paginate($perPage, 'page', $page);

        return $results;
    }

    /**
     * Get search results as a collection (without pagination).
     * 
     * @param string $query
     * @param int|null $limit
     * @return \Illuminate\Support\Collection
     */
    public static function searchProductsCollection(string $query, ?int $limit = null): \Illuminate\Support\Collection
    {
        if (empty($query)) {
            return collect();
        }

        $queryBuilder = Product::search($query)
            ->where('status', 'published');

        if ($limit) {
            $queryBuilder->take($limit);
        }

        return $queryBuilder->get();
    }

    /**
     * Check if search is properly configured.
     * 
     * @return bool
     */
    public static function isConfigured(): bool
    {
        $driver = config('scout.driver');
        return !empty($driver);
    }

    /**
     * Get the current Scout driver.
     * 
     * @return string
     */
    public static function getDriver(): string
    {
        return config('scout.driver', 'database');
    }
}



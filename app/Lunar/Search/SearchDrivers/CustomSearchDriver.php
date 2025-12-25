<?php

namespace App\Lunar\Search\SearchDrivers;

use Lunar\Base\SearchDriverInterface;
use Lunar\Models\Product;

/**
 * Example custom search driver.
 * 
 * To use this, set it in config/lunar/search.php:
 * 
 * 'driver' => 'custom',
 * 'drivers' => [
 *     'custom' => [
 *         'driver' => CustomSearchDriver::class,
 *     ],
 * ]
 */
class CustomSearchDriver implements SearchDriverInterface
{
    /**
     * Perform a search query.
     */
    public function search(string $term, ?int $limit = null, ?int $offset = null): array
    {
        // Implement your custom search logic here
        // This is a simple example - you could integrate with Elasticsearch, Algolia, etc.
        
        $query = Product::query()
            ->where('status', 'published');
        
        // Simple text search (can be enhanced with full-text search, etc.)
        $query->whereHas('urls', function ($q) use ($term) {
            $q->where('slug', 'like', "%{$term}%");
        });
        
        if ($limit) {
            $query->limit($limit);
        }
        
        if ($offset) {
            $query->offset($offset);
        }
        
        return $query->get()->toArray();
    }

    /**
     * Index a product.
     */
    public function index(Product $product): void
    {
        // Implement indexing logic here
        // This could sync with an external search service
    }

    /**
     * Remove a product from the index.
     */
    public function delete(Product $product): void
    {
        // Implement deletion logic here
    }
}



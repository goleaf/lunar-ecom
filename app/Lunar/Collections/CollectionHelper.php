<?php

namespace App\Lunar\Collections;

use Illuminate\Database\Eloquent\Collection;
use Lunar\Models\Collection as LunarCollection;
use Lunar\Models\Product;

/**
 * Helper class for working with Lunar Collections.
 * 
 * Provides convenience methods for managing collections and sorting products.
 * See: https://docs.lunarphp.com/1.x/reference/collections
 */
class CollectionHelper
{
    /**
     * Get products from a collection with proper sorting applied.
     * 
     * Supports the following sort types:
     * - min_price:asc - Sort by minimum price ascending
     * - min_price:desc - Sort by minimum price descending
     * - sku:asc - Sort by SKU ascending
     * - sku:desc - Sort by SKU descending
     * - custom - Use manual position ordering (default for BelongsToMany relationship)
     * 
     * @param LunarCollection $collection
     * @return Collection
     */
    public static function getSortedProducts(LunarCollection $collection): Collection
    {
        $sort = $collection->sort ?? 'custom';
        
        $products = $collection->products()
            ->with(['variants.prices', 'images'])
            ->where('status', 'published');

        // Apply sorting based on collection's sort type
        switch ($sort) {
            case 'min_price:asc':
                // Sort by minimum variant price ascending
                $products = $products->get()->sortBy(function ($product) {
                    return $product->variants->min(function ($variant) {
                        return $variant->prices->min('price');
                    });
                })->values();
                break;

            case 'min_price:desc':
                // Sort by minimum variant price descending
                $products = $products->get()->sortByDesc(function ($product) {
                    return $product->variants->min(function ($variant) {
                        return $variant->prices->min('price');
                    });
                })->values();
                break;

            case 'sku:asc':
                // Sort by SKU ascending
                $products = $products->orderBy('sku', 'asc')->get();
                break;

            case 'sku:desc':
                // Sort by SKU descending
                $products = $products->orderBy('sku', 'desc')->get();
                break;

            case 'custom':
            default:
                // Custom sorting uses the pivot position (default for BelongsToMany)
                // The relationship already orders by pivot position
                $products = $products->get();
                break;
        }

        return $products;
    }

    /**
     * Add products to a collection with positions.
     * 
     * @param LunarCollection $collection
     * @param array $productPositions Array of [product_id => ['position' => int]]
     * @return void
     */
    public static function addProducts(LunarCollection $collection, array $productPositions): void
    {
        $collection->products()->sync($productPositions);
    }

    /**
     * Create a child collection.
     * 
     * @param LunarCollection $parent
     * @param LunarCollection $child
     * @return void
     */
    public static function addChildCollection(LunarCollection $parent, LunarCollection $child): void
    {
        $child->appendNode($parent);
    }

    /**
     * Get all child collections for a collection.
     * 
     * @param LunarCollection $collection
     * @return Collection
     */
    public static function getChildren(LunarCollection $collection): Collection
    {
        return $collection->children;
    }

    /**
     * Get the breadcrumb path for a collection.
     * 
     * @param LunarCollection $collection
     * @return Collection
     */
    public static function getBreadcrumb(LunarCollection $collection): Collection
    {
        return $collection->breadcrumb;
    }
}


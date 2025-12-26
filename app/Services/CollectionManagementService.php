<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\CollectionProductMetadata;
use App\Models\Product;
use App\Services\SmartCollectionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing collections and automatic product assignment.
 */
class CollectionManagementService
{
    /**
     * Assign product to collection.
     *
     * @param  Collection  $collection
     * @param  Product  $product
     * @param  array  $options
     * @return void
     */
    public function assignProduct(Collection $collection, Product $product, array $options = []): void
    {
        // Check if already assigned
        if ($collection->products()->where('products.id', $product->id)->exists()) {
            // Update metadata if exists
            $metadata = CollectionProductMetadata::where('collection_id', $collection->id)
                ->where('product_id', $product->id)
                ->first();
            
            if ($metadata) {
                $metadata->update([
                    'is_auto_assigned' => $options['is_auto_assigned'] ?? $metadata->is_auto_assigned,
                    'position' => $options['position'] ?? $metadata->position,
                    'expires_at' => $options['expires_at'] ?? $metadata->expires_at,
                ]);
            }
            return;
        }

        // Add to collection
        $collection->products()->attach($product->id);

        // Create metadata
        CollectionProductMetadata::create([
            'collection_id' => $collection->id,
            'product_id' => $product->id,
            'is_auto_assigned' => $options['is_auto_assigned'] ?? false,
            'position' => $options['position'] ?? 0,
            'assigned_at' => now(),
            'expires_at' => $options['expires_at'] ?? null,
            'metadata' => $options['metadata'] ?? null,
        ]);

        // Update product count
        $this->updateProductCount($collection);
    }

    /**
     * Remove product from collection.
     *
     * @param  Collection  $collection
     * @param  Product  $product
     * @return void
     */
    public function removeProduct(Collection $collection, Product $product): void
    {
        $collection->products()->detach($product->id);
        CollectionProductMetadata::where('collection_id', $collection->id)
            ->where('product_id', $product->id)
            ->delete();
        
        $this->updateProductCount($collection);
    }

    /**
     * Process automatic assignment for a collection.
     *
     * @param  Collection  $collection
     * @return int Number of products assigned
     */
    public function processAutoAssignment(Collection $collection): int
    {
        if (!$collection->auto_assign || !$collection->isActive()) {
            return 0;
        }

        $rules = $collection->assignment_rules ?? [];
        
        if (empty($rules)) {
            return 0;
        }

        // Get products matching rules
        $products = $this->getProductsMatchingRules($rules);
        
        // Apply max products limit
        if ($collection->max_products) {
            $products = $products->take($collection->max_products);
        }

        // Remove existing auto-assigned products
        $autoAssignedProductIds = CollectionProductMetadata::where('collection_id', $collection->id)
            ->where('is_auto_assigned', true)
            ->pluck('product_id');
        
        if ($autoAssignedProductIds->isNotEmpty()) {
            $collection->products()->detach($autoAssignedProductIds);
            CollectionProductMetadata::where('collection_id', $collection->id)
                ->where('is_auto_assigned', true)
                ->delete();
        }

        // Assign new products
        $assigned = 0;
        foreach ($products as $index => $product) {
            $this->assignProduct($collection, $product, [
                'is_auto_assigned' => true,
                'position' => $index,
            ]);
            $assigned++;
        }

        // Update collection
        $collection->update([
            'product_count' => $collection->products()->count(),
            'last_updated_at' => now(),
        ]);

        return $assigned;
    }

    /**
     * Get products matching assignment rules.
     *
     * @param  array  $rules
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getProductsMatchingRules(array $rules): \Illuminate\Database\Eloquent\Collection
    {
        // Handle collection type shortcuts
        if (isset($rules['type'])) {
            return $this->getProductsByType($rules['type'], $rules);
        }

        $query = Product::published();

        // Apply custom rules
        if (is_array($rules) && !empty($rules)) {
            foreach ($rules as $rule) {
                if (is_array($rule)) {
                    $this->applyRule($query, $rule);
                }
            }
        }

        return $query->get();
    }

    /**
     * Get products by collection type.
     *
     * @param  string  $type
     * @param  array  $options
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getProductsByType(string $type, array $options = []): \Illuminate\Database\Eloquent\Collection
    {
        return match ($type) {
            'bestsellers' => $this->getBestsellers($options),
            'new_arrivals' => $this->getNewArrivals($options),
            'featured' => $this->getFeatured($options),
            'seasonal' => $this->getSeasonal($options),
            default => collect(),
        };
    }

    /**
     * Get bestsellers.
     *
     * @param  array  $options
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getBestsellers(array $options = []): \Illuminate\Database\Eloquent\Collection
    {
        $limit = $options['limit'] ?? 20;
        $days = $options['days'] ?? 30;

        return Product::whereHas('orderLines', function ($q) use ($days) {
            $q->whereHas('order', function ($orderQuery) use ($days) {
                $orderQuery->whereNotNull('placed_at')
                    ->where('placed_at', '>=', now()->subDays($days));
            });
        })
        ->withCount([
            'orderLines as sales_count' => function ($q) use ($days) {
                $q->whereHas('order', function ($orderQuery) use ($days) {
                    $orderQuery->whereNotNull('placed_at')
                        ->where('placed_at', '>=', now()->subDays($days));
                });
            }
        ])
        ->orderByDesc('sales_count')
        ->limit($limit)
        ->get();
    }

    /**
     * Get new arrivals.
     *
     * @param  array  $options
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getNewArrivals(array $options = []): \Illuminate\Database\Eloquent\Collection
    {
        $limit = $options['limit'] ?? 20;
        $days = $options['days'] ?? 30;

        return Product::published()
            ->where('created_at', '>=', now()->subDays($days))
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get featured products.
     *
     * @param  array  $options
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getFeatured(array $options = []): \Illuminate\Database\Eloquent\Collection
    {
        $limit = $options['limit'] ?? 20;

        // Featured products could be marked with a custom attribute or high rating
        return Product::published()
            ->where(function ($q) {
                $q->where('average_rating', '>=', 4.5)
                  ->orWhere('total_reviews', '>=', 10);
            })
            ->orderByDesc('average_rating')
            ->orderByDesc('total_reviews')
            ->limit($limit)
            ->get();
    }

    /**
     * Get seasonal products.
     *
     * @param  array  $options
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getSeasonal(array $options = []): \Illuminate\Database\Eloquent\Collection
    {
        $limit = $options['limit'] ?? 20;
        $season = $options['season'] ?? $this->getCurrentSeason();

        // This would match products with seasonal tags/categories
        return Product::published()
            ->whereHas('categories', function ($q) use ($season) {
                $q->where('name', 'like', "%{$season}%");
            })
            ->limit($limit)
            ->get();
    }

    /**
     * Get current season.
     *
     * @return string
     */
    protected function getCurrentSeason(): string
    {
        $month = now()->month;
        
        return match (true) {
            in_array($month, [12, 1, 2]) => 'winter',
            in_array($month, [3, 4, 5]) => 'spring',
            in_array($month, [6, 7, 8]) => 'summer',
            default => 'fall',
        };
    }

    /**
     * Apply a rule to query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array  $rule
     * @return void
     */
    protected function applyRule($query, array $rule): void
    {
        $field = $rule['field'] ?? null;
        $operator = $rule['operator'] ?? 'equals';
        $value = $rule['value'] ?? null;

        if (!$field) {
            return;
        }

        match ($operator) {
            'equals' => $query->where($field, $value),
            'not_equals' => $query->where($field, '!=', $value),
            'greater_than' => $query->where($field, '>', $value),
            'less_than' => $query->where($field, '<', $value),
            'greater_or_equal' => $query->where($field, '>=', $value),
            'less_or_equal' => $query->where($field, '<=', $value),
            'contains' => $query->where($field, 'like', "%{$value}%"),
            'in' => $query->whereIn($field, (array)$value),
            'not_in' => $query->whereNotIn($field, (array)$value),
            'is_null' => $query->whereNull($field),
            'is_not_null' => $query->whereNotNull($field),
            default => null,
        };
    }

    /**
     * Update product count for collection.
     *
     * @param  Collection  $collection
     * @return void
     */
    public function updateProductCount(Collection $collection): void
    {
        $collection->update([
            'product_count' => $collection->products()->count(),
            'last_updated_at' => now(),
        ]);
    }

    /**
     * Reorder products in collection.
     *
     * @param  Collection  $collection
     * @param  array  $productIds
     * @return void
     */
    public function reorderProducts(Collection $collection, array $productIds): void
    {
        foreach ($productIds as $position => $productId) {
            CollectionProductMetadata::where('collection_id', $collection->id)
                ->where('product_id', $productId)
                ->update(['position' => $position]);
        }
    }

    /**
     * Process all auto-assign collections.
     *
     * @return int Number of collections processed
     */
    public function processAllAutoAssignments(): int
    {
        $collections = Collection::autoAssign()->get();
        $processed = 0;

        foreach ($collections as $collection) {
            try {
                $this->processAutoAssignment($collection);
                $processed++;
            } catch (\Exception $e) {
                Log::error('Failed to process auto-assignment for collection', [
                    'collection_id' => $collection->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $processed;
    }

    /**
     * Remove expired product assignments.
     *
     * @return int Number of assignments removed
     */
    public function removeExpiredAssignments(): int
    {
        $expired = CollectionProductMetadata::whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        $removed = 0;
        foreach ($expired as $metadata) {
            $metadata->collection->products()->detach($metadata->product_id);
            $metadata->delete();
            $this->updateProductCount($metadata->collection);
            $removed++;
        }

        return $removed;
    }
}

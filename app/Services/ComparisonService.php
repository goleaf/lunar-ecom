<?php

namespace App\Services;

use App\Models\ComparisonAnalytic;
use App\Models\ProductComparison;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Lunar\Models\Product;

/**
 * Service for managing product comparisons.
 */
class ComparisonService
{
    /**
     * Maximum number of products that can be compared.
     */
    const MAX_PRODUCTS = 5;

    /**
     * Get or create comparison for current user/session.
     *
     * @return ProductComparison
     */
    protected function getOrCreateComparison(): ProductComparison
    {
        $userId = auth()->id();
        $sessionId = session()->getId();

        if ($userId) {
            $comparison = ProductComparison::where('user_id', $userId)->first();
        } else {
            $comparison = ProductComparison::where('session_id', $sessionId)->first();
        }

        if (!$comparison) {
            $comparison = ProductComparison::create([
                'user_id' => $userId,
                'session_id' => $sessionId,
                'product_ids' => [],
            ]);
        }

        return $comparison;
    }

    /**
     * Add product to comparison.
     *
     * @param  int  $productId
     * @return array
     * @throws \Exception
     */
    public function addToComparison(int $productId): array
    {
        $comparison = $this->getOrCreateComparison();

        if ($comparison->isFull()) {
            throw new \Exception('Maximum ' . self::MAX_PRODUCTS . ' products can be compared at once');
        }

        $productIds = $comparison->product_ids ?? [];

        if (in_array($productId, $productIds)) {
            return [
                'success' => false,
                'message' => 'Product is already in comparison',
                'comparison' => $comparison->fresh(),
            ];
        }

        $productIds[] = $productId;
        $comparison->update(['product_ids' => $productIds]);

        // Track analytics
        $this->trackComparison($productIds);

        return [
            'success' => true,
            'message' => 'Product added to comparison',
            'comparison' => $comparison->fresh(),
            'product_count' => count($productIds),
        ];
    }

    /**
     * Remove product from comparison.
     *
     * @param  int  $productId
     * @return array
     */
    public function removeFromComparison(int $productId): array
    {
        $comparison = $this->getOrCreateComparison();
        $productIds = $comparison->product_ids ?? [];

        $productIds = array_values(array_filter($productIds, fn($id) => $id != $productId));
        $comparison->update(['product_ids' => $productIds]);

        return [
            'success' => true,
            'message' => 'Product removed from comparison',
            'comparison' => $comparison->fresh(),
            'product_count' => count($productIds),
        ];
    }

    /**
     * Get comparison products with full details.
     *
     * @param  array|null  $selectedAttributes
     * @return Collection
     */
    public function getComparisonProducts(?array $selectedAttributes = null): Collection
    {
        $comparison = $this->getOrCreateComparison();
        $productIds = $comparison->product_ids ?? [];

        if (empty($productIds)) {
            return collect();
        }

        // Update selected attributes if provided
        if ($selectedAttributes !== null) {
            $comparison->update(['selected_attributes' => $selectedAttributes]);
        } else {
            $selectedAttributes = $comparison->selected_attributes;
        }

        $products = Product::whereIn('id', $productIds)
            ->with([
                'variants.prices',
                'variants.stock',
                'thumbnail',
                'media',
                'brand',
                'attributeValues.attribute',
                'categories',
            ])
            ->get()
            ->sortBy(function ($product) use ($productIds) {
                return array_search($product->id, $productIds);
            });

        return $products;
    }

    /**
     * Clear comparison.
     *
     * @return void
     */
    public function clearComparison(): void
    {
        $comparison = $this->getOrCreateComparison();
        $comparison->update(['product_ids' => []]);
    }

    /**
     * Get comparison count.
     *
     * @return int
     */
    public function getComparisonCount(): int
    {
        $comparison = $this->getOrCreateComparison();
        return count($comparison->product_ids ?? []);
    }

    /**
     * Get comparison product IDs.
     *
     * @return array
     */
    public function getComparisonProductIds(): array
    {
        $comparison = $this->getOrCreateComparison();
        return $comparison->product_ids ?? [];
    }

    /**
     * Check if product is in comparison.
     *
     * @param  int  $productId
     * @return bool
     */
    public function isInComparison(int $productId): bool
    {
        $productIds = $this->getComparisonProductIds();
        return in_array($productId, $productIds);
    }

    /**
     * Get comparison attributes (all attributes from compared products).
     *
     * @param  array|null  $selectedAttributes
     * @return Collection
     */
    public function getComparisonAttributes(?array $selectedAttributes = null): Collection
    {
        $products = $this->getComparisonProducts($selectedAttributes);

        if ($products->isEmpty()) {
            return collect();
        }

        // Get all unique attributes from all products
        $allAttributes = collect();
        foreach ($products as $product) {
            foreach ($product->attributeValues as $attributeValue) {
                $attribute = $attributeValue->attribute;
                if (!$allAttributes->contains('id', $attribute->id)) {
                    $allAttributes->push($attribute);
                }
            }
        }

        // Filter by selected attributes if provided
        if ($selectedAttributes && !empty($selectedAttributes)) {
            $allAttributes = $allAttributes->whereIn('id', $selectedAttributes);
        }

        return $allAttributes->sortBy('display_order');
    }

    /**
     * Get attribute values for comparison table.
     *
     * @param  array|null  $selectedAttributes
     * @return array
     */
    public function getComparisonTable(?array $selectedAttributes = null): array
    {
        $products = $this->getComparisonProducts($selectedAttributes);
        $attributes = $this->getComparisonAttributes($selectedAttributes);

        $table = [
            'products' => $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->translateAttribute('name'),
                    'slug' => $product->urls->first()?->slug,
                    'image' => $product->thumbnail?->getUrl(),
                    'price' => $this->getProductPrice($product),
                    'rating' => $product->average_rating ?? 0,
                    'total_reviews' => $product->total_reviews ?? 0,
                    'sku' => $product->sku,
                    'brand' => $product->brand?->name,
                    'in_stock' => $this->isProductInStock($product),
                ];
            })->toArray(),
            'attributes' => [],
        ];

        // Build attribute rows
        foreach ($attributes as $attribute) {
            $row = [
                'attribute_id' => $attribute->id,
                'attribute_name' => $attribute->translateAttribute('name'),
                'attribute_type' => $attribute->type,
                'values' => [],
            ];

            foreach ($products as $product) {
                $attributeValue = $product->attributeValues->firstWhere('attribute_id', $attribute->id);
                $row['values'][] = $attributeValue ? $attributeValue->getDisplayValue() : '—';
            }

            $table['attributes'][] = $row;
        }

        return $table;
    }

    /**
     * Get product price (lowest variant price).
     *
     * @param  Product  $product
     * @return int  Price in cents
     */
    protected function getProductPrice(Product $product): int
    {
        $variants = $product->variants;
        if ($variants->isEmpty()) {
            return 0;
        }

        $prices = [];
        foreach ($variants as $variant) {
            if ($variant->base_price) {
                $prices[] = $variant->base_price;
            }
        }

        return !empty($prices) ? min($prices) : 0;
    }

    /**
     * Check if product is in stock.
     *
     * @param  Product  $product
     * @return bool
     */
    protected function isProductInStock(Product $product): bool
    {
        return $product->variants->contains(function ($variant) {
            return ($variant->stock ?? 0) > 0 || $variant->backorder ?? false;
        });
    }

    /**
     * Track comparison analytics.
     *
     * @param  array  $productIds
     * @return void
     */
    protected function trackComparison(array $productIds): void
    {
        if (count($productIds) < 2) {
            return; // Need at least 2 products to compare
        }

        // Sort product IDs for consistent tracking
        sort($productIds);
        $productIdsJson = json_encode($productIds);

        // Find or create analytics record
        $analytic = ComparisonAnalytic::where('product_ids', $productIdsJson)
            ->where('compared_at', '>=', now()->subDay())
            ->first();

        if ($analytic) {
            $analytic->increment('comparison_count');
        } else {
            ComparisonAnalytic::create([
                'product_ids' => $productIds,
                'comparison_count' => 1,
                'user_id' => auth()->id(),
                'session_id' => session()->getId(),
                'compared_at' => now(),
            ]);
        }
    }

    /**
     * Get most compared product pairs.
     *
     * @param  int  $limit
     * @return Collection
     */
    public function getMostComparedPairs(int $limit = 10): Collection
    {
        return ComparisonAnalytic::mostCompared($limit)->get();
    }

    /**
     * Migrate session comparison to user comparison.
     *
     * @param  int  $userId
     * @return void
     */
    public function migrateToUser(int $userId): void
    {
        $sessionId = session()->getId();
        
        $sessionComparison = ProductComparison::where('session_id', $sessionId)
            ->whereNull('user_id')
            ->first();

        if (!$sessionComparison) {
            return;
        }

        $userComparison = ProductComparison::where('user_id', $userId)->first();

        if ($userComparison) {
            // Merge product IDs
            $mergedIds = array_unique(array_merge(
                $userComparison->product_ids ?? [],
                $sessionComparison->product_ids ?? []
            ));
            $mergedIds = array_slice($mergedIds, 0, self::MAX_PRODUCTS);
            
            $userComparison->update(['product_ids' => $mergedIds]);
            $sessionComparison->delete();
        } else {
            // Transfer session comparison to user
            $sessionComparison->update([
                'user_id' => $userId,
                'session_id' => null,
            ]);
        }
    }

    /**
     * Clean up expired comparisons.
     *
     * @return int  Number of comparisons deleted
     */
    public function cleanupExpired(): int
    {
        return ProductComparison::expired()->delete();
    }
}


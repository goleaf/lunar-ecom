<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

/**
 * Service for managing product comparisons.
 */
class ComparisonService
{
    /**
     * Maximum number of products that can be compared.
     */
    const MAX_COMPARISON_ITEMS = 4;

    /**
     * Session key for storing comparison items.
     */
    const SESSION_KEY = 'product_comparison';

    /**
     * Add product to comparison.
     *
     * @param  Product  $product
     * @return bool
     */
    public function addProduct(Product $product): bool
    {
        $items = $this->getComparisonItems();

        // Check if already in comparison
        if ($items->contains('id', $product->id)) {
            return false;
        }

        // Check limit
        if ($items->count() >= self::MAX_COMPARISON_ITEMS) {
            return false;
        }

        // Add to comparison
        $items->push([
            'id' => $product->id,
            'added_at' => now()->toDateTimeString(),
        ]);

        $this->saveComparisonItems($items);

        return true;
    }

    /**
     * Remove product from comparison.
     *
     * @param  Product  $product
     * @return bool
     */
    public function removeProduct(Product $product): bool
    {
        $items = $this->getComparisonItems();
        $items = $items->reject(function ($item) use ($product) {
            return $item['id'] === $product->id;
        });

        $this->saveComparisonItems($items);

        return true;
    }

    /**
     * Clear all comparison items.
     *
     * @return void
     */
    public function clearComparison(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    /**
     * Get comparison items.
     *
     * @return Collection
     */
    public function getComparisonItems(): Collection
    {
        return collect(Session::get(self::SESSION_KEY, []));
    }

    /**
     * Get comparison products with full data.
     *
     * @return Collection
     */
    public function getComparisonProducts(): Collection
    {
        $itemIds = $this->getComparisonItems()->pluck('id');

        if ($itemIds->isEmpty()) {
            return collect();
        }

        return Product::with([
            'variants.prices',
            'variants.stock',
            'media',
            'brand',
            'categories',
            'attributeValues.attribute',
            'reviews' => function ($query) {
                $query->where('is_approved', true);
            },
        ])
        ->whereIn('id', $itemIds)
        ->get()
        ->sortBy(function ($product) use ($itemIds) {
            return $itemIds->search($product->id);
        })
        ->values();
    }

    /**
     * Get comparison count.
     *
     * @return int
     */
    public function getComparisonCount(): int
    {
        return $this->getComparisonItems()->count();
    }

    /**
     * Check if product is in comparison.
     *
     * @param  Product  $product
     * @return bool
     */
    public function isInComparison(Product $product): bool
    {
        return $this->getComparisonItems()->contains('id', $product->id);
    }

    /**
     * Check if comparison is full.
     *
     * @return bool
     */
    public function isFull(): bool
    {
        return $this->getComparisonCount() >= self::MAX_COMPARISON_ITEMS;
    }

    /**
     * Get comparison data for display.
     *
     * @return array
     */
    public function getComparisonData(): array
    {
        $products = $this->getComparisonProducts();

        if ($products->isEmpty()) {
            return [
                'products' => collect(),
                'attributes' => collect(),
                'specifications' => [],
            ];
        }

        // Get all unique attributes across products
        $allAttributes = collect();
        foreach ($products as $product) {
            foreach ($product->attributeValues as $attributeValue) {
                $attribute = $attributeValue->attribute;
                if ($attribute && !$allAttributes->contains('id', $attribute->id)) {
                    $allAttributes->push([
                        'id' => $attribute->id,
                        'handle' => $attribute->handle,
                        'name' => $attribute->translate('name'),
                        'type' => $attribute->type,
                    ]);
                }
            }
        }

        // Get specifications (custom product fields)
        $specifications = [
            'sku' => 'SKU',
            'barcode' => 'Barcode',
            'weight' => 'Weight',
            'dimensions' => 'Dimensions',
            'manufacturer_name' => 'Manufacturer',
            'warranty_period' => 'Warranty Period',
            'condition' => 'Condition',
            'origin_country' => 'Origin Country',
        ];

        return [
            'products' => $products,
            'attributes' => $allAttributes,
            'specifications' => $specifications,
        ];
    }

    /**
     * Save comparison items to session.
     *
     * @param  Collection  $items
     * @return void
     */
    protected function saveComparisonItems(Collection $items): void
    {
        Session::put(self::SESSION_KEY, $items->values()->toArray());
    }
}

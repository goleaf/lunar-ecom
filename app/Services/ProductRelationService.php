<?php

namespace App\Services;

use App\Models\Product;
use Lunar\Base\Enums\ProductAssociation as ProductAssociationEnum;
use Illuminate\Support\Collection;

/**
 * Service for managing product relations (related products, accessories, replacements, etc.).
 */
class ProductRelationService
{
    /**
     * Get accessories for a product (uses cross-sell type).
     *
     * @param  Product  $product
     * @param  int  $limit
     * @return Collection
     */
    public function getAccessories(Product $product, int $limit = 10): Collection
    {
        return $product->associations()
            ->crossSell()
            ->with('target.variants.prices', 'target.images')
            ->limit($limit)
            ->get()
            ->pluck('target');
    }

    /**
     * Get replacement/alternative products (uses alternate type).
     *
     * @param  Product  $product
     * @param  int  $limit
     * @return Collection
     */
    public function getReplacements(Product $product, int $limit = 10): Collection
    {
        return $product->associations()
            ->alternate()
            ->with('target.variants.prices', 'target.images')
            ->limit($limit)
            ->get()
            ->pluck('target');
    }

    /**
     * Get related products (uses related type or falls back to recommendation service).
     *
     * @param  Product  $product
     * @param  int  $limit
     * @return Collection
     */
    public function getRelated(Product $product, int $limit = 10): Collection
    {
        // First try to get manually associated related products
        $related = $product->associations()
            ->type('related')
            ->with('target.variants.prices', 'target.images')
            ->limit($limit)
            ->get()
            ->pluck('target');

        // If not enough, fall back to recommendation service
        if ($related->count() < $limit && class_exists(\App\Services\RecommendationService::class)) {
            $recommendationService = app(\App\Services\RecommendationService::class);
            $recommended = $recommendationService->getRelatedProducts($product, $limit - $related->count());
            $related = $related->merge($recommended);
        }

        return $related->take($limit);
    }

    /**
     * Get cross-sell products.
     *
     * @param  Product  $product
     * @param  int  $limit
     * @return Collection
     */
    public function getCrossSell(Product $product, int $limit = 10): Collection
    {
        return $product->associations()
            ->crossSell()
            ->with('target.variants.prices', 'target.images')
            ->limit($limit)
            ->get()
            ->pluck('target');
    }

    /**
     * Get up-sell products.
     *
     * @param  Product  $product
     * @param  int  $limit
     * @return Collection
     */
    public function getUpSell(Product $product, int $limit = 10): Collection
    {
        return $product->associations()
            ->upSell()
            ->with('target.variants.prices', 'target.images')
            ->limit($limit)
            ->get()
            ->pluck('target');
    }

    /**
     * Get "customers also bought" products.
     *
     * @param  Product  $product
     * @param  int  $limit
     * @return Collection
     */
    public function getCustomersAlsoBought(Product $product, int $limit = 10): Collection
    {
        if (!class_exists(\App\Services\RecommendationService::class)) {
            return collect();
        }

        $recommendationService = app(\App\Services\RecommendationService::class);
        return $recommendationService->getFrequentlyBoughtTogether($product, $limit);
    }

    /**
     * Get all relations for a product grouped by type.
     *
     * @param  Product  $product
     * @param  int  $limitPerType
     * @return array
     */
    public function getAllRelations(Product $product, int $limitPerType = 10): array
    {
        return [
            'related' => $this->getRelated($product, $limitPerType),
            'accessories' => $this->getAccessories($product, $limitPerType),
            'replacements' => $this->getReplacements($product, $limitPerType),
            'cross_sell' => $this->getCrossSell($product, $limitPerType),
            'up_sell' => $this->getUpSell($product, $limitPerType),
            'customers_also_bought' => $this->getCustomersAlsoBought($product, $limitPerType),
        ];
    }

    /**
     * Add accessories to a product.
     *
     * @param  Product  $product
     * @param  array|Collection  $accessoryIds
     * @return void
     */
    public function addAccessories(Product $product, $accessoryIds): void
    {
        $ids = is_array($accessoryIds) ? $accessoryIds : $accessoryIds->toArray();
        
        foreach ($ids as $accessoryId) {
            $product->associate(
                Product::find($accessoryId),
                ProductAssociationEnum::CROSS_SELL
            );
        }
    }

    /**
     * Add replacement products to a product.
     *
     * @param  Product  $product
     * @param  array|Collection  $replacementIds
     * @return void
     */
    public function addReplacements(Product $product, $replacementIds): void
    {
        $ids = is_array($replacementIds) ? $replacementIds : $replacementIds->toArray();
        
        foreach ($ids as $replacementId) {
            $product->associate(
                Product::find($replacementId),
                ProductAssociationEnum::ALTERNATE
            );
        }
    }

    /**
     * Remove accessories from a product.
     *
     * @param  Product  $product
     * @param  array|Collection  $accessoryIds
     * @return void
     */
    public function removeAccessories(Product $product, $accessoryIds): void
    {
        $ids = is_array($accessoryIds) ? $accessoryIds : $accessoryIds->toArray();
        
        foreach ($ids as $accessoryId) {
            $product->dissociate(
                Product::find($accessoryId),
                ProductAssociationEnum::CROSS_SELL
            );
        }
    }

    /**
     * Remove replacement products from a product.
     *
     * @param  Product  $product
     * @param  array|Collection  $replacementIds
     * @return void
     */
    public function removeReplacements(Product $product, $replacementIds): void
    {
        $ids = is_array($replacementIds) ? $replacementIds : $replacementIds->toArray();
        
        foreach ($ids as $replacementId) {
            $product->dissociate(
                Product::find($replacementId),
                ProductAssociationEnum::ALTERNATE
            );
        }
    }

    /**
     * Get bundled products for a product.
     *
     * @param  Product  $product
     * @return Collection
     */
    public function getBundles(Product $product): Collection
    {
        if (!$product->isBundle()) {
            return collect();
        }

        // Get bundle items if product is a bundle
        if ($product->bundle) {
            return $product->bundle->items()
                ->with('product.variants.prices', 'product.images')
                ->get()
                ->pluck('product');
        }

        return collect();
    }

    /**
     * Get products that include this product in their bundles.
     *
     * @param  Product  $product
     * @return Collection
     */
    public function getBundledIn(Product $product): Collection
    {
        return Product::whereHas('bundleItems', function ($query) use ($product) {
            $query->where('product_id', $product->id);
        })
        ->with('bundle')
        ->get();
    }

    /**
     * Add related products.
     *
     * @param  Product  $product
     * @param  array|Collection  $relatedIds
     * @return void
     */
    public function addRelated(Product $product, $relatedIds): void
    {
        $ids = is_array($relatedIds) ? $relatedIds : $relatedIds->toArray();
        
        foreach ($ids as $relatedId) {
            $product->associate(
                Product::find($relatedId),
                'related'
            );
        }
    }

    /**
     * Add cross-sell products.
     *
     * @param  Product  $product
     * @param  array|Collection  $productIds
     * @return void
     */
    public function addCrossSell(Product $product, $productIds): void
    {
        $ids = is_array($productIds) ? $productIds : $productIds->toArray();
        
        foreach ($ids as $productId) {
            $product->associate(
                Product::find($productId),
                ProductAssociationEnum::CROSS_SELL
            );
        }
    }

    /**
     * Add up-sell products.
     *
     * @param  Product  $product
     * @param  array|Collection  $productIds
     * @return void
     */
    public function addUpSell(Product $product, $productIds): void
    {
        $ids = is_array($productIds) ? $productIds : $productIds->toArray();
        
        foreach ($ids as $productId) {
            $product->associate(
                Product::find($productId),
                ProductAssociationEnum::UP_SELL
            );
        }
    }

    /**
     * Get bundled products (products included in a bundle).
     *
     * @param  Product  $product  The bundle product
     * @return Collection
     */
    public function getBundledProducts(Product $product): Collection
    {
        return $this->getBundles($product);
    }
}


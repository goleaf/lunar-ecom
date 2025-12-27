<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\CollectionRule;
use App\Models\Product;
use App\Enums\CollectionType;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Comprehensive Collection Service - Complete collection management.
 * 
 * Handles all collection types:
 * - Manual collections
 * - Rule-based (dynamic) collections
 * - Scheduled collections
 * - Cross-sell / up-sell collections
 */
class ComprehensiveCollectionService
{
    protected CollectionService $collectionService;

    public function __construct(CollectionService $collectionService)
    {
        $this->collectionService = $collectionService;
    }

    /**
     * Create a manual collection.
     *
     * @param array $data
     * @return Collection
     */
    public function createManualCollection(array $data): Collection
    {
        $data['type'] = 'static';
        $data['collection_type'] = $data['collection_type'] ?? CollectionType::STANDARD->value;
        
        return $this->collectionService->createCollection($data);
    }

    /**
     * Create a rule-based (dynamic) collection.
     *
     * @param array $data
     * @param array|null $rules
     * @return Collection
     */
    public function createRuleBasedCollection(array $data, ?array $rules = null): Collection
    {
        $data['type'] = 'dynamic';
        $data['collection_type'] = $data['collection_type'] ?? CollectionType::STANDARD->value;
        
        $collection = $this->collectionService->createCollection($data);

        if ($rules) {
            foreach ($rules as $ruleData) {
                $this->addRuleToCollection($collection, $ruleData);
            }
        }

        return $collection->fresh();
    }

    /**
     * Add rule to collection.
     *
     * @param Collection $collection
     * @param array $ruleData
     * @return CollectionRule
     */
    public function addRuleToCollection(Collection $collection, array $ruleData): CollectionRule
    {
        return CollectionRule::create([
            'collection_id' => $collection->id,
            'conditions' => $ruleData['conditions'] ?? [],
            'logic' => $ruleData['logic'] ?? 'and',
            'priority' => $ruleData['priority'] ?? 0,
            'is_active' => $ruleData['is_active'] ?? true,
            'product_limit' => $ruleData['product_limit'] ?? null,
            'sort_by' => $ruleData['sort_by'] ?? 'created_at',
            'sort_direction' => $ruleData['sort_direction'] ?? 'desc',
        ]);
    }

    /**
     * Get products for a collection (manual or rule-based).
     *
     * @param Collection $collection
     * @return EloquentCollection
     */
    public function getCollectionProducts(Collection $collection): EloquentCollection
    {
        if ($collection->isRuleBased()) {
            return $this->getRuleBasedProducts($collection);
        }

        return $this->collectionService->getProducts($collection);
    }

    /**
     * Get products for rule-based collection.
     *
     * @param Collection $collection
     * @return EloquentCollection
     */
    protected function getRuleBasedProducts(Collection $collection): EloquentCollection
    {
        $rules = $collection->rules()->active()->orderedByPriority()->get();

        if ($rules->isEmpty()) {
            return collect();
        }

        $productIds = collect();

        foreach ($rules as $rule) {
            $query = Product::query();

            // Apply conditions
            $this->applyRuleConditions($query, $rule->conditions, $rule->logic);

            // Apply sorting
            $this->applySorting($query, $rule->sort_by, $rule->sort_direction);

            // Apply limit
            if ($rule->product_limit) {
                $query->limit($rule->product_limit);
            }

            $ruleProductIds = $query->pluck('id');
            $productIds = $productIds->merge($ruleProductIds);
        }

        // Remove duplicates and get products
        $uniqueIds = $productIds->unique()->values()->all();

        if (empty($uniqueIds)) {
            return collect();
        }

        return Product::whereIn('id', $uniqueIds)
            ->with(['variants', 'productType', 'attributes'])
            ->get();
    }

    /**
     * Apply rule conditions to query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $conditions
     * @param string $logic
     * @return void
     */
    protected function applyRuleConditions($query, array $conditions, string $logic): void
    {
        if (empty($conditions)) {
            return;
        }

        if ($logic === 'or') {
            $query->where(function ($q) use ($conditions) {
                foreach ($conditions as $key => $value) {
                    $this->applyCondition($q, $key, $value, 'or');
                }
            });
        } else {
            foreach ($conditions as $key => $value) {
                $this->applyCondition($query, $key, $value, 'and');
            }
        }
    }

    /**
     * Apply a single condition.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $key
     * @param mixed $value
     * @param string $logic
     * @return void
     */
    protected function applyCondition($query, string $key, $value, string $logic): void
    {
        switch ($key) {
            case 'category_id':
                $query->whereHas('categories', function ($q) use ($value) {
                    $q->where('categories.id', $value);
                }, $logic === 'or' ? 'or' : null);
                break;

            case 'category_ids':
                $query->whereHas('categories', function ($q) use ($value) {
                    $q->whereIn('categories.id', (array) $value);
                }, $logic === 'or' ? 'or' : null);
                break;

            case 'brand_id':
                $query->where('brand_id', $value);
                break;

            case 'price_min':
                $query->whereHas('variants.prices', function ($q) use ($value) {
                    $q->where('price', '>=', $value);
                });
                break;

            case 'price_max':
                $query->whereHas('variants.prices', function ($q) use ($value) {
                    $q->where('price', '<=', $value);
                });
                break;

            case 'attributes':
                foreach ((array) $value as $attrHandle => $attrValue) {
                    $query->whereHas('attributeValues.attribute', function ($q) use ($attrHandle, $attrValue) {
                        $q->where('handle', $attrHandle);
                        if (is_array($attrValue)) {
                            $q->whereIn('product_attribute_values.text_value', $attrValue);
                        } else {
                            $q->where('product_attribute_values.text_value', $attrValue);
                        }
                    });
                }
                break;

            case 'tags':
                $query->whereHas('tags', function ($q) use ($value) {
                    $q->whereIn('name', (array) $value);
                });
                break;

            case 'stock_status':
                if ($value === 'in_stock') {
                    $query->whereHas('variants', function ($q) {
                        $q->where('stock', '>', 0);
                    });
                } elseif ($value === 'out_of_stock') {
                    $query->whereHas('variants', function ($q) {
                        $q->where('stock', '<=', 0);
                    });
                }
                break;

            case 'created_after':
                $query->where('created_at', '>=', Carbon::parse($value));
                break;

            case 'created_before':
                $query->where('created_at', '<=', Carbon::parse($value));
                break;

            case 'status':
                $query->where('status', $value);
                break;
        }
    }

    /**
     * Apply sorting to query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $sortBy
     * @param string $sortDirection
     * @return void
     */
    protected function applySorting($query, string $sortBy, string $sortDirection): void
    {
        switch ($sortBy) {
            case 'price':
                $query->join('product_variants', 'products.id', '=', 'product_variants.product_id')
                    ->join('prices', 'product_variants.id', '=', 'prices.priceable_id')
                    ->where('prices.priceable_type', \App\Models\ProductVariant::morphName())
                    ->orderBy('prices.price', $sortDirection)
                    ->select('products.*');
                break;

            case 'name':
                $query->orderBy('attribute_data->name', $sortDirection);
                break;

            case 'popularity':
                // Assuming you have a popularity field or can calculate it
                $query->orderBy('created_at', $sortDirection);
                break;

            case 'created_at':
            default:
                $query->orderBy('created_at', $sortDirection);
                break;
        }
    }

    /**
     * Refresh rule-based collection products.
     *
     * @param Collection $collection
     * @return int Number of products added
     */
    public function refreshRuleBasedCollection(Collection $collection): int
    {
        if (!$collection->isRuleBased()) {
            return 0;
        }

        $products = $this->getRuleBasedProducts($collection);
        $productIds = $products->pluck('id')->toArray();

        // Sync products (replace existing)
        $collection->products()->sync($productIds);

        return count($productIds);
    }

    /**
     * Create scheduled collection.
     *
     * @param array $data
     * @param Carbon|string|null $publishAt
     * @param Carbon|string|null $unpublishAt
     * @return Collection
     */
    public function createScheduledCollection(
        array $data,
        $publishAt = null,
        $unpublishAt = null
    ): Collection {
        $collection = $this->collectionService->createCollection($data);

        if ($publishAt) {
            $collection->schedulePublish($publishAt);
        }

        if ($unpublishAt) {
            $collection->scheduleUnpublish($unpublishAt);
        }

        return $collection->fresh();
    }

    /**
     * Create cross-sell collection.
     *
     * @param array $data
     * @return Collection
     */
    public function createCrossSellCollection(array $data): Collection
    {
        $data['collection_type'] = CollectionType::CROSS_SELL->value;
        return $this->collectionService->createCollection($data);
    }

    /**
     * Create up-sell collection.
     *
     * @param array $data
     * @return Collection
     */
    public function createUpSellCollection(array $data): Collection
    {
        $data['collection_type'] = CollectionType::UP_SELL->value;
        return $this->collectionService->createCollection($data);
    }

    /**
     * Get cross-sell products for a product.
     *
     * @param Product $product
     * @param int $limit
     * @return EloquentCollection
     */
    public function getCrossSellProducts(Product $product, int $limit = 10): EloquentCollection
    {
        // Get from collections
        $collections = Collection::crossSell()
            ->whereHas('products', function ($q) use ($product) {
                $q->where('products.id', $product->id);
            })
            ->get();

        $productIds = collect();
        foreach ($collections as $collection) {
            $products = $this->getCollectionProducts($collection);
            $productIds = $productIds->merge($products->pluck('id'));
        }

        // Also get from product associations
        $service = app(ProductRelationService::class);
        $associated = $service->getCrossSell($product, $limit);

        $productIds = $productIds->merge($associated->pluck('id'))->unique();

        return Product::whereIn('id', $productIds->take($limit)->all())
            ->with(['variants', 'productType', 'attributes'])
            ->get();
    }

    /**
     * Get up-sell products for a product.
     *
     * @param Product $product
     * @param int $limit
     * @return EloquentCollection
     */
    public function getUpSellProducts(Product $product, int $limit = 10): EloquentCollection
    {
        // Get from collections
        $collections = Collection::upSell()
            ->whereHas('products', function ($q) use ($product) {
                $q->where('products.id', $product->id);
            })
            ->get();

        $productIds = collect();
        foreach ($collections as $collection) {
            $products = $this->getCollectionProducts($collection);
            $productIds = $productIds->merge($products->pluck('id'));
        }

        // Also get from product associations
        $service = app(ProductRelationService::class);
        $associated = $service->getUpSell($product, $limit);

        $productIds = $productIds->merge($associated->pluck('id'))->unique();

        return Product::whereIn('id', $productIds->take($limit)->all())
            ->with(['variants', 'productType', 'attributes'])
            ->get();
    }

    /**
     * Process scheduled collections (publish/unpublish).
     *
     * @return array
     */
    public function processScheduledCollections(): array
    {
        $published = 0;
        $unpublished = 0;

        // Publish scheduled collections
        $toPublish = Collection::scheduledForPublish()
            ->where('status', '!=', 'published')
            ->get();

        foreach ($toPublish as $collection) {
            // Update collection status or visibility
            $collection->update(['status' => 'published']);
            $published++;

            // Auto-publish products if enabled
            if ($collection->auto_publish_products) {
                $collection->products()->update(['status' => 'published']);
            }
        }

        // Unpublish scheduled collections
        $toUnpublish = Collection::scheduledForUnpublish()
            ->where('status', '!=', 'unpublished')
            ->get();

        foreach ($toUnpublish as $collection) {
            $collection->update(['status' => 'unpublished']);
            $unpublished++;
        }

        return [
            'published' => $published,
            'unpublished' => $unpublished,
        ];
    }
}


<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\Product;
use App\Enums\CollectionType;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class CollectionService
{
    /**
     * Create a new collection
     */
    public function createCollection(array $data): Collection
    {
        $collection = Collection::create([
            'collection_group_id' => $data['collection_group_id'],
            'sort' => $data['sort'] ?? 0,
            'collection_type' => $data['collection_type'] ?? CollectionType::STANDARD->value,
        ]);

        // Set collection attributes if provided
        if (isset($data['attributes'])) {
            $this->setCollectionAttributes($collection, $data['attributes']);
        }

        return $collection->fresh();
    }

    /**
     * Add products to collection
     */
    public function addProducts(Collection $collection, array $productIds): Collection
    {
        $collection->products()->syncWithoutDetaching($productIds);
        return $collection->fresh();
    }

    /**
     * Remove products from collection
     */
    public function removeProducts(Collection $collection, array $productIds): Collection
    {
        $collection->products()->detach($productIds);
        return $collection->fresh();
    }

    /**
     * Get products in collection
     */
    public function getProducts(Collection $collection): EloquentCollection
    {
        return $collection->products()
            ->with(['variants', 'productType', 'attributes'])
            ->get();
    }

    /**
     * Search collections by criteria
     */
    public function searchCollections(array $filters): EloquentCollection
    {
        $query = Collection::query()->with(['products', 'group']);

        // Filter by collection group
        if (isset($filters['collection_group_id'])) {
            $query->where('collection_group_id', $filters['collection_group_id']);
        }

        // Filter by collection type
        if (isset($filters['collection_type'])) {
            $query->ofType($filters['collection_type']);
        }

        // Filter by attributes
        if (isset($filters['attributes'])) {
            foreach ($filters['attributes'] as $attributeHandle => $value) {
                $query->whereHas('attributes', function ($q) use ($attributeHandle, $value) {
                    $q->where('handle', $attributeHandle)
                      ->where('pivot.value', 'like', '%' . $value . '%');
                });
            }
        }

        return $query->orderBy('sort')->get();
    }

    /**
     * Get collections by type
     */
    public function getCollectionsByType(CollectionType $type): EloquentCollection
    {
        return Collection::query()
            ->ofType($type->value)
            ->with(['products', 'group'])
            ->orderBy('sort')
            ->get();
    }

    /**
     * Get cross-sell collections
     */
    public function getCrossSellCollections(): EloquentCollection
    {
        return $this->getCollectionsByType(CollectionType::CROSS_SELL);
    }

    /**
     * Get up-sell collections
     */
    public function getUpSellCollections(): EloquentCollection
    {
        return $this->getCollectionsByType(CollectionType::UP_SELL);
    }

    /**
     * Get related collections
     */
    public function getRelatedCollections(): EloquentCollection
    {
        return $this->getCollectionsByType(CollectionType::RELATED);
    }

    /**
     * Get bundle collections
     */
    public function getBundleCollections(): EloquentCollection
    {
        return $this->getCollectionsByType(CollectionType::BUNDLE);
    }

    /**
     * Set collection attributes
     */
    protected function setCollectionAttributes(Collection $collection, array $attributes): void
    {
        foreach ($attributes as $handle => $value) {
            $attribute = \App\Models\Attribute::where('handle', $handle)->first();
            if ($attribute) {
                $collection->attributes()->syncWithoutDetaching([
                    $attribute->id => ['value' => $value]
                ]);
            }
        }
    }

    /**
     * Create child collection
     */
    public function createChildCollection(Collection $parent, array $data): Collection
    {
        $collection = Collection::create([
            'collection_group_id' => $parent->collection_group_id,
            'parent_id' => $parent->id,
            'sort' => $data['sort'] ?? 0,
            'collection_type' => $data['collection_type'] ?? $parent->collection_type?->value ?? CollectionType::STANDARD->value,
        ]);

        // Set collection attributes if provided
        if (isset($data['attributes'])) {
            $this->setCollectionAttributes($collection, $data['attributes']);
        }

        return $collection->fresh();
    }

    /**
     * Move collection to different parent
     */
    public function moveCollection(Collection $collection, ?Collection $newParent = null): Collection
    {
        $collection->update([
            'parent_id' => $newParent?->id,
        ]);

        return $collection->fresh();
    }

    /**
     * Get collection tree structure
     */
    public function getCollectionTree(int $collectionGroupId): EloquentCollection
    {
        return Collection::where('collection_group_id', $collectionGroupId)
            ->whereNull('parent_id')
            ->with(['children' => function ($query) {
                $query->orderBy('sort');
            }])
            ->orderBy('sort')
            ->get();
    }

    /**
     * Get all products in collection and its children
     */
    public function getAllProductsInTree(Collection $collection): EloquentCollection
    {
        $productIds = collect();
        
        // Get products from this collection
        $productIds = $productIds->merge($collection->products->pluck('id'));
        
        // Get products from child collections recursively
        foreach ($collection->children as $child) {
            $childProducts = $this->getAllProductsInTree($child);
            $productIds = $productIds->merge($childProducts->pluck('id'));
        }

        return \App\Models\Product::whereIn('id', $productIds->unique())
            ->with(['variants', 'productType', 'attributes'])
            ->get();
    }

    /**
     * Get collection breadcrumb path
     */
    public function getBreadcrumbPath(Collection $collection): array
    {
        $path = [];
        $current = $collection;

        while ($current) {
            array_unshift($path, [
                'id' => $current->id,
                'name' => $current->name ?? "Collection {$current->id}",
                'handle' => $current->handle ?? null,
            ]);
            $current = $current->parent;
        }

        return $path;
    }

    /**
     * Duplicate collection with all its products
     */
    public function duplicateCollection(Collection $collection, array $newData = []): Collection
    {
        $newCollection = Collection::create([
            'collection_group_id' => $newData['collection_group_id'] ?? $collection->collection_group_id,
            'parent_id' => $newData['parent_id'] ?? $collection->parent_id,
            'sort' => $newData['sort'] ?? $collection->sort + 1,
        ]);

        // Copy products
        $productIds = $collection->products->pluck('id')->toArray();
        if (!empty($productIds)) {
            $newCollection->products()->sync($productIds);
        }

        // Copy attributes if they exist
        if ($collection->attributes) {
            foreach ($collection->attributes as $attribute) {
                $newCollection->attributes()->syncWithoutDetaching([
                    $attribute->id => ['value' => $attribute->pivot->value ?? '']
                ]);
            }
        }

        return $newCollection->fresh();
    }

    /**
     * Get collection statistics
     */
    public function getCollectionStats(Collection $collection): array
    {
        $products = $this->getProducts($collection);
        
        return [
            'total_products' => $products->count(),
            'published_products' => $products->where('status', 'published')->count(),
            'draft_products' => $products->where('status', 'draft')->count(),
            'total_variants' => $products->sum(fn($product) => $product->variants->count()),
            'total_stock' => $products->sum(function ($product) {
                return $product->variants->sum('stock');
            }),
            'child_collections' => $collection->children->count(),
        ];
    }
}
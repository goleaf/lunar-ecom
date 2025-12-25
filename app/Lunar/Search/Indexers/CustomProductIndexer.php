<?php

namespace App\Lunar\Search\Indexers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lunar\Models\Product;
use Lunar\Search\ScoutIndexer;

/**
 * Example custom product indexer for extending Lunar search.
 * 
 * This is a scaffolding example. For production use, refer to:
 * https://docs.lunarphp.com/1.x/extending/search
 * 
 * Custom indexers allow you to control:
 * - Searchable fields: What fields are indexed for searching
 * - Sortable fields: What fields can be used for sorting
 * - Filterable fields: What fields can be used for filtering
 */
class CustomProductIndexer extends ScoutIndexer
{
    /**
     * Return the index name for this model.
     * 
     * @param Model $model
     * @return string
     */
    public function searchableAs(Model $model): string
    {
        // You can customize the index name if needed
        // Default would be based on the model's table name
        return 'products';
    }

    /**
     * Return whether the model should be searchable.
     * 
     * @param Model $model
     * @return bool
     */
    public function shouldBeSearchable(Model $model): bool
    {
        // Example: Only index published products
        if ($model instanceof Product) {
            return $model->status === 'published';
        }

        return true;
    }

    /**
     * Allow you to tap into eager loading.
     * 
     * This helps optimize queries when indexing multiple models.
     * 
     * @param Builder $query
     * @return Builder
     */
    public function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with([
            'thumbnail',
            'variants',
            'productType',
            'brand',
            'media', // Eager load media for indexing
            'collections', // Eager load collections
            'tags', // Eager load tags
        ]);
    }

    /**
     * Get the ID used for indexing.
     * 
     * @param Model $model
     * @return mixed
     */
    public function getScoutKey(Model $model): mixed
    {
        return $model->getKey();
    }

    /**
     * Get the column used for the ID.
     * 
     * @param Model $model
     * @return mixed
     */
    public function getScoutKeyName(Model $model): mixed
    {
        return $model->getKeyName();
    }

    /**
     * Return an array of sortable fields.
     * 
     * These fields can be used for sorting search results.
     * 
     * @return array
     */
    public function getSortableFields(): array
    {
        return [
            'created_at',
            'updated_at',
            'price', // Example: if you index price information
        ];
    }

    /**
     * Return an array of filterable fields.
     * 
     * These fields can be used for filtering search results.
     * 
     * @return array
     */
    public function getFilterableFields(): array
    {
        return [
            '__soft_deleted', // Lunar's soft delete filter
            'status', // Filter by product status
            'product_type_id', // Filter by product type
            'brand_id', // Filter by brand
            // Add more filterable fields as needed
        ];
    }

    /**
     * Return an array representing what should be sent to the search service.
     * 
     * This is where you define the actual data that gets indexed.
     * 
     * @param Model $model
     * @param string $engine The search engine being used (e.g., 'algolia', 'meilisearch', 'database')
     * @return array
     */
    public function toSearchableArray(Model $model, string $engine): array
    {
        if (!$model instanceof Product) {
            return [];
        }

        // Start with the base searchable attributes (handles searchable attributes from hub)
        $array = $this->mapSearchableAttributes($model);

        // Add custom fields for searching
        $array = array_merge($array, [
            'id' => $model->id,
            'status' => $model->status,
            'product_type_id' => $model->product_type_id,
            'created_at' => $model->created_at?->timestamp,
            'updated_at' => $model->updated_at?->timestamp,
        ]);

        // Add brand information if available
        if ($model->brand) {
            $array['brand_id'] = $model->brand->id;
            $array['brand_name'] = $model->brand->name;
        }

        // Add variant SKUs for searching
        if ($model->relationLoaded('variants')) {
            $array['skus'] = $model->variants->pluck('sku')->filter()->values()->toArray();
        }

        // Add collection IDs for filtering
        if ($model->relationLoaded('collections')) {
            $array['collection_ids'] = $model->collections->pluck('id')->toArray();
        }

        // Add tag values for filtering/searching
        if ($model->relationLoaded('tags')) {
            $array['tags'] = $model->tags->pluck('value')->toArray();
        }

        return $array;
    }
}



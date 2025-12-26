<?php

namespace App\Lunar\Search\Indexers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use Lunar\Search\ScoutIndexer;
use Lunar\Facades\Pricing;

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
            return $model->isPublished();
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
            'variants.prices',
            'variants.stock',
            'productType',
            'brand',
            'media',
            'collections',
            'categories',
            'attributeValues.attribute',
            'tags',
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
            'price_min', // Sort by minimum price
            'price_max', // Sort by maximum price
            'popularity_score', // Sort by popularity
            'view_count', // Sort by views
            'order_count', // Sort by orders
            'average_rating', // Sort by rating
            'total_reviews', // Sort by review count
        ];
    }

    /**
     * Return an array of filterable fields.
     * 
     * These fields can be used for filtering search results (faceted search).
     * 
     * @return array
     */
    public function getFilterableFields(): array
    {
        return [
            '__soft_deleted', // Lunar's soft delete filter
            'status', // Filter by product status
            'product_type_id', // Filter by product type
            'brand_id', // Filter by brand (facet)
            'category_ids', // Filter by categories (facet)
            'price_min', // Filter by minimum price (range facet)
            'price_max', // Filter by maximum price (range facet)
            'in_stock', // Filter by stock availability (facet)
            'average_rating', // Filter by rating (range facet)
        ];
    }

    /**
     * Return an array representing what should be sent to the search service.
     * 
     * This is where you define the actual data that gets indexed.
     * Includes: name, description, SKU, brand, category names, attribute values.
     * 
     * @param Model $model
     * @return array
     */
    public function toSearchableArray(Model $model): array
    {
        if (!$model instanceof Product) {
            return [];
        }

        // Start with the base searchable attributes (handles searchable attributes from hub)
        $array = $this->mapSearchableAttributes($model);

        // Get product name and description
        $name = $model->translateAttribute('name') ?? '';
        $description = $model->translateAttribute('description') ?? '';

        // Get SKU from custom field or variants
        $sku = $model->sku;
        if (!$sku && $model->relationLoaded('variants')) {
            $sku = $model->variants->first()?->sku;
        }

        // Get brand name
        $brandName = null;
        if ($model->brand) {
            $brandName = $model->brand->name;
        }

        // Get category names
        $categoryNames = [];
        if ($model->relationLoaded('categories')) {
            $categoryNames = $model->categories->map(function ($category) {
                return $category->getName();
            })->filter()->values()->toArray();
        } elseif ($model->relationLoaded('collections')) {
            // Fallback to collections if categories not loaded
            $categoryNames = $model->collections->map(function ($collection) {
                return $collection->translateAttribute('name');
            })->filter()->values()->toArray();
        }

        // Get attribute values
        $attributeValues = [];
        if ($model->relationLoaded('attributeValues')) {
            $attributeValues = $model->attributeValues->map(function ($attributeValue) {
                return $attributeValue->getDisplayValue();
            })->filter()->values()->toArray();
        }

        // Calculate price range
        $priceMin = null;
        $priceMax = null;
        $inStock = false;
        if ($model->relationLoaded('variants')) {
            $prices = [];
            foreach ($model->variants as $variant) {
                // Use Pricing facade to get the matched price
                $pricing = Pricing::for($variant)->get();
                if ($pricing->matched && $pricing->matched->price) {
                    $prices[] = $pricing->matched->price->value;
                }
            }
            if (!empty($prices)) {
                $priceMin = min($prices);
                $priceMax = max($prices);
            }
            
            // Check if any variant is in stock
            $inStock = $model->variants->contains(function ($variant) {
                return $variant->stock > 0 || $variant->backorder;
            });
        }

        // Get product URL slug
        $slug = null;
        if ($model->relationLoaded('urls')) {
            $slug = $model->urls->first()?->slug;
        }

        // Get image URL
        $imageUrl = null;
        if ($model->relationLoaded('media')) {
            $imageUrl = $model->getFirstMediaUrl('images', 'thumb');
        }

        // Get popularity score (from database or calculate)
        $popularityScore = $model->popularity_score ?? 0;
        $viewCount = $model->view_count ?? 0;
        $orderCount = $model->order_count ?? 0;
        
        // Build searchable array
        $array = array_merge($array, [
            'id' => $model->id,
            'status' => $model->status,
            'product_type_id' => $model->product_type_id,
            'created_at' => $model->created_at?->timestamp,
            'updated_at' => $model->updated_at?->timestamp,
            
            // Searchable fields (high priority for relevance)
            'name' => $name,
            'description' => strip_tags($description), // Remove HTML tags
            'sku' => $sku,
            'brand_name' => $brandName,
            'category_names' => $categoryNames,
            'attribute_values' => $attributeValues,
            
            // Filterable fields (for faceted search)
            'brand_id' => $model->brand_id,
            'category_ids' => $model->relationLoaded('categories') 
                ? $model->categories->pluck('id')->toArray()
                : ($model->relationLoaded('collections') 
                    ? $model->collections->pluck('id')->toArray() 
                    : []),
            'price_min' => $priceMin,
            'price_max' => $priceMax,
            'in_stock' => $inStock,
            
            // Ranking fields (for sorting)
            'popularity_score' => $popularityScore,
            'view_count' => $viewCount,
            'order_count' => $orderCount,
            'average_rating' => $model->average_rating ?? 0,
            'total_reviews' => $model->total_reviews ?? 0,
            
            // Additional searchable data
            'skus' => $model->relationLoaded('variants') 
                ? $model->variants->pluck('sku')->filter()->values()->toArray() 
                : [],
            
            // Display fields (for frontend)
            'slug' => $slug,
            'image_url' => $imageUrl,
        ]);

        return $array;
    }

    /**
     * Get searchable attributes from model.
     * 
     * @param Model $model
     * @return array
     */
    protected function mapSearchableAttributes(Model $model): array
    {
        if (!$model instanceof Product) {
            return [];
        }

        $attributes = [];
        
        // Get all searchable attributes from attribute_data
        foreach ($model->attribute_data ?? [] as $handle => $fieldType) {
            $attribute = \Lunar\Models\Attribute::where('handle', $handle)
                ->where('searchable', true)
                ->first();
            
            if ($attribute) {
                $value = $model->translateAttribute($handle);
                if ($value) {
                    $attributes[$handle] = is_string($value) ? strip_tags($value) : $value;
                }
            }
        }

        return $attributes;
    }
}

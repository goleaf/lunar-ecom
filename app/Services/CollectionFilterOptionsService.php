<?php

namespace App\Services;

use Illuminate\Http\Request;
use Lunar\Models\Collection;

class CollectionFilterOptionsService
{
    public function getFilterOptions(Collection $collection, Request $request): array
    {
        $baseQuery = $collection->products()->published();

        // Keep a request clone to preserve semantics of the legacy controller.
        $tempRequest = clone $request;

        return [
            'price_range' => $this->getPriceRange($baseQuery),
            'brands' => $this->getAvailableBrands($baseQuery, $tempRequest),
            'categories' => $this->getAvailableCategories($baseQuery, $tempRequest),
            'attributes' => $this->getAvailableAttributes($baseQuery, $tempRequest),
            'availability' => $this->getAvailabilityCounts($baseQuery, $tempRequest),
        ];
    }

    protected function getPriceRange($query): array
    {
        $products = $query->with('variants.prices')->get();

        $prices = [];
        foreach ($products as $product) {
            foreach ($product->variants as $variant) {
                foreach ($variant->prices as $price) {
                    $prices[] = $price->price->decimal;
                }
            }
        }

        if (empty($prices)) {
            return ['min' => 0, 'max' => 0];
        }

        return [
            'min' => min($prices),
            'max' => max($prices),
        ];
    }

    protected function getAvailableBrands($query, Request $request)
    {
        $tempQuery = clone $query;

        // Apply all filters except brand filter (legacy behaviour keeps this as a no-op).
        if ($request->has('brands')) {
            // Intentionally left blank.
        }

        return $tempQuery->with('brand')
            ->get()
            ->pluck('brand')
            ->filter()
            ->unique('id')
            ->map(function ($brand) use ($query) {
                $count = (clone $query)->where('brand_id', $brand->id)->count();

                return [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'count' => $count,
                ];
            })
            ->sortBy('name')
            ->values();
    }

    protected function getAvailableCategories($query, Request $request)
    {
        $tempQuery = clone $query;

        return $tempQuery->with('categories')
            ->get()
            ->pluck('categories')
            ->flatten()
            ->unique('id')
            ->map(function ($category) use ($query) {
                $count = (clone $query)->whereHas('categories', function ($q) use ($category) {
                    $q->where('categories.id', $category->id);
                })->count();

                return [
                    'id' => $category->id,
                    'name' => $category->translateAttribute('name'),
                    'count' => $count,
                ];
            })
            ->sortBy('name')
            ->values();
    }

    protected function getAvailableAttributes($query, Request $request): array
    {
        $tempQuery = clone $query;

        $products = $tempQuery->with('attributeValues.attribute', 'attributeValues.value')->get();

        $attributes = [];

        foreach ($products as $product) {
            foreach ($product->attributeValues as $attributeValue) {
                $attribute = $attributeValue->attribute;
                $value = $attributeValue->value;

                if (! $attribute || ! $value) {
                    continue;
                }

                $handle = $attribute->handle;

                if (! isset($attributes[$handle])) {
                    $attributes[$handle] = [
                        'handle' => $handle,
                        'name' => $attribute->translateAttribute('name'),
                        'type' => $attribute->type,
                        'values' => [],
                    ];
                }

                $valueId = $value->id;
                if (! isset($attributes[$handle]['values'][$valueId])) {
                    $count = (clone $query)->whereHas('attributeValues', function ($q) use ($attribute, $value) {
                        $q->where('attribute_id', $attribute->id)
                            ->where('attribute_value_id', $value->id);
                    })->count();

                    $attributes[$handle]['values'][$valueId] = [
                        'id' => $valueId,
                        'name' => $value->translateAttribute('name'),
                        'count' => $count,
                    ];
                }
            }
        }

        return array_values($attributes);
    }

    protected function getAvailabilityCounts($query, Request $request): array
    {
        $tempQuery = clone $query;

        return [
            'in_stock' => (clone $tempQuery)->whereHas('variants', function ($q) {
                $q->where('stock', '>', 0);
            })->count(),
            'out_of_stock' => (clone $tempQuery)->whereHas('variants', function ($q) {
                $q->where('stock', '<=', 0);
            })->count(),
            'low_stock' => (clone $tempQuery)->whereHas('variants', function ($q) {
                $q->where('stock', '>', 0)
                    ->where('stock', '<=', 10);
            })->count(),
        ];
    }
}


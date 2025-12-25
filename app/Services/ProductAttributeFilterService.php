<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Attribute;
use App\Models\ProductAttributeValue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Service for filtering products by attribute values.
 */
class ProductAttributeFilterService
{
    /**
     * Apply attribute filters to product query.
     *
     * @param  Builder  $query
     * @param  array  $filters  Array of filters: ['attribute_handle' => value(s)]
     * @param  string  $logic  'and' or 'or'
     * @return Builder
     */
    public function applyFilters(Builder $query, array $filters, string $logic = 'and'): Builder
    {
        if (empty($filters)) {
            return $query;
        }

        if ($logic === 'or') {
            return $this->applyOrFilters($query, $filters);
        }

        return $this->applyAndFilters($query, $filters);
    }

    /**
     * Apply filters with AND logic (product must match ALL filters).
     *
     * @param  Builder  $query
     * @param  array  $filters
     * @return Builder
     */
    protected function applyAndFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $handle => $value) {
            $attribute = Attribute::where('handle', $handle)
                ->where('attribute_type', 'product')
                ->first();

            if (!$attribute) {
                continue;
            }

            $query->whereHas('attributeValues', function ($q) use ($attribute, $value) {
                $this->applyAttributeFilter($q, $attribute, $value);
            });
        }

        return $query;
    }

    /**
     * Apply filters with OR logic (product must match ANY filter).
     *
     * @param  Builder  $query
     * @param  array  $filters
     * @return Builder
     */
    protected function applyOrFilters(Builder $query, array $filters): Builder
    {
        $query->where(function ($q) use ($filters) {
            foreach ($filters as $handle => $value) {
                $attribute = Attribute::where('handle', $handle)
                    ->where('attribute_type', 'product')
                    ->first();

                if (!$attribute) {
                    continue;
                }

                $q->orWhereHas('attributeValues', function ($subQ) use ($attribute, $value) {
                    $this->applyAttributeFilter($subQ, $attribute, $value);
                });
            }
        });

        return $query;
    }

    /**
     * Apply filter for a specific attribute.
     *
     * @param  Builder  $query
     * @param  Attribute  $attribute
     * @param  mixed  $value
     * @return void
     */
    protected function applyAttributeFilter(Builder $query, Attribute $attribute, $value): void
    {
        $query->where('attribute_id', $attribute->id);

        if ($attribute->isNumeric()) {
            // Handle range filters
            if (is_array($value) && isset($value['min']) && isset($value['max'])) {
                $query->whereBetween('numeric_value', [
                    (float) $value['min'],
                    (float) $value['max']
                ]);
            } elseif (is_array($value) && isset($value['min'])) {
                $query->where('numeric_value', '>=', (float) $value['min']);
            } elseif (is_array($value) && isset($value['max'])) {
                $query->where('numeric_value', '<=', (float) $value['max']);
            } else {
                $query->where('numeric_value', (float) $value);
            }
        } elseif ($attribute->isSelect() && is_array($value)) {
            // Multiple values for select/multiselect
            $query->whereIn('text_value', $value);
        } elseif ($attribute->isBoolean()) {
            $query->where('text_value', filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0');
        } else {
            // Text or single value
            $query->where('text_value', $value);
        }
    }

    /**
     * Get product count for each filter option.
     *
     * @param  Attribute  $attribute
     * @param  mixed  $value
     * @param  Builder|null  $baseQuery
     * @return int
     */
    public function getProductCountForFilter(Attribute $attribute, $value, ?Builder $baseQuery = null): int
    {
        $cacheKey = $this->getFilterCacheKey($attribute, $value, $baseQuery);

        return Cache::remember($cacheKey, 1800, function () use ($attribute, $value, $baseQuery) {
            $query = ProductAttributeValue::where('attribute_id', $attribute->id);

            // Apply value filter
            if ($attribute->isNumeric() && is_array($value)) {
                if (isset($value['min']) && isset($value['max'])) {
                    $query->whereBetween('numeric_value', [(float) $value['min'], (float) $value['max']]);
                } elseif (isset($value['min'])) {
                    $query->where('numeric_value', '>=', (float) $value['min']);
                } elseif (isset($value['max'])) {
                    $query->where('numeric_value', '<=', (float) $value['max']);
                }
            } elseif ($attribute->isNumeric()) {
                $query->where('numeric_value', (float) $value);
            } else {
                $query->where('text_value', $value);
            }

            // Apply base query if provided
            if ($baseQuery) {
                $productIds = (clone $baseQuery)->pluck('id');
                $query->whereIn('product_id', $productIds);
            }

            return $query->distinct('product_id')->count('product_id');
        });
    }

    /**
     * Generate cache key for filter.
     *
     * @param  Attribute  $attribute
     * @param  mixed  $value
     * @param  Builder|null  $baseQuery
     * @return string
     */
    protected function getFilterCacheKey(Attribute $attribute, $value, ?Builder $baseQuery = null): string
    {
        $valueHash = md5(json_encode($value));
        $queryHash = $baseQuery ? md5($baseQuery->toSql() . json_encode($baseQuery->getBindings())) : 'all';
        
        return "filter.count.{$attribute->id}.{$valueHash}.{$queryHash}";
    }
}


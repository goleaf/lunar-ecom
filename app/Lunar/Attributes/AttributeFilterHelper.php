<?php

namespace App\Lunar\Attributes;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Services\AttributeService;
use Illuminate\Support\Collection;

/**
 * Helper class for attribute filtering operations.
 * 
 * Provides convenience methods for working with filterable attributes,
 * attribute groups, and filter options.
 */
class AttributeFilterHelper
{
    /**
     * Get filterable attributes grouped by attribute group.
     * 
     * @param int|null $productTypeId
     * @param int|null $categoryId
     * @return Collection
     */
    public static function getGroupedFilterableAttributes(?int $productTypeId = null, ?int $categoryId = null): Collection
    {
        $attributeService = app(AttributeService::class);
        $attributes = $attributeService->getFilterableAttributes($productTypeId, $categoryId);
        
        // Group by attribute group
        return $attributes->groupBy(function ($attribute) {
            return $attribute->attributeGroup?->name ?? 'Other';
        })->map(function ($groupAttributes, $groupName) use ($attributeService) {
            // Get filter options for each attribute
            $options = $attributeService->getFilterOptions($groupAttributes);
            
            return [
                'name' => $groupName,
                'handle' => $groupAttributes->first()?->attributeGroup?->handle ?? 'other',
                'attributes' => $options,
            ];
        });
    }

    /**
     * Get filter options for a specific attribute.
     * 
     * @param Attribute $attribute
     * @param \Illuminate\Database\Eloquent\Builder|null $productQuery
     * @return array
     */
    public static function getFilterOptions(Attribute $attribute, $productQuery = null): array
    {
        $attributeService = app(AttributeService::class);
        $options = $attributeService->getFilterOptions(collect([$attribute]), $productQuery);
        
        return $options->first()['options'] ?? [];
    }

    /**
     * Get active filters from request.
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public static function getActiveFilters($request): array
    {
        $filters = [];
        
        // Get all filter parameters (excluding pagination, sort, etc.)
        $exclude = ['page', 'per_page', 'sort', 'category_id', 'product_type_id', 'brand_id', 'min_price', 'max_price'];
        
        foreach ($request->all() as $key => $value) {
            if (in_array($key, $exclude) || empty($value)) {
                continue;
            }
            
            // Check if it's an attribute handle
            $attribute = Attribute::where('handle', $key)
                ->where('attribute_type', 'product')
                ->where('filterable', true)
                ->first();
            
            if ($attribute) {
                $filters[$key] = $value;
            }
        }
        
        return $filters;
    }

    /**
     * Build filter URL with new filter value.
     * 
     * @param string $baseUrl
     * @param string $attributeHandle
     * @param mixed $value
     * @param array $currentFilters
     * @return string
     */
    public static function buildFilterUrl(string $baseUrl, string $attributeHandle, $value, array $currentFilters = []): string
    {
        $filters = $currentFilters;
        $filters[$attributeHandle] = $value;
        
        $queryString = http_build_query($filters);
        
        return $baseUrl . ($queryString ? '?' . $queryString : '');
    }

    /**
     * Remove filter from URL.
     * 
     * @param string $baseUrl
     * @param string $attributeHandle
     * @param array $currentFilters
     * @return string
     */
    public static function removeFilterUrl(string $baseUrl, string $attributeHandle, array $currentFilters = []): string
    {
        $filters = $currentFilters;
        unset($filters[$attributeHandle]);
        
        $queryString = http_build_query($filters);
        
        return $baseUrl . ($queryString ? '?' . $queryString : '');
    }

    /**
     * Check if a filter is active.
     * 
     * @param string $attributeHandle
     * @param mixed $value
     * @param array $activeFilters
     * @return bool
     */
    public static function isFilterActive(string $attributeHandle, $value, array $activeFilters): bool
    {
        if (!isset($activeFilters[$attributeHandle])) {
            return false;
        }
        
        $activeValue = $activeFilters[$attributeHandle];
        
        // Handle array values (multiple selections)
        if (is_array($activeValue)) {
            return in_array($value, $activeValue);
        }
        
        return $activeValue == $value;
    }

    /**
     * Get filter display name.
     * 
     * @param mixed $attribute
     * @return string
     */
    public static function getFilterDisplayName($attribute): string
    {
        if (is_array($attribute)) {
            $name = $attribute['name'] ?? null;
            if (is_array($name)) {
                return $name[app()->getLocale()] ?? $name[array_key_first($name)] ?? ($attribute['handle'] ?? 'Attribute');
            }

            return $name ?? ($attribute['handle'] ?? 'Attribute');
        }

        $name = $attribute->name;
        
        if (is_array($name)) {
            return $name[app()->getLocale()] ?? $name[array_key_first($name)] ?? $attribute->handle;
        }
        
        return $name ?? $attribute->handle;
    }

    /**
     * Format filter value for display.
     * 
     * @param mixed $attribute
     * @param mixed $value
     * @return string
     */
    public static function formatFilterValue($attribute, $value): string
    {
        if (is_array($attribute)) {
            $isNumeric = $attribute['is_numeric'] ?? false;
            $isBoolean = $attribute['is_boolean'] ?? false;
            $isColor = $attribute['is_color'] ?? false;
            $unit = $attribute['unit'] ?? null;

            if ($isNumeric) {
                return $value . ($unit ? ' ' . $unit : '');
            }

            if ($isBoolean) {
                return $value ? 'Yes' : 'No';
            }

            if ($isColor) {
                return ucfirst($value);
            }

            return (string) $value;
        }

        if ($attribute->isNumeric()) {
            return $value . ($attribute->unit ? ' ' . $attribute->unit : '');
        }
        
        if ($attribute->isBoolean()) {
            return $value ? 'Yes' : 'No';
        }
        
        if ($attribute->isColor()) {
            return ucfirst($value);
        }
        
        return (string) $value;
    }
}

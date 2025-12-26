<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductAttributeValue;
use App\Models\Attribute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Service for managing product attributes and values.
 */
class AttributeService
{
    /**
     * Assign attributes to a product.
     *
     * @param  Product  $product
     * @param  array  $attributes  Array of ['attribute_id' => value] or ['handle' => value]
     * @return void
     */
    public function assignAttributesToProduct(Product $product, array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            // Resolve attribute by ID or handle
            $attribute = is_numeric($key)
                ? Attribute::find($key)
                : Attribute::where('handle', $key)
                    ->where('attribute_type', 'product')
                    ->first();

            if (!$attribute) {
                continue;
            }

            // Normalize value based on attribute type
            $normalizedValue = $this->normalizeValue($value, $attribute);

            // Create or update attribute value
            ProductAttributeValue::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'attribute_id' => $attribute->id,
                ],
                [
                    'value' => $normalizedValue,
                ]
            );
        }

        // Clear cache
        $this->clearProductCache($product);
    }

    /**
     * Get all attributes for a product.
     *
     * @param  Product  $product
     * @return Collection
     */
    public function getProductAttributes(Product $product): Collection
    {
        return Cache::remember("product.{$product->id}.attributes", 3600, function () use ($product) {
            return $product->attributeValues()
                ->with('attribute')
                ->get()
                ->mapWithKeys(function ($attributeValue) {
                    return [
                        $attributeValue->attribute->handle => [
                            'attribute' => $attributeValue->attribute,
                            'value' => $attributeValue->value,
                            'display_value' => $attributeValue->getDisplayValue(),
                            'numeric_value' => $attributeValue->numeric_value,
                            'text_value' => $attributeValue->text_value,
                        ]
                    ];
                });
        });
    }

    /**
     * Get filterable attributes for a product type or category.
     *
     * @param  int|null  $productTypeId
     * @param  int|null  $categoryId
     * @return Collection
     */
    public function getFilterableAttributes(?int $productTypeId = null, ?int $categoryId = null): Collection
    {
        $cacheKey = "filterable_attributes.{$productTypeId}.{$categoryId}";

        return Cache::remember($cacheKey, 3600, function () use ($productTypeId, $categoryId) {
            $query = Attribute::where('attribute_type', 'product')
                ->where('filterable', true)
                ->ordered();

            // Filter by product type if provided
            if ($productTypeId) {
                $query->whereHas('attributables', function ($q) use ($productTypeId) {
                    $q->where('attributable_type', 'product_type')
                      ->where('attributable_id', $productTypeId);
                });
            }

            return $query->with('attributeGroup')->get();
        });
    }

    /**
     * Get filter options with product counts.
     *
     * @param  Collection  $attributes
     * @param  \Illuminate\Database\Eloquent\Builder|null  $productQuery
     * @return Collection
     */
    public function getFilterOptions(Collection $attributes, $productQuery = null): Collection
    {
        return $attributes->map(function ($attribute) use ($productQuery) {
            if (!($attribute instanceof Attribute)) {
                $attribute = Attribute::find($attribute->id) ?? $attribute;
            }

            $valueCounts = method_exists($attribute, 'getValueCounts')
                ? $attribute->getValueCounts($productQuery)
                : collect();
            
            return [
                'id' => $attribute->id,
                'handle' => $attribute->handle,
                'name' => $attribute->name,
                'type' => method_exists($attribute, 'getTypeName')
                    ? $attribute->getTypeName()
                    : class_basename($attribute->type),
                'unit' => $attribute->unit,
                'is_numeric' => method_exists($attribute, 'isNumeric') ? $attribute->isNumeric() : false,
                'is_color' => method_exists($attribute, 'isColor') ? $attribute->isColor() : false,
                'is_select' => method_exists($attribute, 'isSelect') ? $attribute->isSelect() : false,
                'is_boolean' => method_exists($attribute, 'isBoolean') ? $attribute->isBoolean() : false,
                'options' => $this->formatFilterOptions($attribute, $valueCounts),
            ];
        });
    }

    /**
     * Format filter options based on attribute type.
     *
     * @param  mixed  $attribute
     * @param  Collection  $valueCounts
     * @return array
     */
    protected function formatFilterOptions($attribute, Collection $valueCounts): array
    {
        $typeName = method_exists($attribute, 'getTypeName')
            ? $attribute->getTypeName()
            : class_basename($attribute->type);
        $isNumeric = method_exists($attribute, 'isNumeric') ? $attribute->isNumeric() : $typeName === 'Number';
        $isColor = method_exists($attribute, 'isColor')
            ? $attribute->isColor()
            : ($typeName === 'Color' || str_contains(strtolower($attribute->handle ?? ''), 'color'));
        $isBoolean = method_exists($attribute, 'isBoolean') ? $attribute->isBoolean() : $typeName === 'Boolean';

        if ($isNumeric) {
            $values = $valueCounts->keys()->sort()->values();
            return [
                'min' => $values->min() ?? 0,
                'max' => $values->max() ?? 0,
                'unit' => $attribute->unit,
            ];
        }

        if ($isColor) {
            return $valueCounts->map(function ($count, $value) {
                return [
                    'value' => $value,
                    'label' => ucfirst($value),
                    'count' => $count,
                    'hex' => $this->colorNameToHex($value),
                ];
            })->values()->toArray();
        }

        if ($isBoolean) {
            return [
                [
                    'value' => true,
                    'label' => 'Yes',
                    'count' => $valueCounts->get('1') ?? $valueCounts->get('true') ?? 0,
                ],
                [
                    'value' => false,
                    'label' => 'No',
                    'count' => $valueCounts->get('0') ?? $valueCounts->get('false') ?? 0,
                ],
            ];
        }

        // Select/Multiselect/Text
        return $valueCounts->map(function ($count, $value) {
            return [
                'value' => $value,
                'label' => $value,
                'count' => $count,
            ];
        })->values()->toArray();
    }

    /**
     * Convert color name to hex code (basic implementation).
     *
     * @param  string  $colorName
     * @return string
     */
    protected function colorNameToHex(string $colorName): string
    {
        $colors = [
            'red' => '#FF0000',
            'blue' => '#0000FF',
            'green' => '#008000',
            'yellow' => '#FFFF00',
            'black' => '#000000',
            'white' => '#FFFFFF',
            'gray' => '#808080',
            'grey' => '#808080',
            'orange' => '#FFA500',
            'purple' => '#800080',
            'pink' => '#FFC0CB',
            'brown' => '#A52A2A',
        ];

        $lowerName = strtolower(trim($colorName));
        return $colors[$lowerName] ?? '#CCCCCC';
    }

    /**
     * Normalize value based on attribute type.
     *
     * @param  mixed  $value
     * @param  Attribute  $attribute
     * @return mixed
     */
    protected function normalizeValue($value, Attribute $attribute)
    {
        $typeName = class_basename($attribute->type);

        switch ($typeName) {
            case 'Number':
                return is_numeric($value) ? (float) $value : null;
            
            case 'Boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            
            case 'TranslatedText':
                if (is_array($value)) {
                    return $value;
                }
                return [app()->getLocale() => $value];
            
            default:
                return $value;
        }
    }

    /**
     * Clear cache for a product.
     *
     * @param  Product  $product
     * @return void
     */
    protected function clearProductCache(Product $product): void
    {
        Cache::forget("product.{$product->id}.attributes");
    }

    /**
     * Clear all attribute caches.
     *
     * @return void
     */
    public function clearAllCaches(): void
    {
        Cache::flush();
    }
}

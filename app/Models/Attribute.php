<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Lunar\Models\Attribute as LunarAttribute;

/**
 * Extended Attribute model with filtering support.
 * 
 * Additional fields:
 * - unit: Unit of measurement (kg, cm, etc.)
 * - display_order: Order for display in filters
 * 
 * Lunar already provides:
 * - searchable: Boolean for search indexing
 * - filterable: Boolean for filter availability
 */
class Attribute extends LunarAttribute
{
    /** @use HasFactory<\Database\Factories\AttributeFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'unit',
        'display_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'display_order' => 'integer',
    ];

    /**
     * Product attribute values relationship.
     *
     * @return HasMany
     */
    public function productAttributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class, 'attribute_id');
    }

    /**
     * Get attribute type name (simplified).
     *
     * @return string
     */
    public function getTypeName(): string
    {
        return class_basename($this->type);
    }

    /**
     * Check if attribute is numeric type.
     *
     * @return bool
     */
    public function isNumeric(): bool
    {
        return in_array($this->getTypeName(), ['Number']);
    }

    /**
     * Check if attribute is color type.
     *
     * @return bool
     */
    public function isColor(): bool
    {
        // Check if type is Color or handle contains 'color'
        return $this->getTypeName() === 'Color' || 
               str_contains(strtolower($this->handle), 'color');
    }

    /**
     * Check if attribute is select/multiselect type.
     *
     * @return bool
     */
    public function isSelect(): bool
    {
        return in_array($this->getTypeName(), ['Select', 'MultiSelect', 'ListField']);
    }

    /**
     * Check if attribute is boolean type.
     *
     * @return bool
     */
    public function isBoolean(): bool
    {
        return $this->getTypeName() === 'Boolean';
    }

    /**
     * Get unique values for this attribute (for filter options).
     *
     * @return \Illuminate\Support\Collection
     */
    public function getUniqueValues()
    {
        return ProductAttributeValue::where('attribute_id', $this->id)
            ->when($this->isNumeric(), function ($query) {
                return $query->whereNotNull('numeric_value')
                    ->selectRaw('DISTINCT numeric_value as value')
                    ->orderBy('numeric_value');
            }, function ($query) {
                return $query->whereNotNull('text_value')
                    ->selectRaw('DISTINCT text_value as value')
                    ->orderBy('text_value');
            })
            ->pluck('value');
    }

    /**
     * Get value counts for filter display.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|null  $productQuery
     * @return \Illuminate\Support\Collection
     */
    public function getValueCounts($productQuery = null)
    {
        $query = ProductAttributeValue::where('attribute_id', $this->id);
        
        if ($productQuery) {
            $productIds = (clone $productQuery)->pluck('id');
            $query->whereIn('product_id', $productIds);
        }

        if ($this->isNumeric()) {
            return $query->whereNotNull('numeric_value')
                ->selectRaw('numeric_value as value, COUNT(*) as count')
                ->groupBy('numeric_value')
                ->orderBy('numeric_value')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->value => $item->count];
                });
        }

        return $query->whereNotNull('text_value')
            ->selectRaw('text_value as value, COUNT(*) as count')
            ->groupBy('text_value')
            ->orderBy('text_value')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->value => $item->count];
            });
    }

    /**
     * Scope a query to only include filterable attributes.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilterable($query)
    {
        return $query->where('filterable', true);
    }

    /**
     * Scope a query to only include searchable attributes.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchable($query)
    {
        return $query->where('searchable', true);
    }

    /**
     * Scope a query to order by display order.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('position');
    }
}

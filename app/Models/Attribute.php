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
        'sortable',
        'validation_rules',
        'code',
        'scope',
        'localizable',
        'channel_specific',
        'required',
        'default_value',
        'ui_hint',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'display_order' => 'integer',
        'sortable' => 'boolean',
        'validation_rules' => 'array',
        'localizable' => 'boolean',
        'channel_specific' => 'boolean',
        'required' => 'boolean',
        'default_value' => 'array',
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

    /**
     * Scope a query to only include sortable attributes.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSortable($query)
    {
        return $query->where('sortable', true);
    }

    /**
     * Check if attribute is text type.
     *
     * @return bool
     */
    public function isText(): bool
    {
        return in_array($this->getTypeName(), ['Text']);
    }

    /**
     * Check if attribute is long text type.
     *
     * @return bool
     */
    public function isLongText(): bool
    {
        return in_array($this->getTypeName(), ['Text']) && 
               ($this->configuration['richtext'] ?? false);
    }

    /**
     * Check if attribute is date type.
     *
     * @return bool
     */
    public function isDate(): bool
    {
        return in_array($this->getTypeName(), ['Date']);
    }

    /**
     * Check if attribute is file/media type.
     *
     * @return bool
     */
    public function isFile(): bool
    {
        return in_array($this->getTypeName(), ['File', 'Media']);
    }

    /**
     * Check if attribute is measurement type (has unit).
     *
     * @return bool
     */
    public function isMeasurement(): bool
    {
        return !empty($this->unit) && $this->isNumeric();
    }

    /**
     * Check if attribute is JSON type.
     *
     * @return bool
     */
    public function isJson(): bool
    {
        return in_array($this->getTypeName(), ['JSON', 'Json']);
    }

    /**
     * Get validation rules for this attribute.
     *
     * @return array
     */
    public function getValidationRules(): array
    {
        $rules = $this->validation_rules ?? [];

        // Add required rule if attribute is required
        if ($this->required) {
            $rules['required'] = true;
        }

        // Add type-specific validation
        if ($this->isNumeric()) {
            $rules['numeric'] = true;
            if (isset($rules['min'])) {
                $rules['min'] = (float) $rules['min'];
            }
            if (isset($rules['max'])) {
                $rules['max'] = (float) $rules['max'];
            }
        }

        if ($this->isText() || $this->isLongText()) {
            if (isset($rules['max_length'])) {
                $rules['max'] = $rules['max_length'];
            }
            if (isset($rules['min_length'])) {
                $rules['min'] = $rules['min_length'];
            }
        }

        return $rules;
    }

    /**
     * Validate a value against this attribute's validation rules.
     *
     * @param  mixed  $value
     * @return bool
     */
    public function validateValue($value): bool
    {
        $rules = $this->getValidationRules();

        // Check required
        if (($rules['required'] ?? false) && ($value === null || $value === '')) {
            return false;
        }

        // Type-specific validation
        if ($this->isNumeric()) {
            if (!is_numeric($value)) {
                return false;
            }
            $numValue = (float) $value;
            if (isset($rules['min']) && $numValue < $rules['min']) {
                return false;
            }
            if (isset($rules['max']) && $numValue > $rules['max']) {
                return false;
            }
        }

        if ($this->isText() || $this->isLongText()) {
            if (!is_string($value)) {
                return false;
            }
            if (isset($rules['max']) && strlen($value) > $rules['max']) {
                return false;
            }
            if (isset($rules['min']) && strlen($value) < $rules['min']) {
                return false;
            }
            if (isset($rules['pattern']) && !preg_match($rules['pattern'], $value)) {
                return false;
            }
        }

        if ($this->isDate()) {
            try {
                new \DateTime($value);
            } catch (\Exception $e) {
                return false;
            }
        }

        return true;
    }

    /**
     * Variant attribute values relationship.
     *
     * @return HasMany
     */
    public function variantAttributeValues(): HasMany
    {
        return $this->hasMany(VariantAttributeValue::class, 'attribute_id');
    }

    /**
     * Channel attribute values relationship.
     *
     * @return HasMany
     */
    public function channelAttributeValues(): HasMany
    {
        return $this->hasMany(ChannelAttributeValue::class, 'attribute_id');
    }

    /**
     * Check if attribute is for products.
     *
     * @return bool
     */
    public function isProductScope(): bool
    {
        return in_array($this->scope ?? 'product', ['product', 'both']);
    }

    /**
     * Check if attribute is for variants.
     *
     * @return bool
     */
    public function isVariantScope(): bool
    {
        return in_array($this->scope ?? 'product', ['variant', 'both']);
    }

    /**
     * Check if attribute is localizable.
     *
     * @return bool
     */
    public function isLocalizable(): bool
    {
        return $this->localizable ?? false;
    }

    /**
     * Check if attribute is channel-specific.
     *
     * @return bool
     */
    public function isChannelSpecific(): bool
    {
        return $this->channel_specific ?? false;
    }

    /**
     * Check if attribute is required.
     *
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->required ?? false;
    }

    /**
     * Get default value for this attribute.
     *
     * @return mixed
     */
    public function getDefaultValue()
    {
        if ($this->default_value !== null) {
            return $this->default_value;
        }

        // Type-specific defaults
        return match($this->getTypeName()) {
            'Number' => 0,
            'Boolean' => false,
            'Select', 'MultiSelect' => null,
            'JSON', 'Json' => [],
            default => null,
        };
    }

    /**
     * Scope attributes by scope.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $scope
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByScope($query, string $scope)
    {
        return $query->where(function ($q) use ($scope) {
            $q->where('scope', $scope)
              ->orWhere('scope', 'both');
        });
    }

    /**
     * Scope localizable attributes.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLocalizable($query)
    {
        return $query->where('localizable', true);
    }

    /**
     * Scope channel-specific attributes.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeChannelSpecific($query)
    {
        return $query->where('channel_specific', true);
    }

    /**
     * Scope required attributes.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRequired($query)
    {
        return $query->where('required', true);
    }

    /**
     * Scope by UI hint.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $hint
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUIHint($query, string $hint)
    {
        return $query->where('ui_hint', $hint);
    }
}

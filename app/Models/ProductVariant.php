<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Validation\ValidationException;
use Lunar\Models\ProductVariant as LunarProductVariant;
use Lunar\Models\ProductOptionValue;

// Lazy load Media class to avoid autoload issues
if (!defined('MEDIA_CLASS_LOADED')) {
    if (class_exists('Spatie\MediaLibrary\MediaCollections\Models\Media')) {
        define('MEDIA_CLASS_LOADED', true);
    }
}

/**
 * Extended ProductVariant model with advanced variant management.
 * 
 * Complete variant fields implementation:
 * 
 * Variant Fields:
 * - sku: Unique SKU (from base Lunar model)
 * - barcode: EAN/UPC/custom barcode (EAN-13 validated)
 * - variant_name: Explicit variant name (e.g., "Red / XL"), falls back to generated name
 * - Attribute combination: Via variantOptions relationship (size, color, material, etc.)
 * - price_override: Variant-specific price override (per currency via Lunar pricing)
 * - compare_at_price: Compare-at price (strike-through price)
 * - cost_price: Internal cost price
 * - weight: Weight in grams
 * - dimensions: Length, width, height (from base Lunar model)
 * - stock: Stock quantity
 * - backorder: Backorder quantity
 * - low_stock_threshold: Low-stock threshold for alerts
 * - purchasable: Availability status (always/in_stock/never)
 * - enabled: Enable/disable individual variant
 * - position: Variant ordering/priority within product
 * 
 * Variant-Specific SEO:
 * - meta_title: Variant-specific SEO title
 * - meta_description: Variant-specific SEO description
 * - meta_keywords: Variant-specific SEO keywords
 * 
 * Variant Logic:
 * - Infinite variants per product: Supported
 * - Variant inheritance: inheritFromProduct() method for field inheritance
 * - Variant-specific images: Via images() relationship with primary flag
 * - Variant-specific SEO: getMetaTitle(), getMetaDescription(), getMetaKeywords()
 * - Disable individual variants: enabled field
 * - Variant ordering: position field with scopeOrdered() scope
 * 
 * Relationships:
 * - belongsToMany ProductOptionValue (via existing pivot table)
 * - belongsToMany Media (variant-specific images)
 * 
 * To use this custom model, register it in AppServiceProvider::boot():
 * 
 * \Lunar\Facades\ModelManifest::replace(
 *     \Lunar\Models\Contracts\ProductVariant::class,
 *     \App\Models\ProductVariant::class,
 * );
 * 
 * Or use addDirectory() to register all models in a directory:
 * 
 * \Lunar\Facades\ModelManifest::addDirectory(__DIR__.'/../Models');
 * 
 * See: https://docs.lunarphp.com/1.x/extending/models
 */
class ProductVariant extends LunarProductVariant
{
    /** @use HasFactory<\Database\Factories\ProductVariantFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'price_override',
        'cost_price',
        'compare_at_price',
        'weight',
        'barcode',
        'enabled',
        'variant_name',
        'low_stock_threshold',
        'position',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price_override' => 'integer',
        'cost_price' => 'integer',
        'compare_at_price' => 'integer',
        'weight' => 'integer',
        'enabled' => 'boolean',
        'low_stock_threshold' => 'integer',
        'position' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Validate barcode on saving
        static::saving(function ($variant) {
            if ($variant->barcode && !$variant->validateEan13($variant->barcode)) {
                throw ValidationException::withMessages([
                    'barcode' => ['The barcode must be a valid EAN-13 format.']
                ]);
            }

            // Validate variant option combinations for duplicates
            if ($variant->exists && $variant->isDirty()) {
                $variant->validateUniqueOptionCombination();
            }
        });
    }

    /**
     * Get the variant options relationship.
     * This uses the existing ProductOptionValue relationship.
     * 
     * Note: Lunar's base model may have a 'values()' method.
     * This provides both 'variantOptions()' and 'values()' for compatibility.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function variantOptions()
    {
        return $this->belongsToMany(
            ProductOptionValue::class,
            config('lunar.database.table_prefix') . 'product_option_value_product_variant',
            'variant_id',
            'value_id'
        )->withTimestamps();
    }

    /**
     * Alias for variantOptions() for compatibility with Lunar's base model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function values(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->variantOptions();
    }

    /**
     * Get validation rules for the custom attributes.
     *
     * @param  int|null  $variantId  The variant ID to exclude from unique checks (for updates)
     * @return array<string, string|array>
     */
    public static function getValidationRules(?int $variantId = null): array
    {
        $tableName = (new static)->getTable();
        $barcodeRule = ['nullable', 'string', 'size:13'];
        $barcodeRule[] = function ($attribute, $value, $fail) {
            if ($value && !(new static)->validateEan13($value)) {
                $fail('The barcode must be a valid EAN-13 format.');
            }
        };

        return [
            'price_override' => ['nullable', 'integer', 'min:0'],
            'cost_price' => ['nullable', 'integer', 'min:0'],
            'compare_at_price' => ['nullable', 'integer', 'min:0'],
            'weight' => ['nullable', 'integer', 'min:0'],
            'barcode' => $barcodeRule,
            'enabled' => ['nullable', 'boolean'],
            'variant_name' => ['nullable', 'string', 'max:255'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'position' => ['nullable', 'integer', 'min:0'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:500'],
            'option_values' => ['required', 'array', 'min:1'],
            'option_values.*' => ['required', 'exists:' . config('lunar.database.table_prefix') . 'product_option_values,id'],
        ];
    }

    /**
     * Get custom validation error messages.
     *
     * @return array<string, string>
     */
    public static function getValidationMessages(): array
    {
        return [
            'price_override.integer' => 'The price override must be an integer value.',
            'price_override.min' => 'The price override must be at least 0.',
            'cost_price.integer' => 'The cost price must be an integer value.',
            'cost_price.min' => 'The cost price must be at least 0.',
            'compare_at_price.integer' => 'The compare-at price must be an integer value.',
            'compare_at_price.min' => 'The compare-at price must be at least 0.',
            'weight.integer' => 'The weight must be an integer value.',
            'weight.min' => 'The weight must be at least 0.',
            'barcode.size' => 'The barcode must be exactly 13 characters.',
            'enabled.boolean' => 'The enabled field must be true or false.',
            'variant_name.max' => 'The variant name must not exceed 255 characters.',
            'low_stock_threshold.integer' => 'The low stock threshold must be an integer.',
            'low_stock_threshold.min' => 'The low stock threshold must be at least 0.',
            'position.integer' => 'The position must be an integer.',
            'position.min' => 'The position must be at least 0.',
            'meta_title.max' => 'The meta title must not exceed 255 characters.',
            'meta_description.max' => 'The meta description must not exceed 500 characters.',
            'meta_keywords.max' => 'The meta keywords must not exceed 500 characters.',
            'option_values.required' => 'At least one option value must be selected.',
            'option_values.array' => 'Option values must be an array.',
            'option_values.min' => 'At least one option value must be selected.',
            'option_values.*.exists' => 'One or more selected option values are invalid.',
        ];
    }

    /**
     * Validate EAN-13 barcode format.
     *
     * @param  string  $barcode
     * @return bool
     */
    public function validateEan13(string $barcode): bool
    {
        // Remove any non-digit characters
        $barcode = preg_replace('/\D/', '', $barcode);

        // EAN-13 must be exactly 13 digits
        if (strlen($barcode) !== 13) {
            return false;
        }

        // Calculate check digit
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $barcode[$i];
            // Multiply odd positions by 1, even positions by 3
            $sum += ($i % 2 === 0) ? $digit : ($digit * 3);
        }

        $checkDigit = (10 - ($sum % 10)) % 10;
        $providedCheckDigit = (int) $barcode[12];

        return $checkDigit === $providedCheckDigit;
    }

    /**
     * Validate that this variant's option combination is unique.
     *
     * @return void
     * @throws ValidationException
     */
    public function validateUniqueOptionCombination(): void
    {
        if (!$this->product_id) {
            return;
        }

        $optionValueIds = $this->variantOptions()->pluck('product_option_values.id')->sort()->values()->toArray();
        
        if (empty($optionValueIds)) {
            return;
        }

        // Check for other variants with the same option combination
        $existing = static::where('product_id', $this->product_id)
            ->where('id', '!=', $this->id)
            ->whereHas('variantOptions', function ($query) use ($optionValueIds) {
                $query->whereIn('product_option_values.id', $optionValueIds);
            })
            ->withCount(['variantOptions' => function ($query) use ($optionValueIds) {
                $query->whereIn('product_option_values.id', $optionValueIds);
            }])
            ->having('variant_options_count', '=', count($optionValueIds))
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'option_values' => ['A variant with this exact option combination already exists.']
            ]);
        }
    }

    /**
     * Get the effective price (price_override or base price).
     *
     * @param  int  $quantity
     * @param  \Lunar\Models\CustomerGroup|null  $customerGroup
     * @return int|null
     */
    public function getEffectivePrice(int $quantity = 1, $customerGroup = null): ?int
    {
        if ($this->price_override !== null) {
            return $this->price_override;
        }

        // Use Lunar's pricing system
        $pricing = \Lunar\Facades\Pricing::qty($quantity)->for($this);
        
        if ($customerGroup) {
            $pricing = $pricing->customerGroup($customerGroup);
        }

        $response = $pricing->get();
        return $response->matched?->price?->value;
    }

    /**
     * Check if variant is available for purchase.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        if ($this->purchasable === 'never') {
            return false;
        }

        if ($this->purchasable === 'in_stock' && $this->stock <= 0) {
            return false;
        }

        return true;
    }

    /**
     * Get formatted weight with units.
     *
     * @return Attribute
     */
    protected function formattedWeight(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->weight ? number_format($this->weight, 0) . ' g' : null,
        );
    }

    /**
     * Scope a query to only include enabled variants.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * Scope a query to only include available variants.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAvailable($query)
    {
        return $query->where('enabled', true)
            ->where(function ($q) {
                $q->where('purchasable', 'always')
                  ->orWhere(function ($subQ) {
                      $subQ->where('purchasable', 'in_stock')
                           ->where('stock', '>', 0);
                  });
            });
    }

    /**
     * Scope a query to filter by option values.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  array  $optionValueIds
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithOptionValues($query, array $optionValueIds)
    {
        return $query->whereHas('variantOptions', function ($q) use ($optionValueIds) {
            $q->whereIn('product_option_values.id', $optionValueIds);
        });
    }

    /**
     * Scope a query to order variants by position (priority).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query, string $direction = 'asc')
    {
        return $query->orderBy('position', $direction)->orderBy('id', $direction);
    }

    /**
     * Scope a query to filter low stock variants.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLowStock($query)
    {
        return $query->where(function ($q) {
            $q->whereColumn('stock', '<=', 'low_stock_threshold')
              ->orWhere(function ($subQ) {
                  $subQ->whereNull('low_stock_threshold')
                       ->where('stock', '<=', 10);
              });
        })->where('stock', '>', 0);
    }


    /**
     * Get thumbnail image URL.
     *
     * @param  string  $conversion
     * @return string|null
     */
    public function getThumbnailUrl(string $conversion = 'thumb'): ?string
    {
        $thumbnail = $this->getThumbnail();
        
        if (!$thumbnail) {
            // Fallback to product image
            return $this->product?->getFirstMediaUrl('images', $conversion);
        }

        return $thumbnail->getUrl($conversion) ?? $thumbnail->getUrl();
    }

    /**
     * Attach an image to this variant.
     *
     * @param  \Spatie\MediaLibrary\MediaCollections\Models\Media  $media
     * @param  bool  $primary
     * @return void
     */
    public function attachImage($media, bool $primary = false): void
    {
        // If setting as primary, unset other primary images
        if ($primary) {
            $this->images()->updateExistingPivot(
                $this->images()->pluck('media_id')->toArray(),
                ['primary' => false]
            );
        }

        $this->images()->syncWithoutDetaching([
            $media->id => ['primary' => $primary]
        ]);
    }

    /**
     * Set primary image for this variant.
     *
     * @param  \Spatie\MediaLibrary\MediaCollections\Models\Media  $media
     * @return void
     */
    public function setPrimaryImage($media): void
    {
        $this->attachImage($media, true);
    }

    /**
     * Detach an image from this variant.
     *
     * @param  int  $mediaId
     * @return void
     */
    public function detachImage(int $mediaId): void
    {
        $this->images()->detach($mediaId);
    }

    /**
     * Get all variant images with primary flag.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getVariantImages(): \Illuminate\Support\Collection
    {
        return $this->images()->get()->map(function ($media) {
            return [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'thumb_url' => $media->getUrl('thumb'),
                'primary' => (bool) $media->pivot?->primary,
            ];
        });
    }

    /**
     * Check if variant has images.
     *
     * @return bool
     */
    public function hasImages(): bool
    {
        return $this->images()->count() > 0;
    }

    /**
     * Get variant display name.
     * Uses explicit variant_name if set, otherwise generates from option values.
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        // Use explicit variant name if set
        if ($this->variant_name) {
            return $this->variant_name;
        }

        // Fallback to generated name from option values
        $values = $this->variantOptions()
            ->with('option')
            ->get()
            ->map(function ($value) {
                return $value->translateAttribute('name');
            })
            ->join(' / ');

        return $values ?: $this->sku;
    }

    /**
     * Get variant option values as array.
     *
     * @return array
     */
    public function getOptionValuesArray(): array
    {
        return $this->variantOptions()
            ->with('option')
            ->get()
            ->mapWithKeys(function ($value) {
                return [$value->option->handle => $value->translateAttribute('name')];
            })
            ->toArray();
    }

    /**
     * Get stock status.
     * Uses low_stock_threshold if set, otherwise defaults to 10.
     *
     * @return string
     */
    public function getStockStatus(): string
    {
        $threshold = $this->low_stock_threshold ?? 10;

        if ($this->stock > $threshold) {
            return 'in_stock';
        } elseif ($this->stock > 0) {
            return 'low_stock';
        } elseif ($this->backorder > 0) {
            return 'backorder';
        }
        return 'out_of_stock';
    }

    /**
     * Check if variant is low stock based on threshold.
     *
     * @return bool
     */
    public function isLowStock(): bool
    {
        if ($this->stock <= 0) {
            return false; // Out of stock, not low stock
        }

        $threshold = $this->low_stock_threshold ?? 10;
        return $this->stock <= $threshold;
    }

    /**
     * Check if variant has sufficient stock.
     *
     * @param  int  $quantity
     * @return bool
     */
    public function hasSufficientStock(int $quantity): bool
    {
        if ($this->purchasable === 'never') {
            return false;
        }

        if ($this->purchasable === 'in_stock') {
            return $this->stock >= $quantity;
        }

        // For 'always', check if we have stock or backorder
        return ($this->stock + $this->backorder) >= $quantity;
    }

    /**
     * Decrement stock.
     *
     * @param  int  $quantity
     * @return bool
     */
    public function decrementStock(int $quantity): bool
    {
        if (!$this->hasSufficientStock($quantity)) {
            return false;
        }

        if ($this->stock >= $quantity) {
            $this->decrement('stock', $quantity);
        } else {
            // Use backorder
            $remaining = $quantity - $this->stock;
            $this->update(['stock' => 0]);
            $this->decrement('backorder', $remaining);
        }

        return true;
    }

    /**
     * Increment stock.
     *
     * @param  int  $quantity
     * @return void
     */
    public function incrementStock(int $quantity): void
    {
        $this->increment('stock', $quantity);
    }

    /**
     * Price histories relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function priceHistories()
    {
        return $this->hasMany(\App\Models\PriceHistory::class, 'variant_id');
    }

    /**
     * Inventory levels relationship (multi-warehouse).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function inventoryLevels()
    {
        return $this->hasMany(\App\Models\InventoryLevel::class, 'product_variant_id');
    }

    /**
     * Stock reservations relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stockReservations()
    {
        return $this->hasMany(\App\Models\StockReservation::class, 'product_variant_id');
    }

    /**
     * Stock movements relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stockMovements()
    {
        return $this->hasMany(\App\Models\StockMovement::class, 'product_variant_id');
    }

    /**
     * Get price using MatrixPricingService.
     *
     * @param  int  $quantity
     * @param  \Lunar\Models\Currency|null  $currency
     * @param  \Lunar\Models\CustomerGroup|string|null  $customerGroup
     * @param  string|null  $region
     * @return array
     */
    public function getMatrixPrice(
        int $quantity = 1,
        ?\Lunar\Models\Currency $currency = null,
        $customerGroup = null,
        ?string $region = null
    ): array {
        $service = app(\App\Services\MatrixPricingService::class);
        return $service->calculatePrice($this, $quantity, $currency, $customerGroup, $region);
    }

    /**
     * Get tiered pricing.
     *
     * @param  \Lunar\Models\Currency|null  $currency
     * @param  \Lunar\Models\CustomerGroup|string|null  $customerGroup
     * @param  string|null  $region
     * @return \Illuminate\Support\Collection
     */
    public function getTieredPricing(
        ?\Lunar\Models\Currency $currency = null,
        $customerGroup = null,
        ?string $region = null
    ): \Illuminate\Support\Collection {
        $service = app(\App\Services\MatrixPricingService::class);
        return $service->getTieredPricing($this, $currency, $customerGroup, $region);
    }

    /**
     * Get volume discounts.
     *
     * @param  \Lunar\Models\Currency|null  $currency
     * @param  \Lunar\Models\CustomerGroup|string|null  $customerGroup
     * @return array
     */
    public function getVolumeDiscounts(
        ?\Lunar\Models\Currency $currency = null,
        $customerGroup = null
    ): array {
        $service = app(\App\Services\MatrixPricingService::class);
        return $service->getVolumeDiscounts($this, $currency, $customerGroup);
    }

    /**
     * Variant attribute values relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variantAttributeValues()
    {
        return $this->hasMany(VariantAttributeValue::class, 'product_variant_id');
    }

    /**
     * Attributes relationship through variant attribute values.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function variantAttributes()
    {
        return $this->belongsToMany(
            Attribute::class,
            config('lunar.database.table_prefix') . 'variant_attribute_values',
            'product_variant_id',
            'attribute_id'
        )->using(VariantAttributeValue::class)
          ->withPivot('value', 'numeric_value', 'text_value')
          ->withTimestamps();
    }

    /**
     * Get variant-specific SEO meta title.
     * Falls back to product meta title or product name if not set.
     *
     * @return string
     */
    public function getMetaTitle(): string
    {
        if ($this->meta_title) {
            return $this->meta_title;
        }

        // Fallback to product meta title or name
        if ($this->product) {
            $productMetaTitle = $this->product->translateAttribute('meta_title');
            if ($productMetaTitle) {
                return $productMetaTitle . ' - ' . $this->getDisplayName();
            }

            $productName = $this->product->translateAttribute('name');
            if ($productName) {
                return $productName . ' - ' . $this->getDisplayName();
            }
        }

        return $this->getDisplayName();
    }

    /**
     * Get variant-specific SEO meta description.
     * Falls back to product meta description or generated description if not set.
     *
     * @return string
     */
    public function getMetaDescription(): string
    {
        if ($this->meta_description) {
            return $this->meta_description;
        }

        // Fallback to product meta description
        if ($this->product) {
            $productMetaDescription = $this->product->translateAttribute('meta_description');
            if ($productMetaDescription) {
                return $productMetaDescription;
            }

            // Generate description from product and variant
            $productName = $this->product->translateAttribute('name');
            $variantName = $this->getDisplayName();
            return "Buy {$productName} - {$variantName}. High quality products with fast shipping.";
        }

        return $this->getDisplayName();
    }

    /**
     * Get variant-specific SEO meta keywords.
     * Falls back to product keywords with variant attributes if not set.
     *
     * @return string
     */
    public function getMetaKeywords(): string
    {
        if ($this->meta_keywords) {
            return $this->meta_keywords;
        }

        // Generate keywords from product and variant attributes
        $keywords = [];

        if ($this->product) {
            $productName = $this->product->translateAttribute('name');
            if ($productName) {
                $keywords[] = $productName;
            }

            // Add variant display name
            $keywords[] = $this->getDisplayName();

            // Add option values as keywords
            $optionValues = $this->variantOptions()
                ->with('option')
                ->get()
                ->map(function ($value) {
                    return $value->translateAttribute('name');
                })
                ->toArray();

            $keywords = array_merge($keywords, $optionValues);
        }

        return implode(', ', array_unique($keywords));
    }

    /**
     * Get variant SEO meta tags array.
     *
     * @return array
     */
    public function getSEOMetaTags(): array
    {
        return [
            'title' => $this->getMetaTitle(),
            'description' => $this->getMetaDescription(),
            'keywords' => $this->getMetaKeywords(),
        ];
    }

    /**
     * Inherit values from parent product if variant values are not set.
     * This implements variant inheritance logic.
     *
     * @param  string  $field
     * @return mixed
     */
    public function inheritFromProduct(string $field)
    {
        // Check if variant has the field set
        if ($this->$field !== null && $this->$field !== '') {
            return $this->$field;
        }

        // Fallback to product value
        if ($this->product) {
            return $this->product->$field ?? null;
        }

        return null;
    }
}

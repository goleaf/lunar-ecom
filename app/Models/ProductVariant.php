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
 * Additional fields:
 * - price_override: Variant-specific price override
 * - cost_price: Cost price for the variant
 * - compare_at_price: Compare-at price
 * - weight: Weight in grams
 * - barcode: EAN-13 barcode
 * - enabled: Enable/disable variant
 * 
 * Relationships:
 * - belongsToMany ProductOptionValue (via existing pivot table)
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
     * Get variant display name (option values combined).
     *
     * @return string
     */
    public function getDisplayName(): string
    {
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
     *
     * @return string
     */
    public function getStockStatus(): string
    {
        if ($this->stock > 10) {
            return 'in_stock';
        } elseif ($this->stock > 0) {
            return 'low_stock';
        } elseif ($this->backorder > 0) {
            return 'backorder';
        }
        return 'out_of_stock';
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
}

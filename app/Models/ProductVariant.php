<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'sku',
        'gtin',
        'ean',
        'upc',
        'isbn',
        'barcode',
        'internal_reference',
        'variant_name',
        'title',
        'status',
        'visibility',
        'channel_visibility',
        'sku_format',
        'price_override',
        'cost_price',
        'compare_at_price',
        'tax_inclusive',
        'price_rounding_rules',
        'map_price',
        'price_locked',
        'discount_override',
        'pricing_hook',
        'min_order_quantity',
        'max_order_quantity',
        'backorder_allowed',
        'backorder_limit',
        'is_virtual',
        'stock_status',
        'weight',
        'volumetric_weight',
        'volumetric_divisor',
        'shipping_class',
        'is_fragile',
        'is_hazardous',
        'hazardous_class',
        'origin_country',
        'hs_code',
        'customs_description',
        'lead_time_days',
        'enabled',
        'low_stock_threshold',
        'position',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'url_slug',
        'canonical_url',
        'canonical_inheritance',
        'robots_meta',
        'og_title',
        'og_description',
        'og_image_id',
        'twitter_card',
        'out_of_stock_visibility',
        'preorder_enabled',
        'preorder_release_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'uuid' => 'string',
        'sku_format' => 'array',
        'channel_visibility' => 'array',
        'price_override' => 'integer',
        'cost_price' => 'integer',
        'compare_at_price' => 'integer',
        'tax_inclusive' => 'boolean',
        'price_rounding_rules' => 'array',
        'map_price' => 'integer',
        'price_locked' => 'boolean',
        'discount_override' => 'array',
        'min_order_quantity' => 'integer',
        'max_order_quantity' => 'integer',
        'backorder_limit' => 'integer',
        'is_virtual' => 'boolean',
        'weight' => 'integer',
        'volumetric_weight' => 'integer',
        'volumetric_divisor' => 'integer',
        'is_fragile' => 'boolean',
        'is_hazardous' => 'boolean',
        'lead_time_days' => 'integer',
        'enabled' => 'boolean',
        'low_stock_threshold' => 'integer',
        'position' => 'integer',
        'preorder_enabled' => 'boolean',
        'preorder_release_date' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Generate UUID on creation
        static::creating(function ($variant) {
            if (empty($variant->uuid)) {
                $variant->uuid = (string) Str::uuid();
            }
        });

        // Validate barcode on saving
        static::saving(function ($variant) {
            // Validate EAN-13 barcode
            if ($variant->barcode && !$variant->validateEan13($variant->barcode)) {
                throw ValidationException::withMessages([
                    'barcode' => ['The barcode must be a valid EAN-13 format.']
                ]);
            }

            // Validate EAN
            if ($variant->ean && !$variant->validateEan13($variant->ean)) {
                throw ValidationException::withMessages([
                    'ean' => ['The EAN must be a valid EAN-13 format.']
                ]);
            }

            // Validate UPC (12 digits)
            if ($variant->upc && !$variant->validateUPC($variant->upc)) {
                throw ValidationException::withMessages([
                    'upc' => ['The UPC must be a valid 12-digit format.']
                ]);
            }

            // Validate ISBN (13 digits)
            if ($variant->isbn && !$variant->validateISBN($variant->isbn)) {
                throw ValidationException::withMessages([
                    'isbn' => ['The ISBN must be a valid 13-digit format.']
                ]);
            }

            // Auto-generate SKU if not set
            if (empty($variant->sku) && $variant->product_id) {
                $variant->sku = $variant->generateSKU();
            }

        // Auto-generate variant title if not set
        if (empty($variant->title) && empty($variant->variant_name)) {
            $variant->title = $variant->generateTitle();
        }

        // Calculate volumetric weight if dimensions changed
        if ($variant->isDirty(['dimensions', 'volumetric_divisor']) || 
            ($variant->exists && !$variant->volumetric_weight && $variant->dimensions)) {
            $service = app(\App\Services\VariantShippingService::class);
            $service->updateVolumetricWeight($variant);
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

        $eanRule = ['nullable', 'string', 'size:13'];
        $eanRule[] = function ($attribute, $value, $fail) {
            if ($value && !(new static)->validateEan13($value)) {
                $fail('The EAN must be a valid EAN-13 format.');
            }
        };

        $upcRule = ['nullable', 'string', 'size:12'];
        $upcRule[] = function ($attribute, $value, $fail) {
            if ($value && !(new static)->validateUPC($value)) {
                $fail('The UPC must be a valid 12-digit format.');
            }
        };

        $isbnRule = ['nullable', 'string', 'size:13'];
        $isbnRule[] = function ($attribute, $value, $fail) {
            if ($value && !(new static)->validateISBN($value)) {
                $fail('The ISBN must be a valid ISBN-13 format.');
            }
        };

        return [
            'uuid' => ['nullable', 'uuid', 'unique:' . $tableName . ',uuid,' . ($variantId ?? 'NULL') . ',id'],
            'sku' => ['nullable', 'string', 'max:255', 'unique:' . $tableName . ',sku,' . ($variantId ?? 'NULL') . ',id'],
            'gtin' => ['nullable', 'string', 'max:14'],
            'ean' => $eanRule,
            'upc' => $upcRule,
            'isbn' => $isbnRule,
            'barcode' => $barcodeRule,
            'internal_reference' => ['nullable', 'string', 'max:100'],
            'title' => ['nullable', 'string', 'max:255'],
            'variant_name' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:active,inactive,archived'],
            'visibility' => ['nullable', 'in:public,hidden,channel_specific'],
            'channel_visibility' => ['nullable', 'array'],
            'channel_visibility.*' => ['integer', 'exists:' . config('lunar.database.table_prefix') . 'channels,id'],
            'sku_format' => ['nullable', 'array'],
            'price_override' => ['nullable', 'integer', 'min:0'],
            'cost_price' => ['nullable', 'integer', 'min:0'],
            'compare_at_price' => ['nullable', 'integer', 'min:0'],
            'weight' => ['nullable', 'integer', 'min:0'],
            'volumetric_weight' => ['nullable', 'integer', 'min:0'],
            'volumetric_divisor' => ['nullable', 'integer', 'min:1'],
            'shipping_class' => ['nullable', 'string', 'max:50'],
            'is_fragile' => ['nullable', 'boolean'],
            'is_hazardous' => ['nullable', 'boolean'],
            'hazardous_class' => ['nullable', 'string', 'max:50'],
            'origin_country' => ['nullable', 'string', 'size:2'],
            'hs_code' => ['nullable', 'string', 'max:20'],
            'customs_description' => ['nullable', 'string'],
            'lead_time_days' => ['nullable', 'integer', 'min:0'],
            'enabled' => ['nullable', 'boolean'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'position' => ['nullable', 'integer', 'min:0'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:500'],
            'url_slug' => ['nullable', 'string', 'max:255', 'unique:' . $tableName . ',url_slug,' . ($variantId ?? 'NULL') . ',id'],
            'canonical_url' => ['nullable', 'url', 'max:500'],
            'canonical_inheritance' => ['nullable', 'in:inherit,override,none'],
            'robots_meta' => ['nullable', 'string', 'max:50'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string'],
            'og_image_id' => ['nullable', 'integer', 'exists:media,id'],
            'twitter_card' => ['nullable', 'in:summary,summary_large_image,app,player'],
            'out_of_stock_visibility' => ['nullable', 'in:hide,show_unavailable,show_available'],
            'preorder_enabled' => ['nullable', 'boolean'],
            'preorder_release_date' => ['nullable', 'date'],
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
            'out_of_stock_visibility.in' => 'The out of stock visibility must be one of: hide, show_unavailable, show_available.',
            'preorder_enabled.boolean' => 'The preorder enabled field must be true or false.',
            'preorder_release_date.date' => 'The preorder release date must be a valid date.',
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
        // Check status
        if ($this->status !== 'active') {
            return false;
        }

        if (!$this->enabled) {
            return false;
        }

        if ($this->purchasable === 'never') {
            return false;
        }

        // Check pre-order availability
        if ($this->preorder_enabled) {
            return true; // Pre-orders are always available
        }

        if ($this->purchasable === 'in_stock' && $this->stock <= 0) {
            return false;
        }

        return true;
    }

    /**
     * Check if variant is visible in a specific channel.
     *
     * @param  int|null  $channelId
     * @return bool
     */
    public function isVisibleInChannel(?int $channelId = null): bool
    {
        // Check status
        if ($this->status === 'archived') {
            return false;
        }

        // Public visibility
        if ($this->visibility === 'public') {
            return true;
        }

        // Hidden visibility
        if ($this->visibility === 'hidden') {
            return false;
        }

        // Channel-specific visibility
        if ($this->visibility === 'channel_specific') {
            if (!$channelId) {
                return false;
            }

            $channelVisibility = $this->channel_visibility ?? [];
            return in_array($channelId, $channelVisibility);
        }

        return true;
    }

    /**
     * Get status label.
     *
     * @return string
     */
    public function getStatusLabel(): string
    {
        return match($this->status ?? 'active') {
            'active' => 'Active',
            'inactive' => 'Inactive',
            'archived' => 'Archived',
            default => 'Unknown',
        };
    }

    /**
     * Get visibility label.
     *
     * @return string
     */
    public function getVisibilityLabel(): string
    {
        return match($this->visibility ?? 'public') {
            'public' => 'Public',
            'hidden' => 'Hidden',
            'channel_specific' => 'Channel Specific',
            default => 'Unknown',
        };
    }

    /**
     * Archive variant.
     *
     * @return bool
     */
    public function archive(): bool
    {
        return $this->update(['status' => 'archived']);
    }

    /**
     * Activate variant.
     *
     * @return bool
     */
    public function activate(): bool
    {
        return $this->update(['status' => 'active']);
    }

    /**
     * Deactivate variant.
     *
     * @return bool
     */
    public function deactivate(): bool
    {
        return $this->update(['status' => 'inactive']);
    }

    /**
     * Check if variant should be visible based on stock status and visibility rules.
     *
     * @return bool
     */
    public function isVisible(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        // Get total available stock across all warehouses
        $totalStock = $this->getTotalAvailableStock();

        // If in stock, always visible
        if ($totalStock > 0) {
            return true;
        }

        // Check out-of-stock visibility rules
        return match ($this->out_of_stock_visibility ?? 'show_unavailable') {
            'hide' => false,
            'show_unavailable' => true,
            'show_available' => true,
            default => true,
        };
    }

    /**
     * Check if variant is available for pre-order.
     *
     * @return bool
     */
    public function isPreorderAvailable(): bool
    {
        if (!$this->preorder_enabled) {
            return false;
        }

        if (!$this->enabled) {
            return false;
        }

        if ($this->purchasable === 'never') {
            return false;
        }

        // Check if release date is in the future
        if ($this->preorder_release_date && $this->preorder_release_date->isPast()) {
            return false; // Already released
        }

        return true;
    }

    /**
     * Get total available stock across all warehouses.
     *
     * @return int
     */
    public function getTotalAvailableStock(): int
    {
        return \App\Models\InventoryLevel::where('product_variant_id', $this->id)
            ->sum(DB::raw('quantity - reserved_quantity'));
    }

    /**
     * Get total stock across all warehouses (including reserved).
     *
     * @return int
     */
    public function getTotalStock(): int
    {
        return \App\Models\InventoryLevel::where('product_variant_id', $this->id)
            ->sum('quantity');
    }

    /**
     * Get total reserved stock across all warehouses.
     *
     * @return int
     */
    public function getTotalReservedStock(): int
    {
        return \App\Models\InventoryLevel::where('product_variant_id', $this->id)
            ->sum('reserved_quantity');
    }

    /**
     * Get stock breakdown by warehouse.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getStockByWarehouse(): \Illuminate\Support\Collection
    {
        return \App\Models\InventoryLevel::where('product_variant_id', $this->id)
            ->with('warehouse')
            ->get()
            ->map(function ($level) {
                return [
                    'warehouse_id' => $level->warehouse_id,
                    'warehouse_name' => $level->warehouse->name,
                    'quantity' => $level->quantity,
                    'reserved_quantity' => $level->reserved_quantity,
                    'available_quantity' => $level->available_quantity,
                    'incoming_quantity' => $level->incoming_quantity,
                    'status' => $level->status,
                ];
            });
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
     * Scope a query to filter by status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include active variants.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to filter by visibility.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $visibility
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVisibility($query, string $visibility)
    {
        return $query->where('visibility', $visibility);
    }

    /**
     * Scope a query to only include public variants.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    /**
     * Scope a query to filter by channel visibility.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $channelId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVisibleInChannel($query, int $channelId)
    {
        return $query->where(function ($q) use ($channelId) {
            $q->where('visibility', 'public')
              ->orWhere(function ($subQ) use ($channelId) {
                  $subQ->where('visibility', 'channel_specific')
                       ->whereJsonContains('channel_visibility', $channelId);
              });
        });
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
     * @param  int|null  $channelId
     * @param  string|null  $locale
     * @return string|null
     */
    public function getThumbnailUrl(string $conversion = 'thumb', ?int $channelId = null, ?string $locale = null): ?string
    {
        $primaryImage = $this->getPrimaryImage($channelId, $locale);
        
        if ($primaryImage && $primaryImage->media) {
            return $primaryImage->media->getUrl($conversion) ?? $primaryImage->media->getUrl();
        }
        
        // Fallback to product image
        return $this->product?->getFirstMediaUrl('images', $conversion);
    }

    /**
     * Get thumbnail (legacy method).
     * Compatible with Lunar's base method signature.
     *
     * @return \Spatie\MediaLibrary\MediaCollections\Models\Media|null
     */
    public function getThumbnail(): ?\Spatie\MediaLibrary\MediaCollections\Models\Media
    {
        $primaryImage = $this->getPrimaryImage();
        return $primaryImage?->media;
    }

    /**
     * Attach media to this variant.
     *
     * @param  int  $mediaId
     * @param  array  $options
     * @return VariantMedia
     */
    public function attachMedia(int $mediaId, array $options = []): VariantMedia
    {
        $service = app(\App\Services\VariantMediaService::class);
        return $service->attachMedia($this, $mediaId, $options);
    }

    /**
     * Attach an image to this variant (legacy method).
     *
     * @param  \Spatie\MediaLibrary\MediaCollections\Models\Media  $media
     * @param  bool  $primary
     * @param  array  $options
     * @return VariantMedia
     */
    public function attachImage($media, bool $primary = false, array $options = []): VariantMedia
    {
        $options['primary'] = $primary;
        $options['media_type'] = $options['media_type'] ?? 'image';
        return $this->attachMedia($media->id, $options);
    }

    /**
     * Set primary image for this variant.
     *
     * @param  int  $mediaId
     * @param  string|null  $mediaType
     * @return bool
     */
    public function setPrimaryImage(int $mediaId, ?string $mediaType = null): bool
    {
        $service = app(\App\Services\VariantMediaService::class);
        return $service->setPrimaryMedia($this, $mediaId, $mediaType);
    }

    /**
     * Detach media from this variant.
     *
     * @param  int  $mediaId
     * @return bool
     */
    public function detachMedia(int $mediaId): bool
    {
        $service = app(\App\Services\VariantMediaService::class);
        return $service->detachMedia($this, $mediaId);
    }

    /**
     * Detach an image from this variant (legacy method).
     *
     * @param  int  $mediaId
     * @return bool
     */
    public function detachImage(int $mediaId): bool
    {
        return $this->detachMedia($mediaId);
    }

    /**
     * Reorder media.
     *
     * @param  array  $mediaIds
     * @return void
     */
    public function reorderMedia(array $mediaIds): void
    {
        $service = app(\App\Services\VariantMediaService::class);
        $service->reorderMedia($this, $mediaIds);
    }

    /**
     * Variant media relationship (enhanced with metadata).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variantMedia()
    {
        return $this->hasMany(VariantMedia::class, 'product_variant_id');
    }

    /**
     * Media relationship via pivot table (legacy support).
     * Compatible with Lunar's base method signature.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function images(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        $prefix = config('lunar.database.table_prefix');
        
        return $this->belongsToMany(
            \Spatie\MediaLibrary\MediaCollections\Models\Media::class,
            "{$prefix}media_product_variant",
            'product_variant_id',
            'media_id'
        )->withPivot(['primary', 'position', 'media_type', 'channel_id', 'locale', 'alt_text', 'caption', 'accessibility_metadata', 'media_metadata'])
          ->withTimestamps()
          ->orderBy('position');
    }

    /**
     * Get all variant images with primary flag.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getVariantImages(): \Illuminate\Support\Collection
    {
        $service = app(\App\Services\VariantMediaService::class);
        return $service->getImages($this);
    }

    /**
     * Check if variant has images.
     *
     * @return bool
     */
    public function hasImages(): bool
    {
        return $this->variantMedia()->where('media_type', 'image')->count() > 0;
    }

    /**
     * Get media gallery with all types.
     *
     * @param  array  $options
     * @return array
     */
    public function getMediaGallery(array $options = []): array
    {
        $service = app(\App\Services\VariantMediaService::class);
        return $service->getMediaGallery($this, $options);
    }

    /**
     * Get primary image.
     *
     * @param  int|null  $channelId
     * @param  string|null  $locale
     * @return VariantMedia|null
     */
    public function getPrimaryImage(?int $channelId = null, ?string $locale = null): ?VariantMedia
    {
        $service = app(\App\Services\VariantMediaService::class);
        return $service->getPrimaryImage($this, $channelId, $locale);
    }

    /**
     * Get images.
     *
     * @param  array  $options
     * @return \Illuminate\Support\Collection
     */
    public function getImages(array $options = []): \Illuminate\Support\Collection
    {
        $service = app(\App\Services\VariantMediaService::class);
        return $service->getImages($this, $options);
    }

    /**
     * Get videos.
     *
     * @param  array  $options
     * @return \Illuminate\Support\Collection
     */
    public function getVideos(array $options = []): \Illuminate\Support\Collection
    {
        $service = app(\App\Services\VariantMediaService::class);
        return $service->getVideos($this, $options);
    }

    /**
     * Get 360° images.
     *
     * @param  array  $options
     * @return \Illuminate\Support\Collection
     */
    public function get360Images(array $options = []): \Illuminate\Support\Collection
    {
        $service = app(\App\Services\VariantMediaService::class);
        return $service->get360Images($this, $options);
    }

    /**
     * Get 3D models.
     *
     * @param  array  $options
     * @return \Illuminate\Support\Collection
     */
    public function get3DModels(array $options = []): \Illuminate\Support\Collection
    {
        $service = app(\App\Services\VariantMediaService::class);
        return $service->get3DModels($this, $options);
    }

    /**
     * Get AR files.
     *
     * @param  array  $options
     * @return \Illuminate\Support\Collection
     */
    public function getARFiles(array $options = []): \Illuminate\Support\Collection
    {
        $service = app(\App\Services\VariantMediaService::class);
        return $service->getARFiles($this, $options);
    }

    /**
     * Get variant display name.
     * Priority: title > variant_name > generated title > SKU
     *
     * @return string
     */
    public function getDisplayName(): string
    {
        // Priority 1: Explicit title
        if ($this->title) {
            return $this->title;
        }

        // Priority 2: Explicit variant name
        if ($this->variant_name) {
            return $this->variant_name;
        }

        // Priority 3: Generated title from option values
        $generated = $this->generateTitle();
        if ($generated && $generated !== 'Variant New') {
            return $generated;
        }

        // Fallback: SKU
        return $this->sku ?? 'Variant';
    }

    /**
     * Get variant title (alias for getDisplayName for consistency).
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->getDisplayName();
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
     * Digital product relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function digitalProduct()
    {
        return $this->hasOne(\App\Models\DigitalProduct::class, 'product_variant_id');
    }

    /**
     * Download links relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function downloadLinks()
    {
        return $this->hasMany(\App\Models\DownloadLink::class, 'product_variant_id');
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
     * Variant attribute combination relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function attributeCombination()
    {
        return $this->hasOne(\App\Models\VariantAttributeCombination::class, 'variant_id');
    }

    /**
     * Get defining attributes for this variant.
     *
     * @return array
     */
    public function getDefiningAttributes(): array
    {
        return $this->attributeCombination?->defining_attributes ?? [];
    }

    /**
     * Get informational attributes for this variant.
     *
     * @return array
     */
    public function getInformationalAttributes(): array
    {
        return $this->attributeCombination?->informational_attributes ?? [];
    }

    /**
     * Check if variant is partial (missing some defining attributes).
     *
     * @return bool
     */
    public function isPartial(): bool
    {
        return $this->attributeCombination?->is_partial ?? false;
    }

    /**
     * Get attribute combination as array.
     *
     * @return array
     */
    public function getAttributeCombination(): array
    {
        return $this->attributeCombination?->combination ?? [];
    }

    /**
     * Variant prices relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variantPrices()
    {
        return $this->hasMany(\App\Models\VariantPrice::class, 'variant_id');
    }

    /**
     * Variant price hooks relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function priceHooks()
    {
        return $this->hasMany(\App\Models\VariantPriceHook::class, 'variant_id');
    }

    /**
     * Get price using VariantPricingService.
     *
     * @param  int  $quantity
     * @param  \Lunar\Models\Currency|null  $currency
     * @param  \Lunar\Models\Channel|null  $channel
     * @param  \Lunar\Models\CustomerGroup|null  $customerGroup
     * @param  bool  $includeTax
     * @return array
     */
    public function getPrice(
        int $quantity = 1,
        ?\Lunar\Models\Currency $currency = null,
        ?\Lunar\Models\Channel $channel = null,
        ?\Lunar\Models\CustomerGroup $customerGroup = null,
        bool $includeTax = false
    ): array {
        $service = app(\App\Services\VariantPricingService::class);
        return $service->calculatePrice($this, $quantity, $currency, $channel, $customerGroup, $includeTax);
    }

    /**
     * Get tiered pricing.
     *
     * @param  \Lunar\Models\Currency|null  $currency
     * @param  \Lunar\Models\Channel|null  $channel
     * @param  \Lunar\Models\CustomerGroup|null  $customerGroup
     * @return \Illuminate\Support\Collection
     */
    public function getTieredPricing(
        ?\Lunar\Models\Currency $currency = null,
        ?\Lunar\Models\Channel $channel = null,
        ?\Lunar\Models\CustomerGroup $customerGroup = null
    ): \Illuminate\Support\Collection {
        $service = app(\App\Services\VariantPricingService::class);
        return $service->getTieredPricing($this, $currency, $channel, $customerGroup);
    }

    /**
     * Check if price is locked.
     *
     * @return bool
     */
    public function isPriceLocked(): bool
    {
        return $this->price_locked ?? false;
    }

    /**
     * Lock price (prevent discounts).
     *
     * @return bool
     */
    public function lockPrice(): bool
    {
        return $this->update(['price_locked' => true]);
    }

    /**
     * Unlock price (allow discounts).
     *
     * @return bool
     */
    public function unlockPrice(): bool
    {
        return $this->update(['price_locked' => false]);
    }

    /**
     * Get available stock.
     *
     * @param  int|null  $warehouseId
     * @return int
     */
    public function getAvailableStock(?int $warehouseId = null): int
    {
        $service = app(\App\Services\VariantInventoryService::class);
        return $service->getAvailableStock($this, $warehouseId);
    }

    /**
     * Check if variant has sufficient stock.
     *
     * @param  int  $quantity
     * @param  int|null  $warehouseId
     * @return bool
     */
    public function hasSufficientStock(int $quantity, ?int $warehouseId = null): bool
    {
        $service = app(\App\Services\VariantInventoryService::class);
        return $service->hasSufficientStock($this, $quantity, $warehouseId);
    }

    /**
     * Get stock status.
     *
     * @param  int|null  $warehouseId
     * @return string
     */
    public function getStockStatus(?int $warehouseId = null): string
    {
        $service = app(\App\Services\VariantInventoryService::class);
        return $service->getStockStatus($this, $warehouseId);
    }

    /**
     * Get stock breakdown by warehouse.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getStockBreakdown(): \Illuminate\Support\Collection
    {
        $service = app(\App\Services\VariantInventoryService::class);
        return $service->getStockBreakdown($this);
    }

    /**
     * Check if variant is virtual (services/digital).
     *
     * @return bool
     */
    public function isVirtual(): bool
    {
        return $this->is_virtual ?? false;
    }

    /**
     * Check if backorder is allowed.
     *
     * @return bool
     */
    public function allowsBackorder(): bool
    {
        return in_array($this->backorder_allowed ?? 'no', ['yes', 'limit']);
    }

    /**
     * Get shipping weight (actual or volumetric, whichever is greater).
     *
     * @return int Weight in grams
     */
    public function getShippingWeight(): int
    {
        $service = app(\App\Services\VariantShippingService::class);
        return $service->getShippingWeight($this);
    }

    /**
     * Get volumetric weight.
     *
     * @return int|null Weight in grams
     */
    public function getVolumetricWeight(): ?int
    {
        $service = app(\App\Services\VariantShippingService::class);
        return $service->calculateVolumetricWeight($this);
    }

    /**
     * Get shipping requirements.
     *
     * @return array
     */
    public function getShippingRequirements(): array
    {
        $service = app(\App\Services\VariantShippingService::class);
        return $service->getShippingRequirements($this);
    }

    /**
     * Get customs information.
     *
     * @return array
     */
    public function getCustomsInfo(): array
    {
        $service = app(\App\Services\VariantShippingService::class);
        return $service->getCustomsInfo($this);
    }

    /**
     * Get lead time information.
     *
     * @return array
     */
    public function getLeadTimeInfo(): array
    {
        $service = app(\App\Services\VariantShippingService::class);
        return $service->getLeadTimeInfo($this);
    }

    /**
     * Check if variant requires special handling.
     *
     * @return bool
     */
    public function requiresSpecialHandling(): bool
    {
        return ($this->is_fragile ?? false) || ($this->is_hazardous ?? false);
    }

    /**
     * Check if variant is fragile.
     *
     * @return bool
     */
    public function isFragile(): bool
    {
        return $this->is_fragile ?? false;
    }

    /**
     * Check if variant is hazardous.
     *
     * @return bool
     */
    public function isHazardous(): bool
    {
        return $this->is_hazardous ?? false;
    }

    /**
     * Get origin country (variant-level or product-level).
     *
     * @return string|null ISO 2-character country code
     */
    public function getOriginCountry(): ?string
    {
        return $this->origin_country ?? $this->product->origin_country ?? null;
    }

    /**
     * Get HS code (variant-level or product-level).
     *
     * @return string|null
     */
    public function getHSCode(): ?string
    {
        return $this->hs_code ?? $this->product->hs_code ?? null;
    }

    /**
     * Get lead time in days.
     *
     * @return int
     */
    public function getLeadTimeDays(): int
    {
        return $this->lead_time_days ?? $this->product->lead_time_days ?? 0;
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

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Lunar\Models\Product as LunarProduct;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * Extended Product model with custom attributes.
 * 
 * Custom attributes:
 * - SKU (unique, indexed)
 * - Barcode (EAN-13 validation)
 * - Weight (in grams)
 * - Dimensions (length/width/height in cm)
 * - Manufacturer name
 * - Warranty period (in months)
 * - Condition (new/refurbished/used)
 * - Origin country
 * - Custom meta (JSON field for unlimited custom fields)
 * 
 * To use this custom model, register it in AppServiceProvider::boot():
 * 
 * \Lunar\Facades\ModelManifest::replace(
 *     \Lunar\Models\Contracts\Product::class,
 *     \App\Models\Product::class,
 * );
 * 
 * Or use addDirectory() to register all models in a directory:
 * 
 * \Lunar\Facades\ModelManifest::addDirectory(__DIR__.'/../Models');
 * 
 * See: https://docs.lunarphp.com/1.x/extending/models
 */
class Product extends LunarProduct
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;
    use LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sku',
        'barcode',
        'weight',
        'length',
        'width',
        'height',
        'manufacturer_name',
        'warranty_period',
        'condition',
        'origin_country',
        'custom_meta',
        'average_rating',
        'total_reviews',
        'rating_5_count',
        'rating_4_count',
        'rating_3_count',
        'rating_2_count',
        'rating_1_count',
        'is_bundle',
        'is_digital',
        'digital_product_settings',
        // Product Core Model fields
        'visibility',
        'short_description',
        'full_description',
        'technical_description',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'published_at',
        'scheduled_publish_at',
        'scheduled_unpublish_at',
        'publish_at',
        'unpublish_at',
        'is_coming_soon',
        'coming_soon_message',
        'expected_available_at',
        'is_locked',
        'locked_by',
        'locked_at',
        'lock_reason',
        'version',
        'parent_version_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'weight' => 'integer',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'warranty_period' => 'integer',
        'custom_meta' => 'array',
        'average_rating' => 'decimal:2',
        'total_reviews' => 'integer',
        'rating_5_count' => 'integer',
        'rating_4_count' => 'integer',
        'rating_3_count' => 'integer',
        'rating_2_count' => 'integer',
        'rating_1_count' => 'integer',
        'is_bundle' => 'boolean',
        'is_digital' => 'boolean',
        'digital_product_settings' => 'array',
        // Product Core Model casts
        'is_locked' => 'boolean',
        'version' => 'integer',
        'published_at' => 'datetime',
        'scheduled_publish_at' => 'datetime',
        'scheduled_unpublish_at' => 'datetime',
        'publish_at' => 'datetime',
        'unpublish_at' => 'datetime',
        'is_coming_soon' => 'boolean',
        'expected_available_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Validate barcode on saving
        static::saving(function ($product) {
            if ($product->barcode && !$product->validateEan13($product->barcode)) {
                throw ValidationException::withMessages([
                    'barcode' => ['The barcode must be a valid EAN-13 format.']
                ]);
            }

            // Check if product is locked
            if ($product->is_locked && $product->isDirty() && !$product->isDirty('is_locked')) {
                throw ValidationException::withMessages([
                    'product' => ['This product is locked and cannot be edited. Reason: ' . ($product->lock_reason ?? 'No reason provided')]
                ]);
            }

            // Auto-set published_at when status changes to published
            if ($product->isDirty('status') && $product->status === 'published' && !$product->published_at) {
                $product->published_at = now();
            }

            // Increment version on update (not create)
            if ($product->exists && $product->isDirty() && !$product->isDirty('version')) {
                $product->version = ($product->version ?? 0) + 1;
            }
        });
    }

    /**
     * Configure activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status',
                'visibility',
                'short_description',
                'full_description',
                'technical_description',
                'meta_title',
                'meta_description',
                'meta_keywords',
                'published_at',
                'scheduled_publish_at',
                'scheduled_unpublish_at',
                'is_locked',
                'lock_reason',
                'version',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Product {$eventName}")
            ->useLogName('product');
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
     * Get validation rules for the custom attributes.
     *
     * @param  int|null  $productId  The product ID to exclude from unique checks (for updates)
     * @return array<string, string|array>
     */
    public static function getValidationRules(?int $productId = null): array
    {
        $tableName = (new static)->getTable();
        $skuRule = ['nullable', 'string', 'max:255'];
        if ($productId) {
            $skuRule[] = "unique:{$tableName},sku,{$productId},id";
        } else {
            $skuRule[] = "unique:{$tableName},sku";
        }

        return [
            'sku' => $skuRule,
            'barcode' => [
                'nullable',
                'string',
                'size:13',
                function ($attribute, $value, $fail) {
                    if ($value && !(new static)->validateEan13($value)) {
                        $fail('The barcode must be a valid EAN-13 format.');
                    }
                },
            ],
            'weight' => ['nullable', 'integer', 'min:0'],
            'length' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'width' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'height' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'manufacturer_name' => ['nullable', 'string', 'max:255'],
            'warranty_period' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'condition' => ['nullable', 'in:new,refurbished,used'],
            'origin_country' => ['nullable', 'string', 'size:2'],
            'custom_meta' => ['nullable', 'array'],
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
            'sku.unique' => 'The SKU has already been taken. Please use a unique SKU.',
            'sku.max' => 'The SKU must not exceed 255 characters.',
            'barcode.size' => 'The barcode must be exactly 13 characters.',
            'barcode.regex' => 'The barcode must be a valid EAN-13 format.',
            'weight.integer' => 'The weight must be an integer value.',
            'weight.min' => 'The weight must be at least 0 grams.',
            'length.numeric' => 'The length must be a valid number.',
            'length.min' => 'The length must be at least 0 cm.',
            'length.max' => 'The length must not exceed 999999.99 cm.',
            'width.numeric' => 'The width must be a valid number.',
            'width.min' => 'The width must be at least 0 cm.',
            'width.max' => 'The width must not exceed 999999.99 cm.',
            'height.numeric' => 'The height must be a valid number.',
            'height.min' => 'The height must be at least 0 cm.',
            'height.max' => 'The height must not exceed 999999.99 cm.',
            'manufacturer_name.max' => 'The manufacturer name must not exceed 255 characters.',
            'warranty_period.integer' => 'The warranty period must be an integer value.',
            'warranty_period.min' => 'The warranty period must be at least 0 months.',
            'warranty_period.max' => 'The warranty period must not exceed 65535 months.',
            'condition.in' => 'The condition must be one of: new, refurbished, or used.',
            'origin_country.size' => 'The origin country must be exactly 2 characters (ISO country code).',
            'custom_meta.array' => 'The custom meta must be a valid array.',
        ];
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
     * Get formatted dimensions.
     *
     * @return Attribute
     */
    protected function formattedDimensions(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->length || !$this->width || !$this->height) {
                    return null;
                }
                return sprintf(
                    '%s × %s × %s cm',
                    number_format((float) $this->length, 2),
                    number_format((float) $this->width, 2),
                    number_format((float) $this->height, 2)
                );
            },
        );
    }

    /**
     * Get formatted warranty period.
     *
     * @return Attribute
     */
    protected function formattedWarrantyPeriod(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->warranty_period) {
                    return null;
                }
                $years = floor($this->warranty_period / 12);
                $months = $this->warranty_period % 12;
                
                $parts = [];
                if ($years > 0) {
                    $parts[] = $years . ' ' . ($years === 1 ? 'year' : 'years');
                }
                if ($months > 0) {
                    $parts[] = $months . ' ' . ($months === 1 ? 'month' : 'months');
                }
                
                return implode(' and ', $parts) ?: null;
            },
        );
    }

    /**
     * Get volume in cubic centimeters.
     *
     * @return Attribute
     */
    protected function volume(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->length || !$this->width || !$this->height) {
                    return null;
                }
                return round($this->length * $this->width * $this->height, 2);
            },
        );
    }

    /**
     * Get formatted volume.
     *
     * @return Attribute
     */
    protected function formattedVolume(): Attribute
    {
        return Attribute::make(
            get: function () {
                $volume = $this->volume;
                return $volume ? number_format($volume, 2) . ' cm³' : null;
            },
        );
    }

    /**
     * Scope a query to filter by SKU.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $sku
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySku($query, string $sku)
    {
        return $query->where('sku', $sku);
    }

    /**
     * Scope a query to filter by barcode.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $barcode
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByBarcode($query, string $barcode)
    {
        return $query->where('barcode', $barcode);
    }

    /**
     * Scope a query to filter by manufacturer.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $manufacturer
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByManufacturer($query, string $manufacturer)
    {
        return $query->where('manufacturer_name', $manufacturer);
    }

    /**
     * Scope a query to filter by condition.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $condition
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCondition($query, string $condition)
    {
        return $query->where('condition', $condition);
    }

    /**
     * Scope a query to filter by origin country.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $countryCode
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByOriginCountry($query, string $countryCode)
    {
        return $query->where('origin_country', strtoupper($countryCode));
    }

    /**
     * Scope a query to filter products with warranty.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithWarranty($query)
    {
        return $query->whereNotNull('warranty_period')->where('warranty_period', '>', 0);
    }

    /**
     * Scope a query to filter products by weight range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int|null  $minWeight
     * @param  int|null  $maxWeight
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByWeightRange($query, ?int $minWeight = null, ?int $maxWeight = null)
    {
        if ($minWeight !== null) {
            $query->where('weight', '>=', $minWeight);
        }
        if ($maxWeight !== null) {
            $query->where('weight', '<=', $maxWeight);
        }
        return $query;
    }

    /**
     * Scope a query to filter products by warranty period range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int|null  $minMonths
     * @param  int|null  $maxMonths
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByWarrantyPeriodRange($query, ?int $minMonths = null, ?int $maxMonths = null)
    {
        if ($minMonths !== null) {
            $query->where('warranty_period', '>=', $minMonths);
        }
        if ($maxMonths !== null) {
            $query->where('warranty_period', '<=', $maxMonths);
        }
        return $query;
    }

    /**
     * Scope a query to search products by custom attributes.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $searchTerm
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearchCustomAttributes($query, string $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('sku', 'like', "%{$searchTerm}%")
              ->orWhere('barcode', 'like', "%{$searchTerm}%")
              ->orWhere('manufacturer_name', 'like', "%{$searchTerm}%");
        });
    }

    /**
     * Categories relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function categories()
    {
        return $this->belongsToMany(
            Category::class,
            config('lunar.database.table_prefix') . 'category_product',
            'product_id',
            'category_id'
        )->withPivot('position')
          ->withTimestamps()
          ->orderByPivot('position');
    }

    /**
     * Product attribute values relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attributeValues()
    {
        return $this->hasMany(ProductAttributeValue::class, 'product_id');
    }

    /**
     * Channel attribute values relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function channelAttributeValues()
    {
        return $this->hasMany(ChannelAttributeValue::class, 'product_id');
    }

    /**
     * Attributes relationship through attribute values.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function attributes()
    {
        return $this->belongsToMany(
            Attribute::class,
            config('lunar.database.table_prefix') . 'product_attribute_values',
            'product_id',
            'attribute_id'
        )->using(ProductAttributeValue::class)
          ->withPivot('value', 'numeric_value', 'text_value')
          ->withTimestamps();
    }

    /**
     * Reviews relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reviews()
    {
        return $this->hasMany(Review::class, 'product_id');
    }

    /**
     * Approved reviews relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function approvedReviews()
    {
        return $this->hasMany(Review::class, 'product_id')
            ->where('is_approved', true);
    }

    /**
     * Update rating cache for this product.
     *
     * @return void
     */
    public function updateRatingCache(): void
    {
        $reviews = $this->approvedReviews()->get();

        $totalReviews = $reviews->count();
        $averageRating = $totalReviews > 0 
            ? round($reviews->avg('rating'), 2) 
            : 0;

        $ratingCounts = [
            'rating_5_count' => $reviews->where('rating', 5)->count(),
            'rating_4_count' => $reviews->where('rating', 4)->count(),
            'rating_3_count' => $reviews->where('rating', 3)->count(),
            'rating_2_count' => $reviews->where('rating', 2)->count(),
            'rating_1_count' => $reviews->where('rating', 1)->count(),
        ];

        $this->updateQuietly([
            'average_rating' => $averageRating,
            'total_reviews' => $totalReviews,
            ...$ratingCounts,
        ]);
    }

    /**
     * Get rating distribution as percentages.
     *
     * @return array
     */
    public function getRatingDistribution(): array
    {
        $total = $this->total_reviews;
        if ($total === 0) {
            return [
                5 => 0,
                4 => 0,
                3 => 0,
                2 => 0,
                1 => 0,
            ];
        }

        return [
            5 => round(($this->rating_5_count / $total) * 100, 1),
            4 => round(($this->rating_4_count / $total) * 100, 1),
            3 => round(($this->rating_3_count / $total) * 100, 1),
            2 => round(($this->rating_2_count / $total) * 100, 1),
            1 => round(($this->rating_1_count / $total) * 100, 1),
        ];
    }

    /**
     * Product views relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function views()
    {
        return $this->hasMany(\App\Models\ProductView::class, 'product_id');
    }

    /**
     * Purchase associations where this product is the source.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function purchaseAssociations()
    {
        return $this->hasMany(\App\Models\ProductPurchaseAssociation::class, 'product_id');
    }

    /**
     * Purchase associations where this product is associated.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function associatedPurchaseAssociations()
    {
        return $this->hasMany(\App\Models\ProductPurchaseAssociation::class, 'associated_product_id');
    }

    /**
     * Recommendation rules where this product is the source.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function recommendationRules()
    {
        return $this->hasMany(\App\Models\RecommendationRule::class, 'source_product_id');
    }

    /**
     * Bundle relationship (if this product is a bundle).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function bundle()
    {
        return $this->hasOne(\App\Models\Bundle::class, 'product_id');
    }

    /**
     * Bundle items where this product is included.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function bundleItems()
    {
        return $this->hasMany(\App\Models\BundleItem::class, 'product_id');
    }

    /**
     * Stock notifications for this product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function stockNotifications()
    {
        return $this->hasMany(\App\Models\StockNotification::class, 'product_id');
    }

    /**
     * Product schedules relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function schedules()
    {
        return $this->hasMany(\App\Models\ProductSchedule::class, 'product_id');
    }

    /**
     * Product workflow relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function workflow()
    {
        return $this->hasOne(\App\Models\ProductWorkflow::class, 'product_id');
    }

    /**
     * Product workflow history relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function workflowHistory()
    {
        return $this->hasMany(\App\Models\ProductWorkflowHistory::class, 'product_id');
    }

    /**
     * Product analytics relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function analytics()
    {
        return $this->hasMany(\App\Models\ProductAnalytics::class, 'product_id');
    }

    /**
     * Abandoned carts relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function abandonedCarts()
    {
        return $this->hasMany(\App\Models\AbandonedCart::class, 'product_id');
    }

    /**
     * Price elasticity data relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function priceElasticity()
    {
        return $this->hasMany(\App\Models\PriceElasticity::class, 'product_id');
    }

    /**
     * A/B tests relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function abTests()
    {
        return $this->hasMany(\App\Models\ProductABTest::class, 'product_id');
    }

    /**
     * Variant attribute combinations relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variantAttributeCombinations()
    {
        return $this->hasMany(\App\Models\VariantAttributeCombination::class, 'product_id');
    }

    /**
     * Variant attribute dependencies relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function variantAttributeDependencies()
    {
        return $this->hasMany(\App\Models\VariantAttributeDependency::class, 'product_id');
    }

    /**
     * Get analytics summary for product.
     *
     * @param  \Carbon\Carbon|null  $startDate
     * @param  \Carbon\Carbon|null  $endDate
     * @return array
     */
    public function getAnalyticsSummary(?\Carbon\Carbon $startDate = null, ?\Carbon\Carbon $endDate = null): array
    {
        $service = app(\App\Services\ProductAnalyticsService::class);
        
        return [
            'views' => \App\Models\ProductView::where('product_id', $this->id)
                ->when($startDate, fn($q) => $q->where('viewed_at', '>=', $startDate))
                ->when($endDate, fn($q) => $q->where('viewed_at', '<=', $endDate))
                ->count(),
            'unique_views' => \App\Models\ProductView::where('product_id', $this->id)
                ->when($startDate, fn($q) => $q->where('viewed_at', '>=', $startDate))
                ->when($endDate, fn($q) => $q->where('viewed_at', '<=', $endDate))
                ->distinct('session_id')
                ->count('session_id'),
            'conversion_rate' => $service->getConversionRate($this, $startDate, $endDate),
            'revenue' => $service->getRevenue($this, $startDate, $endDate),
            'abandoned_cart_rate' => app(\App\Services\AbandonedCartService::class)
                ->getAbandonedCartRate($this, $startDate, $endDate),
        ];
    }

    /**
     * Active schedules relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activeSchedules()
    {
        return $this->hasMany(\App\Models\ProductSchedule::class, 'product_id')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Product questions relationship.
     */
    public function questions()
    {
        return $this->hasMany(\App\Models\ProductQuestion::class, 'product_id');
    }

    /**
     * Approved questions relationship.
     */
    public function approvedQuestions()
    {
        return $this->hasMany(\App\Models\ProductQuestion::class, 'product_id')
            ->where('status', 'approved')
            ->where('is_public', true);
    }

    /**
     * Get Q&A count for product cards.
     */
    public function getQaCountAttribute(): int
    {
        return $this->approvedQuestions()->count();
    }

    /**
     * Get answered Q&A count.
     */
    public function getAnsweredQaCountAttribute(): int
    {
        return $this->approvedQuestions()->where('is_answered', true)->count();
    }

    /**
     * Fit reviews relationship.
     */
    public function fitReviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\FitReview::class, 'product_id');
    }

    /**
     * Approved fit reviews relationship.
     */
    public function approvedFitReviews(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\FitReview::class, 'product_id')
            ->where('is_approved', true);
    }

    /**
     * Product badge assignments relationship.
     */
    public function badgeAssignments()
    {
        return $this->hasMany(\App\Models\ProductBadgeAssignment::class, 'product_id');
    }

    /**
     * Product badges relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function badges()
    {
        return $this->belongsToMany(
            \App\Models\ProductBadge::class,
            config('lunar.database.table_prefix') . 'product_badge_assignments',
            'product_id',
            'badge_id'
        )->using(\App\Models\ProductBadgeAssignment::class)
          ->withPivot(['assignment_type', 'rule_id', 'priority', 'display_position', 'visibility_rules', 'starts_at', 'expires_at', 'assigned_at', 'assigned_by', 'is_active'])
          ->withTimestamps();
    }

    /**
     * Get active badges for this product.
     *
     * @param  string|null  $context
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveBadges(?string $context = null)
    {
        return app(\App\Services\BadgeService::class)->getProductBadges($this, $context);
    }

    /**
     * Check if product is a bundle.
     *
     * @return bool
     */
    public function isBundle(): bool
    {
        return $this->is_bundle ?? false;
    }

    /**
     * Get accessories for this product (uses cross-sell type).
     *
     * @param  int  $limit
     * @return \Illuminate\Support\Collection
     */
    public function getAccessories(int $limit = 10): \Illuminate\Support\Collection
    {
        $service = app(\App\Services\ProductRelationService::class);
        return $service->getAccessories($this, $limit);
    }

    /**
     * Get replacement/alternative products (uses alternate type).
     *
     * @param  int  $limit
     * @return \Illuminate\Support\Collection
     */
    public function getReplacements(int $limit = 10): \Illuminate\Support\Collection
    {
        $service = app(\App\Services\ProductRelationService::class);
        return $service->getReplacements($this, $limit);
    }

    /**
     * Get all relations for this product grouped by type.
     *
     * @param  int  $limitPerType
     * @return array
     */
    public function getAllRelations(int $limitPerType = 10): array
    {
        $service = app(\App\Services\ProductRelationService::class);
        return $service->getAllRelations($this, $limitPerType);
    }

    /**
     * Get a custom meta field value.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function getCustomMeta(string $key, $default = null)
    {
        return $this->custom_meta[$key] ?? $default;
    }

    /**
     * Set a custom meta field value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return $this
     */
    public function setCustomMeta(string $key, $value)
    {
        $meta = $this->custom_meta ?? [];
        $meta[$key] = $value;
        $this->custom_meta = $meta;
        return $this;
    }

    /**
     * Remove a custom meta field.
     *
     * @param  string  $key
     * @return $this
     */
    public function removeCustomMeta(string $key)
    {
        $meta = $this->custom_meta ?? [];
        unset($meta[$key]);
        $this->custom_meta = $meta;
        return $this;
    }

    /**
     * Get all custom meta fields as an array.
     *
     * @return array
     */
    public function getAllCustomMeta(): array
    {
        return $this->custom_meta ?? [];
    }

    /**
     * Check if a custom meta field exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasCustomMeta(string $key): bool
    {
        return isset($this->custom_meta[$key]);
    }

    /**
     * Relationship to manufacturer (using Brand model if manufacturer_name matches brand name).
     * This is a convenience method - manufacturer_name is stored as a string.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|null
     */
    public function manufacturer()
    {
        // If manufacturer_name matches a brand name, return the relationship
        if ($this->manufacturer_name) {
            return $this->belongsTo(
                \Lunar\Models\Brand::class,
                'manufacturer_name',
                'name'
            );
        }
        return null;
    }

    /**
     * Check if product has all required dimensions.
     *
     * @return bool
     */
    public function hasDimensions(): bool
    {
        return $this->length !== null && $this->width !== null && $this->height !== null;
    }

    /**
     * Check if product has weight.
     *
     * @return bool
     */
    public function hasWeight(): bool
    {
        return $this->weight !== null && $this->weight > 0;
    }

    /**
     * Check if product has warranty.
     *
     * @return bool
     */
    public function hasWarranty(): bool
    {
        return $this->warranty_period !== null && $this->warranty_period > 0;
    }

    /**
     * Get shipping weight in kilograms.
     *
     * @return float|null
     */
    public function getWeightInKg(): ?float
    {
        return $this->weight ? round($this->weight / 1000, 3) : null;
    }

    /**
     * Get shipping weight in pounds.
     *
     * @return float|null
     */
    public function getWeightInLbs(): ?float
    {
        $kg = $this->getWeightInKg();
        return $kg ? round($kg * 2.20462, 3) : null;
    }

    /**
     * Get dimensions in a specific unit.
     *
     * @param  string  $unit  'cm' or 'in'
     * @return array|null  ['length' => float, 'width' => float, 'height' => float]
     */
    public function getDimensionsInUnit(string $unit = 'cm'): ?array
    {
        if (!$this->hasDimensions()) {
            return null;
        }

        if ($unit === 'in') {
            return [
                'length' => round($this->length / 2.54, 2),
                'width' => round($this->width / 2.54, 2),
                'height' => round($this->height / 2.54, 2),
            ];
        }

        return [
            'length' => (float) $this->length,
            'width' => (float) $this->width,
            'height' => (float) $this->height,
        ];
    }

    /**
     * Get formatted dimensions in a specific unit.
     *
     * @param  string  $unit  'cm' or 'in'
     * @return string|null
     */
    public function getFormattedDimensionsInUnit(string $unit = 'cm'): ?string
    {
        $dims = $this->getDimensionsInUnit($unit);
        if (!$dims) {
            return null;
        }

        return sprintf(
            '%s × %s × %s %s',
            number_format($dims['length'], 2),
            number_format($dims['width'], 2),
            number_format($dims['height'], 2),
            $unit
        );
    }

    /**
     * Scope a query to filter products by dimensions range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  float|null  $minVolume
     * @param  float|null  $maxVolume
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByVolumeRange($query, ?float $minVolume = null, ?float $maxVolume = null)
    {
        if ($minVolume !== null || $maxVolume !== null) {
            $query->whereRaw('(length * width * height) >= ?', [$minVolume ?? 0])
                  ->whereRaw('(length * width * height) <= ?', [$maxVolume ?? PHP_FLOAT_MAX]);
        }
        return $query;
    }

    /**
     * Scope a query to filter products that have complete shipping information.
     * 
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithShippingInfo($query)
    {
        return $query->whereNotNull('weight')
                     ->whereNotNull('length')
                     ->whereNotNull('width')
                     ->whereNotNull('height');
    }

    /**
     * Price matrices relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function priceMatrices()
    {
        return $this->hasMany(\App\Models\PriceMatrix::class, 'product_id');
    }

    /**
     * Active price matrices relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function activePriceMatrices()
    {
        return $this->hasMany(\App\Models\PriceMatrix::class, 'product_id')
            ->where('is_active', true);
    }

    /**
     * Price histories relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function priceHistories()
    {
        return $this->hasMany(\App\Models\PriceHistory::class, 'product_id');
    }

    /**
     * Size guides relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function sizeGuides()
    {
        return $this->belongsToMany(
            \App\Models\SizeGuide::class,
            config('lunar.database.table_prefix') . 'product_size_guide',
            'product_id',
            'size_guide_id'
        )->withPivot(['region', 'priority'])
          ->withTimestamps();
    }

    /**
     * Fit finder quizzes relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function fitFinderQuizzes()
    {
        return $this->belongsToMany(
            \App\Models\FitFinderQuiz::class,
            'product_fit_finder_quizzes',
            'product_id',
            'fit_finder_quiz_id'
        )->withTimestamps();
    }

    /**
     * Fit feedback relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fitFeedbacks()
    {
        return $this->hasMany(\App\Models\FitFeedback::class, 'product_id');
    }

    // ============================================
    // Product Core Model - Relationships
    // ============================================

    /**
     * Product versions relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function versions()
    {
        return $this->hasMany(ProductVersion::class, 'product_id');
    }

    /**
     * Parent version relationship (if this product is based on a version).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parentVersion()
    {
        return $this->belongsTo(ProductVersion::class, 'parent_version_id');
    }

    /**
     * User who locked the product.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function lockedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'locked_by');
    }

    // ============================================
    // Product Core Model - Scopes
    // ============================================

    /**
     * Scope a query to filter by visibility.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $visibility
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByVisibility($query, string $visibility)
    {
        return $query->where('visibility', $visibility);
    }

    /**
     * Scope a query to filter public products.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublic($query)
    {
        return $query->where('visibility', 'public');
    }

    /**
     * Scope a query to filter private products.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePrivate($query)
    {
        return $query->where('visibility', 'private');
    }

    /**
     * Scope a query to filter scheduled products.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeScheduled($query)
    {
        return $query->where('visibility', 'scheduled');
    }

    /**
     * Scope a query to filter published products.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at');
    }

    /**
     * Scope a query to filter draft products.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope a query to filter active products.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to filter archived products.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    /**
     * Scope a query to filter discontinued products.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDiscontinued($query)
    {
        return $query->where('status', 'discontinued');
    }

    /**
     * Scope a query to filter locked products.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLocked($query)
    {
        return $query->where('is_locked', true);
    }

    /**
     * Scope a query to filter unlocked products.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
    }

    /**
     * Scope a query to filter products scheduled for publish.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeScheduledForPublish($query)
    {
        return $query->whereNotNull('scheduled_publish_at')
            ->where('scheduled_publish_at', '<=', now());
    }

    /**
     * Scope a query to filter products scheduled for unpublish.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeScheduledForUnpublish($query)
    {
        return $query->whereNotNull('scheduled_unpublish_at')
            ->where('scheduled_unpublish_at', '<=', now());
    }

    // ============================================
    // Product Core Model - Business Logic Methods
    // ============================================

    /**
     * Publish the product.
     *
     * @param  \DateTimeInterface|string|null  $publishAt
     * @return $this
     */
    public function publish($publishAt = null)
    {
        if ($this->is_locked) {
            throw ValidationException::withMessages([
                'product' => ['Cannot publish locked product. Reason: ' . ($this->lock_reason ?? 'No reason provided')]
            ]);
        }

        $this->status = 'published';
        $this->visibility = 'public';
        $this->published_at = $publishAt ?? now();
        $this->scheduled_publish_at = null;
        $this->save();

        return $this;
    }

    /**
     * Unpublish the product.
     *
     * @return $this
     */
    public function unpublish()
    {
        $this->status = 'draft';
        $this->scheduled_unpublish_at = null;
        $this->save();

        return $this;
    }

    /**
     * Schedule product for future publish.
     *
     * @param  \DateTimeInterface|string  $publishAt
     * @return $this
     */
    public function schedulePublish($publishAt)
    {
        if ($this->is_locked) {
            throw ValidationException::withMessages([
                'product' => ['Cannot schedule locked product. Reason: ' . ($this->lock_reason ?? 'No reason provided')]
            ]);
        }

        $this->visibility = 'scheduled';
        $this->scheduled_publish_at = is_string($publishAt) ? \Carbon\Carbon::parse($publishAt) : $publishAt;
        $this->save();

        return $this;
    }

    /**
     * Schedule product for future unpublish.
     *
     * @param  \DateTimeInterface|string  $unpublishAt
     * @return $this
     */
    public function scheduleUnpublish($unpublishAt)
    {
        $this->scheduled_unpublish_at = is_string($unpublishAt) ? \Carbon\Carbon::parse($unpublishAt) : $unpublishAt;
        $this->save();

        return $this;
    }

    /**
     * Lock the product (prevent edits while live orders exist).
     *
     * @param  string|null  $reason
     * @param  \App\Models\User|null  $user
     * @return $this
     */
    public function lock(?string $reason = null, ?\App\Models\User $user = null)
    {
        $this->is_locked = true;
        $this->lock_reason = $reason ?? 'Product has live orders';
        $this->locked_by = $user?->id ?? auth()->id();
        $this->locked_at = now();
        $this->save();

        return $this;
    }

    /**
     * Unlock the product.
     *
     * @return $this
     */
    public function unlock()
    {
        $this->is_locked = false;
        $this->lock_reason = null;
        $this->locked_by = null;
        $this->locked_at = null;
        $this->save();

        return $this;
    }

    /**
     * Check if product is locked.
     *
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->is_locked === true;
    }

    /**
     * Check if product is published.
     *
     * @return bool
     */
    public function isPublished(): bool
    {
        return $this->status === 'published' && $this->published_at !== null;
    }

    /**
     * Check if product is scheduled for publish.
     *
     * @return bool
     */
    public function isScheduledForPublish(): bool
    {
        return $this->visibility === 'scheduled' 
            && $this->scheduled_publish_at !== null
            && $this->scheduled_publish_at->isFuture();
    }

    /**
     * Check if product is scheduled for unpublish.
     *
     * @return bool
     */
    public function isScheduledForUnpublish(): bool
    {
        return $this->scheduled_unpublish_at !== null
            && $this->scheduled_unpublish_at->isFuture();
    }

    /**
     * Create a version snapshot of the product.
     *
     * @param  string|null  $versionName
     * @param  string|null  $versionNotes
     * @return ProductVersion
     */
    public function createVersion(?string $versionName = null, ?string $versionNotes = null): ProductVersion
    {
        $versionNumber = $this->versions()->max('version_number') ?? 0;
        $versionNumber++;

        // Create snapshot of product data
        $productData = $this->getAttributes();
        unset($productData['id'], $productData['created_at'], $productData['updated_at']);

        return ProductVersion::create([
            'product_id' => $this->id,
            'version_number' => $versionNumber,
            'version_name' => $versionName,
            'version_notes' => $versionNotes,
            'product_data' => $productData,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Restore product to a specific version.
     *
     * @param  ProductVersion|int  $version
     * @return $this
     */
    public function restoreVersion($version)
    {
        if ($this->is_locked) {
            throw ValidationException::withMessages([
                'product' => ['Cannot restore locked product. Reason: ' . ($this->lock_reason ?? 'No reason provided')]
            ]);
        }

        if (is_int($version)) {
            $version = $this->versions()->where('version_number', $version)->firstOrFail();
        }

        if ($version->product_id !== $this->id) {
            throw ValidationException::withMessages([
                'version' => ['Version does not belong to this product']
            ]);
        }

        $version->restore();
        $this->parent_version_id = $version->id;
        $this->save();

        return $this;
    }

    /**
     * Duplicate/clone the product.
     *
     * @param  string|null  $newName
     * @return Product
     */
    public function duplicate(?string $newName = null): Product
    {
        if ($this->is_locked) {
            throw ValidationException::withMessages([
                'product' => ['Cannot duplicate locked product. Reason: ' . ($this->lock_reason ?? 'No reason provided')]
            ]);
        }

        $newProduct = $this->replicate();
        $newProduct->status = 'draft';
        $newProduct->visibility = 'private';
        $newProduct->published_at = null;
        $newProduct->scheduled_publish_at = null;
        $newProduct->scheduled_unpublish_at = null;
        $newProduct->is_locked = false;
        $newProduct->locked_by = null;
        $newProduct->locked_at = null;
        $newProduct->lock_reason = null;
        $newProduct->version = 1;
        $newProduct->parent_version_id = null;

        // Update name if provided
        if ($newName) {
            // Lunar uses attribute_data for translatable fields
            $attributeData = $newProduct->attribute_data ?? [];
            foreach ($attributeData as $locale => $data) {
                if (isset($data['name'])) {
                    $attributeData[$locale]['name'] = $newName;
                }
            }
            $newProduct->attribute_data = $attributeData;
        }

        $newProduct->save();

        // Duplicate relationships
        $this->duplicateRelationships($newProduct);

        return $newProduct;
    }

    /**
     * Duplicate product relationships to a new product.
     *
     * @param  Product  $newProduct
     * @return void
     */
    protected function duplicateRelationships(Product $newProduct): void
    {
        // Duplicate variants
        foreach ($this->variants as $variant) {
            $newVariant = $variant->replicate();
            $newVariant->product_id = $newProduct->id;
            $newVariant->save();
        }

        // Duplicate URLs (with new slug)
        foreach ($this->urls as $url) {
            $newUrl = $url->replicate();
            $newUrl->element_id = $newProduct->id;
            $newUrl->slug = $url->slug . '-copy-' . time();
            $newUrl->default = false;
            $newUrl->save();
        }

        // Duplicate collections
        $newProduct->collections()->sync($this->collections->pluck('id'));

        // Duplicate categories
        $newProduct->categories()->sync($this->categories->pluck('id'));

        // Duplicate tags
        $newProduct->tags()->sync($this->tags->pluck('id'));
    }

    /**
     * Product availability relationship.
     */
    public function availability(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\ProductAvailability::class, 'product_id')
            ->where('is_active', true)
            ->orderBy('priority', 'desc');
    }

    /**
     * All availability relationship (including inactive).
     */
    public function allAvailability(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\ProductAvailability::class, 'product_id')
            ->orderBy('priority', 'desc');
    }

    /**
     * Availability bookings relationship.
     */
    public function availabilityBookings(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\AvailabilityBooking::class, 'product_id');
    }

    /**
     * Availability rules relationship.
     */
    public function availabilityRules(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\AvailabilityRule::class, 'product_id')
            ->where('is_active', true)
            ->orderBy('priority', 'desc');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Model for pricing rules.
 */
class PricingRule extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'pricing_rules';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'handle',
        'description',
        'rule_type',
        'priority',
        'scope_type',
        'product_id',
        'product_variant_id',
        'category_id',
        'brand_id',
        'customer_group_id',
        'channel_id',
        'customer_id',
        'conditions',
        'rule_config',
        'starts_at',
        'ends_at',
        'is_stackable',
        'max_stack_depth',
        'is_active',
        'currency_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'conditions' => 'array',
        'rule_config' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_stackable' => 'boolean',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'max_stack_depth' => 'integer',
    ];

    /**
     * Product relationship.
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(\Lunar\Models\Product::class);
    }

    /**
     * Variant relationship.
     *
     * @return BelongsTo
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Category relationship.
     *
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(\Lunar\Models\Collection::class, 'category_id');
    }

    /**
     * Brand relationship.
     *
     * @return BelongsTo
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(\Lunar\Models\Brand::class);
    }

    /**
     * Customer group relationship.
     *
     * @return BelongsTo
     */
    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(\Lunar\Models\CustomerGroup::class);
    }

    /**
     * Channel relationship.
     *
     * @return BelongsTo
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(\Lunar\Models\Channel::class);
    }

    /**
     * Customer relationship.
     *
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'customer_id');
    }

    /**
     * Currency relationship.
     *
     * @return BelongsTo
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(\Lunar\Models\Currency::class);
    }

    /**
     * Check if rule is currently valid.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = Carbon::now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    /**
     * Check if rule applies to variant.
     *
     * @param  ProductVariant  $variant
     * @param  array  $context
     * @return bool
     */
    public function appliesTo(ProductVariant $variant, array $context = []): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        // Check scope
        if (!$this->matchesScope($variant, $context)) {
            return false;
        }

        // Check conditions
        if (!$this->matchesConditions($variant, $context)) {
            return false;
        }

        // Check currency
        if ($this->currency_id && isset($context['currency_id']) && $this->currency_id !== $context['currency_id']) {
            return false;
        }

        return true;
    }

    /**
     * Check if rule matches scope.
     *
     * @param  ProductVariant  $variant
     * @param  array  $context
     * @return bool
     */
    protected function matchesScope(ProductVariant $variant, array $context): bool
    {
        return match ($this->scope_type) {
            'global' => true,
            'product' => $this->product_id === $variant->product_id,
            'variant' => $this->product_variant_id === $variant->id,
            'category' => $variant->product && $variant->product->collections->contains('id', $this->category_id),
            'collection' => $variant->product && $variant->product->collections->contains('id', $this->category_id),
            'brand' => $variant->product && $variant->product->brand_id === $this->brand_id,
            'customer_group' => isset($context['customer_group_id']) && $this->customer_group_id === $context['customer_group_id'],
            'channel' => isset($context['channel_id']) && $this->channel_id === $context['channel_id'],
            'customer' => isset($context['customer_id']) && $this->customer_id === $context['customer_id'],
            default => false,
        };
    }

    /**
     * Check if rule matches conditions.
     *
     * @param  ProductVariant  $variant
     * @param  array  $context
     * @return bool
     */
    protected function matchesConditions(ProductVariant $variant, array $context): bool
    {
        $conditions = $this->conditions ?? [];

        if (empty($conditions)) {
            return true;
        }

        // Variant ID / Product ID
        if (isset($conditions['variant_ids']) && is_array($conditions['variant_ids'])) {
            if (!in_array($variant->id, $conditions['variant_ids'])) {
                return false;
            }
        }

        if (isset($conditions['product_ids']) && is_array($conditions['product_ids'])) {
            if (!$variant->product_id || !in_array($variant->product_id, $conditions['product_ids'])) {
                return false;
            }
        }

        // Category / Collection
        if (isset($conditions['category_ids']) && is_array($conditions['category_ids'])) {
            $productCategories = $variant->product?->collections->pluck('id')->toArray() ?? [];
            if (empty(array_intersect($conditions['category_ids'], $productCategories))) {
                return false;
            }
        }

        if (isset($conditions['collection_ids']) && is_array($conditions['collection_ids'])) {
            $productCollections = $variant->product?->collections->pluck('id')->toArray() ?? [];
            if (empty(array_intersect($conditions['collection_ids'], $productCollections))) {
                return false;
            }
        }

        // Customer group
        if (isset($conditions['customer_group_ids']) && is_array($conditions['customer_group_ids'])) {
            if (!isset($context['customer_group_id']) || !in_array($context['customer_group_id'], $conditions['customer_group_ids'])) {
                return false;
            }
        }

        // Individual customer
        if (isset($conditions['customer_ids']) && is_array($conditions['customer_ids'])) {
            if (!isset($context['customer_id']) || !in_array($context['customer_id'], $conditions['customer_ids'])) {
                return false;
            }
        }

        // Channel
        if (isset($conditions['channel_ids']) && is_array($conditions['channel_ids'])) {
            if (!isset($context['channel_id']) || !in_array($context['channel_id'], $conditions['channel_ids'])) {
                return false;
            }
        }

        // Country / Region
        if (isset($conditions['countries']) && is_array($conditions['countries'])) {
            $countryCode = $context['country_code'] ?? $context['shipping_country'] ?? null;
            if (!$countryCode || !in_array($countryCode, $conditions['countries'])) {
                return false;
            }
        }

        if (isset($conditions['regions']) && is_array($conditions['regions'])) {
            $region = $context['region'] ?? $context['shipping_region'] ?? null;
            if (!$region || !in_array($region, $conditions['regions'])) {
                return false;
            }
        }

        // Currency
        if (isset($conditions['currency_ids']) && is_array($conditions['currency_ids'])) {
            if (!isset($context['currency_id']) || !in_array($context['currency_id'], $conditions['currency_ids'])) {
                return false;
            }
        }

        // Quantity (tiers)
        if (isset($conditions['min_quantity']) && ($context['quantity'] ?? 1) < $conditions['min_quantity']) {
            return false;
        }

        if (isset($conditions['max_quantity']) && ($context['quantity'] ?? 1) > $conditions['max_quantity']) {
            return false;
        }

        if (isset($conditions['quantity_tiers']) && is_array($conditions['quantity_tiers'])) {
            $quantity = $context['quantity'] ?? 1;
            $matchesTier = false;
            foreach ($conditions['quantity_tiers'] as $tier) {
                $min = $tier['min'] ?? 0;
                $max = $tier['max'] ?? PHP_INT_MAX;
                if ($quantity >= $min && $quantity <= $max) {
                    $matchesTier = true;
                    break;
                }
            }
            if (!$matchesTier) {
                return false;
            }
        }

        // Date / Time
        if (isset($conditions['date_from'])) {
            $dateFrom = is_string($conditions['date_from']) ? \Carbon\Carbon::parse($conditions['date_from']) : $conditions['date_from'];
            if (now()->lt($dateFrom)) {
                return false;
            }
        }

        if (isset($conditions['date_to'])) {
            $dateTo = is_string($conditions['date_to']) ? \Carbon\Carbon::parse($conditions['date_to']) : $conditions['date_to'];
            if (now()->gt($dateTo)) {
                return false;
            }
        }

        if (isset($conditions['time_from'])) {
            $timeFrom = is_string($conditions['time_from']) ? \Carbon\Carbon::parse($conditions['time_from'])->format('H:i') : $conditions['time_from'];
            if (now()->format('H:i') < $timeFrom) {
                return false;
            }
        }

        if (isset($conditions['time_to'])) {
            $timeTo = is_string($conditions['time_to']) ? \Carbon\Carbon::parse($conditions['time_to'])->format('H:i') : $conditions['time_to'];
            if (now()->format('H:i') > $timeTo) {
                return false;
            }
        }

        if (isset($conditions['day_of_week']) && is_array($conditions['day_of_week'])) {
            $currentDay = now()->dayOfWeek; // 0 = Sunday, 6 = Saturday
            if (!in_array($currentDay, $conditions['day_of_week'])) {
                return false;
            }
        }

        // Cart subtotal
        if (isset($conditions['min_cart_subtotal']) && ($context['cart_subtotal'] ?? 0) < $conditions['min_cart_subtotal']) {
            return false;
        }

        if (isset($conditions['max_cart_subtotal']) && ($context['cart_subtotal'] ?? 0) > $conditions['max_cart_subtotal']) {
            return false;
        }

        // Stock level
        if (isset($conditions['min_stock_level']) && ($variant->stock ?? 0) < $conditions['min_stock_level']) {
            return false;
        }

        if (isset($conditions['max_stock_level']) && ($variant->stock ?? 0) > $conditions['max_stock_level']) {
            return false;
        }

        if (isset($conditions['stock_status']) && is_array($conditions['stock_status'])) {
            $stockStatus = $variant->stock_status ?? 'in_stock';
            if (!in_array($stockStatus, $conditions['stock_status'])) {
                return false;
            }
        }

        // First-time buyer flag
        if (isset($conditions['first_time_buyer']) && $conditions['first_time_buyer'] === true) {
            if (isset($context['customer_id'])) {
                $orderCount = \Lunar\Models\Order::where('user_id', $context['customer_id'])
                    ->whereIn('status', ['placed', 'completed'])
                    ->count();
                if ($orderCount > 0) {
                    return false;
                }
            } else {
                return false; // No customer = not first-time buyer
            }
        }

        if (isset($conditions['first_time_buyer']) && $conditions['first_time_buyer'] === false) {
            if (isset($context['customer_id'])) {
                $orderCount = \Lunar\Models\Order::where('user_id', $context['customer_id'])
                    ->whereIn('status', ['placed', 'completed'])
                    ->count();
                if ($orderCount === 0) {
                    return false;
                }
            }
        }

        // Product tags
        if (isset($conditions['product_tags']) && is_array($conditions['product_tags'])) {
            $productTags = $variant->product?->tags->pluck('id')->toArray() ?? [];
            if (empty(array_intersect($conditions['product_tags'], $productTags))) {
                return false;
            }
        }

        // Variant attributes
        if (isset($conditions['variant_attributes']) && is_array($conditions['variant_attributes'])) {
            foreach ($conditions['variant_attributes'] as $attributeHandle => $expectedValue) {
                // Check if variant has this attribute value
                $variantValue = $variant->variantOptions()
                    ->whereHas('option', function ($q) use ($attributeHandle) {
                        $q->where('handle', $attributeHandle);
                    })
                    ->first();
                
                if (!$variantValue || $variantValue->translateAttribute('name') !== $expectedValue) {
                    return false;
                }
            }
        }

        // Brand
        if (isset($conditions['brand_ids']) && is_array($conditions['brand_ids'])) {
            if (!$variant->product?->brand_id || !in_array($variant->product->brand_id, $conditions['brand_ids'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Scope active rules.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', Carbon::now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', Carbon::now());
            });
    }

    /**
     * Scope by rule type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeType($query, string $type)
    {
        return $query->where('rule_type', $type);
    }

    /**
     * Scope ordered by priority.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderedByPriority($query)
    {
        return $query->orderByDesc('priority')->orderBy('id');
    }

    /**
     * Scope stackable rules.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStackable($query)
    {
        return $query->where('is_stackable', true);
    }
}

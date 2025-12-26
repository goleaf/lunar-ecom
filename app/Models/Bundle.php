<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Lunar\Models\Product;

/**
 * Bundle model for product bundles and kits.
 */
class Bundle extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'bundles';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'name',
        'description',
        'slug',
        'sku',
        'pricing_type',
        'discount_amount',
        'bundle_price',
        'inventory_type',
        'stock',
        'min_quantity',
        'max_quantity',
        'is_active',
        'is_featured',
        'display_order',
        'image',
        'allow_customization',
        'show_individual_prices',
        'show_savings',
        'meta_title',
        'meta_description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'discount_amount' => 'integer',
        'bundle_price' => 'integer',
        'stock' => 'integer',
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'display_order' => 'integer',
        'allow_customization' => 'boolean',
        'show_individual_prices' => 'boolean',
        'show_savings' => 'boolean',
    ];

    /**
     * Computed attributes for API consumers.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'bundle_type',
        'discount_type',
        'discount_value',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($bundle) {
            if (empty($bundle->slug)) {
                $bundle->slug = Str::slug($bundle->name);
            }
        });
    }

    /**
     * Product relationship.
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Bundle items relationship.
     *
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(BundleItem::class)->orderBy('display_order');
    }

    /**
     * Required bundle items relationship.
     *
     * @return HasMany
     */
    public function requiredItems(): HasMany
    {
        return $this->hasMany(BundleItem::class)->where('is_required', true)->orderBy('display_order');
    }

    /**
     * Optional bundle items relationship.
     *
     * @return HasMany
     */
    public function optionalItems(): HasMany
    {
        return $this->hasMany(BundleItem::class)->where('is_required', false)->orderBy('display_order');
    }

    /**
     * Bundle prices relationship.
     *
     * @return HasMany
     */
    public function prices(): HasMany
    {
        return $this->hasMany(BundlePrice::class);
    }

    /**
     * Calculate total price of individual items.
     *
     * @param  \Lunar\Models\Currency|null  $currency
     * @param  int|null  $customerGroupId
     * @return int
     */
    public function calculateIndividualTotal(?\Lunar\Models\Currency $currency = null, ?int $customerGroupId = null): int
    {
        $currency = $currency ?? \Lunar\Facades\Currency::getDefault();
        $total = 0;

        foreach ($this->items as $item) {
            $variant = $item->productVariant ?? $item->product->variants->first();
            if (!$variant) {
                continue;
            }

            $pricing = \Lunar\Facades\Pricing::for($variant)
                ->currency($currency)
                ->customerGroup($customerGroupId)
                ->get();

            if ($pricing->matched?->price) {
                $itemPrice = $item->price_override ?? $pricing->matched->price->value;
                $itemDiscount = $item->discount_amount ?? 0;
                $total += ($itemPrice - $itemDiscount) * $item->quantity;
            }
        }

        return $total;
    }

    /**
     * Calculate bundle price.
     *
     * @param  \Lunar\Models\Currency|null  $currency
     * @param  int|null  $customerGroupId
     * @param  int  $quantity
     * @return int
     */
    public function calculatePrice(?\Lunar\Models\Currency $currency = null, ?int $customerGroupId = null, int $quantity = 1): int
    {
        $currency = $currency ?? \Lunar\Facades\Currency::getDefault();

        // Check for fixed bundle price
        if ($this->pricing_type === 'fixed' && $this->bundle_price) {
            // Check for price tier
            $priceTier = BundlePrice::where('bundle_id', $this->id)
                ->where('currency_id', $currency->id)
                ->where('customer_group_id', $customerGroupId)
                ->where('min_quantity', '<=', $quantity)
                ->where(function ($q) use ($quantity) {
                    $q->whereNull('max_quantity')
                      ->orWhere('max_quantity', '>=', $quantity);
                })
                ->orderByDesc('min_quantity')
                ->first();

            if ($priceTier) {
                return $priceTier->price * $quantity;
            }

            return $this->bundle_price * $quantity;
        }

        // Calculate from individual items
        $individualTotal = $this->calculateIndividualTotal($currency, $customerGroupId);

        if ($this->pricing_type === 'percentage' && $this->discount_amount) {
            $discount = ($individualTotal * $this->discount_amount) / 100;
            return ($individualTotal - $discount) * $quantity;
        }

        if ($this->pricing_type === 'fixed' && $this->discount_amount) {
            return ($individualTotal - $this->discount_amount) * $quantity;
        }

        return $individualTotal * $quantity;
    }

    /**
     * Calculate savings amount.
     *
     * @param  \Lunar\Models\Currency|null  $currency
     * @param  int|null  $customerGroupId
     * @return int
     */
    public function calculateSavings(?\Lunar\Models\Currency $currency = null, ?int $customerGroupId = null): int
    {
        $currency = $currency ?? \Lunar\Facades\Currency::getDefault();
        $individualTotal = $this->calculateIndividualTotal($currency, $customerGroupId);
        $bundlePrice = $this->calculatePrice($currency, $customerGroupId);
        
        return max(0, $individualTotal - $bundlePrice);
    }

    /**
     * Check if bundle is available.
     *
     * @param  int  $quantity
     * @return bool
     */
    public function isAvailable(int $quantity = 1): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->inventory_type === 'unlimited') {
            return true;
        }

        if ($this->inventory_type === 'independent') {
            return $this->stock >= $quantity;
        }

        // Component-based: check if all required items are available
        foreach ($this->requiredItems as $item) {
            $variant = $item->productVariant ?? $item->product->variants->first();
            if (!$variant || !$variant->hasSufficientStock($item->quantity * $quantity)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get available stock.
     *
     * @return int|null
     */
    public function getAvailableStock(): ?int
    {
        if ($this->inventory_type === 'unlimited') {
            return null;
        }

        if ($this->inventory_type === 'independent') {
            return $this->stock;
        }

        // Component-based: return minimum available stock from required items
        $minStock = null;
        foreach ($this->requiredItems as $item) {
            $variant = $item->productVariant ?? $item->product->variants->first();
            if ($variant) {
                $available = intval($variant->stock / $item->quantity);
                if ($minStock === null || $available < $minStock) {
                    $minStock = $available;
                }
            }
        }

        return $minStock ?? 0;
    }

    /**
     * Scope to get active bundles.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get featured bundles.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Determine if the bundle uses fixed pricing.
     */
    public function isFixed(): bool
    {
        return $this->pricing_type === 'fixed';
    }

    /**
     * Determine if the bundle pricing is calculated dynamically.
     */
    public function isDynamic(): bool
    {
        return $this->pricing_type === 'dynamic';
    }

    /**
     * Accessor for JS components expecting bundle_type.
     */
    public function getBundleTypeAttribute(): string
    {
        return $this->isDynamic() ? 'dynamic' : 'fixed';
    }

    /**
     * Accessor for discount type used by UI helpers.
     */
    public function getDiscountTypeAttribute(): string
    {
        return $this->pricing_type === 'percentage' ? 'percentage' : 'fixed';
    }

    /**
     * Accessor for discount value compatibility.
     */
    public function getDiscountValueAttribute(): ?int
    {
        return $this->discount_amount;
    }

    /**
     * Increment view counter when the column exists.
     */
    public function incrementView(): void
    {
        $this->safeIncrementColumn('view_count');
    }

    /**
     * Increment add to cart counter when the column exists.
     */
    public function incrementAddToCart(): void
    {
        $this->safeIncrementColumn('add_to_cart_count');
    }

    /**
     * Increment purchase counter when the column exists.
     */
    public function incrementPurchase(): void
    {
        $this->safeIncrementColumn('purchase_count');
    }

    /**
     * Increment a column only if it exists on the table.
     */
    protected function safeIncrementColumn(string $column): void
    {
        static $columnCache = [];

        $table = $this->getTable();
        if (!isset($columnCache[$table])) {
            $columnCache[$table] = Schema::getColumnListing($table);
        }

        if (in_array($column, $columnCache[$table] ?? [], true)) {
            $this->increment($column);
        }
    }
}

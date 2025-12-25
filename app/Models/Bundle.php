<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Lunar\Models\Product;

/**
 * Bundle model for product bundles and kits.
 */
class Bundle extends Model
{
    use HasFactory;

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
        'bundle_type',
        'discount_type',
        'discount_value',
        'min_items',
        'max_items',
        'category_id',
        'show_individual_prices',
        'show_savings',
        'allow_individual_returns',
        'view_count',
        'add_to_cart_count',
        'purchase_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_items' => 'integer',
        'max_items' => 'integer',
        'show_individual_prices' => 'boolean',
        'show_savings' => 'boolean',
        'allow_individual_returns' => 'boolean',
        'view_count' => 'integer',
        'add_to_cart_count' => 'integer',
        'purchase_count' => 'integer',
    ];

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
     * Category relationship (for "Build Your Own" bundles).
     *
     * @return BelongsTo
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Category::class);
    }

    /**
     * Bundle items relationship.
     *
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(BundleItem::class, 'bundle_id')->orderBy('display_order');
    }

    /**
     * Required items (non-optional).
     *
     * @return HasMany
     */
    public function requiredItems(): HasMany
    {
        return $this->items()->where('is_optional', false);
    }

    /**
     * Optional items.
     *
     * @return HasMany
     */
    public function optionalItems(): HasMany
    {
        return $this->items()->where('is_optional', true);
    }

    /**
     * Bundle analytics relationship.
     *
     * @return HasMany
     */
    public function analytics(): HasMany
    {
        return $this->hasMany(BundleAnalytic::class, 'bundle_id');
    }

    /**
     * Scope to get fixed bundles.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFixed($query)
    {
        return $query->where('bundle_type', 'fixed');
    }

    /**
     * Scope to get dynamic bundles.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDynamic($query)
    {
        return $query->where('bundle_type', 'dynamic');
    }

    /**
     * Check if bundle is fixed.
     *
     * @return bool
     */
    public function isFixed(): bool
    {
        return $this->bundle_type === 'fixed';
    }

    /**
     * Check if bundle is dynamic.
     *
     * @return bool
     */
    public function isDynamic(): bool
    {
        return $this->bundle_type === 'dynamic';
    }

    /**
     * Increment view count.
     *
     * @return void
     */
    public function incrementView(): void
    {
        $this->increment('view_count');
    }

    /**
     * Increment add to cart count.
     *
     * @return void
     */
    public function incrementAddToCart(): void
    {
        $this->increment('add_to_cart_count');
    }

    /**
     * Increment purchase count.
     *
     * @return void
     */
    public function incrementPurchase(): void
    {
        $this->increment('purchase_count');
    }
}

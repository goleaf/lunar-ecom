<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Product;
use Lunar\Models\ProductVariant;

/**
 * BundleItem model for bundle contents.
 */
class BundleItem extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'bundle_items';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'bundle_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'is_optional',
        'custom_price_override',
        'display_order',
        'group_name',
        'group_min_selection',
        'group_max_selection',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'is_optional' => 'boolean',
        'custom_price_override' => 'decimal:2',
        'display_order' => 'integer',
        'group_min_selection' => 'integer',
        'group_max_selection' => 'integer',
    ];

    /**
     * Bundle relationship.
     *
     * @return BelongsTo
     */
    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class);
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
     * Product variant relationship.
     *
     * @return BelongsTo
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Get the price for this item in the bundle.
     *
     * @return int  Price in cents
     */
    public function getPrice(): int
    {
        if ($this->custom_price_override) {
            return (int) ($this->custom_price_override * 100);
        }

        $variant = $this->productVariant;
        if (!$variant) {
            // Get default variant
            $variant = $this->product->variants->first();
        }

        if (!$variant) {
            return 0;
        }

        // Get the base price from variant
        $price = $variant->base_price ?? $variant->price ?? 0;
        
        return (int) $price;
    }

    /**
     * Scope to get items by group.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $groupName
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInGroup($query, string $groupName)
    {
        return $query->where('group_name', $groupName);
    }
}

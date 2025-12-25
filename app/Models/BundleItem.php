<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Product;
use Lunar\Models\ProductVariant;

/**
 * BundleItem model for bundle components.
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
        'min_quantity',
        'max_quantity',
        'is_required',
        'is_default',
        'price_override',
        'discount_amount',
        'display_order',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'is_required' => 'boolean',
        'is_default' => 'boolean',
        'price_override' => 'integer',
        'discount_amount' => 'integer',
        'display_order' => 'integer',
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
     * Get the variant to use (specific variant or first variant of product).
     *
     * @return ProductVariant|null
     */
    public function getVariant(): ?ProductVariant
    {
        if ($this->product_variant_id) {
            return $this->productVariant;
        }

        return $this->product->variants->first();
    }

    /**
     * Get item price.
     *
     * @param  \Lunar\Models\Currency|null  $currency
     * @param  int|null  $customerGroupId
     * @return int
     */
    public function getPrice(?\Lunar\Models\Currency $currency = null, ?int $customerGroupId = null): int
    {
        $currency = $currency ?? \Lunar\Facades\Currency::getDefault();
        $variant = $this->getVariant();

        if (!$variant) {
            return 0;
        }

        // Use price override if set
        if ($this->price_override) {
            $price = $this->price_override;
        } else {
            $pricing = \Lunar\Facades\Pricing::for($variant)
                ->currency($currency)
                ->customerGroup($customerGroupId)
                ->get();

            $price = $pricing->matched?->price->value ?? 0;
        }

        // Apply discount
        if ($this->discount_amount) {
            $price -= $this->discount_amount;
        }

        return max(0, $price);
    }

    /**
     * Check if item is available.
     *
     * @param  int  $bundleQuantity
     * @return bool
     */
    public function isAvailable(int $bundleQuantity = 1): bool
    {
        $variant = $this->getVariant();
        if (!$variant) {
            return false;
        }

        $requiredQuantity = $this->quantity * $bundleQuantity;
        return $variant->hasSufficientStock($requiredQuantity);
    }
}

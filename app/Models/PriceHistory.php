<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PriceMatrix;
use Lunar\Models\Currency;
use Lunar\Models\CustomerGroup;

/**
 * PriceHistory model for tracking price changes.
 */
class PriceHistory extends Model
{
    use HasFactory;

    protected $table = 'lunar_price_histories';

    protected $fillable = [
        'product_id',
        'variant_id',
        'price_matrix_id',
        'currency_id',
        'customer_group_id',
        'region',
        'old_price',
        'new_price',
        'quantity_tier',
        'change_type',
        'change_reason',
        'changed_by',
    ];

    protected $casts = [
        'old_price' => 'integer',
        'new_price' => 'integer',
        'quantity_tier' => 'integer',
    ];

    /**
     * Change types
     */
    const TYPE_CREATED = 'created';
    const TYPE_UPDATED = 'updated';
    const TYPE_DELETED = 'deleted';
    const TYPE_APPROVED = 'approved';
    const TYPE_REJECTED = 'rejected';

    /**
     * Get the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the variant.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Get the price matrix.
     */
    public function priceMatrix(): BelongsTo
    {
        return $this->belongsTo(PriceMatrix::class, 'price_matrix_id');
    }

    /**
     * Get the currency.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the customer group.
     */
    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class);
    }

    /**
     * Get the user who made the change.
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'changed_by');
    }

    /**
     * Scope by product.
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope by variant.
     */
    public function scopeForVariant($query, int $variantId)
    {
        return $query->where('variant_id', $variantId);
    }

    /**
     * Scope by change type.
     */
    public function scopeByChangeType($query, string $type)
    {
        return $query->where('change_type', $type);
    }

    /**
     * Get price change percentage.
     */
    public function getPriceChangePercent(): ?float
    {
        if (!$this->old_price || $this->old_price === 0) {
            return null;
        }

        $change = $this->new_price - $this->old_price;
        return round(($change / $this->old_price) * 100, 2);
    }
}

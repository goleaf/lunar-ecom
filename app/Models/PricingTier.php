<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingTier extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'pricing_tiers';
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'price_matrix_id',
        'tier_name',
        'min_quantity',
        'max_quantity',
        'price',
        'price_adjustment',
        'percentage_discount',
        'pricing_type',
        'display_order',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'price' => 'decimal:2',
        'price_adjustment' => 'decimal:2',
        'percentage_discount' => 'integer',
        'display_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the price matrix.
     */
    public function priceMatrix(): BelongsTo
    {
        return $this->belongsTo(PriceMatrix::class, 'price_matrix_id');
    }

    /**
     * Check if quantity falls within this tier.
     */
    public function matchesQuantity(int $quantity): bool
    {
        if ($quantity < $this->min_quantity) {
            return false;
        }

        if ($this->max_quantity && $quantity > $this->max_quantity) {
            return false;
        }

        return true;
    }
}



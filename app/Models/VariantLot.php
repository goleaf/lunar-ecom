<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model for tracking variant lots/batches.
 */
class VariantLot extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'variant_lots';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_variant_id',
        'lot_number',
        'batch_number',
        'manufacture_date',
        'expiry_date',
        'quantity',
        'quantity_allocated',
        'quantity_sold',
        'notes',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
        'quantity' => 'integer',
        'quantity_allocated' => 'integer',
        'quantity_sold' => 'integer',
        'metadata' => 'array',
    ];

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
     * Get available quantity.
     *
     * @return int
     */
    public function getAvailableQuantityAttribute(): int
    {
        return $this->quantity - $this->quantity_allocated - $this->quantity_sold;
    }

    /**
     * Check if lot is expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    /**
     * Allocate quantity.
     *
     * @param  int  $quantity
     * @return bool
     */
    public function allocate(int $quantity): bool
    {
        if ($this->available_quantity < $quantity) {
            return false;
        }

        return $this->update([
            'quantity_allocated' => $this->quantity_allocated + $quantity,
        ]);
    }

    /**
     * Mark as sold.
     *
     * @param  int  $quantity
     * @return bool
     */
    public function markAsSold(int $quantity): bool
    {
        if ($this->quantity_allocated < $quantity) {
            return false;
        }

        return $this->update([
            'quantity_allocated' => $this->quantity_allocated - $quantity,
            'quantity_sold' => $this->quantity_sold + $quantity,
        ]);
    }
}



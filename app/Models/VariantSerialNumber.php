<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model for tracking variant serial numbers.
 */
class VariantSerialNumber extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'variant_serial_numbers';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_variant_id',
        'order_line_id',
        'serial_number',
        'status',
        'notes',
        'allocated_at',
        'sold_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'allocated_at' => 'datetime',
        'sold_at' => 'datetime',
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
     * Order line relationship.
     *
     * @return BelongsTo
     */
    public function orderLine(): BelongsTo
    {
        return $this->belongsTo(\Lunar\Models\OrderLine::class);
    }

    /**
     * Scope available serial numbers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * Allocate serial number to order.
     *
     * @param  int  $orderLineId
     * @return bool
     */
    public function allocate(int $orderLineId): bool
    {
        return $this->update([
            'order_line_id' => $orderLineId,
            'status' => 'allocated',
            'allocated_at' => now(),
        ]);
    }

    /**
     * Mark as sold.
     *
     * @return bool
     */
    public function markAsSold(): bool
    {
        return $this->update([
            'status' => 'sold',
            'sold_at' => now(),
        ]);
    }
}



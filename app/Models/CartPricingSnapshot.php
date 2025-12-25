<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Cart;

/**
 * Cart Pricing Snapshot model.
 * 
 * Stores pricing snapshots for audit trail purposes.
 * Optional - snapshots can also be calculated on-the-fly.
 */
class CartPricingSnapshot extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'cart_pricing_snapshots';
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'cart_id',
        'snapshot_type',
        'pricing_data',
        'trigger',
        'pricing_version',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'pricing_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Cart relationship.
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }
}


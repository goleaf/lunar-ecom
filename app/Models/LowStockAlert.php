<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * LowStockAlert model for tracking low stock alerts.
 */
class LowStockAlert extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'low_stock_alerts';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'inventory_level_id',
        'product_variant_id',
        'warehouse_id',
        'current_quantity',
        'reorder_point',
        'is_resolved',
        'resolved_at',
        'resolved_by',
        'notification_sent',
        'notification_sent_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'current_quantity' => 'integer',
        'reorder_point' => 'integer',
        'is_resolved' => 'boolean',
        'notification_sent' => 'boolean',
        'resolved_at' => 'datetime',
        'notification_sent_at' => 'datetime',
    ];

    /**
     * Inventory level relationship.
     *
     * @return BelongsTo
     */
    public function inventoryLevel(): BelongsTo
    {
        return $this->belongsTo(InventoryLevel::class);
    }

    /**
     * Product variant relationship.
     *
     * @return BelongsTo
     */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(\Lunar\Models\ProductVariant::class);
    }

    /**
     * Warehouse relationship.
     *
     * @return BelongsTo
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Resolver relationship.
     *
     * @return BelongsTo
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'resolved_by');
    }

    /**
     * Scope to get unresolved alerts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    /**
     * Scope to get alerts that need notification.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNeedsNotification($query)
    {
        return $query->where('is_resolved', false)
            ->where('notification_sent', false);
    }
}


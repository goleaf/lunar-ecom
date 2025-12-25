<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * StockMovement model for tracking all stock changes.
 */
class StockMovement extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'stock_movements';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_variant_id',
        'warehouse_id',
        'inventory_level_id',
        'type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'reference_type',
        'reference_id',
        'reference_number',
        'reason',
        'notes',
        'created_by',
        'movement_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
        'movement_date' => 'datetime',
    ];

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
     * Inventory level relationship.
     *
     * @return BelongsTo
     */
    public function inventoryLevel(): BelongsTo
    {
        return $this->belongsTo(InventoryLevel::class);
    }

    /**
     * Creator relationship.
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Reference relationship (polymorphic).
     *
     * @return MorphTo
     */
    public function reference(): MorphTo
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    /**
     * Scope to get movements by type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get movements for a product variant.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $productVariantId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForVariant($query, int $productVariantId)
    {
        return $query->where('product_variant_id', $productVariantId);
    }

    /**
     * Scope to get movements for a warehouse.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $warehouseId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    /**
     * Scope to get recent movements.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('movement_date', '>=', now()->subDays($days));
    }

    /**
     * Check if movement is an increase (incoming stock).
     *
     * @return bool
     */
    public function isIncrease(): bool
    {
        return in_array($this->type, ['in', 'return', 'adjustment']) && $this->quantity > 0;
    }

    /**
     * Check if movement is a decrease (outgoing stock).
     *
     * @return bool
     */
    public function isDecrease(): bool
    {
        return in_array($this->type, ['out', 'sale', 'damage', 'loss', 'reservation']) || $this->quantity < 0;
    }
}


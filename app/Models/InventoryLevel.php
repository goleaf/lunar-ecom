<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * InventoryLevel model for tracking stock per warehouse.
 */
class InventoryLevel extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'inventory_levels';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_variant_id',
        'warehouse_id',
        'quantity',              // On-hand quantity
        'reserved_quantity',     // Reserved for orders
        'incoming_quantity',     // Expected incoming stock
        'damaged_quantity',      // Damaged/unusable stock
        'preorder_quantity',     // Preorder reservations
        'backorder_limit',       // Warehouse-specific backorder limit
        'reorder_point',         // Alert when quantity < this
        'safety_stock_level',    // Minimum stock to maintain
        'reorder_quantity',      // Suggested order quantity
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',           // On-hand quantity
        'reserved_quantity' => 'integer',   // Reserved quantity
        'incoming_quantity' => 'integer',   // Incoming quantity
        'damaged_quantity' => 'integer',    // Damaged quantity
        'preorder_quantity' => 'integer',   // Preorder quantity
        'backorder_limit' => 'integer',    // Backorder limit
        'reorder_point' => 'integer',       // Reorder point
        'safety_stock_level' => 'integer',  // Safety stock level
        'reorder_quantity' => 'integer',    // Reorder quantity
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Update status based on quantity
        static::saving(function ($inventoryLevel) {
            $inventoryLevel->updateStatus();
        });
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
     * Inventory transactions relationship.
     *
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'product_variant_id', 'product_variant_id')
            ->where('warehouse_id', $this->warehouse_id);
    }

    /**
     * Stock movements relationship.
     *
     * @return HasMany
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'inventory_level_id');
    }

    /**
     * Stock reservations relationship.
     *
     * @return HasMany
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(StockReservation::class);
    }

    /**
     * Low stock alerts relationship.
     *
     * @return HasMany
     */
    public function lowStockAlerts(): HasMany
    {
        return $this->hasMany(LowStockAlert::class);
    }

    /**
     * Get available quantity (on-hand - reserved - damaged).
     * 
     * Available = On-hand quantity - Reserved quantity - Damaged quantity
     *
     * @return int
     */
    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->reserved_quantity - $this->damaged_quantity);
    }
    
    /**
     * Get on-hand quantity (usable stock).
     * 
     * On-hand = Total quantity - Damaged quantity
     *
     * @return int
     */
    public function getOnHandQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->damaged_quantity);
    }

    /**
     * Get total quantity (on-hand + incoming).
     * 
     * Total = On-hand quantity + Incoming quantity
     *
     * @return int
     */
    public function getTotalQuantityAttribute(): int
    {
        return $this->quantity + $this->incoming_quantity;
    }
    
    /**
     * Get sellable quantity (available + preorder capacity).
     * 
     * Sellable = Available quantity + Preorder capacity
     *
     * @return int
     */
    public function getSellableQuantityAttribute(): int
    {
        return $this->available_quantity + $this->preorder_quantity;
    }
    
    /**
     * Get net quantity (on-hand - reserved).
     * 
     * Net = On-hand quantity - Reserved quantity
     *
     * @return int
     */
    public function getNetQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->reserved_quantity);
    }

    /**
     * Check if stock is low (below reorder point).
     *
     * @return bool
     */
    public function isLowStock(): bool
    {
        return $this->available_quantity < $this->reorder_point;
    }
    
    /**
     * Check if stock is below safety stock level.
     *
     * @return bool
     */
    public function isBelowSafetyStock(): bool
    {
        return $this->available_quantity < $this->safety_stock_level;
    }
    
    /**
     * Check if stock is at or above safety stock level.
     *
     * @return bool
     */
    public function isAtOrAboveSafetyStock(): bool
    {
        return $this->available_quantity >= $this->safety_stock_level;
    }

    /**
     * Check if out of stock.
     *
     * @return bool
     */
    public function isOutOfStock(): bool
    {
        return $this->available_quantity <= 0;
    }

    /**
     * Update status based on quantity and availability.
     *
     * @return void
     */
    public function updateStatus(): void
    {
        $available = $this->available_quantity;
        
        if ($available <= 0) {
            // Check if backorder is allowed
            $variant = $this->productVariant;
            if ($variant && $variant->backorder_allowed) {
                $this->status = 'backorder';
            } else {
                $this->status = 'out_of_stock';
            }
        } elseif ($this->isLowStock()) {
            $this->status = 'low_stock';
        } elseif ($this->preorder_quantity > 0 && $available <= $this->preorder_quantity) {
            $this->status = 'preorder';
        } else {
            $this->status = 'in_stock';
        }
    }

    /**
     * Scope to get low stock items.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity', '<', 'reorder_point');
    }

    /**
     * Scope to get out of stock items.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOutOfStock($query)
    {
        return $query->whereRaw('(quantity - reserved_quantity) <= 0');
    }

    /**
     * Scope to get in stock items.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInStock($query)
    {
        return $query->whereRaw('(quantity - reserved_quantity) > 0');
    }
}

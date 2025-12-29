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
        'type',                      // Movement type
        'quantity',                  // Quantity change (positive for in, negative for out)
        'quantity_before',           // On-hand quantity before
        'quantity_after',            // On-hand quantity after
        'reserved_quantity_before',   // Reserved quantity before
        'reserved_quantity_after',   // Reserved quantity after
        'available_quantity_before', // Available quantity before
        'available_quantity_after', // Available quantity after
        'reference_type',            // Reference model type
        'reference_id',               // Reference model ID
        'reference_number',           // Reference number (order #, etc.)
        'reason',                    // Reason for movement
        'notes',                     // Additional notes
        'metadata',                  // JSON metadata
        'created_by',                // Actor (user ID)
        'actor_type',                // Actor type (user, system, api, import)
        'actor_identifier',          // Actor identifier (for system/API)
        'ip_address',                // IP address for audit trail
        'movement_date',             // Timestamp of movement
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
        'reserved_quantity_before' => 'integer',
        'reserved_quantity_after' => 'integer',
        'available_quantity_before' => 'integer',
        'available_quantity_after' => 'integer',
        'movement_date' => 'datetime',
        'metadata' => 'array',
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
        return in_array($this->type, ['in', 'return', 'manual_adjustment', 'import', 'correction']) 
            && $this->quantity > 0;
    }

    /**
     * Check if movement is a decrease (outgoing stock).
     *
     * @return bool
     */
    public function isDecrease(): bool
    {
        return in_array($this->type, ['out', 'sale', 'damage', 'loss', 'reservation', 'transfer']) 
            || $this->quantity < 0;
    }

    /**
     * Get actor name (user name, system, API, etc.).
     *
     * @return string
     */
    public function getActorNameAttribute(): string
    {
        if ($this->actor_type === 'user' && $this->creator) {
            return $this->creator->name ?? 'Unknown User';
        }

        return match($this->actor_type) {
            'system' => 'System',
            'staff' => $this->actor_identifier ? "Staff #{$this->actor_identifier}" : 'Staff',
            'api' => 'API',
            'import' => 'Import',
            default => 'Unknown',
        };
    }

    /**
     * Scope to get movements by actor type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $actorType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByActorType($query, string $actorType)
    {
        return $query->where('actor_type', $actorType);
    }

    /**
     * Scope to get movements by actor (user).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByActor($query, int $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Scope to get movements in date range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \DateTimeInterface|string  $from
     * @param  \DateTimeInterface|string  $to
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('movement_date', [$from, $to]);
    }
}


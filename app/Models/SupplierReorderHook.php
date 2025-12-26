<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Supplier Reorder Hook model.
 * 
 * Manages supplier reorder automation:
 * - Integration with supplier systems
 * - Automated reorder creation
 * - Reorder tracking
 */
class SupplierReorderHook extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'supplier_reorder_hooks';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_variant_id',
        'warehouse_id',
        'supplier_name',
        'supplier_code',
        'supplier_sku',
        'supplier_url',
        'supplier_config',
        'reorder_point',
        'reorder_quantity',
        'min_order_quantity',
        'max_order_quantity',
        'unit_cost',
        'trigger_type',
        'trigger_conditions',
        'integration_type',
        'integration_config',
        'is_active',
        'last_reorder_at',
        'reorder_count',
        'last_reorder_response',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'supplier_config' => 'array',
        'trigger_conditions' => 'array',
        'integration_config' => 'array',
        'reorder_point' => 'integer',
        'reorder_quantity' => 'integer',
        'min_order_quantity' => 'integer',
        'max_order_quantity' => 'integer',
        'unit_cost' => 'decimal:2',
        'is_active' => 'boolean',
        'last_reorder_at' => 'datetime',
        'reorder_count' => 'integer',
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
     * Scope active hooks.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by trigger type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeTriggerType($query, string $type)
    {
        return $query->where('trigger_type', $type);
    }

    /**
     * Scope by integration type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIntegrationType($query, string $type)
    {
        return $query->where('integration_type', $type);
    }

    /**
     * Check if hook should trigger reorder.
     *
     * @param  int  $currentQuantity
     * @return bool
     */
    public function shouldTriggerReorder(int $currentQuantity): bool
    {
        if (!$this->is_active) {
            return false;
        }

        return match($this->trigger_type) {
            'auto_on_low_stock' => $currentQuantity <= $this->reorder_point,
            'auto_on_out_of_stock' => $currentQuantity <= 0,
            'manual', 'scheduled' => false,
            default => false,
        };
    }
}



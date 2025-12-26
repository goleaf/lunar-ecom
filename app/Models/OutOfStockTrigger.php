<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Out of Stock Trigger model.
 * 
 * Tracks when variants go out of stock:
 * - Trigger timestamp
 * - Recovery tracking
 * - Automation actions
 */
class OutOfStockTrigger extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'out_of_stock_triggers';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_variant_id',
        'warehouse_id',
        'triggered_at',
        'quantity_before',
        'quantity_after',
        'trigger_reason',
        'is_recovered',
        'recovered_at',
        'recovery_quantity',
        'recovery_reason',
        'automation_triggered',
        'automation_actions',
        'notification_sent',
        'notification_sent_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'triggered_at' => 'datetime',
        'recovered_at' => 'datetime',
        'notification_sent_at' => 'datetime',
        'automation_actions' => 'array',
        'is_recovered' => 'boolean',
        'automation_triggered' => 'boolean',
        'notification_sent' => 'boolean',
        'quantity_before' => 'integer',
        'quantity_after' => 'integer',
        'recovery_quantity' => 'integer',
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
     * Scope unrecovered triggers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnrecovered($query)
    {
        return $query->where('is_recovered', false);
    }

    /**
     * Scope recovered triggers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecovered($query)
    {
        return $query->where('is_recovered', true);
    }

    /**
     * Mark as recovered.
     *
     * @param  int  $quantity
     * @param  string|null  $reason
     * @return void
     */
    public function markRecovered(int $quantity, ?string $reason = null): void
    {
        $this->update([
            'is_recovered' => true,
            'recovered_at' => now(),
            'recovery_quantity' => $quantity,
            'recovery_reason' => $reason ?? 'restock',
        ]);
    }
}



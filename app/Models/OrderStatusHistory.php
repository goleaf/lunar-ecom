<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Order;
use App\Models\User;

/**
 * Order Status History Model
 * 
 * Tracks all status changes for orders, providing a complete audit trail.
 */
class OrderStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'lunar_order_status_history';

    protected $fillable = [
        'order_id',
        'status',
        'previous_status',
        'notes',
        'changed_by',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the order that this status history belongs to.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user who changed the status.
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Scope to filter by status.
     */
    public function scopeForStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get history for a specific order.
     */
    public function scopeForOrder($query, int $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    /**
     * Scope to get recent changes first.
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}


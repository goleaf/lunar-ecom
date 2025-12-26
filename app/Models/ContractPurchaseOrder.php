<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Order;
use App\Models\B2BContract;
use App\Models\User;

/**
 * Contract Purchase Order Model
 * 
 * Manages purchase orders associated with B2B contracts.
 */
class ContractPurchaseOrder extends Model
{
    use HasFactory;

    protected $table = 'lunar_contract_purchase_orders';

    protected $fillable = [
        'contract_id',
        'order_id',
        'po_number',
        'po_date',
        'required_date',
        'notes',
        'status',
        'approved_by',
        'approved_at',
        'meta',
    ];

    protected $casts = [
        'po_date' => 'date',
        'required_date' => 'date',
        'approved_at' => 'datetime',
        'meta' => 'array',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_FULFILLED = 'fulfilled';

    /**
     * Get the contract that owns this purchase order.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(B2BContract::class, 'contract_id');
    }

    /**
     * Get the order associated with this purchase order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user who approved this purchase order.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Approve the purchase order.
     */
    public function approve(User $approver): bool
    {
        return $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);
    }

    /**
     * Reject the purchase order.
     */
    public function reject(User $rejector, ?string $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $rejector->id,
            'approved_at' => now(),
            'notes' => ($this->notes ?? '') . "\n\nRejected: " . ($notes ?? 'No reason provided'),
        ]);
    }

    /**
     * Mark as fulfilled.
     */
    public function markAsFulfilled(): bool
    {
        return $this->update([
            'status' => self::STATUS_FULFILLED,
        ]);
    }

    /**
     * Scope to get pending purchase orders.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get approved purchase orders.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }
}



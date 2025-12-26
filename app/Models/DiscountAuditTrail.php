<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Cart;
use Lunar\Models\Discount;
use Lunar\Models\Order;

/**
 * Discount Audit Trail Model
 * 
 * Tracks all discount applications for compliance, auditing, and debugging.
 */
class DiscountAuditTrail extends Model
{
    protected $table = 'lunar_discount_audit_trails';

    protected $fillable = [
        'discount_id',
        'cart_id',
        'order_id',
        'user_id',
        'discount_type',
        'stacking_mode',
        'stacking_strategy',
        'priority',
        'price_before_discount',
        'discount_amount',
        'price_after_discount',
        'scope',
        'reason',
        'conflict_resolution',
        'applied_with',
        'jurisdiction',
        'map_protected',
        'b2b_contract',
        'manual_coupon',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'applied_with' => 'array',
        'metadata' => 'array',
        'map_protected' => 'boolean',
        'b2b_contract' => 'boolean',
        'manual_coupon' => 'boolean',
        'price_before_discount' => 'integer',
        'discount_amount' => 'integer',
        'price_after_discount' => 'integer',
        'priority' => 'integer',
    ];

    /**
     * Get the discount that was applied
     */
    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    /**
     * Get the cart where discount was applied
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get the order where discount was applied
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the user who applied the discount
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Scope: filter by discount type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('discount_type', $type);
    }

    /**
     * Scope: filter by scope
     */
    public function scopeOfScope($query, string $scope)
    {
        return $query->where('scope', $scope);
    }

    /**
     * Scope: filter by jurisdiction
     */
    public function scopeInJurisdiction($query, string $jurisdiction)
    {
        return $query->where('jurisdiction', $jurisdiction);
    }

    /**
     * Scope: filter by date range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}



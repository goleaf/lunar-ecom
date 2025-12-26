<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'type',
        'value',
        'start_at',
        'end_at',
        'usage_limit',
        'per_user_limit',
        'eligible_product_ids',
        'eligible_category_ids',
        'eligible_collection_ids',
        'stack_policy',
        'created_by_rule_id',
        'assigned_to_user_id',
        'is_active',
        'meta',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'usage_limit' => 'integer',
        'per_user_limit' => 'integer',
        'eligible_product_ids' => 'array',
        'eligible_category_ids' => 'array',
        'eligible_collection_ids' => 'array',
        'is_active' => 'boolean',
        'meta' => 'array',
    ];

    // Types
    const TYPE_PERCENTAGE = 'percentage';
    const TYPE_FIXED = 'fixed';
    const TYPE_FREE_SHIPPING = 'free_shipping';

    /**
     * Get the referral rule that created this coupon.
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(ReferralRule::class, 'created_by_rule_id');
    }

    /**
     * Get the user this coupon is assigned to.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * Get redemptions for this coupon.
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    /**
     * Check if coupon is valid.
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->start_at && $this->start_at->isFuture()) {
            return false;
        }

        if ($this->end_at && $this->end_at->isPast()) {
            return false;
        }

        if ($this->usage_limit && $this->redemptions()->count() >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Check if user can use this coupon.
     */
    public function canBeUsedBy(User $user): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        if ($this->assigned_to_user_id && $this->assigned_to_user_id !== $user->id) {
            return false;
        }

        $userRedemptions = $this->redemptions()->where('user_id', $user->id)->count();
        if ($userRedemptions >= $this->per_user_limit) {
            return false;
        }

        return true;
    }
}


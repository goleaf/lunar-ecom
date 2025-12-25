<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Lunar\Models\CustomerGroup;

/**
 * Referral Program Model
 * 
 * Represents a referral program with configurable rules, rewards, and eligibility.
 */
class ReferralProgram extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'handle',
        'description',
        'is_active',
        'starts_at',
        'ends_at',
        'eligible_customer_groups',
        'eligible_users',
        'eligible_conditions',
        'referrer_rewards',
        'referee_rewards',
        'max_referrals_per_referrer',
        'max_referrals_total',
        'max_rewards_per_referrer',
        'allow_self_referral',
        'require_referee_purchase',
        'stacking_mode',
        'stacking_rules',
        'referral_code_validity_days',
        'reward_validity_days',
        'total_referrals',
        'total_rewards_issued',
        'total_reward_value',
        'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'eligible_customer_groups' => 'array',
        'eligible_users' => 'array',
        'eligible_conditions' => 'array',
        'referrer_rewards' => 'array',
        'referee_rewards' => 'array',
        'stacking_rules' => 'array',
        'allow_self_referral' => 'boolean',
        'require_referee_purchase' => 'boolean',
        'max_referrals_per_referrer' => 'integer',
        'max_referrals_total' => 'integer',
        'max_rewards_per_referrer' => 'integer',
        'referral_code_validity_days' => 'integer',
        'reward_validity_days' => 'integer',
        'total_referrals' => 'integer',
        'total_rewards_issued' => 'integer',
        'total_reward_value' => 'decimal:2',
        'meta' => 'array',
    ];

    /**
     * Get all referral codes for this program.
     */
    public function referralCodes(): HasMany
    {
        return $this->hasMany(ReferralCode::class);
    }

    /**
     * Get active referral codes.
     */
    public function activeReferralCodes(): HasMany
    {
        return $this->referralCodes()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Get all referral events.
     */
    public function events(): HasMany
    {
        return $this->hasMany(ReferralEvent::class);
    }

    /**
     * Get all rewards issued.
     */
    public function rewards(): HasMany
    {
        return $this->hasMany(ReferralReward::class);
    }

    /**
     * Get analytics records.
     */
    public function analytics(): HasMany
    {
        return $this->hasMany(ReferralAnalytics::class);
    }

    /**
     * Get eligible customer groups.
     */
    public function eligibleCustomerGroups(): BelongsToMany
    {
        return $this->belongsToMany(
            CustomerGroup::class,
            'referral_program_customer_groups',
            'referral_program_id',
            'customer_group_id'
        );
    }

    /**
     * Check if program is currently active.
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if a user/customer is eligible for this program.
     */
    public function isEligible($user = null, $customer = null): bool
    {
        // Check if program is active
        if (!$this->isCurrentlyActive()) {
            return false;
        }

        // Check customer groups
        if ($this->eligible_customer_groups && $customer) {
            $customerGroupIds = $customer->customerGroups()->pluck('id')->toArray();
            $eligibleGroups = array_intersect($this->eligible_customer_groups, $customerGroupIds);
            if (empty($eligibleGroups)) {
                return false;
            }
        }

        // Check specific users
        if ($this->eligible_users && $user) {
            if (!in_array($user->id, $this->eligible_users)) {
                return false;
            }
        }

        // Check custom conditions
        if ($this->eligible_conditions) {
            // Custom logic can be implemented here
            // For example: min_orders, min_spend, etc.
        }

        return true;
    }

    /**
     * Check if program has reached max referrals limit.
     */
    public function hasReachedMaxReferrals(): bool
    {
        if ($this->max_referrals_total === null) {
            return false;
        }

        return $this->total_referrals >= $this->max_referrals_total;
    }

    /**
     * Scope to get active programs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            });
    }
}


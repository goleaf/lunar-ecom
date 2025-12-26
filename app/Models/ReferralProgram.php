<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'status',
        'start_at',
        'end_at',
        'channel_ids',
        'currency_scope',
        'currency_ids',
        'audience_scope',
        'audience_user_ids',
        'audience_group_ids',
        'terms_url',
        'referral_landing_template_id',
        'fraud_policy_id',
        'last_click_wins',
        'attribution_ttl_days',
        'referral_code_validity_days',
        'default_stacking_mode',
        'max_total_discount_percent',
        'max_total_discount_amount',
        'apply_before_tax',
        'shipping_discount_stacks',
        'total_referrals',
        'total_rewards_issued',
        'total_reward_value',
        'meta',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'channel_ids' => 'array',
        'currency_ids' => 'array',
        'audience_user_ids' => 'array',
        'audience_group_ids' => 'array',
        'last_click_wins' => 'boolean',
        'attribution_ttl_days' => 'integer',
        'referral_code_validity_days' => 'integer',
        'default_stacking_mode' => 'string',
        'max_total_discount_percent' => 'decimal:2',
        'max_total_discount_amount' => 'decimal:2',
        'apply_before_tax' => 'boolean',
        'shipping_discount_stacks' => 'boolean',
        'total_referrals' => 'integer',
        'total_rewards_issued' => 'integer',
        'total_reward_value' => 'decimal:2',
        'meta' => 'array',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_ACTIVE = 'active';
    const STATUS_PAUSED = 'paused';
    const STATUS_ARCHIVED = 'archived';

    // Currency scope
    const CURRENCY_SCOPE_ALL = 'all';
    const CURRENCY_SCOPE_SPECIFIC = 'specific';

    // Audience scope
    const AUDIENCE_SCOPE_ALL = 'all';
    const AUDIENCE_SCOPE_USERS = 'users';
    const AUDIENCE_SCOPE_GROUPS = 'groups';

    /**
     * Landing template for the referral landing page.
     */
    public function landingTemplate(): BelongsTo
    {
        return $this->belongsTo(ReferralLandingTemplate::class, 'referral_landing_template_id');
    }

    /**
     * Get referral rules for this program.
     */
    public function rules(): HasMany
    {
        return $this->hasMany(ReferralRule::class);
    }

    /**
     * Get active referral rules.
     */
    public function activeRules(): HasMany
    {
        return $this->rules()->where('is_active', true);
    }

    /**
     * Get referral attributions.
     */
    public function attributions(): HasMany
    {
        return $this->hasMany(ReferralAttribution::class, 'program_id');
    }

    /**
     * Get analytics records.
     */
    public function analytics(): HasMany
    {
        return $this->hasMany(ReferralAnalytics::class);
    }

    /**
     * Check if program is currently active.
     */
    public function isCurrentlyActive(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if ($this->start_at && $this->start_at->isFuture()) {
            return false;
        }

        if ($this->end_at && $this->end_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if a user is eligible for this program.
     */
    public function isEligibleForUser(?User $user = null): bool
    {
        if (!$this->isCurrentlyActive()) {
            return false;
        }

        if (!$user) {
            return $this->audience_scope === self::AUDIENCE_SCOPE_ALL;
        }

        // Check audience scope
        switch ($this->audience_scope) {
            case self::AUDIENCE_SCOPE_ALL:
                return true;

            case self::AUDIENCE_SCOPE_USERS:
                return $this->audience_user_ids && in_array($user->id, $this->audience_user_ids);

            case self::AUDIENCE_SCOPE_GROUPS:
                return $this->audience_group_ids && $user->group_id && 
                       in_array($user->group_id, $this->audience_group_ids);

            default:
                return false;
        }
    }

    /**
     * Scope to get active programs.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) {
                $q->whereNull('start_at')
                    ->orWhere('start_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_at')
                    ->orWhere('end_at', '>=', now());
            });
    }
}


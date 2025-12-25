<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FraudPolicy extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'allow_same_ip',
        'max_signups_per_ip_per_day',
        'max_orders_per_ip_per_day',
        'block_disposable_emails',
        'block_same_card_fingerprint',
        'require_email_verified',
        'require_phone_verified',
        'min_account_age_days_before_reward',
        'manual_review_threshold',
        'custom_rules',
        'is_active',
    ];

    protected $casts = [
        'allow_same_ip' => 'boolean',
        'max_signups_per_ip_per_day' => 'integer',
        'max_orders_per_ip_per_day' => 'integer',
        'block_disposable_emails' => 'boolean',
        'block_same_card_fingerprint' => 'boolean',
        'require_email_verified' => 'boolean',
        'require_phone_verified' => 'boolean',
        'min_account_age_days_before_reward' => 'integer',
        'manual_review_threshold' => 'integer',
        'custom_rules' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get referral rules using this policy.
     */
    public function referralRules(): HasMany
    {
        return $this->hasMany(ReferralRule::class);
    }
}


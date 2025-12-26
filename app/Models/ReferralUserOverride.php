<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralUserOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'referral_program_id',
        'referral_rule_id',
        'reward_value_override',
        'stacking_mode_override',
        'max_redemptions_override',
        'block_referrals',
        'vip_tier',
        'metadata',
    ];

    protected $casts = [
        'reward_value_override' => 'decimal:2',
        'max_redemptions_override' => 'integer',
        'block_referrals' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the referral program.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(ReferralProgram::class, 'referral_program_id');
    }

    /**
     * Get the referral rule.
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(ReferralRule::class, 'referral_rule_id');
    }
}



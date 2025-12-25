<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Referral Analytics Model
 * 
 * Stores aggregated analytics data for referral programs and codes.
 */
class ReferralAnalytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'referral_program_id',
        'referral_code_id',
        'date',
        'clicks',
        'signups',
        'first_purchases',
        'repeat_purchases',
        'total_orders',
        'total_revenue',
        'rewards_issued',
        'rewards_value',
        'click_to_signup_rate',
        'signup_to_purchase_rate',
        'overall_conversion_rate',
        'aggregation_level',
    ];

    protected $casts = [
        'date' => 'date',
        'clicks' => 'integer',
        'signups' => 'integer',
        'first_purchases' => 'integer',
        'repeat_purchases' => 'integer',
        'total_orders' => 'integer',
        'total_revenue' => 'decimal:2',
        'rewards_issued' => 'integer',
        'rewards_value' => 'decimal:2',
        'click_to_signup_rate' => 'decimal:2',
        'signup_to_purchase_rate' => 'decimal:2',
        'overall_conversion_rate' => 'decimal:2',
    ];

    // Aggregation levels
    const LEVEL_DAILY = 'daily';
    const LEVEL_WEEKLY = 'weekly';
    const LEVEL_MONTHLY = 'monthly';

    /**
     * Get the referral program.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(ReferralProgram::class, 'referral_program_id');
    }

    /**
     * Get the referral code.
     */
    public function referralCode(): BelongsTo
    {
        return $this->belongsTo(ReferralCode::class, 'referral_code_id');
    }

    /**
     * Calculate conversion rates.
     */
    public function calculateConversionRates(): void
    {
        $this->click_to_signup_rate = $this->clicks > 0 
            ? round(($this->signups / $this->clicks) * 100, 2) 
            : 0;

        $this->signup_to_purchase_rate = $this->signups > 0 
            ? round(($this->total_orders / $this->signups) * 100, 2) 
            : 0;

        $this->overall_conversion_rate = $this->clicks > 0 
            ? round(($this->total_orders / $this->clicks) * 100, 2) 
            : 0;
    }
}


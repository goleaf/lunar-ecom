<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReferralAttribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'referee_user_id',
        'referrer_user_id',
        'program_id',
        'code_used',
        'attributed_at',
        'attribution_method',
        'status',
        'rejection_reason',
        'priority',
        'metadata',
    ];

    protected $casts = [
        'attributed_at' => 'datetime',
        'priority' => 'integer',
        'metadata' => 'array',
    ];

    // Attribution methods
    const METHOD_CODE = 'code';
    const METHOD_LINK = 'link';
    const METHOD_MANUAL_ADMIN = 'manual_admin';

    // Status
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_REJECTED = 'rejected';

    /**
     * Get the referee user.
     */
    public function referee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referee_user_id');
    }

    /**
     * Get the referrer user.
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    /**
     * Get the referral program.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(ReferralProgram::class, 'program_id');
    }

    /**
     * Get wallet transactions related to this attribution.
     */
    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'related_referral_id');
    }

    /**
     * Confirm the attribution.
     */
    public function confirm(): bool
    {
        return $this->update(['status' => self::STATUS_CONFIRMED]);
    }

    /**
     * Reject the attribution.
     */
    public function reject(string $reason): bool
    {
        return $this->update([
            'status' => self::STATUS_REJECTED,
            'rejection_reason' => $reason,
        ]);
    }
}


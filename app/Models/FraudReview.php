<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Order;

class FraudReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'referral_attribution_id',
        'order_id',
        'reviewed_by_user_id',
        'status',
        'risk_score',
        'risk_factors',
        'review_notes',
        'reviewed_at',
    ];

    protected $casts = [
        'risk_score' => 'integer',
        'risk_factors' => 'array',
        'reviewed_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_ESCALATED = 'escalated';

    /**
     * Get the referral attribution.
     */
    public function attribution(): BelongsTo
    {
        return $this->belongsTo(ReferralAttribution::class);
    }

    /**
     * Get the order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the reviewer.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    /**
     * Approve the review.
     */
    public function approve(?User $reviewer = null, ?string $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_APPROVED,
            'reviewed_by_user_id' => $reviewer?->id ?? auth()->id(),
            'review_notes' => $notes,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Reject the review.
     */
    public function reject(?User $reviewer = null, ?string $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_REJECTED,
            'reviewed_by_user_id' => $reviewer?->id ?? auth()->id(),
            'review_notes' => $notes,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Escalate the review.
     */
    public function escalate(?User $reviewer = null, ?string $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_ESCALATED,
            'reviewed_by_user_id' => $reviewer?->id ?? auth()->id(),
            'review_notes' => $notes,
            'reviewed_at' => now(),
        ]);
    }
}



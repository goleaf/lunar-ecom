<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ReferralAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'actor_type',
        'actor_id',
        'action',
        'subject_type',
        'subject_id',
        'before',
        'after',
        'notes',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
    ];

    // Actor types
    const ACTOR_ADMIN = 'admin';
    const ACTOR_USER = 'user';
    const ACTOR_SYSTEM = 'system';

    // Actions
    const ACTION_RULE_UPDATED = 'rule_updated';
    const ACTION_REWARD_ISSUED = 'reward_issued';
    const ACTION_REWARD_REVERSED = 'reward_reversed';
    const ACTION_ATTRIBUTION_CHANGED = 'attribution_changed';
    const ACTION_ATTRIBUTION_CONFIRMED = 'attribution_confirmed';
    const ACTION_ATTRIBUTION_REJECTED = 'attribution_rejected';
    const ACTION_COUPON_CREATED = 'coupon_created';
    const ACTION_COUPON_REDEEMED = 'coupon_redeemed';
    const ACTION_WALLET_CREDIT = 'wallet_credit';
    const ACTION_WALLET_DEBIT = 'wallet_debit';

    /**
     * Get the subject (polymorphic).
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the actor (polymorphic).
     */
    public function actor(): MorphTo
    {
        return $this->morphTo('actor', 'actor_type', 'actor_id');
    }

    /**
     * Create an audit log entry.
     */
    public static function log(
        string $action,
        $subject,
        ?array $before = null,
        ?array $after = null,
        ?string $actorType = self::ACTOR_SYSTEM,
        ?int $actorId = null,
        ?string $notes = null
    ): self {
        return self::create([
            'actor_type' => $actorType,
            'actor_id' => $actorId ?? (auth()->check() ? auth()->id() : null),
            'action' => $action,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
            'before' => $before,
            'after' => $after,
            'notes' => $notes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}



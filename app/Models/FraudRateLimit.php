<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FraudRateLimit extends Model
{
    use HasFactory;

    protected $fillable = [
        'identifier_type',
        'identifier_hash',
        'action_type',
        'count',
        'date',
    ];

    protected $casts = [
        'count' => 'integer',
        'date' => 'date',
    ];

    const IDENTIFIER_IP = 'ip';
    const IDENTIFIER_DEVICE = 'device_fingerprint';
    const IDENTIFIER_EMAIL = 'email';
    const IDENTIFIER_PAYMENT = 'payment_fingerprint';

    const ACTION_SIGNUP = 'signup';
    const ACTION_ORDER = 'order';
    const ACTION_REFERRAL_CLICK = 'referral_click';

    /**
     * Increment count for identifier and action.
     */
    public static function incrementCount(
        string $identifierType,
        string $identifierHash,
        string $actionType
    ): int {
        $today = now()->toDateString();

        return static::updateOrCreate(
            [
                'identifier_type' => $identifierType,
                'identifier_hash' => $identifierHash,
                'action_type' => $actionType,
                'date' => $today,
            ],
            []
        )->increment('count') ? static::where([
            'identifier_type' => $identifierType,
            'identifier_hash' => $identifierHash,
            'action_type' => $actionType,
            'date' => $today,
        ])->value('count') : 0;
    }

    /**
     * Get count for identifier and action today.
     */
    public static function getCountToday(
        string $identifierType,
        string $identifierHash,
        string $actionType
    ): int {
        return static::where([
            'identifier_type' => $identifierType,
            'identifier_hash' => $identifierHash,
            'action_type' => $actionType,
            'date' => now()->toDateString(),
        ])->value('count') ?? 0;
    }

    /**
     * Check if limit exceeded.
     */
    public static function isLimitExceeded(
        string $identifierType,
        string $identifierHash,
        string $actionType,
        int $limit
    ): bool {
        return static::getCountToday($identifierType, $identifierHash, $actionType) >= $limit;
    }

    /**
     * Clean old records (older than X days).
     */
    public static function cleanOldRecords(int $days = 30): int
    {
        return static::where('date', '<', now()->subDays($days))->delete();
    }
}



<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Customer;

class ConsentTracking extends Model
{
    use HasFactory;

    protected $table = 'consent_tracking';

    const TYPE_COOKIE = 'cookie';
    const TYPE_MARKETING = 'marketing';
    const TYPE_ANALYTICS = 'analytics';
    const TYPE_PREFERENCES = 'preferences';
    const TYPE_DATA_PROCESSING = 'data_processing';
    const TYPE_THIRD_PARTY = 'third_party';

    const METHOD_BANNER = 'banner';
    const METHOD_SETTINGS = 'settings';
    const METHOD_API = 'api';
    const METHOD_IMPORT = 'import';

    protected $fillable = [
        'user_id',
        'customer_id',
        'session_id',
        'consent_type',
        'purpose',
        'consented',
        'consent_method',
        'ip_address',
        'user_agent',
        'consented_at',
        'withdrawn_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'consented' => 'boolean',
            'consented_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Record consent
     */
    public static function recordConsent(
        string $consentType,
        string $purpose,
        bool $consented,
        ?int $userId = null,
        ?int $customerId = null,
        ?string $sessionId = null,
        ?string $method = null,
        array $metadata = []
    ): self {
        return self::create([
            'user_id' => $userId,
            'customer_id' => $customerId,
            'session_id' => $sessionId ?? session()->getId(),
            'consent_type' => $consentType,
            'purpose' => $purpose,
            'consented' => $consented,
            'consent_method' => $method ?? self::METHOD_BANNER,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'consented_at' => $consented ? now() : null,
            'withdrawn_at' => $consented ? null : now(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Check if user has given consent for a specific type
     */
    public static function hasConsent(
        string $consentType,
        ?int $userId = null,
        ?int $customerId = null,
        ?string $sessionId = null
    ): bool {
        $query = self::where('consent_type', $consentType)
            ->where('consented', true)
            ->whereNull('withdrawn_at');

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($customerId) {
            $query->where('customer_id', $customerId);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        } else {
            $query->where('session_id', session()->getId());
        }

        return $query->exists();
    }

    /**
     * Withdraw consent
     */
    public function withdraw(): void
    {
        $this->update([
            'consented' => false,
            'withdrawn_at' => now(),
        ]);
    }
}

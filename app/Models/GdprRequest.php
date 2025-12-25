<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Lunar\Models\Customer;

class GdprRequest extends Model
{
    use HasFactory;

    const TYPE_EXPORT = 'export';
    const TYPE_DELETION = 'deletion';
    const TYPE_ANONYMIZATION = 'anonymization';
    const TYPE_RECTIFICATION = 'rectification';

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_REJECTED = 'rejected';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'type',
        'status',
        'user_id',
        'customer_id',
        'email',
        'verification_token',
        'verified_at',
        'processed_at',
        'completed_at',
        'rejection_reason',
        'notes',
        'ip_address',
        'user_agent',
        'export_file_path',
        'request_data',
        'processing_log',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'processed_at' => 'datetime',
            'completed_at' => 'datetime',
            'request_data' => 'array',
            'processing_log' => 'array',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($request) {
            if (empty($request->verification_token)) {
                $request->verification_token = Str::random(64);
            }
        });
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
     * Mark request as verified
     */
    public function markAsVerified(): void
    {
        $this->update([
            'verified_at' => now(),
            'status' => self::STATUS_PROCESSING,
        ]);
    }

    /**
     * Mark request as completed
     */
    public function markAsCompleted(?string $filePath = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'export_file_path' => $filePath,
        ]);
    }

    /**
     * Mark request as rejected
     */
    public function markAsRejected(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Add log entry
     */
    public function addLog(string $message, array $context = []): void
    {
        $logs = $this->processing_log ?? [];
        $logs[] = [
            'timestamp' => now()->toIso8601String(),
            'message' => $message,
            'context' => $context,
        ];
        $this->update(['processing_log' => $logs]);
    }

    /**
     * Check if request is verified
     */
    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * Check if request is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}

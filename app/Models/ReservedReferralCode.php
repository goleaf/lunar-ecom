<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservedReferralCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'reserved_for_user_id',
        'reserved_for_email',
        'notes',
        'reserved_by_user_id',
        'reserved_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'reserved_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user this code is reserved for.
     */
    public function reservedForUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reserved_for_user_id');
    }

    /**
     * Get the user who reserved this code.
     */
    public function reservedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reserved_by_user_id');
    }

    /**
     * Check if reservation is valid.
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}



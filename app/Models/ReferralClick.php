<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralClick extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_user_id',
        'referral_code',
        'ip_hash',
        'user_agent_hash',
        'landing_url',
        'session_id',
    ];

    /**
     * Get the referrer user.
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }
}



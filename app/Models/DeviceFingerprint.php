<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceFingerprint extends Model
{
    use HasFactory;

    protected $fillable = [
        'fingerprint_hash',
        'user_agent_hash',
        'screen_resolution',
        'timezone',
        'language',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get referral attributions using this device.
     */
    public function attributions(): HasMany
    {
        return $this->hasMany(ReferralAttribution::class, 'device_fingerprint_hash', 'fingerprint_hash');
    }
}



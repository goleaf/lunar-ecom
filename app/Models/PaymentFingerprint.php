<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentFingerprint extends Model
{
    use HasFactory;

    protected $fillable = [
        'fingerprint_hash',
        'card_last4',
        'card_brand',
        'card_country',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get referral attributions using this payment fingerprint.
     */
    public function attributions(): HasMany
    {
        return $this->hasMany(ReferralAttribution::class, 'payment_fingerprint_hash', 'fingerprint_hash');
    }
}



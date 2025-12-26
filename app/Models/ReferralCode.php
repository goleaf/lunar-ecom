<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use Lunar\Models\Customer;

/**
 * Referral Code Model
 * 
 * Represents a unique referral code/link that can be shared by referrers.
 */
class ReferralCode extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'referral_program_id',
        'code',
        'slug',
        'referrer_id',
        'referrer_customer_id',
        'is_active',
        'expires_at',
        'max_uses',
        'current_uses',
        'custom_url',
        'total_clicks',
        'total_signups',
        'total_purchases',
        'total_revenue',
        'meta',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'max_uses' => 'integer',
        'current_uses' => 'integer',
        'total_clicks' => 'integer',
        'total_signups' => 'integer',
        'total_purchases' => 'integer',
        'total_revenue' => 'decimal:2',
        'meta' => 'array',
    ];

    /**
     * Get the referral program.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(ReferralProgram::class, 'referral_program_id');
    }

    /**
     * Get the referrer user.
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    /**
     * Get the referrer customer.
     */
    public function referrerCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'referrer_customer_id');
    }

    /**
     * Get all events for this code.
     */
    public function events(): HasMany
    {
        return $this->hasMany(ReferralEvent::class);
    }

    /**
     * Get tracking records.
     */
    public function tracking(): HasMany
    {
        return $this->hasMany(ReferralTracking::class);
    }

    /**
     * Get analytics records.
     */
    public function analytics(): HasMany
    {
        return $this->hasMany(ReferralAnalytics::class);
    }

    /**
     * Check if code is currently valid.
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses && $this->current_uses >= $this->max_uses) {
            return false;
        }

        if (!$this->program || !$this->program->isCurrentlyActive()) {
            return false;
        }

        return true;
    }

    /**
     * Generate referral URL.
     */
    public function getReferralUrl(): string
    {
        if ($this->custom_url) {
            return $this->custom_url;
        }

        $baseUrl = config('app.url');
        $slug = $this->slug ?: $this->code;
        
        return "{$baseUrl}/ref/{$slug}";
    }

    /**
     * Increment usage counter.
     */
    public function incrementUses(): void
    {
        $this->increment('current_uses');
    }

    /**
     * Increment click counter.
     */
    public function incrementClicks(): void
    {
        $this->increment('total_clicks');
    }

    /**
     * Increment signup counter.
     */
    public function incrementSignups(): void
    {
        $this->increment('total_signups');
    }

    /**
     * Increment purchase counter.
     */
    public function incrementPurchases(): void
    {
        $this->increment('total_purchases');
    }

    /**
     * Add revenue.
     */
    public function addRevenue(float $amount): void
    {
        $this->increment('total_revenue', $amount);
    }

    /**
     * Scope to get active codes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope to get codes for a user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('referrer_id', $userId);
    }

    /**
     * Scope to get codes for a customer.
     */
    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('referrer_customer_id', $customerId);
    }
}



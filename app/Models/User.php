<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Lunar\Base\LunarUser;
use Lunar\Base\Traits\LunarUser as LunarUserTrait;

class User extends Authenticatable implements LunarUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, LunarUserTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'status',
        'group_id',
        'referral_code',
        'referral_link_slug',
        'referred_by_user_id',
        'referred_at',
        'referral_blocked',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'referred_at' => 'datetime',
            'referral_blocked' => 'boolean',
        ];
    }

    /**
     * Get the user group.
     */
    public function group()
    {
        return $this->belongsTo(UserGroup::class, 'group_id');
    }

    /**
     * Get the user who referred this user.
     */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by_user_id');
    }

    /**
     * Get users referred by this user.
     */
    public function referees()
    {
        return $this->hasMany(User::class, 'referred_by_user_id');
    }

    /**
     * Generate a unique referral code using safe alphabet.
     */
    public function generateReferralCode(?int $length = null): string
    {
        if ($this->referral_code) {
            return $this->referral_code;
        }

        $generator = app(\App\Services\ReferralCodeGeneratorService::class);
        $code = $generator->generate($length ?? 8);
        $slug = $generator->generateSlug($code);

        $this->update([
            'referral_code' => $code,
            'referral_link_slug' => $slug,
        ]);
        
        return $code;
    }

    /**
     * Regenerate referral code.
     */
    public function regenerateReferralCode(?int $length = null): string
    {
        $generator = app(\App\Services\ReferralCodeGeneratorService::class);
        return $generator->regenerateForUser($this, $length);
    }

    /**
     * Get referral link.
     */
    public function getReferralLink(): string
    {
        $slug = $this->referral_link_slug ?: $this->referral_code;
        return config('app.url') . '/ref/' . $slug;
    }

    /**
     * Get user's wallet.
     */
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * Get coupons assigned to this user.
     */
    public function coupons()
    {
        return $this->hasMany(Coupon::class, 'assigned_to_user_id');
    }

    /**
     * Get referral attributions as referee.
     */
    public function referralAttributions()
    {
        return $this->hasMany(ReferralAttribution::class, 'referee_user_id');
    }

    /**
     * Get referral clicks as referrer.
     */
    public function referralClicks()
    {
        return $this->hasMany(ReferralClick::class, 'referrer_user_id');
    }

    /**
     * Get coupon redemptions.
     */
    public function couponRedemptions()
    {
        return $this->hasMany(CouponRedemption::class);
    }
}

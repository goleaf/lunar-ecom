<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ProductComparison model for storing user/session product comparisons.
 */
class ProductComparison extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'product_comparisons';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'session_id',
        'product_ids',
        'selected_attributes',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'product_ids' => 'array',
        'selected_attributes' => 'array',
        'expires_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Set expiration to 30 days from now
        static::creating(function ($comparison) {
            if (!$comparison->expires_at) {
                $comparison->expires_at = now()->addDays(30);
            }
        });
    }

    /**
     * User relationship.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Get products relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function products()
    {
        return $this->belongsToMany(
            \Lunar\Models\Product::class,
            null,
            null,
            'product_id',
            'product_id'
        )->whereIn('id', $this->product_ids ?? []);
    }

    /**
     * Check if comparison is expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get product count.
     *
     * @return int
     */
    public function getProductCountAttribute(): int
    {
        return count($this->product_ids ?? []);
    }

    /**
     * Check if comparison is full (max 5 products).
     *
     * @return bool
     */
    public function isFull(): bool
    {
        return $this->product_count >= 5;
    }

    /**
     * Scope to get active (non-expired) comparisons.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope to get expired comparisons.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }
}

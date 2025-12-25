<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Review model for product reviews and ratings.
 */
class Review extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'reviews';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'customer_id',
        'order_id',
        'rating',
        'title',
        'content',
        'pros',
        'cons',
        'recommended',
        'is_approved',
        'approved_at',
        'approved_by',
        'is_verified_purchase',
        'helpful_count',
        'not_helpful_count',
        'admin_response',
        'responded_at',
        'responded_by',
        'report_count',
        'is_reported',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rating' => 'integer',
        'pros' => 'array',
        'cons' => 'array',
        'recommended' => 'boolean',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
        'is_verified_purchase' => 'boolean',
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
        'responded_at' => 'datetime',
        'report_count' => 'integer',
        'is_reported' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Update product rating cache when review is approved/changed
        static::saved(function ($review) {
            if ($review->is_approved) {
                $review->product->updateRatingCache();
            }
        });

        static::deleted(function ($review) {
            if ($review->is_approved) {
                $review->product->updateRatingCache();
            }
        });
    }

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->useDisk('public')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
        // Max 5 files handled in validation, not in collection definition
    }

    /**
     * Product relationship.
     *
     * @return BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Customer relationship.
     *
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(\Lunar\Models\Customer::class);
    }

    /**
     * Order relationship.
     *
     * @return BelongsTo
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(\Lunar\Models\Order::class);
    }

    /**
     * Approved by user relationship.
     *
     * @return BelongsTo
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    /**
     * Responded by user relationship.
     *
     * @return BelongsTo
     */
    public function responder(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'responded_by');
    }

    /**
     * Helpful votes relationship.
     *
     * @return HasMany
     */
    public function helpfulVotes(): HasMany
    {
        return $this->hasMany(ReviewHelpfulVote::class, 'review_id');
    }

    /**
     * Scope a query to only include approved reviews.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope a query to only include verified purchases.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVerifiedPurchase($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    /**
     * Scope a query to order by most helpful.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMostHelpful($query)
    {
        return $query->orderByDesc('helpful_count')
            ->orderByDesc('created_at');
    }

    /**
     * Scope a query to order by most recent.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMostRecent($query)
    {
        return $query->orderByDesc('created_at');
    }

    /**
     * Scope a query to order by highest rating.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHighestRating($query)
    {
        return $query->orderByDesc('rating')
            ->orderByDesc('created_at');
    }

    /**
     * Scope a query to order by lowest rating.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLowestRating($query)
    {
        return $query->orderBy('rating')
            ->orderByDesc('created_at');
    }

    /**
     * Scope a query to only include pending moderation.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    /**
     * Scope a query to only include reported reviews.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeReported($query)
    {
        return $query->where('is_reported', true);
    }

    /**
     * Check if customer has already voted on this review.
     *
     * @param  int|null  $customerId
     * @param  string|null  $sessionId
     * @param  string|null  $ipAddress
     * @return bool
     */
    public function hasVoted(?int $customerId = null, ?string $sessionId = null, ?string $ipAddress = null): bool
    {
        $query = $this->helpfulVotes();

        if ($customerId) {
            $query->where('customer_id', $customerId);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        } elseif ($ipAddress) {
            $query->where('ip_address', $ipAddress);
        } else {
            return false;
        }

        return $query->exists();
    }

    /**
     * Get helpful percentage.
     *
     * @return float
     */
    public function getHelpfulPercentageAttribute(): float
    {
        $total = $this->helpful_count + $this->not_helpful_count;
        if ($total === 0) {
            return 0;
        }
        return round(($this->helpful_count / $total) * 100, 1);
    }

    /**
     * Update helpful counts from votes.
     *
     * @return void
     */
    public function updateHelpfulCounts(): void
    {
        $this->helpful_count = $this->helpfulVotes()->where('is_helpful', true)->count();
        $this->not_helpful_count = $this->helpfulVotes()->where('is_helpful', false)->count();
        $this->saveQuietly();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Lunar\Models\Channel;
use App\Models\Product;

/**
 * ChannelProductData model for channel-specific product data.
 * 
 * Stores:
 * - Channel-specific visibility
 * - Channel-specific descriptions
 * - Channel-specific SEO fields
 * - Geo-restrictions per channel
 */
class ChannelProductData extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'channel_product_data';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'channel_id',
        'visibility',
        'is_visible',
        'published_at',
        'scheduled_publish_at',
        'scheduled_unpublish_at',
        'short_description',
        'full_description',
        'technical_description',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'allowed_countries',
        'blocked_countries',
        'allowed_regions',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_visible' => 'boolean',
        'published_at' => 'datetime',
        'scheduled_publish_at' => 'datetime',
        'scheduled_unpublish_at' => 'datetime',
        'allowed_countries' => 'array',
        'blocked_countries' => 'array',
        'allowed_regions' => 'array',
    ];

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
     * Channel relationship.
     *
     * @return BelongsTo
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * Scope to get visible products for a channel.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  Channel|int  $channel
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVisibleForChannel($query, $channel)
    {
        $channelId = $channel instanceof Channel ? $channel->id : $channel;
        
        return $query->where('channel_id', $channelId)
            ->where('is_visible', true)
            ->where(function ($q) {
                $q->whereNull('published_at')
                  ->orWhere('published_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('scheduled_publish_at')
                  ->orWhere('scheduled_publish_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('scheduled_unpublish_at')
                  ->orWhere('scheduled_unpublish_at', '>', now());
            });
    }

    /**
     * Check if product is available in a specific country for this channel.
     *
     * @param  string  $countryCode  ISO 2-letter country code
     * @return bool
     */
    public function isAvailableInCountry(string $countryCode): bool
    {
        // Check blocked countries first
        if ($this->blocked_countries && in_array($countryCode, $this->blocked_countries)) {
            return false;
        }
        
        // If allowed countries specified, check if country is in list
        if ($this->allowed_countries && !empty($this->allowed_countries)) {
            return in_array($countryCode, $this->allowed_countries);
        }
        
        // If no restrictions, allow
        return true;
    }

    /**
     * Scope to get published products for channel.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublished($query)
    {
        return $query->where('is_visible', true)
            ->where('visibility', 'public')
            ->where(function ($q) {
                $q->whereNull('published_at')
                  ->orWhere('published_at', '<=', now());
            });
    }
}


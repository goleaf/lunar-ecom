<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Lunar\Models\Channel;

/**
 * ChannelMedia model for channel-specific media assignments.
 * 
 * Links Spatie Media Library media to channels, allowing different
 * images/media per channel (e.g., different product images for web vs mobile).
 */
class ChannelMedia extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'channel_media';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'channel_id',
        'mediable_type',
        'mediable_id',
        'media_id',
        'collection_name',
        'position',
        'is_primary',
        'alt_text',
        'caption',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'position' => 'integer',
        'is_primary' => 'boolean',
        'alt_text' => 'array',
        'caption' => 'array',
    ];

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
     * Mediable relationship (polymorphic).
     *
     * @return MorphTo
     */
    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the media model from Spatie Media Library.
     *
     * @return \Spatie\MediaLibrary\MediaCollections\Models\Media|null
     */
    public function getMedia()
    {
        $mediable = $this->mediable;
        if (!$mediable) {
            return null;
        }
        
        return $mediable->getMedia($this->collection_name)
            ->where('id', $this->media_id)
            ->first();
    }

    /**
     * Scope to get media for a specific channel.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  Channel|int  $channel
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForChannel($query, $channel)
    {
        $channelId = $channel instanceof Channel ? $channel->id : $channel;
        return $query->where('channel_id', $channelId);
    }

    /**
     * Scope to get primary media.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope to order by position.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }
}


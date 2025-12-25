<?php

namespace App\Services;

use App\Models\ChannelMedia;
use Lunar\Models\Channel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Service for managing channel-specific media.
 */
class ChannelMediaService
{
    /**
     * Assign media to a channel.
     *
     * @param  Model  $model  Model that uses Spatie Media Library
     * @param  Channel  $channel
     * @param  int  $mediaId  Spatie Media Library media ID
     * @param  string  $collectionName
     * @param  array  $options
     * @return ChannelMedia
     */
    public function assignMedia(
        Model $model,
        Channel $channel,
        int $mediaId,
        string $collectionName = 'default',
        array $options = []
    ): ChannelMedia {
        return ChannelMedia::updateOrCreate(
            [
                'channel_id' => $channel->id,
                'mediable_type' => get_class($model),
                'mediable_id' => $model->id,
                'media_id' => $mediaId,
                'collection_name' => $collectionName,
            ],
            [
                'position' => $options['position'] ?? 0,
                'is_primary' => $options['is_primary'] ?? false,
                'alt_text' => $options['alt_text'] ?? null,
                'caption' => $options['caption'] ?? null,
            ]
        );
    }

    /**
     * Get media for a model in a specific channel.
     *
     * @param  Model  $model
     * @param  Channel  $channel
     * @param  string  $collectionName
     * @return Collection
     */
    public function getMedia(Model $model, Channel $channel, string $collectionName = 'default'): Collection
    {
        $channelMedia = ChannelMedia::where('mediable_type', get_class($model))
            ->where('mediable_id', $model->id)
            ->where('channel_id', $channel->id)
            ->where('collection_name', $collectionName)
            ->ordered()
            ->get();
        
        // Get actual media models from Spatie Media Library
        return $channelMedia->map(function ($channelMediaItem) use ($model, $collectionName) {
            $media = $model->getMedia($collectionName)
                ->where('id', $channelMediaItem->media_id)
                ->first();
            
            if ($media) {
                $media->channel_position = $channelMediaItem->position;
                $media->is_channel_primary = $channelMediaItem->is_primary;
                $media->channel_alt_text = $channelMediaItem->alt_text;
                $media->channel_caption = $channelMediaItem->caption;
            }
            
            return $media;
        })->filter();
    }

    /**
     * Get primary media for a model in a channel.
     *
     * @param  Model  $model
     * @param  Channel  $channel
     * @param  string  $collectionName
     * @return \Spatie\MediaLibrary\MediaCollections\Models\Media|null
     */
    public function getPrimaryMedia(Model $model, Channel $channel, string $collectionName = 'default')
    {
        $channelMedia = ChannelMedia::where('mediable_type', get_class($model))
            ->where('mediable_id', $model->id)
            ->where('channel_id', $channel->id)
            ->where('collection_name', $collectionName)
            ->primary()
            ->first();
        
        if (!$channelMedia) {
            // Fallback to default primary media
            return $model->getFirstMedia($collectionName);
        }
        
        return $model->getMedia($collectionName)
            ->where('id', $channelMedia->media_id)
            ->first();
    }

    /**
     * Remove media assignment from channel.
     *
     * @param  Model  $model
     * @param  Channel  $channel
     * @param  int  $mediaId
     * @param  string  $collectionName
     * @return bool
     */
    public function removeMedia(
        Model $model,
        Channel $channel,
        int $mediaId,
        string $collectionName = 'default'
    ): bool {
        return ChannelMedia::where('mediable_type', get_class($model))
            ->where('mediable_id', $model->id)
            ->where('channel_id', $channel->id)
            ->where('media_id', $mediaId)
            ->where('collection_name', $collectionName)
            ->delete() > 0;
    }

    /**
     * Set primary media for channel.
     *
     * @param  Model  $model
     * @param  Channel  $channel
     * @param  int  $mediaId
     * @param  string  $collectionName
     * @return void
     */
    public function setPrimaryMedia(
        Model $model,
        Channel $channel,
        int $mediaId,
        string $collectionName = 'default'
    ): void {
        // Remove primary flag from all media in this channel
        ChannelMedia::where('mediable_type', get_class($model))
            ->where('mediable_id', $model->id)
            ->where('channel_id', $channel->id)
            ->where('collection_name', $collectionName)
            ->update(['is_primary' => false]);
        
        // Set new primary
        ChannelMedia::where('mediable_type', get_class($model))
            ->where('mediable_id', $model->id)
            ->where('channel_id', $channel->id)
            ->where('media_id', $mediaId)
            ->where('collection_name', $collectionName)
            ->update(['is_primary' => true]);
    }

    /**
     * Reorder media for channel.
     *
     * @param  Model  $model
     * @param  Channel  $channel
     * @param  array  $mediaIds  Array of media IDs in desired order
     * @param  string  $collectionName
     * @return void
     */
    public function reorderMedia(
        Model $model,
        Channel $channel,
        array $mediaIds,
        string $collectionName = 'default'
    ): void {
        foreach ($mediaIds as $position => $mediaId) {
            ChannelMedia::where('mediable_type', get_class($model))
                ->where('mediable_id', $model->id)
                ->where('channel_id', $channel->id)
                ->where('media_id', $mediaId)
                ->where('collection_name', $collectionName)
                ->update(['position' => $position]);
        }
    }
}


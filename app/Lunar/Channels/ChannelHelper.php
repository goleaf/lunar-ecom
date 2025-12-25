<?php

namespace App\Lunar\Channels;

use Illuminate\Support\Collection;
use App\Models\Channel;

/**
 * Helper class for working with Lunar Channels.
 * 
 * Provides convenience methods for managing channels and channel assignments.
 * See: https://docs.lunarphp.com/1.x/reference/channels
 */
class ChannelHelper
{
    /**
     * Get the default channel.
     * 
     * @return Channel|null
     */
    public static function getDefault(): ?Channel
    {
        return Channel::getDefault();
    }

    /**
     * Get all channels.
     * 
     * @return Collection<Channel>
     */
    public static function getAll(): Collection
    {
        return Channel::all();
    }

    /**
     * Find a channel by ID.
     * 
     * @param int $id
     * @return Channel|null
     */
    public static function find(int $id): ?Channel
    {
        return Channel::find($id);
    }

    /**
     * Find a channel by handle.
     * 
     * @param string $handle
     * @return Channel|null
     */
    public static function findByHandle(string $handle): ?Channel
    {
        return Channel::where('handle', $handle)->first();
    }

    /**
     * Schedule a model for one or more channels.
     * 
     * The model must use the HasChannels trait.
     * 
     * @param \Illuminate\Database\Eloquent\Model $model Model that uses HasChannels trait
     * @param Channel|Collection|array $channels Channel(s) to schedule
     * @param \Carbon\Carbon|null $startsAt Start date (null for immediate)
     * @param \Carbon\Carbon|null $endsAt End date (null for permanent)
     * @return void
     */
    public static function scheduleChannel(
        $model,
        Channel|Collection|array $channels,
        ?\Carbon\Carbon $startsAt = null,
        ?\Carbon\Carbon $endsAt = null
    ): void {
        $model->scheduleChannel($channels, $startsAt, $endsAt);
    }

    /**
     * Schedule a model for immediate availability on a channel.
     * 
     * @param \Illuminate\Database\Eloquent\Model $model Model that uses HasChannels trait
     * @param Channel|Collection|array $channels Channel(s) to schedule
     * @return void
     */
    public static function scheduleChannelImmediate(
        $model,
        Channel|Collection|array $channels
    ): void {
        $model->scheduleChannel($channels);
    }

    /**
     * Query models for a specific channel.
     * 
     * The model must use the HasChannels trait.
     * 
     * @param string $modelClass Model class name
     * @param Channel|int $channel Channel instance or ID
     * @param \Carbon\Carbon|null $startDate Start date for availability
     * @param \Carbon\Carbon|null $endDate End date for availability
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function queryByChannel(
        string $modelClass,
        Channel|int $channel,
        ?\Carbon\Carbon $startDate = null,
        ?\Carbon\Carbon $endDate = null
    ) {
        if ($channel instanceof Channel) {
            $channelInstance = $channel;
        } else {
            $channelInstance = Channel::find($channel);
        }

        if (!$channelInstance) {
            throw new \InvalidArgumentException("Channel not found");
        }

        return $modelClass::channel($channelInstance, $startDate, $endDate);
    }

    /**
     * Query models for multiple channels.
     * 
     * The model must use the HasChannels trait.
     * 
     * @param string $modelClass Model class name
     * @param Channel[]|int[]|Collection $channels Channel instances, IDs, or Collection
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function queryByChannels(
        string $modelClass,
        array|Collection $channels
    ) {
        return $modelClass::channel($channels);
    }

    /**
     * Check if a model is available for a channel.
     * 
     * @param \Illuminate\Database\Eloquent\Model $model Model that uses HasChannels trait
     * @param Channel|int $channel Channel instance or ID
     * @param \Carbon\Carbon|null $date Date to check (defaults to now)
     * @return bool
     */
    public static function isAvailableForChannel(
        $model,
        Channel|int $channel,
        ?\Carbon\Carbon $date = null
    ): bool {
        if ($channel instanceof Channel) {
            $channelInstance = $channel;
        } else {
            $channelInstance = Channel::find($channel);
        }

        if (!$channelInstance) {
            return false;
        }

        $date = $date ?? now();

        $query = get_class($model)::channel($channelInstance, $date, $date)
            ->where($model->getKeyName(), $model->getKey());

        return $query->exists();
    }

    /**
     * Get channels for a model.
     * 
     * @param \Illuminate\Database\Eloquent\Model $model Model that uses HasChannels trait
     * @return Collection<Channel>
     */
    public static function getChannelsForModel($model): Collection
    {
        return $model->channels;
    }

    /**
     * Create a new channel.
     * 
     * @param string $name Channel name
     * @param string $handle Channel handle (slug)
     * @param bool $default Whether this is the default channel
     * @return Channel
     */
    public static function create(
        string $name,
        string $handle,
        bool $default = false
    ): Channel {
        return Channel::create([
            'name' => $name,
            'handle' => $handle,
            'default' => $default,
        ]);
    }
}



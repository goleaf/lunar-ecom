<?php

namespace App\Models;

use App\Enums\CollectionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Lunar\Models\Collection as LunarCollection;

class Collection extends LunarCollection
{
    /** @use HasFactory<\Database\Factories\CollectionFactory> */
    use HasFactory;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'collection_type' => CollectionType::class,
        'scheduled_publish_at' => 'datetime',
        'scheduled_unpublish_at' => 'datetime',
        'auto_publish_products' => 'boolean',
    ];

    /**
     * Scope a query to only include cross-sell collections.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCrossSell($query)
    {
        return $query->where('collection_type', CollectionType::CROSS_SELL->value);
    }

    /**
     * Scope a query to only include up-sell collections.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUpSell($query)
    {
        return $query->where('collection_type', CollectionType::UP_SELL->value);
    }

    /**
     * Scope a query to only include related collections.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRelated($query)
    {
        return $query->where('collection_type', CollectionType::RELATED->value);
    }

    /**
     * Scope a query to only include bundle collections.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBundles($query)
    {
        return $query->where('collection_type', CollectionType::BUNDLE->value);
    }

    /**
     * Scope a query to filter by collection type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  CollectionType|string  $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, $type)
    {
        $typeValue = $type instanceof CollectionType ? $type->value : $type;
        return $query->where('collection_type', $typeValue);
    }

    /**
     * Scope a query to only include scheduled collections.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeScheduled($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('scheduled_publish_at')
              ->orWhereNotNull('scheduled_unpublish_at');
        });
    }

    /**
     * Scope a query to filter collections scheduled for publish.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeScheduledForPublish($query)
    {
        return $query->whereNotNull('scheduled_publish_at')
            ->where('scheduled_publish_at', '<=', now());
    }

    /**
     * Scope a query to filter collections scheduled for unpublish.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeScheduledForUnpublish($query)
    {
        return $query->whereNotNull('scheduled_unpublish_at')
            ->where('scheduled_unpublish_at', '<=', now());
    }

    /**
     * Check if collection is scheduled.
     *
     * @return bool
     */
    public function isScheduled(): bool
    {
        return $this->scheduled_publish_at !== null || $this->scheduled_unpublish_at !== null;
    }

    /**
     * Check if collection is scheduled for publish.
     *
     * @return bool
     */
    public function isScheduledForPublish(): bool
    {
        return $this->scheduled_publish_at !== null 
            && $this->scheduled_publish_at->isFuture();
    }

    /**
     * Check if collection is scheduled for unpublish.
     *
     * @return bool
     */
    public function isScheduledForUnpublish(): bool
    {
        return $this->scheduled_unpublish_at !== null 
            && $this->scheduled_unpublish_at->isFuture();
    }

    /**
     * Schedule collection for future publish.
     *
     * @param  \Carbon\Carbon|string  $publishAt
     * @return void
     */
    public function schedulePublish($publishAt): void
    {
        $this->scheduled_publish_at = is_string($publishAt) ? \Carbon\Carbon::parse($publishAt) : $publishAt;
        $this->save();
    }

    /**
     * Schedule collection for future unpublish.
     *
     * @param  \Carbon\Carbon|string  $unpublishAt
     * @return void
     */
    public function scheduleUnpublish($unpublishAt): void
    {
        $this->scheduled_unpublish_at = is_string($unpublishAt) ? \Carbon\Carbon::parse($unpublishAt) : $unpublishAt;
        $this->save();
    }

    /**
     * Clear scheduled publish date.
     *
     * @return void
     */
    public function clearScheduledPublish(): void
    {
        $this->scheduled_publish_at = null;
        $this->save();
    }

    /**
     * Clear scheduled unpublish date.
     *
     * @return void
     */
    public function clearScheduledUnpublish(): void
    {
        $this->scheduled_unpublish_at = null;
        $this->save();
    }
}
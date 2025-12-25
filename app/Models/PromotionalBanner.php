<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * PromotionalBanner model for homepage banners.
 */
class PromotionalBanner extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    public function getTable()
    {
        return config('lunar.database.table_prefix') . 'promotional_banners';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'subtitle',
        'description',
        'position',
        'order',
        'is_active',
        'link',
        'link_text',
        'link_type',
        'display_conditions',
        'starts_at',
        'ends_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'display_conditions' => 'array',
        'order' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * Register media collections and conversions.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('banners')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
    }

    /**
     * Register media conversions.
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('desktop')
            ->width(1920)
            ->height(600)
            ->quality(85)
            ->performOnCollections('banners');

        $this->addMediaConversion('tablet')
            ->width(1024)
            ->height(400)
            ->quality(85)
            ->performOnCollections('banners');

        $this->addMediaConversion('mobile')
            ->width(768)
            ->height(300)
            ->quality(85)
            ->performOnCollections('banners');
    }

    /**
     * Check if banner is currently active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Get the banner image URL.
     *
     * @param  string  $conversion
     * @return string|null
     */
    public function getImageUrl(string $conversion = 'desktop'): ?string
    {
        $media = $this->getFirstMedia('banners');
        
        if (!$media) {
            return null;
        }

        return $media->getUrl($conversion);
    }

    /**
     * Scope to get active banners.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')
                  ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')
                  ->orWhere('ends_at', '>', now());
            });
    }

    /**
     * Scope to get banners by position.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $position
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPosition($query, string $position)
    {
        return $query->where('position', $position)->orderBy('order');
    }
}


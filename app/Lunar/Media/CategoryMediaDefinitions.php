<?php

namespace App\Lunar\Media;

use Lunar\Base\MediaDefinitionsInterface;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Custom Media Definitions for Categories.
 * 
 * Provides media collections and conversions specifically for category images.
 */
class CategoryMediaDefinitions implements MediaDefinitionsInterface
{
    /**
     * Register media conversions for category images.
     * 
     * @param HasMedia $model
     * @param Media|null $media
     * @return void
     */
    public function registerMediaConversions(HasMedia $model, Media $media = null): void
    {
        // Small thumbnail for category cards
        $model->addMediaConversion('small')
            ->fit(Fit::Fill, 200, 200)
            ->sharpen(10)
            ->quality(85)
            ->keepOriginalImageFormat();

        // Medium size for category listings
        $model->addMediaConversion('thumb')
            ->fit(Fit::Fill, 400, 400)
            ->sharpen(10)
            ->quality(85)
            ->keepOriginalImageFormat();

        // Large size for category detail pages
        $model->addMediaConversion('large')
            ->fit(Fit::Fill, 800, 800)
            ->sharpen(10)
            ->quality(90)
            ->keepOriginalImageFormat();

        // Banner size for category headers
        $model->addMediaConversion('banner')
            ->width(1200)
            ->height(400)
            ->sharpen(10)
            ->quality(90)
            ->keepOriginalImageFormat();
    }

    /**
     * Register media collections for categories.
     * 
     * @param HasMedia $model
     * @return void
     */
    public function registerMediaCollections(HasMedia $model): void
    {
        $fallbackUrl = config('lunar.media.fallback.url');
        $fallbackPath = config('lunar.media.fallback.path');

        $model->mediaCollections = [];

        $collection = $model->addMediaCollection('image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif']);

        if ($fallbackUrl) {
            $collection = $collection->useFallbackUrl($fallbackUrl);
        }

        if ($fallbackPath) {
            $collection = $collection->useFallbackPath($fallbackPath);
        }
    }

    /**
     * Get media collection titles.
     * 
     * @return array
     */
    public function getMediaCollectionTitles(): array
    {
        return [
            'image' => 'Category Image',
        ];
    }

    /**
     * Get media collection descriptions.
     * 
     * @return array
     */
    public function getMediaCollectionDescriptions(): array
    {
        return [
            'image' => 'Category image for display',
        ];
    }
}


<?php

namespace App\Lunar\Media;

use Lunar\Base\MediaDefinitionsInterface;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Custom Media Definitions for Brands.
 * 
 * Provides media collections and conversions specifically for brand logos.
 */
class BrandMediaDefinitions implements MediaDefinitionsInterface
{
    /**
     * Register media conversions for brand logos.
     * 
     * @param HasMedia $model
     * @param Media|null $media
     * @return void
     */
    public function registerMediaConversions(HasMedia $model, Media $media = null): void
    {
        // Small logo for thumbnails
        $model->addMediaConversion('small')
            ->fit(Fit::Contain, 100, 100)
            ->sharpen(10)
            ->quality(90)
            ->keepOriginalImageFormat();

        // Medium logo for brand cards
        $model->addMediaConversion('thumb')
            ->fit(Fit::Contain, 200, 200)
            ->sharpen(10)
            ->quality(90)
            ->keepOriginalImageFormat();

        // Large logo for brand pages
        $model->addMediaConversion('large')
            ->fit(Fit::Contain, 400, 400)
            ->sharpen(10)
            ->quality(95)
            ->keepOriginalImageFormat();
    }

    /**
     * Register media collections for brands.
     * 
     * @param HasMedia $model
     * @return void
     */
    public function registerMediaCollections(HasMedia $model): void
    {
        $fallbackUrl = config('lunar.media.fallback.url');
        $fallbackPath = config('lunar.media.fallback.path');

        $model->mediaCollections = [];

        $collection = $model->addMediaCollection('logo')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/svg+xml', 'image/webp']);

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
            'logo' => 'Brand Logo',
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
            'logo' => 'Brand logo image',
        ];
    }
}


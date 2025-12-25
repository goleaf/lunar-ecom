<?php

namespace App\Lunar\Media;

use Lunar\Base\MediaDefinitionsInterface;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Media Definitions for Review Images.
 * 
 * Provides media collections and conversions for review images.
 */
class ReviewMediaDefinitions implements MediaDefinitionsInterface
{
    /**
     * Register media conversions for the model.
     * 
     * @param HasMedia $model
     * @param Media|null $media
     * @return void
     */
    public function registerMediaConversions(HasMedia $model, Media $media = null): void
    {
        // Thumbnail for review image galleries
        $model->addMediaConversion('thumb')
            ->fit(Fit::Fill, 200, 200)
            ->sharpen(10)
            ->quality(85)
            ->keepOriginalImageFormat();

        // Medium size for review image display
        $model->addMediaConversion('medium')
            ->fit(Fit::Fill, 600, 600)
            ->sharpen(10)
            ->quality(90)
            ->keepOriginalImageFormat();

        // Large size for lightbox/zoom
        $model->addMediaConversion('large')
            ->fit(Fit::Fill, 1200, 1200)
            ->sharpen(10)
            ->quality(92)
            ->keepOriginalImageFormat();
    }

    /**
     * Register media collections for the model.
     * 
     * @param HasMedia $model
     * @return void
     */
    public function registerMediaCollections(HasMedia $model): void
    {
        $model->addMediaCollection('images')
            ->useDisk('public')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    /**
     * Get media collection titles.
     * 
     * @return array
     */
    public function getMediaCollectionTitles(): array
    {
        return [
            'images' => 'Review Images',
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
            'images' => 'Images uploaded by customers in their reviews',
        ];
    }
}


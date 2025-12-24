<?php

namespace App\Lunar\Media;

use Lunar\Base\MediaDefinitionsInterface;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Custom Media Definitions for Lunar Models.
 * 
 * Provides custom media collections and conversions for Products and Collections.
 * See: https://docs.lunarphp.com/1.x/reference/media
 */
class CustomMediaDefinitions implements MediaDefinitionsInterface
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
        // Add a conversion for the admin panel to use
        $model->addMediaConversion('small')
            ->fit(Fit::Fill, 300, 300)
            ->sharpen(10)
            ->keepOriginalImageFormat();

        // Additional conversions for storefront use
        $model->addMediaConversion('thumb')
            ->fit(Fit::Fill, 400, 400)
            ->sharpen(10)
            ->keepOriginalImageFormat();

        $model->addMediaConversion('medium')
            ->fit(Fit::Fill, 800, 800)
            ->sharpen(10)
            ->keepOriginalImageFormat();

        $model->addMediaConversion('large')
            ->fit(Fit::Fill, 1200, 1200)
            ->sharpen(10)
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
        $fallbackUrl = config('lunar.media.fallback.url');
        $fallbackPath = config('lunar.media.fallback.path');

        // Reset to avoid duplication
        $model->mediaCollections = [];

        $collection = $model->addMediaCollection('images');

        if ($fallbackUrl) {
            $collection = $collection->useFallbackUrl($fallbackUrl);
        }

        if ($fallbackPath) {
            $collection = $collection->useFallbackPath($fallbackPath);
        }

        $this->registerCollectionConversions($collection, $model);
    }

    /**
     * Register conversions for the media collection.
     * 
     * @param MediaCollection $collection
     * @param HasMedia $model
     * @return void
     */
    protected function registerCollectionConversions(MediaCollection $collection, HasMedia $model): void
    {
        $conversions = [
            'zoom' => [
                'width' => 500,
                'height' => 500,
            ],
            'large' => [
                'width' => 800,
                'height' => 800,
            ],
            'medium' => [
                'width' => 500,
                'height' => 500,
            ],
        ];

        $collection->registerMediaConversions(function (Media $media) use ($model, $conversions) {
            foreach ($conversions as $key => $conversion) {
                $model->addMediaConversion($key)
                    ->fit(
                        Fit::Fill,
                        $conversion['width'],
                        $conversion['height']
                    )->keepOriginalImageFormat();
            }
        });
    }

    /**
     * Get media collection titles.
     * 
     * @return array
     */
    public function getMediaCollectionTitles(): array
    {
        return [
            'images' => 'Images',
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
            'images' => 'Product or collection images',
        ];
    }
}


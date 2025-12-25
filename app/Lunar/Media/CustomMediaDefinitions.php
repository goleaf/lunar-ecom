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
 * Provides custom media collections and conversions for Products, Collections, and Brands.
 * Includes responsive images, optimization, and multiple size conversions.
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
        // Small thumbnail for admin panel and thumbnails
        $model->addMediaConversion('small')
            ->fit(Fit::Fill, 300, 300)
            ->sharpen(10)
            ->quality(85)
            ->keepOriginalImageFormat();

        // Thumbnail for product cards and listings
        $model->addMediaConversion('thumb')
            ->fit(Fit::Fill, 400, 400)
            ->sharpen(10)
            ->quality(85)
            ->keepOriginalImageFormat();

        // Medium size for product detail pages
        $model->addMediaConversion('medium')
            ->fit(Fit::Fill, 800, 800)
            ->sharpen(10)
            ->quality(90)
            ->keepOriginalImageFormat();

        // Large size for zoom/lightbox
        $model->addMediaConversion('large')
            ->fit(Fit::Fill, 1200, 1200)
            ->sharpen(10)
            ->quality(92)
            ->keepOriginalImageFormat();

        // Extra large for high-DPI displays
        $model->addMediaConversion('xlarge')
            ->fit(Fit::Fill, 1920, 1920)
            ->sharpen(10)
            ->quality(95)
            ->keepOriginalImageFormat();

        // Responsive image sizes for srcset
        $model->addMediaConversion('responsive_320')
            ->width(320)
            ->sharpen(10)
            ->quality(85)
            ->keepOriginalImageFormat();

        $model->addMediaConversion('responsive_640')
            ->width(640)
            ->sharpen(10)
            ->quality(85)
            ->keepOriginalImageFormat();

        $model->addMediaConversion('responsive_768')
            ->width(768)
            ->sharpen(10)
            ->quality(85)
            ->keepOriginalImageFormat();

        $model->addMediaConversion('responsive_1024')
            ->width(1024)
            ->sharpen(10)
            ->quality(90)
            ->keepOriginalImageFormat();

        $model->addMediaConversion('responsive_1280')
            ->width(1280)
            ->sharpen(10)
            ->quality(90)
            ->keepOriginalImageFormat();

        $model->addMediaConversion('responsive_1920')
            ->width(1920)
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
     * This method registers additional conversions (zoom, large, medium) 
     * that are registered when media is added to the collection.
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


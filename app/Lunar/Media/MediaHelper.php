<?php

namespace App\Lunar\Media;

use Illuminate\Support\Collection;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Helper class for working with Lunar Media.
 * 
 * Provides convenience methods for managing media on Products and Collections.
 * See: https://docs.lunarphp.com/1.x/reference/media
 */
class MediaHelper
{
    /**
     * Get all images for a model.
     * 
     * @param HasMedia $model
     * @param string $collectionName
     * @return Collection
     */
    public static function getImages(HasMedia $model, string $collectionName = 'images'): Collection
    {
        return $model->getMedia($collectionName);
    }

    /**
     * Get the first image URL with optional conversion.
     * 
     * @param HasMedia $model
     * @param string $collectionName
     * @param string|null $conversion
     * @return string|null
     */
    public static function getFirstImageUrl(HasMedia $model, string $collectionName = 'images', ?string $conversion = null): ?string
    {
        $media = $model->getFirstMedia($collectionName);
        
        if (!$media) {
            // Return fallback URL if configured
            return config('lunar.media.fallback.url');
        }

        return $conversion 
            ? $media->getUrl($conversion)
            : $media->getUrl();
    }

    /**
     * Get the first image path with optional conversion.
     * 
     * @param HasMedia $model
     * @param string $collectionName
     * @param string|null $conversion
     * @return string|null
     */
    public static function getFirstImagePath(HasMedia $model, string $collectionName = 'images', ?string $conversion = null): ?string
    {
        $media = $model->getFirstMedia($collectionName);
        
        if (!$media) {
            // Return fallback path if configured
            return config('lunar.media.fallback.path');
        }

        return $conversion 
            ? $media->getPath($conversion)
            : $media->getPath();
    }

    /**
     * Add an image to a model.
     * 
     * Example usage:
     * MediaHelper::addImage($product, $request->file('image'));
     * 
     * @param HasMedia $model
     * @param mixed $file File upload or file path
     * @param string $collectionName
     * @return Media
     */
    public static function addImage(HasMedia $model, $file, string $collectionName = 'images'): Media
    {
        return $model->addMedia($file)->toMediaCollection($collectionName);
    }

    /**
     * Get thumbnail image URL (primary image with small conversion).
     * 
     * This is a convenience method that uses the thumbnail relationship
     * if available, otherwise falls back to first image.
     * 
     * @param HasMedia $model
     * @param string $conversion
     * @return string|null
     */
    public static function getThumbnailUrl(HasMedia $model, string $conversion = 'thumb'): ?string
    {
        // Try to get thumbnail relationship (primary image)
        if (method_exists($model, 'thumbnail') && $model->thumbnail) {
            return $conversion 
                ? $model->thumbnail->getUrl($conversion)
                : $model->thumbnail->getUrl();
        }

        // Fallback to first image
        return static::getFirstImageUrl($model, 'images', $conversion);
    }

    /**
     * Check if model has images.
     * 
     * @param HasMedia $model
     * @param string $collectionName
     * @return bool
     */
    public static function hasImages(HasMedia $model, string $collectionName = 'images'): bool
    {
        return $model->getMedia($collectionName)->count() > 0;
    }

    /**
     * Get all image URLs with optional conversion.
     * 
     * @param HasMedia $model
     * @param string $collectionName
     * @param string|null $conversion
     * @return Collection
     */
    public static function getAllImageUrls(HasMedia $model, string $collectionName = 'images', ?string $conversion = null): Collection
    {
        return $model->getMedia($collectionName)->map(function ($media) use ($conversion) {
            return [
                'url' => $conversion ? $media->getUrl($conversion) : $media->getUrl(),
                'original' => $media->getUrl(),
                'name' => $media->name,
                'id' => $media->id,
            ];
        });
    }
}



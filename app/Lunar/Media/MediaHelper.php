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

    /**
     * Get responsive image srcset for a media item.
     * 
     * @param Media $media
     * @param array $breakpoints Optional custom breakpoints
     * @return string Srcset string
     */
    public static function getResponsiveSrcset(Media $media, array $breakpoints = null): string
    {
        $breakpoints = $breakpoints ?? config('lunar.media.responsive.breakpoints', [320, 640, 768, 1024, 1280, 1920]);
        $srcset = [];

        foreach ($breakpoints as $width) {
            $conversion = 'responsive_' . $width;
            try {
                $url = $media->getUrl($conversion);
                $srcset[] = "{$url} {$width}w";
            } catch (\Exception $e) {
                // Conversion might not exist, skip it
                continue;
            }
        }

        return implode(', ', $srcset);
    }

    /**
     * Get responsive image attributes (srcset and sizes).
     * 
     * @param Media $media
     * @param string $sizeType Size type from config (default, product_card, product_detail)
     * @param array $breakpoints Optional custom breakpoints
     * @return array Array with 'srcset' and 'sizes' keys
     */
    public static function getResponsiveAttributes(Media $media, string $sizeType = 'default', array $breakpoints = null): array
    {
        $srcset = static::getResponsiveSrcset($media, $breakpoints);
        $sizes = config("lunar.media.responsive.sizes.{$sizeType}", config('lunar.media.responsive.sizes.default'));

        return [
            'srcset' => $srcset,
            'sizes' => $sizes,
        ];
    }

    /**
     * Get responsive image HTML attributes string.
     * 
     * @param Media $media
     * @param string $sizeType
     * @param array $breakpoints
     * @return string HTML attributes string
     */
    public static function getResponsiveAttributesString(Media $media, string $sizeType = 'default', array $breakpoints = null): string
    {
        $attrs = static::getResponsiveAttributes($media, $sizeType, $breakpoints);
        
        $html = '';
        if (!empty($attrs['srcset'])) {
            $html .= 'srcset="' . htmlspecialchars($attrs['srcset']) . '" ';
        }
        if (!empty($attrs['sizes'])) {
            $html .= 'sizes="' . htmlspecialchars($attrs['sizes']) . '"';
        }

        return trim($html);
    }

    /**
     * Add multiple images to a model.
     * 
     * @param HasMedia $model
     * @param array $files Array of uploaded files
     * @param string $collectionName
     * @return Collection Collection of Media models
     */
    public static function addMultipleImages(HasMedia $model, array $files, string $collectionName = 'images'): Collection
    {
        $mediaItems = collect();

        foreach ($files as $file) {
            if ($file && $file->isValid()) {
                $media = $model->addMedia($file)
                    ->withCustomProperties(['order' => $mediaItems->count()])
                    ->toMediaCollection($collectionName);
                $mediaItems->push($media);
            }
        }

        return $mediaItems;
    }

    /**
     * Delete a media item by ID.
     * 
     * @param HasMedia $model
     * @param int $mediaId
     * @return bool
     */
    public static function deleteImage(HasMedia $model, int $mediaId): bool
    {
        $media = $model->getMedia()->firstWhere('id', $mediaId);
        
        if ($media) {
            $media->delete();
            return true;
        }

        return false;
    }

    /**
     * Reorder media items.
     * 
     * @param HasMedia $model
     * @param array $mediaIds Array of media IDs in desired order
     * @return void
     */
    public static function reorderImages(HasMedia $model, array $mediaIds): void
    {
        foreach ($mediaIds as $index => $mediaId) {
            $media = $model->getMedia()->firstWhere('id', $mediaId);
            if ($media) {
                $media->setCustomProperty('order', $index);
                $media->save();
            }
        }
    }
}



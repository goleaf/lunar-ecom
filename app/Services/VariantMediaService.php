<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\VariantMedia;
use Lunar\Models\Channel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Comprehensive variant media service.
 * 
 * Handles:
 * - Variant-specific image gallery
 * - Variant-specific videos
 * - 360° images
 * - 3D models / AR files
 * - Fallback to product images
 * - Media per channel
 * - Media per locale
 * - Sort order per variant
 * - Alt text & accessibility metadata
 */
class VariantMediaService
{
    /**
     * Get all media for variant.
     *
     * @param  ProductVariant  $variant
     * @param  array  $options
     * @return Collection
     */
    public function getMedia(
        ProductVariant $variant,
        array $options = []
    ): Collection {
        $channelId = $options['channel_id'] ?? null;
        $locale = $options['locale'] ?? app()->getLocale();
        $mediaType = $options['media_type'] ?? null;
        $includeFallback = $options['include_fallback'] ?? true;

        $query = VariantMedia::where('product_variant_id', $variant->id)
            ->orderBy('position')
            ->orderBy('id');

        // Filter by channel
        if ($channelId) {
            $query->where(function ($q) use ($channelId) {
                $q->whereNull('channel_id')
                  ->orWhere('channel_id', $channelId);
            });
        }

        // Filter by locale
        $query->where(function ($q) use ($locale) {
            $q->whereNull('locale')
              ->orWhere('locale', $locale);
        });

        // Filter by media type
        if ($mediaType) {
            $query->where('media_type', $mediaType);
        }

        $media = $query->with('media')->get();

        // If no variant media and fallback enabled, get product media
        if ($media->isEmpty() && $includeFallback) {
            return $this->getProductMediaFallback($variant->product, $channelId, $locale, $mediaType);
        }

        return $media;
    }

    /**
     * Get product media as fallback.
     *
     * @param  \App\Models\Product  $product
     * @param  int|null  $channelId
     * @param  string  $locale
     * @param  string|null  $mediaType
     * @return Collection
     */
    protected function getProductMediaFallback(
        $product,
        ?int $channelId,
        string $locale,
        ?string $mediaType
    ): Collection {
        // Use product's media collection
        $mediaCollection = $product->getMedia('images');
        
        return $mediaCollection->map(function ($media, $index) use ($locale) {
            return new VariantMedia([
                'media_id' => $media->id,
                'product_variant_id' => null, // Product media
                'media_type' => 'image',
                'channel_id' => null,
                'locale' => null,
                'primary' => $index === 0,
                'position' => $index + 1,
                'alt_text' => null,
                'caption' => null,
                'is_fallback' => true,
            ]);
        });
    }

    /**
     * Get images for variant.
     *
     * @param  ProductVariant  $variant
     * @param  array  $options
     * @return Collection
     */
    public function getImages(ProductVariant $variant, array $options = []): Collection
    {
        return $this->getMedia($variant, array_merge($options, ['media_type' => 'image']));
    }

    /**
     * Get videos for variant.
     *
     * @param  ProductVariant  $variant
     * @param  array  $options
     * @return Collection
     */
    public function getVideos(ProductVariant $variant, array $options = []): Collection
    {
        return $this->getMedia($variant, array_merge($options, ['media_type' => 'video']));
    }

    /**
     * Get 360° images for variant.
     *
     * @param  ProductVariant  $variant
     * @param  array  $options
     * @return Collection
     */
    public function get360Images(ProductVariant $variant, array $options = []): Collection
    {
        return $this->getMedia($variant, array_merge($options, ['media_type' => 'image_360']));
    }

    /**
     * Get 3D models / AR files for variant.
     *
     * @param  ProductVariant  $variant
     * @param  array  $options
     * @return Collection
     */
    public function get3DModels(ProductVariant $variant, array $options = []): Collection
    {
        return $this->getMedia($variant, array_merge($options, ['media_type' => 'model_3d']));
    }

    /**
     * Get AR files for variant.
     *
     * @param  ProductVariant  $variant
     * @param  array  $options
     * @return Collection
     */
    public function getARFiles(ProductVariant $variant, array $options = []): Collection
    {
        return $this->getMedia($variant, array_merge($options, ['media_type' => 'ar_file']));
    }

    /**
     * Get primary image for variant.
     *
     * @param  ProductVariant  $variant
     * @param  int|null  $channelId
     * @param  string|null  $locale
     * @return VariantMedia|null
     */
    public function getPrimaryImage(
        ProductVariant $variant,
        ?int $channelId = null,
        ?string $locale = null
    ): ?VariantMedia {
        $query = VariantMedia::where('product_variant_id', $variant->id)
            ->where('media_type', 'image')
            ->where('primary', true)
            ->orderBy('position');

        if ($channelId) {
            $query->where(function ($q) use ($channelId) {
                $q->whereNull('channel_id')
                  ->orWhere('channel_id', $channelId);
            });
        }

        if ($locale) {
            $query->where(function ($q) use ($locale) {
                $q->whereNull('locale')
                  ->orWhere('locale', $locale);
            });
        }

        $primary = $query->with('media')->first();

        // Fallback to product image
        if (!$primary && $variant->product) {
            $productMedia = $variant->product->getFirstMedia('images');
            if ($productMedia) {
                return new VariantMedia([
                    'media_id' => $productMedia->id,
                    'media_type' => 'image',
                    'primary' => true,
                    'is_fallback' => true,
                ]);
            }
        }

        return $primary;
    }

    /**
     * Attach media to variant.
     *
     * @param  ProductVariant  $variant
     * @param  int  $mediaId
     * @param  array  $options
     * @return VariantMedia
     */
    public function attachMedia(ProductVariant $variant, int $mediaId, array $options = []): VariantMedia
    {
        // If setting as primary, unset other primary images of same type
        if ($options['primary'] ?? false) {
            VariantMedia::where('product_variant_id', $variant->id)
                ->where('media_type', $options['media_type'] ?? 'image')
                ->where('channel_id', $options['channel_id'] ?? null)
                ->where('locale', $options['locale'] ?? null)
                ->update(['primary' => false]);
        }

        // Get next position
        $maxPosition = VariantMedia::where('product_variant_id', $variant->id)
            ->where('media_type', $options['media_type'] ?? 'image')
            ->max('position') ?? 0;

        return VariantMedia::create([
            'media_id' => $mediaId,
            'product_variant_id' => $variant->id,
            'media_type' => $options['media_type'] ?? 'image',
            'channel_id' => $options['channel_id'] ?? null,
            'locale' => $options['locale'] ?? null,
            'primary' => $options['primary'] ?? false,
            'position' => $options['position'] ?? ($maxPosition + 1),
            'alt_text' => $options['alt_text'] ?? null,
            'caption' => $options['caption'] ?? null,
            'accessibility_metadata' => $options['accessibility_metadata'] ?? null,
            'media_metadata' => $options['media_metadata'] ?? null,
        ]);
    }

    /**
     * Detach media from variant.
     *
     * @param  ProductVariant  $variant
     * @param  int  $mediaId
     * @return bool
     */
    public function detachMedia(ProductVariant $variant, int $mediaId): bool
    {
        return VariantMedia::where('product_variant_id', $variant->id)
            ->where('media_id', $mediaId)
            ->delete() > 0;
    }

    /**
     * Set primary media.
     *
     * @param  ProductVariant  $variant
     * @param  int  $mediaId
     * @param  string|null  $mediaType
     * @return bool
     */
    public function setPrimaryMedia(
        ProductVariant $variant,
        int $mediaId,
        ?string $mediaType = null
    ): bool {
        // Unset all primary of same type
        VariantMedia::where('product_variant_id', $variant->id)
            ->where('media_type', $mediaType ?? 'image')
            ->update(['primary' => false]);

        // Set new primary
        return VariantMedia::where('product_variant_id', $variant->id)
            ->where('media_id', $mediaId)
            ->update(['primary' => true]) > 0;
    }

    /**
     * Reorder media.
     *
     * @param  ProductVariant  $variant
     * @param  array  $mediaIds  Array of media IDs in desired order
     * @return void
     */
    public function reorderMedia(ProductVariant $variant, array $mediaIds): void
    {
        DB::transaction(function () use ($variant, $mediaIds) {
            foreach ($mediaIds as $position => $mediaId) {
                VariantMedia::where('product_variant_id', $variant->id)
                    ->where('media_id', $mediaId)
                    ->update(['position' => $position + 1]);
            }
        });
    }

    /**
     * Get media gallery with all types.
     *
     * @param  ProductVariant  $variant
     * @param  array  $options
     * @return array
     */
    public function getMediaGallery(ProductVariant $variant, array $options = []): array
    {
        $channelId = $options['channel_id'] ?? null;
        $locale = $options['locale'] ?? app()->getLocale();

        return [
            'images' => $this->getImages($variant, $options)->map(function ($variantMedia) use ($locale) {
                return $this->formatMedia($variantMedia, $locale);
            }),
            'videos' => $this->getVideos($variant, $options)->map(function ($variantMedia) use ($locale) {
                return $this->formatMedia($variantMedia, $locale);
            }),
            'images_360' => $this->get360Images($variant, $options)->map(function ($variantMedia) use ($locale) {
                return $this->formatMedia($variantMedia, $locale);
            }),
            'models_3d' => $this->get3DModels($variant, $options)->map(function ($variantMedia) use ($locale) {
                return $this->formatMedia($variantMedia, $locale);
            }),
            'ar_files' => $this->getARFiles($variant, $options)->map(function ($variantMedia) use ($locale) {
                return $this->formatMedia($variantMedia, $locale);
            }),
            'primary_image' => $this->getPrimaryImage($variant, $channelId, $locale),
        ];
    }

    /**
     * Format media for response.
     *
     * @param  VariantMedia  $variantMedia
     * @param  string  $locale
     * @return array
     */
    protected function formatMedia(VariantMedia $variantMedia, string $locale): array
    {
        $media = $variantMedia->media;

        if (!$media) {
            return [];
        }

        return [
            'id' => $media->id,
            'variant_media_id' => $variantMedia->id,
            'media_type' => $variantMedia->media_type,
            'url' => $media->getUrl(),
            'thumb_url' => $media->getUrl('thumb'),
            'primary' => $variantMedia->primary,
            'position' => $variantMedia->position,
            'alt_text' => $variantMedia->getAltText($locale),
            'caption' => $variantMedia->getCaption($locale),
            'accessibility_metadata' => $variantMedia->accessibility_metadata,
            'media_metadata' => $variantMedia->media_metadata,
            'channel_id' => $variantMedia->channel_id,
            'locale' => $variantMedia->locale,
            'is_fallback' => $variantMedia->is_fallback ?? false,
        ];
    }

    /**
     * Bulk attach media.
     *
     * @param  ProductVariant  $variant
     * @param  array  $mediaData  Array of ['media_id' => int, 'options' => array]
     * @return Collection
     */
    public function bulkAttachMedia(ProductVariant $variant, array $mediaData): Collection
    {
        $attached = collect();

        DB::transaction(function () use ($variant, $mediaData, &$attached) {
            foreach ($mediaData as $data) {
                $mediaId = $data['media_id'];
                $options = $data['options'] ?? [];
                
                $attached->push($this->attachMedia($variant, $mediaId, $options));
            }
        });

        return $attached;
    }

    /**
     * Update media metadata.
     *
     * @param  ProductVariant  $variant
     * @param  int  $mediaId
     * @param  array  $metadata
     * @return bool
     */
    public function updateMediaMetadata(ProductVariant $variant, int $mediaId, array $metadata): bool
    {
        return VariantMedia::where('product_variant_id', $variant->id)
            ->where('media_id', $mediaId)
            ->update($metadata) > 0;
    }

    /**
     * Sync media for variant (replace all media).
     *
     * @param  ProductVariant  $variant
     * @param  array  $mediaData
     * @return void
     */
    public function syncMedia(ProductVariant $variant, array $mediaData): void
    {
        DB::transaction(function () use ($variant, $mediaData) {
            // Delete existing media
            VariantMedia::where('product_variant_id', $variant->id)->delete();

            // Attach new media
            $this->bulkAttachMedia($variant, $mediaData);
        });
    }
}


<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ChannelProductData;
use Lunar\Models\Channel;
use Illuminate\Support\Collection;

/**
 * Service for managing channel-specific product data.
 */
class ChannelProductService
{
    /**
     * Get or create channel-specific data for a product.
     *
     * @param  Product  $product
     * @param  Channel  $channel
     * @return ChannelProductData
     */
    public function getOrCreateChannelData(Product $product, Channel $channel): ChannelProductData
    {
        return ChannelProductData::firstOrCreate(
            [
                'product_id' => $product->id,
                'channel_id' => $channel->id,
            ],
            [
                'visibility' => 'public',
                'is_visible' => true,
            ]
        );
    }

    /**
     * Get channel-specific visibility for a product.
     *
     * @param  Product  $product
     * @param  Channel  $channel
     * @return string|null
     */
    public function getVisibility(Product $product, Channel $channel): ?string
    {
        $data = $this->getOrCreateChannelData($product, $channel);
        return $data->visibility ?? $product->visibility ?? 'public';
    }

    /**
     * Set channel-specific visibility for a product.
     *
     * @param  Product  $product
     * @param  Channel  $channel
     * @param  string  $visibility
     * @return ChannelProductData
     */
    public function setVisibility(Product $product, Channel $channel, string $visibility): ChannelProductData
    {
        $data = $this->getOrCreateChannelData($product, $channel);
        $data->update(['visibility' => $visibility]);
        return $data;
    }

    /**
     * Get channel-specific description.
     *
     * @param  Product  $product
     * @param  Channel  $channel
     * @param  string  $type  'short', 'full', or 'technical'
     * @return string|null
     */
    public function getDescription(Product $product, Channel $channel, string $type = 'full'): ?string
    {
        $data = $this->getOrCreateChannelData($product, $channel);
        
        return match($type) {
            'short' => $data->short_description ?? $product->short_description,
            'full' => $data->full_description ?? $product->translateAttribute('description'),
            'technical' => $data->technical_description ?? $product->technical_description,
            default => null,
        };
    }

    /**
     * Set channel-specific description.
     *
     * @param  Product  $product
     * @param  Channel  $channel
     * @param  string  $type
     * @param  string  $description
     * @return ChannelProductData
     */
    public function setDescription(Product $product, Channel $channel, string $type, string $description): ChannelProductData
    {
        $data = $this->getOrCreateChannelData($product, $channel);
        
        $field = match($type) {
            'short' => 'short_description',
            'full' => 'full_description',
            'technical' => 'technical_description',
            default => 'full_description',
        };
        
        $data->update([$field => $description]);
        return $data;
    }

    /**
     * Get channel-specific SEO fields.
     *
     * @param  Product  $product
     * @param  Channel  $channel
     * @return array
     */
    public function getSEOFields(Product $product, Channel $channel): array
    {
        $data = $this->getOrCreateChannelData($product, $channel);
        
        return [
            'meta_title' => $data->meta_title ?? $product->meta_title,
            'meta_description' => $data->meta_description ?? $product->meta_description,
            'meta_keywords' => $data->meta_keywords ?? $product->meta_keywords,
        ];
    }

    /**
     * Set channel-specific SEO fields.
     *
     * @param  Product  $product
     * @param  Channel  $channel
     * @param  array  $seoFields
     * @return ChannelProductData
     */
    public function setSEOFields(Product $product, Channel $channel, array $seoFields): ChannelProductData
    {
        $data = $this->getOrCreateChannelData($product, $channel);
        
        $data->update([
            'meta_title' => $seoFields['meta_title'] ?? null,
            'meta_description' => $seoFields['meta_description'] ?? null,
            'meta_keywords' => $seoFields['meta_keywords'] ?? null,
        ]);
        
        return $data;
    }

    /**
     * Check if product is visible in channel.
     *
     * @param  Product  $product
     * @param  Channel  $channel
     * @return bool
     */
    public function isVisible(Product $product, Channel $channel): bool
    {
        $data = ChannelProductData::where('product_id', $product->id)
            ->where('channel_id', $channel->id)
            ->first();
        
        if (!$data) {
            // Fallback to product's default visibility
            return $product->visibility === 'public';
        }
        
        if (!$data->is_visible) {
            return false;
        }
        
        // Check published_at
        if ($data->published_at && $data->published_at->isFuture()) {
            return false;
        }
        
        // Check scheduled publish
        if ($data->scheduled_publish_at && $data->scheduled_publish_at->isFuture()) {
            return false;
        }
        
        // Check scheduled unpublish
        if ($data->scheduled_unpublish_at && $data->scheduled_unpublish_at->isPast()) {
            return false;
        }
        
        return $data->visibility === 'public';
    }

    /**
     * Get all channels where product is visible.
     *
     * @param  Product  $product
     * @return Collection
     */
    public function getVisibleChannels(Product $product): Collection
    {
        return Channel::whereHas('channelProductData', function ($query) use ($product) {
            $query->where('product_id', $product->id)
                ->where('is_visible', true)
                ->where('visibility', 'public')
                ->where(function ($q) {
                    $q->whereNull('published_at')
                      ->orWhere('published_at', '<=', now());
                });
        })->orWhereDoesntHave('channelProductData', function ($query) use ($product) {
            $query->where('product_id', $product->id);
        })->get();
    }

    /**
     * Set geo-restrictions for product in channel.
     *
     * @param  Product  $product
     * @param  Channel  $channel
     * @param  array|null  $allowedCountries
     * @param  array|null  $blockedCountries
     * @param  array|null  $allowedRegions
     * @return ChannelProductData
     */
    public function setGeoRestrictions(
        Product $product,
        Channel $channel,
        ?array $allowedCountries = null,
        ?array $blockedCountries = null,
        ?array $allowedRegions = null
    ): ChannelProductData {
        $data = $this->getOrCreateChannelData($product, $channel);
        
        $data->update([
            'allowed_countries' => $allowedCountries,
            'blocked_countries' => $blockedCountries,
            'allowed_regions' => $allowedRegions,
        ]);
        
        return $data;
    }

    /**
     * Check if product is available in country for channel.
     *
     * @param  Product  $product
     * @param  Channel  $channel
     * @param  string  $countryCode
     * @return bool
     */
    public function isAvailableInCountry(Product $product, Channel $channel, string $countryCode): bool
    {
        $data = ChannelProductData::where('product_id', $product->id)
            ->where('channel_id', $channel->id)
            ->first();
        
        if (!$data) {
            // Check channel-level restrictions
            return app(GeoRestrictionService::class)->isCountryAllowed($channel, $countryCode);
        }
        
        return $data->isAvailableInCountry($countryCode);
    }
}


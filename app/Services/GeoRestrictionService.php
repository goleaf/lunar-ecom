<?php

namespace App\Services;

use Lunar\Models\Channel;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

/**
 * Service for managing geo-restrictions.
 */
class GeoRestrictionService
{
    /**
     * Check if country is allowed for channel.
     *
     * @param  Channel  $channel
     * @param  string  $countryCode  ISO 2-letter country code
     * @return bool
     */
    public function isCountryAllowed(Channel $channel, string $countryCode): bool
    {
        $cacheKey = "geo.channel.{$channel->id}.{$countryCode}";
        
        return Cache::remember($cacheKey, 3600, function () use ($channel, $countryCode) {
            // Check blocked countries first
            if ($channel->blocked_countries && in_array($countryCode, $channel->blocked_countries)) {
                return false;
            }
            
            // If allowed countries specified, check if country is in list
            if ($channel->allowed_countries && !empty($channel->allowed_countries)) {
                return in_array($countryCode, $channel->allowed_countries);
            }
            
            // If no restrictions, allow
            return true;
        });
    }

    /**
     * Check if product is available in country for channel.
     *
     * @param  Product  $product
     * @param  Channel  $channel
     * @param  string  $countryCode
     * @return bool
     */
    public function isProductAvailableInCountry(
        Product $product,
        Channel $channel,
        string $countryCode
    ): bool {
        // First check channel-level restrictions
        if (!$this->isCountryAllowed($channel, $countryCode)) {
            return false;
        }
        
        // Then check product-level restrictions
        $channelProductService = app(ChannelProductService::class);
        return $channelProductService->isAvailableInCountry($product, $channel, $countryCode);
    }

    /**
     * Get allowed countries for channel.
     *
     * @param  Channel  $channel
     * @return array|null
     */
    public function getAllowedCountries(Channel $channel): ?array
    {
        return $channel->allowed_countries;
    }

    /**
     * Get blocked countries for channel.
     *
     * @param  Channel  $channel
     * @return array|null
     */
    public function getBlockedCountries(Channel $channel): ?array
    {
        return $channel->blocked_countries;
    }

    /**
     * Set geo-restrictions for channel.
     *
     * @param  Channel  $channel
     * @param  array|null  $allowedCountries
     * @param  array|null  $blockedCountries
     * @param  array|null  $allowedRegions
     * @return Channel
     */
    public function setChannelRestrictions(
        Channel $channel,
        ?array $allowedCountries = null,
        ?array $blockedCountries = null,
        ?array $allowedRegions = null
    ): Channel {
        $channel->update([
            'allowed_countries' => $allowedCountries,
            'blocked_countries' => $blockedCountries,
            'allowed_regions' => $allowedRegions,
        ]);
        
        // Clear cache
        $this->clearCache($channel);
        
        return $channel;
    }

    /**
     * Clear geo-restriction cache for channel.
     *
     * @param  Channel  $channel
     * @return void
     */
    public function clearCache(Channel $channel): void
    {
        // Clear all country-specific cache entries for this channel
        // In production, you might want to use cache tags if supported
        Cache::forget("geo.channel.{$channel->id}");
    }

    /**
     * Get country code from request (IP geolocation or header).
     *
     * @return string|null
     */
    public function getCountryFromRequest(): ?string
    {
        // Check for country header (set by CDN/load balancer)
        $countryHeader = request()->header('CF-IPCountry') // Cloudflare
            ?? request()->header('X-Country-Code')
            ?? request()->header('CloudFront-Viewer-Country'); // AWS CloudFront
        
        if ($countryHeader) {
            return strtoupper($countryHeader);
        }
        
        // Fallback to IP geolocation (would need a service like MaxMind GeoIP2)
        // For now, return null
        return null;
    }
}


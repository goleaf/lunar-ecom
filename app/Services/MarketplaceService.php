<?php

namespace App\Services;

use Lunar\Models\Channel;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing marketplace integrations.
 * 
 * Provides marketplace-ready structure for syncing products to:
 * - Amazon
 * - eBay
 * - Etsy
 * - Shopify
 * - WooCommerce
 * - Custom marketplaces
 */
class MarketplaceService
{
    /**
     * Supported marketplace types.
     */
    const MARKETPLACE_TYPES = [
        'webstore',
        'amazon',
        'ebay',
        'etsy',
        'shopify',
        'woocommerce',
        'custom',
    ];

    /**
     * Create a marketplace channel.
     *
     * @param  string  $name
     * @param  string  $handle
     * @param  string  $marketplaceType
     * @param  array  $config
     * @return Channel
     */
    public function createMarketplaceChannel(
        string $name,
        string $handle,
        string $marketplaceType,
        array $config = []
    ): Channel {
        if (!in_array($marketplaceType, self::MARKETPLACE_TYPES)) {
            throw new \InvalidArgumentException("Invalid marketplace type: {$marketplaceType}");
        }

        return Channel::create([
            'name' => $name,
            'handle' => $handle,
            'marketplace_type' => $marketplaceType,
            'marketplace_config' => $config,
            'is_active' => true,
            'sync_enabled' => false, // Enable manually after setup
        ]);
    }

    /**
     * Sync product to marketplace.
     *
     * @param  Product  $product
     * @param  Channel  $channel
     * @return bool
     */
    public function syncProductToMarketplace(Product $product, Channel $channel): bool
    {
        if (!$channel->sync_enabled) {
            Log::warning("Sync disabled for channel: {$channel->handle}");
            return false;
        }

        try {
            $method = 'syncTo' . ucfirst($channel->marketplace_type);
            
            if (method_exists($this, $method)) {
                return $this->$method($product, $channel);
            }
            
            // Default sync handler
            return $this->syncToCustom($product, $channel);
        } catch (\Exception $e) {
            Log::error("Failed to sync product {$product->id} to channel {$channel->handle}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sync product to Amazon.
     *
     * @param  Product  $product
     * @param  Channel  $channel
     * @return bool
     */
    protected function syncToAmazon(Product $product, Channel $channel): bool
    {
        $config = $channel->marketplace_config ?? [];
        
        // Validate required config
        if (empty($config['api_key']) || empty($config['api_secret'])) {
            throw new \RuntimeException('Amazon API credentials not configured');
        }

        // Build Amazon product data
        $amazonData = $this->buildAmazonProductData($product, $channel);
        
        // Here you would make API call to Amazon MWS/SP-API
        // For now, just log
        Log::info("Syncing product {$product->id} to Amazon", $amazonData);
        
        return true;
    }

    /**
     * Sync product to eBay.
     *
     * @param  Product  $product
     * @param  Channel  $channel
     * @return bool
     */
    protected function syncToEbay(Product $product, Channel $channel): bool
    {
        $config = $channel->marketplace_config ?? [];
        
        if (empty($config['app_id']) || empty($config['dev_id']) || empty($config['cert_id'])) {
            throw new \RuntimeException('eBay API credentials not configured');
        }

        $ebayData = $this->buildEbayProductData($product, $channel);
        
        Log::info("Syncing product {$product->id} to eBay", $ebayData);
        
        return true;
    }

    /**
     * Sync product to custom marketplace.
     *
     * @param  Product  $product
     * @param  Channel  $channel
     * @return bool
     */
    protected function syncToCustom(Product $product, Channel $channel): bool
    {
        // Custom marketplace sync logic
        // Can be extended via events/listeners
        event(new \App\Events\ProductSyncingToMarketplace($product, $channel));
        
        return true;
    }

    /**
     * Build Amazon product data structure.
     *
     * @param  Product  $product
     * @param  Channel  $channel
     * @return array
     */
    protected function buildAmazonProductData(Product $product, Channel $channel): array
    {
        $channelProductService = app(ChannelProductService::class);
        
        return [
            'sku' => $product->sku ?? $product->variants->first()?->sku,
            'title' => $product->translateAttribute('name'),
            'description' => $channelProductService->getDescription($product, $channel, 'full'),
            'price' => $this->getChannelPrice($product, $channel),
            'quantity' => $product->variants->sum('stock'),
            'images' => $this->getChannelImages($product, $channel),
            'category' => $product->categories->first()?->getName(),
            'brand' => $product->brand?->name,
            'condition' => $product->condition ?? 'New',
            'weight' => $product->weight,
            'dimensions' => [
                'length' => $product->length,
                'width' => $product->width,
                'height' => $product->height,
            ],
        ];
    }

    /**
     * Build eBay product data structure.
     *
     * @param  Product  $product
     * @param  Channel  $channel
     * @return array
     */
    protected function buildEbayProductData(Product $product, Channel $channel): array
    {
        $channelProductService = app(ChannelProductService::class);
        
        return [
            'title' => $product->translateAttribute('name'),
            'description' => $channelProductService->getDescription($product, $channel, 'full'),
            'start_price' => $this->getChannelPrice($product, $channel),
            'quantity' => $product->variants->sum('stock'),
            'pictures' => $this->getChannelImages($product, $channel),
            'category_id' => $this->getEbayCategoryId($product, $channel),
            'condition' => $this->mapConditionToEbay($product->condition ?? 'new'),
            'shipping' => $this->getShippingInfo($product, $channel),
        ];
    }

    /**
     * Get channel-specific price for product.
     *
     * @param  Product  $product
     * @param  Channel  $channel
     * @return float|null
     */
    protected function getChannelPrice(Product $product, Channel $channel): ?float
    {
        $variant = $product->variants->first();
        if (!$variant) {
            return null;
        }

        // Get price for channel
        $price = $variant->prices()
            ->where('channel_id', $channel->id)
            ->first();
        
        if ($price) {
            return $price->price->decimal;
        }
        
        // Fallback to default price
        $defaultPrice = $variant->prices()->first();
        return $defaultPrice?->price->decimal;
    }

    /**
     * Get channel-specific images.
     *
     * @param  Product  $product
     * @param  Channel  $channel
     * @return array
     */
    protected function getChannelImages(Product $product, Channel $channel): array
    {
        $channelMediaService = app(ChannelMediaService::class);
        $media = $channelMediaService->getMedia($product, $channel, 'images');
        
        return $media->map(function ($item) {
            return $item->getUrl('large') ?? $item->getUrl();
        })->toArray();
    }

    /**
     * Get eBay category ID (would need mapping).
     *
     * @param  Product  $product
     * @param  Channel  $channel
     * @return string|null
     */
    protected function getEbayCategoryId(Product $product, Channel $channel): ?string
    {
        // This would map your categories to eBay categories
        // For now, return null
        return null;
    }

    /**
     * Map condition to eBay format.
     *
     * @param  string  $condition
     * @return string
     */
    protected function mapConditionToEbay(string $condition): string
    {
        return match($condition) {
            'new' => 'New',
            'refurbished' => 'Refurbished',
            'used' => 'Used',
            default => 'New',
        };
    }

    /**
     * Get shipping information.
     *
     * @param  Product  $product
     * @param  Channel  $channel
     * @return array
     */
    protected function getShippingInfo(Product $product, Channel $channel): array
    {
        return [
            'weight' => $product->weight,
            'dimensions' => [
                'length' => $product->length,
                'width' => $product->width,
                'height' => $product->height,
            ],
        ];
    }

    /**
     * Bulk sync products to marketplace.
     *
     * @param  Collection  $products
     * @param  Channel  $channel
     * @return array  ['success' => int, 'failed' => int]
     */
    public function bulkSyncProducts(Collection $products, Channel $channel): array
    {
        $success = 0;
        $failed = 0;
        
        foreach ($products as $product) {
            if ($this->syncProductToMarketplace($product, $channel)) {
                $success++;
            } else {
                $failed++;
            }
        }
        
        // Update last sync timestamp
        $channel->update(['last_synced_at' => now()]);
        
        return [
            'success' => $success,
            'failed' => $failed,
            'total' => $products->count(),
        ];
    }

    /**
     * Get sync status for channel.
     *
     * @param  Channel  $channel
     * @return array
     */
    public function getSyncStatus(Channel $channel): array
    {
        return [
            'sync_enabled' => $channel->sync_enabled,
            'last_synced_at' => $channel->last_synced_at?->toIso8601String(),
            'marketplace_type' => $channel->marketplace_type,
            'is_active' => $channel->is_active,
        ];
    }
}


<?php

namespace App\Services\Cache;

use App\Models\ProductVariant;
use App\Models\PriceMatrix;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Lunar\Models\Currency;
use Lunar\Models\CustomerGroup;
use Lunar\Models\Channel;
use Lunar\Models\Price;
use Illuminate\Support\Collection;

/**
 * Pricing Cache Service
 * 
 * Caches pricing inputs (not final results):
 * - Attribute metadata
 * - Variant availability matrix
 * - Base prices
 * - Contract price lists
 * - Promotion definitions
 * 
 * Never caches:
 * - Final cart totals
 * - Stock availability (hard)
 * - Locked order prices
 */
class PricingCacheService
{
    protected string $prefix = 'pricing';
    protected int $defaultTtl = 3600; // 1 hour
    protected array $requestCache = []; // In-memory request scope cache

    protected function cacheEnabled(): bool
    {
        // Never require external Redis in tests.
        if (app()->environment('testing')) {
            return false;
        }

        return (bool) config('pricing_cache.enabled', true);
    }

    protected function cacheStore(): string
    {
        $store = (string) config('pricing_cache.store', 'redis');
        return $store !== '' ? $store : (string) config('cache.default', 'array');
    }

    protected function cacheGet(string $key, $default = null)
    {
        if (!$this->cacheEnabled()) {
            return $default;
        }

        try {
            return Cache::store($this->cacheStore())->get($key, $default);
        } catch (\Throwable) {
            return $default;
        }
    }

    protected function cachePut(string $key, $value, int $ttlSeconds): void
    {
        if (!$this->cacheEnabled()) {
            return;
        }

        try {
            Cache::store($this->cacheStore())->put($key, $value, $ttlSeconds);
        } catch (\Throwable) {
            // Ignore cache failures (e.g. Redis unavailable) - pricing must still work.
        }
    }

    protected function cacheIncrement(string $key, int $by = 1): void
    {
        if (!$this->cacheEnabled()) {
            return;
        }

        try {
            Cache::store($this->cacheStore())->increment($key, $by);
        } catch (\Throwable) {
            // Ignore.
        }
    }

    /**
     * Get attribute metadata (cached).
     */
    public function getAttributeMetadata(int $variantId, ?int $currencyId = null, ?int $customerGroupId = null): ?array
    {
        $key = $this->buildKey('attr_meta', [
            'variant' => $variantId,
            'currency' => $currencyId,
            'group' => $customerGroupId,
        ]);

        // Check request cache first
        if (isset($this->requestCache[$key])) {
            return $this->requestCache[$key];
        }

        // Check cache
        $cached = $this->cacheGet($key);
        if ($cached !== null) {
            $this->requestCache[$key] = $cached;
            return $cached;
        }

        // Load from database
        $variant = ProductVariant::with(['product.attributeGroups.attributes'])->find($variantId);
        if (!$variant) {
            return null;
        }

        $metadata = [
            'variant_id' => $variantId,
            'product_id' => $variant->product_id,
            'sku' => $variant->sku,
            'attributes' => $variant->product->attributeGroups->flatMap->attributes->map(function ($attr) {
                return [
                    'id' => $attr->id,
                    'name' => $attr->name,
                    'type' => $attr->type,
                    'required' => $attr->required ?? false,
                ];
            })->toArray(),
            'version' => $this->getVersion('attribute_metadata'),
        ];

        // Cache with version
        $this->cachePut($key, $metadata, $this->defaultTtl);
        $this->requestCache[$key] = $metadata;

        return $metadata;
    }

    /**
     * Get variant availability matrix (cached).
     */
    public function getVariantAvailabilityMatrix(int $variantId, ?int $channelId = null): ?array
    {
        $key = $this->buildKey('variant_avail', [
            'variant' => $variantId,
            'channel' => $channelId,
        ]);

        // Check request cache first
        if (isset($this->requestCache[$key])) {
            return $this->requestCache[$key];
        }

        // Check cache
        $cached = $this->cacheGet($key);
        if ($cached !== null) {
            $this->requestCache[$key] = $cached;
            return $cached;
        }

        // Load from database
        $variant = ProductVariant::find($variantId);
        if (!$variant) {
            return null;
        }

        $matrix = [
            'variant_id' => $variantId,
            'purchasable' => $variant->purchasable ?? 'always',
            'backorder' => $variant->backorder ?? 0,
            'stock' => $variant->stock ?? 0,
            'status' => $variant->status ?? 'draft',
            'channel_id' => $channelId,
            'version' => $this->getVersion('variant_availability'),
        ];

        // Cache with version
        $this->cachePut($key, $matrix, $this->defaultTtl);
        $this->requestCache[$key] = $matrix;

        return $matrix;
    }

    /**
     * Get base price (cached).
     */
    public function getBasePrice(
        int $variantId,
        int $currencyId,
        ?int $customerGroupId = null,
        ?int $channelId = null,
        int $quantity = 1
    ): ?int {
        $key = $this->buildKey('base_price', [
            'variant' => $variantId,
            'currency' => $currencyId,
            'group' => $customerGroupId,
            'channel' => $channelId,
            'qty' => $quantity,
        ]);

        // Check request cache first
        if (isset($this->requestCache[$key])) {
            return $this->requestCache[$key];
        }

        // Check cache
        $cached = $this->cacheGet($key);
        if ($cached !== null) {
            $this->requestCache[$key] = $cached;
            return $cached;
        }

        // Load from database
        $query = Price::where('priceable_type', ProductVariant::morphName())
            ->where('priceable_id', $variantId)
            ->where('currency_id', $currencyId)
            ->where('tier', '<=', $quantity);

        if ($channelId) {
            $query->where('channel_id', $channelId);
        }

        if ($customerGroupId) {
            $query->where('customer_group_id', $customerGroupId);
        }

        $price = $query->orderByDesc('tier')->first();
        $basePrice = $price?->price ?? 0;

        // Cache with version
        $this->cachePut($key, $basePrice, $this->defaultTtl);
        $this->requestCache[$key] = $basePrice;

        return $basePrice;
    }

    /**
     * Get contract price list (cached).
     */
    public function getContractPriceList(
        int $customerId,
        int $variantId,
        int $currencyId
    ): ?array {
        $key = $this->buildKey('contract_prices', [
            'customer' => $customerId,
            'variant' => $variantId,
            'currency' => $currencyId,
        ]);

        // Check request cache first
        if (isset($this->requestCache[$key])) {
            return $this->requestCache[$key];
        }

        // Check cache
        $cached = $this->cacheGet($key);
        if ($cached !== null) {
            $this->requestCache[$key] = $cached;
            return $cached;
        }

        // Load from database (B2B contracts)
        // TODO: Replace with actual B2B contract model when available
        $contractPrices = [
            'customer_id' => $customerId,
            'variant_id' => $variantId,
            'prices' => [],
            'version' => $this->getVersion('contract_prices'),
        ];

        // Cache with version
        $this->cachePut($key, $contractPrices, $this->defaultTtl);
        $this->requestCache[$key] = $contractPrices;

        return $contractPrices;
    }

    /**
     * Get promotion definitions (cached).
     */
    public function getPromotionDefinitions(
        ?int $channelId = null,
        ?int $customerGroupId = null
    ): Collection {
        $key = $this->buildKey('promotions', [
            'channel' => $channelId,
            'group' => $customerGroupId,
        ]);

        // Check request cache first
        if (isset($this->requestCache[$key])) {
            return collect($this->requestCache[$key]);
        }

        // Check cache
        $cached = $this->cacheGet($key);
        if ($cached !== null) {
            $this->requestCache[$key] = $cached;
            return collect($cached);
        }

        // Load from database
        $now = now();
        $promotions = \Lunar\Models\Discount::where('active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->when($channelId, function ($q) use ($channelId) {
                // Filter by channel if applicable
            })
            ->get()
            ->map(function ($discount) {
                return [
                    'id' => $discount->id,
                    'name' => $discount->name,
                    'coupon' => $discount->coupon,
                    'type' => $discount->type ?? 'fixed',
                    'value' => $discount->value ?? 0,
                    'data' => $discount->data ?? [],
                    'version' => $this->getVersion('promotions'),
                ];
            })
            ->toArray();

        // Cache with version
        $this->cachePut($key, $promotions, $this->defaultTtl);
        $this->requestCache[$key] = $promotions;

        return collect($promotions);
    }

    /**
     * Get currency rates (cached).
     */
    public function getCurrencyRates(): array
    {
        $key = $this->buildKey('currency_rates', []);

        // Check request cache first
        if (isset($this->requestCache[$key])) {
            return $this->requestCache[$key];
        }

        // Check cache
        $cached = $this->cacheGet($key);
        if ($cached !== null) {
            $this->requestCache[$key] = $cached;
            return $cached;
        }

        // Load from database
        $currencies = Currency::all();
        $rates = [];
        $defaultCurrency = $currencies->where('default', true)->first();

        foreach ($currencies as $currency) {
            $rates[$currency->id] = [
                'id' => $currency->id,
                'code' => $currency->code,
                'exchange_rate' => $currency->exchange_rate ?? 1.0,
                'default' => $currency->default ?? false,
                'version' => $this->getVersion('currency_rates'),
            ];
        }

        // Cache with shorter TTL (5 minutes) for currency rates
        $this->cachePut($key, $rates, 300);
        $this->requestCache[$key] = $rates;

        return $rates;
    }

    /**
     * Invalidate cache by tag.
     */
    public function invalidateByTag(string $tag): void
    {
        // Redis tag-based invalidation
        $pattern = $this->buildKey($tag, ['*']);
        if ($this->cacheEnabled()) {
            try {
                $keys = Redis::keys($pattern);
                if (!empty($keys)) {
                    Redis::del($keys);
                }
            } catch (\Throwable) {
                // Ignore invalidation failures when Redis is unavailable.
            }
        }

        // Clear request cache
        $this->requestCache = [];
    }

    /**
     * Invalidate cache by version.
     */
    public function invalidateByVersion(string $type): void
    {
        $this->incrementVersion($type);
        $this->invalidateByTag($type);
    }

    /**
     * Build cache key with versioning.
     */
    protected function buildKey(string $type, array $params): string
    {
        $parts = [$this->prefix, $type];
        
        foreach ($params as $key => $value) {
            if ($value !== null) {
                $parts[] = "{$key}:{$value}";
            }
        }

        return implode(':', $parts);
    }

    /**
     * Get version for cache type.
     */
    protected function getVersion(string $type): int
    {
        $key = "{$this->prefix}:version:{$type}";
        return (int) $this->cacheGet($key, 1);
    }

    /**
     * Increment version for cache type.
     */
    protected function incrementVersion(string $type): void
    {
        $key = "{$this->prefix}:version:{$type}";
        $this->cacheIncrement($key);
    }

    /**
     * Clear request-scope cache.
     */
    public function clearRequestCache(): void
    {
        $this->requestCache = [];
    }

    /**
     * Get cache statistics.
     */
    public function getStats(): array
    {
        $redisConnected = null;
        if ($this->cacheEnabled()) {
            try {
                $redisConnected = Redis::ping() === 'PONG';
            } catch (\Throwable) {
                $redisConnected = false;
            }
        }

        return [
            'request_cache_size' => count($this->requestCache),
            'redis_connected' => $redisConnected,
        ];
    }
}



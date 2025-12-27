<?php

namespace App\Services\Observability;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Pricing Metrics Service
 * 
 * Tracks:
 * - Price calculation timing
 * - Cache hit ratios
 * - Checkout failure metrics
 * - Stock contention metrics
 * - Promotion usage metrics
 */
class PricingMetricsService
{
    protected string $prefix = 'metrics:pricing';

    protected function enabled(): bool
    {
        if (app()->environment('testing')) {
            return false;
        }

        return (bool) config('pricing_cache.observability.enabled', true);
    }

    protected function safeRedis(callable $fn, $default = null)
    {
        if (!$this->enabled()) {
            return $default;
        }

        try {
            return $fn();
        } catch (\Throwable) {
            return $default;
        }
    }

    /**
     * Record price calculation timing.
     */
    public function recordPriceCalculation(string $operation, float $durationMs, bool $fromCache = false): void
    {
        if (!$this->enabled()) {
            return;
        }

        $key = "{$this->prefix}:timing:{$operation}";
        
        // Store timing data
        $this->safeRedis(fn () => Redis::lpush("{$key}:durations", $durationMs));
        $this->safeRedis(fn () => Redis::ltrim("{$key}:durations", 0, 999)); // Keep last 1000 measurements
        
        // Track cache hits/misses
        if ($fromCache) {
            $this->safeRedis(fn () => Redis::incr("{$key}:cache_hits"));
        } else {
            $this->safeRedis(fn () => Redis::incr("{$key}:cache_misses"));
        }

        // Log slow operations (> 500ms)
        if ($durationMs > 500) {
            Log::warning('Slow price calculation', [
                'operation' => $operation,
                'duration_ms' => $durationMs,
                'from_cache' => $fromCache,
            ]);
        }
    }

    /**
     * Record cache hit.
     */
    public function recordCacheHit(string $cacheType): void
    {
        $this->safeRedis(fn () => Redis::incr("{$this->prefix}:cache:hits:{$cacheType}"));
        $this->safeRedis(fn () => Redis::incr("{$this->prefix}:cache:hits:total"));
    }

    /**
     * Record cache miss.
     */
    public function recordCacheMiss(string $cacheType): void
    {
        $this->safeRedis(fn () => Redis::incr("{$this->prefix}:cache:misses:{$cacheType}"));
        $this->safeRedis(fn () => Redis::incr("{$this->prefix}:cache:misses:total"));
    }

    /**
     * Record checkout failure.
     */
    public function recordCheckoutFailure(string $reason, ?int $cartId = null): void
    {
        $this->safeRedis(fn () => Redis::incr("{$this->prefix}:checkout:failures:{$reason}"));
        $this->safeRedis(fn () => Redis::incr("{$this->prefix}:checkout:failures:total"));

        Log::error('Checkout failure recorded', [
            'reason' => $reason,
            'cart_id' => $cartId,
        ]);
    }

    /**
     * Record stock contention.
     */
    public function recordStockContention(int $variantId, string $operation): void
    {
        $this->safeRedis(fn () => Redis::incr("{$this->prefix}:stock:contention:{$variantId}:{$operation}"));
        $this->safeRedis(fn () => Redis::incr("{$this->prefix}:stock:contention:total"));

        Log::warning('Stock contention detected', [
            'variant_id' => $variantId,
            'operation' => $operation,
        ]);
    }

    /**
     * Record promotion usage.
     */
    public function recordPromotionUsage(int $promotionId, float $discountAmount): void
    {
        $this->safeRedis(fn () => Redis::incr("{$this->prefix}:promotions:usage:{$promotionId}"));
        $this->safeRedis(fn () => Redis::incr("{$this->prefix}:promotions:usage:total"));
        $this->safeRedis(fn () => Redis::incrbyfloat("{$this->prefix}:promotions:discount_total", $discountAmount));
    }

    /**
     * Get cache hit ratio.
     */
    public function getCacheHitRatio(string $cacheType = 'total'): float
    {
        $hits = (int) ($this->safeRedis(fn () => Redis::get("{$this->prefix}:cache:hits:{$cacheType}"), 0) ?? 0);
        $misses = (int) ($this->safeRedis(fn () => Redis::get("{$this->prefix}:cache:misses:{$cacheType}"), 0) ?? 0);
        
        $total = $hits + $misses;
        
        if ($total === 0) {
            return 0.0;
        }

        return ($hits / $total) * 100;
    }

    /**
     * Get average calculation time.
     */
    public function getAverageCalculationTime(string $operation): float
    {
        $key = "{$this->prefix}:timing:{$operation}:durations";
        $durations = $this->safeRedis(fn () => Redis::lrange($key, 0, -1), []);
        
        if (empty($durations)) {
            return 0.0;
        }

        $sum = array_sum(array_map('floatval', $durations));
        return $sum / count($durations);
    }

    /**
     * Get metrics summary.
     */
    public function getMetricsSummary(): array
    {
        return [
            'cache' => [
                'hit_ratio' => $this->getCacheHitRatio(),
                'hits' => (int) ($this->safeRedis(fn () => Redis::get("{$this->prefix}:cache:hits:total"), 0) ?? 0),
                'misses' => (int) ($this->safeRedis(fn () => Redis::get("{$this->prefix}:cache:misses:total"), 0) ?? 0),
            ],
            'checkout' => [
                'failures' => (int) ($this->safeRedis(fn () => Redis::get("{$this->prefix}:checkout:failures:total"), 0) ?? 0),
            ],
            'stock' => [
                'contention' => (int) ($this->safeRedis(fn () => Redis::get("{$this->prefix}:stock:contention:total"), 0) ?? 0),
            ],
            'promotions' => [
                'usage_count' => (int) ($this->safeRedis(fn () => Redis::get("{$this->prefix}:promotions:usage:total"), 0) ?? 0),
                'discount_total' => (float) ($this->safeRedis(fn () => Redis::get("{$this->prefix}:promotions:discount_total"), 0.0) ?? 0.0),
            ],
            'timing' => [
                'price_calculation' => $this->getAverageCalculationTime('price_calculation'),
                'cart_pricing' => $this->getAverageCalculationTime('cart_pricing'),
            ],
        ];
    }

    /**
     * Reset metrics (for testing/debugging).
     */
    public function resetMetrics(): void
    {
        $this->safeRedis(function () {
            $keys = Redis::keys("{$this->prefix}:*");
            if (!empty($keys)) {
                Redis::del($keys);
            }
        });
    }
}



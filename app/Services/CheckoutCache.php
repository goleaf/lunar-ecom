<?php

namespace App\Services;

use App\Models\CheckoutLock;
use Illuminate\Support\Facades\Cache;

/**
 * Caching service for checkout operations.
 */
class CheckoutCache
{
    protected int $ttlSeconds;

    public function __construct()
    {
        $this->ttlSeconds = config('checkout.default_ttl_minutes', 15) * 60;
    }

    /**
     * Cache checkout status for cart.
     */
    public function cacheStatus(int $cartId, array $status): void
    {
        Cache::put(
            "checkout:status:{$cartId}",
            $status,
            now()->addSeconds(30) // Short TTL for status
        );
    }

    /**
     * Get cached checkout status.
     */
    public function getCachedStatus(int $cartId): ?array
    {
        return Cache::get("checkout:status:{$cartId}");
    }

    /**
     * Clear cached status.
     */
    public function clearStatus(int $cartId): void
    {
        Cache::forget("checkout:status:{$cartId}");
    }

    /**
     * Cache active lock count.
     */
    public function cacheActiveLockCount(int $count): void
    {
        Cache::put(
            'checkout:active_count',
            $count,
            now()->addMinutes(1)
        );
    }

    /**
     * Get cached active lock count.
     */
    public function getCachedActiveLockCount(): ?int
    {
        return Cache::get('checkout:active_count');
    }

    /**
     * Clear all checkout cache.
     */
    public function clearAll(): void
    {
        Cache::forget('checkout:active_count');
        // Note: Individual status caches expire automatically
    }
}



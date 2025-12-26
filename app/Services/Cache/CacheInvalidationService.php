<?php

namespace App\Services\Cache;

use App\Services\Cache\PricingCacheService;
use Illuminate\Support\Facades\Log;

/**
 * Cache Invalidation Service
 * 
 * Handles cache invalidation on:
 * - Price update
 * - Promotion update
 * - Contract update
 * - Stock update
 * - Currency rate update
 */
class CacheInvalidationService
{
    public function __construct(
        protected PricingCacheService $pricingCache
    ) {}

    /**
     * Invalidate on price update.
     */
    public function onPriceUpdate(int $variantId, ?int $currencyId = null, ?int $customerGroupId = null): void
    {
        Log::info('Cache invalidation: Price updated', [
            'variant_id' => $variantId,
            'currency_id' => $currencyId,
            'customer_group_id' => $customerGroupId,
        ]);

        // Invalidate base prices
        $this->pricingCache->invalidateByVersion('base_price');
        
        // Invalidate variant availability (may affect pricing)
        $this->pricingCache->invalidateByTag('variant_avail');
    }

    /**
     * Invalidate on promotion update.
     */
    public function onPromotionUpdate(?int $promotionId = null): void
    {
        Log::info('Cache invalidation: Promotion updated', [
            'promotion_id' => $promotionId,
        ]);

        // Invalidate promotion definitions
        $this->pricingCache->invalidateByVersion('promotions');
    }

    /**
     * Invalidate on contract update.
     */
    public function onContractUpdate(int $customerId, ?int $variantId = null): void
    {
        Log::info('Cache invalidation: Contract updated', [
            'customer_id' => $customerId,
            'variant_id' => $variantId,
        ]);

        // Invalidate contract prices
        $this->pricingCache->invalidateByVersion('contract_prices');
    }

    /**
     * Invalidate on stock update.
     */
    public function onStockUpdate(int $variantId): void
    {
        Log::info('Cache invalidation: Stock updated', [
            'variant_id' => $variantId,
        ]);

        // Invalidate variant availability matrix
        $this->pricingCache->invalidateByTag('variant_avail');
    }

    /**
     * Invalidate on currency rate update.
     */
    public function onCurrencyRateUpdate(?int $currencyId = null): void
    {
        Log::info('Cache invalidation: Currency rate updated', [
            'currency_id' => $currencyId,
        ]);

        // Invalidate currency rates
        $this->pricingCache->invalidateByVersion('currency_rates');
        
        // Also invalidate base prices (they may need conversion)
        $this->pricingCache->invalidateByVersion('base_price');
    }

    /**
     * Invalidate all pricing cache.
     */
    public function invalidateAll(): void
    {
        Log::info('Cache invalidation: All pricing cache');
        
        $types = [
            'base_price',
            'variant_avail',
            'contract_prices',
            'promotions',
            'currency_rates',
            'attribute_metadata',
        ];

        foreach ($types as $type) {
            $this->pricingCache->invalidateByVersion($type);
        }
    }
}



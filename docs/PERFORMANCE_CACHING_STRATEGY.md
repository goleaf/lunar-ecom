# Performance & Caching Strategy

## Overview

This document outlines the comprehensive performance and caching strategy implemented for the e-commerce pricing system. The strategy focuses on caching inputs rather than results, ensuring fast, safe, and scalable operations.

## 1️⃣ Pricing Cache Strategy

### Cached Data (Inputs)

The system caches pricing inputs, not final results:

- **Attribute metadata** - Product variant attributes and metadata
- **Variant availability matrix** - Purchasable status, stock, backorder info
- **Base prices** - Base pricing data per variant/currency/channel
- **Contract price lists** - B2B contract pricing data
- **Promotion definitions** - Active promotions and discount rules
- **Currency rates** - Exchange rates for multi-currency support

### Never Cached

These are never cached to ensure accuracy:

- **Final cart totals** - Always calculated fresh
- **Stock availability (hard)** - Real-time stock checks
- **Locked order prices** - Immutable once order is created

## 2️⃣ Cache Layers

### In-Memory (Request Scope)
- Fastest access, per-request cache
- Cleared after each request
- Implemented in `PricingCacheService::$requestCache`

### Redis (Shared)
- Shared across all application instances
- Versioned cache keys for invalidation
- Configurable TTL per cache type
- Default: 1 hour, configurable via `config/pricing_cache.php`

### HTTP Cache (API)
- Browser/CDN cache headers
- For read-only catalog endpoints
- Implemented via `HttpCache` middleware

### Edge Cache (Read-Only Catalog)
- CDN-level caching for static catalog data
- Configured via HTTP cache headers

## 3️⃣ Invalidation Rules

Cache invalidates automatically on:

- **Price update** → Invalidates base prices and variant availability
- **Promotion update** → Invalidates promotion definitions
- **Contract update** → Invalidates contract price lists
- **Stock update** → Invalidates variant availability matrix
- **Currency rate update** → Invalidates currency rates and base prices

### Implementation

- **Versioned cache keys** - Each cache type has a version counter
- **Event-driven invalidation** - Model observers trigger cache invalidation
- **Tag-based cache flushing** - Redis tags for bulk invalidation

### Listeners

- `InvalidatePriceCache` - Listens to `Price` model events
- `InvalidatePromotionCache` - Listens to `Discount` model and promotion events
- `InvalidateStockCache` - Listens to `ProductVariant` stock changes
- `InvalidateContractCache` - Listens to contract validity changes
- `InvalidateCurrencyCache` - Listens to `Currency` model changes

## 4️⃣ Async & Queues

Background jobs for heavy operations:

### Price Recalculation Jobs
- `RecalculatePriceJob` - Async cart repricing
- Used for bulk updates and background repricing
- Retries: 3 attempts, 120s timeout

### Stock Sync Jobs
- `SyncStockJob` - Multi-warehouse stock synchronization
- Batch processing for all variants
- Retries: 3 attempts, 180s timeout

### Promotion Indexing
- `IndexPromotionsJob` - Rebuilds promotion cache
- Runs on promotion changes
- Retries: 3 attempts, 300s timeout

### Search Reindexing
- `ReindexSearchJob` - Product search index updates
- Handles price/stock changes in search
- Retries: 3 attempts, 600s timeout

### Contract Expiration Jobs
- `ExpireContractsJob` - Checks expired B2B contracts
- Invalidates cache for expired contracts
- Retries: 3 attempts, 300s timeout

## 5️⃣ Load Protection

### Rate-Limited Checkout
- `RateLimitCheckout` middleware
- Limits: 10 attempts per minute per user/IP
- Configurable via `config/pricing_cache.php`

### Idempotent Endpoints
- `IdempotentRequest` middleware
- Uses `Idempotency-Key` header
- Prevents duplicate processing
- Caches successful responses for 24 hours

### Retry-Safe Operations
- All queue jobs implement retry logic
- Failed jobs logged for monitoring
- Circuit breaker pattern for external services

### Circuit Breakers
- `CircuitBreaker` service for external API calls
- Protects against cascading failures
- States: closed → open → half-open
- Configurable thresholds and timeouts

## 6️⃣ Observability

### Metrics Tracked

- **Price calculation timing** - Per-operation duration tracking
- **Cache hit ratios** - Cache performance metrics
- **Checkout failure metrics** - Failure reasons and counts
- **Stock contention metrics** - Concurrent access tracking
- **Promotion usage metrics** - Promotion application tracking

### Implementation

- `PricingMetricsService` - Centralized metrics collection
- Redis-backed metrics storage
- Slow operation logging (>500ms threshold)
- API endpoints for metrics access

### API Endpoints

- `GET /api/metrics/pricing` - Full metrics summary
- `GET /api/metrics/cache-hit-ratio/{type?}` - Cache hit ratio by type

## 🔁 End-to-End Flow

### Cart Loads
1. Cart loads → cached metadata retrieved
2. Variant availability checked from cache
3. Base prices loaded from cache

### Pricing Engine Resolves Prices
1. Base prices resolved (cached)
2. B2B contract overrides applied (cached)
3. Quantity tiers applied
4. Discounts applied via stacking rules (promotions cached)

### Checkout Starts
1. Price + stock locked (not cached)
2. Stock reservation created
3. Order created → immutable record

### Payment Captured
1. Stock committed
2. Order finalized
3. Cache cleared for affected items

### Result
- ✅ No race conditions
- ✅ No price drift
- ✅ No overselling
- ✅ Fast performance
- ✅ Safe operations
- ✅ Scalable architecture

## Configuration

All settings in `config/pricing_cache.php`:

```php
'enabled' => env('PRICING_CACHE_ENABLED', true),
'ttl' => [
    'default' => 3600, // 1 hour
    'base_price' => 3600,
    'variant_availability' => 1800, // 30 minutes
    'contract_prices' => 7200, // 2 hours
    'promotions' => 1800, // 30 minutes
    'currency_rates' => 300, // 5 minutes
],
'store' => env('PRICING_CACHE_STORE', 'redis'),
```

## Usage Examples

### Using Cache Service

```php
use App\Services\Cache\PricingCacheService;

$cacheService = app(PricingCacheService::class);

// Get base price (cached)
$price = $cacheService->getBasePrice(
    variantId: 123,
    currencyId: 1,
    customerGroupId: 2,
    channelId: 1,
    quantity: 5
);

// Get promotion definitions (cached)
$promotions = $cacheService->getPromotionDefinitions(
    channelId: 1,
    customerGroupId: 2
);
```

### Using Circuit Breaker

```php
use App\Services\CircuitBreaker;

$circuitBreaker = new CircuitBreaker('external_api');

$result = $circuitBreaker->call(
    operation: fn() => $externalApi->call(),
    fallback: fn() => $defaultValue
);
```

### Using Metrics

```php
use App\Services\Observability\PricingMetricsService;

$metrics = app(PricingMetricsService::class);

// Record cache hit
$metrics->recordCacheHit('base_price');

// Get metrics summary
$summary = $metrics->getMetricsSummary();
```

## Files Created

### Services
- `app/Services/Cache/PricingCacheService.php` - Main caching service
- `app/Services/Cache/CacheInvalidationService.php` - Cache invalidation logic
- `app/Services/CircuitBreaker.php` - Circuit breaker pattern
- `app/Services/Observability/PricingMetricsService.php` - Metrics collection

### Listeners
- `app/Listeners/Cache/InvalidatePriceCache.php`
- `app/Listeners/Cache/InvalidatePromotionCache.php`
- `app/Listeners/Cache/InvalidateStockCache.php`
- `app/Listeners/Cache/InvalidateContractCache.php`
- `app/Listeners/Cache/InvalidateCurrencyCache.php`

### Jobs
- `app/Jobs/RecalculatePriceJob.php`
- `app/Jobs/SyncStockJob.php`
- `app/Jobs/IndexPromotionsJob.php`
- `app/Jobs/ReindexSearchJob.php`
- `app/Jobs/ExpireContractsJob.php`

### Middleware
- `app/Http/Middleware/RateLimitCheckout.php`
- `app/Http/Middleware/IdempotentRequest.php`
- `app/Http/Middleware/HttpCache.php`

### Configuration
- `config/pricing_cache.php` - Cache configuration

### Controllers
- `app/Http/Controllers/Api/MetricsController.php` - Metrics API

## Next Steps

1. **Enable Redis** - Ensure Redis is configured and running
2. **Configure Environment** - Set cache TTLs and thresholds in `.env`
3. **Monitor Metrics** - Set up dashboards for cache hit ratios
4. **Test Circuit Breakers** - Verify failure handling
5. **Load Testing** - Test under high load scenarios

## Performance Targets

- **Cache Hit Ratio**: >80% for base prices
- **Price Calculation**: <100ms average (cached)
- **Checkout Response**: <500ms end-to-end
- **Stock Checks**: <50ms (not cached, real-time)
- **Promotion Lookup**: <10ms (cached)



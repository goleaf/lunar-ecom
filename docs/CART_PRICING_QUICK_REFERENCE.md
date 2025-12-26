# Cart Pricing Engine - Quick Reference Guide

## 🚀 Quick Start

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Basic Usage

#### Calculate Cart Prices
```php
use App\Services\CartPricingEngine;

$pricingEngine = app(CartPricingEngine::class);
$result = $pricingEngine->calculateCartPrices($cart);

// Access pricing data
$subtotal = $result->subtotal;
$grandTotal = $result->grandTotal;
$lineItems = $result->lineItems;
```

#### Reprice Cart
```php
// Automatic repricing (via events)
// Cart automatically reprices on:
// - Quantity changes
// - Variant changes
// - Customer login/logout
// - Address changes
// - Currency changes
// - Promotion changes

// Manual repricing
$pricingEngine->repriceCart($cart, 'quantity_changed');
```

#### Force Reprice (Checkout)
```php
use App\Contracts\CartManagerInterface;

$cartManager = app(CartManagerInterface::class);
$cart = $cartManager->forceReprice();
```

#### Get Formatted Output
```php
use App\Services\CartPricing\CartPricingOutputFormatter;

$formatter = app(CartPricingOutputFormatter::class);
$output = $formatter->formatCartPricing($cart);
```

## 📡 API Endpoints

### Get Detailed Pricing
```http
GET /cart/pricing
```

**Response:**
```json
{
  "pricing": {
    "subtotal": 10000,
    "subtotal_decimal": 100.00,
    "total_discounts": 2000,
    "total_discounts_decimal": 20.00,
    "tax_total": 1600,
    "tax_total_decimal": 16.00,
    "shipping_total": 500,
    "shipping_total_decimal": 5.00,
    "grand_total": 10100,
    "grand_total_decimal": 101.00,
    "discount_breakdown": { ... },
    "tax_breakdown": { ... },
    "shipping_cost": { ... },
    "audit_trail": {
      "calculated_at": "2025-12-26T10:00:00Z",
      "pricing_version": 1,
      "applied_rules": [ ... ],
      "price_hash": "...",
      "requires_reprice": false
    },
    "line_items": [ ... ]
  }
}
```

## 🔧 Configuration

**File**: `config/lunar/cart.php`

```php
'pricing' => [
    'auto_reprice' => true,              // Auto-reprice on changes
    'enforce_map' => true,                // Enforce MAP prices
    'enforce_minimum_price' => true,      // Prevent negative prices
    'price_expiration_hours' => 24,      // Quote validity period
    'enable_price_hash' => true,          // Enable tamper detection
    'store_snapshots' => false,           // Store snapshots in DB
],
```

## 🎯 Common Tasks

### Set MAP Price
```php
use App\Services\MAPEnforcementService;

$mapService = app(MAPEnforcementService::class);
$mapService->setMAPPrice(
    variant: $variant,
    currencyId: $currencyId,
    minPrice: 10000, // in cents
    channelId: $channelId,
    enforcementLevel: 'strict' // or 'warning'
);
```

### Check Price Integrity
```php
use App\Services\CartPricing\PriceIntegrityService;

$integrityService = app(PriceIntegrityService::class);
$result = $integrityService->validateCartPrices($cart);

if (!$result->isValid) {
    // Handle errors
    foreach ($result->errors as $error) {
        // Log or handle error
    }
}
```

### Trigger Repricing Manually
```php
use App\Services\CartPricing\RepricingTriggerService;

$triggerService = app(RepricingTriggerService::class);
$triggerService->triggerReprice($cart, 'quantity_changed');
```

## 📊 Pricing Pipeline Steps

1. **ResolveBasePriceStep** - Base price from AdvancedPricingService
2. **ApplyB2BContractStep** - B2B contract overrides
3. **ApplyQuantityTierStep** - Quantity tier pricing
4. **ApplyItemDiscountsStep** - Item-level discounts
5. **ApplyCartDiscountsStep** - Cart-level discounts
6. **CalculateShippingStep** - Shipping calculation
7. **CalculateTaxStep** - Tax calculation
8. **ApplyRoundingStep** - Currency-specific rounding

## 🔍 Event Listeners

The system automatically reprices on these events:

- `CartQuantityChanged` - Quantity updated
- `CartVariantChanged` - Variant changed
- `CartCustomerChanged` - Customer login/logout
- `CartAddressChanged` - Shipping/billing address changed
- `CartCurrencyChanged` - Currency changed
- `PromotionActivated` - Promotion activated
- `PromotionExpired` - Promotion expired
- `StockChanged` - Stock level changed
- `ContractValidityChanged` - B2B contract validity changed

## 🛡️ Price Integrity

### Minimum Price Enforcement
Automatically prevents negative or zero prices.

### MAP Enforcement
```php
// Strict enforcement - blocks price below MAP
// Warning enforcement - logs warning but allows

$mapService->enforceOnCartLine($cartLine, 'strict');
```

### Price Tamper Detection
```php
// Price hash is automatically generated and verified
// Detects if prices have been manually modified
$integrityService->detectPriceMismatch($cart);
```

## 📝 Data Structures

### CartPricingResult
```php
$result->subtotal              // Pre-discount subtotal (cents)
$result->totalDiscounts        // Total discounts (cents)
$result->taxTotal              // Total tax (cents)
$result->shippingTotal         // Shipping cost (cents)
$result->grandTotal            // Final total (cents)
$result->lineItems             // Collection of LineItemPricing
$result->discountBreakdown     // DiscountBreakdown DTO
$result->taxBreakdown          // TaxBreakdown DTO
$result->shippingCost          // ShippingCost DTO
$result->appliedRules          // Array of applied rules
$result->priceHash             // SHA-256 hash for tamper detection
$result->calculatedAt          // Carbon timestamp
$result->pricingVersion        // Version counter
```

### LineItemPricing
```php
$linePricing->cartLineId           // Cart line ID
$linePricing->originalUnitPrice    // Base price (cents)
$linePricing->finalUnitPrice        // Final price (cents)
$linePricing->quantity              // Quantity
$linePricing->lineTotal             // Line total (cents)
$linePricing->discountBreakdown     // Item discount breakdown
$linePricing->taxBase               // Tax base (cents)
$linePricing->taxAmount             // Tax amount (cents)
$linePricing->appliedRules          // Applied rules array
$linePricing->priceSource           // 'base', 'contract', 'promo', 'matrix'
$linePricing->tierPrice             // Tier price if applicable
$linePricing->tierName              // Tier name/description
```

## 🐛 Troubleshooting

### Prices Not Updating
1. Check if `auto_reprice` is enabled in config
2. Verify observers are registered in `AppServiceProvider`
3. Check event listeners in `EventServiceProvider`
4. Verify cart has `requires_reprice` flag set

### MAP Not Enforcing
1. Verify MAP price exists for variant/currency/channel
2. Check `enforce_map` config is `true`
3. Verify MAP price is within validity period
4. Check enforcement level (strict vs warning)

### Discounts Not Applying
1. Verify discount is active
2. Check discount date range (starts_at/ends_at)
3. Verify customer group restrictions
4. Check minimum cart value requirements

## 📚 Related Documentation

- `CART_PRICING_ENGINE_FINAL_SUMMARY.md` - Complete implementation details
- `CART_PRICING_ENGINE_READY.md` - Production readiness guide
- `CART_PRICING_ENGINE_COMPLETE.md` - Implementation status

## 🔗 Integration Points

- **AdvancedPricingService** - Base price resolution
- **MatrixPricingService** - Quantity tier pricing
- **Lunar Discount System** - Discount application
- **Lunar Tax System** - Tax calculation
- **Lunar Shipping System** - Shipping calculation

---

**Last Updated**: Implementation complete


# Cart Pricing Engine - README

## 🎯 Overview

The Cart Pricing Engine is a comprehensive, deterministic, auditable, and real-time pricing system for the Lunar e-commerce platform. It recomputes prices on every cart change, stores detailed pricing metadata, and provides complete audit trails.

## ✨ Key Features

- **Deterministic Pricing**: Same inputs always produce same outputs
- **Real-Time Repricing**: Automatic repricing on all cart changes
- **Complete Audit Trail**: Full tracking of all pricing decisions
- **Price Integrity**: MAP enforcement, tamper detection, validation
- **Flexible Architecture**: Easy to extend with new pricing rules

## 🚀 Quick Start

### 1. Run Migrations

```bash
php artisan migrate
```

### 2. Basic Usage

```php
use App\Services\CartPricingEngine;

$pricingEngine = app(CartPricingEngine::class);
$result = $pricingEngine->calculateCartPrices($cart);

// Access pricing data
$subtotal = $result->subtotal;
$grandTotal = $result->grandTotal;
```

### 3. API Endpoint

```http
GET /cart/pricing
```

Returns complete pricing breakdown with audit trail.

## 📚 Documentation

- **CART_PRICING_QUICK_REFERENCE.md** - Quick reference guide
- **CART_PRICING_ENGINE_READY.md** - Production readiness guide
- **DEPLOYMENT_CHECKLIST.md** - Deployment checklist
- **IMPLEMENTATION_SUMMARY.md** - Complete implementation details

## 🔧 Configuration

Edit `config/lunar/cart.php`:

```php
'pricing' => [
    'auto_reprice' => true,
    'enforce_map' => true,
    'enforce_minimum_price' => true,
    'price_expiration_hours' => 24,
    'enable_price_hash' => true,
    'store_snapshots' => false,
],
```

## 📖 Architecture

The pricing engine uses an 8-step pipeline:

1. Resolve Base Price
2. Apply B2B Contract
3. Apply Quantity Tier
4. Apply Item Discounts
5. Apply Cart Discounts
6. Calculate Shipping
7. Calculate Tax
8. Apply Rounding

## 🔍 Event System

Automatic repricing triggers on:
- Quantity changes
- Variant changes
- Customer login/logout
- Address changes
- Currency changes
- Promotion changes
- Stock changes
- Contract validity changes

## 🛡️ Price Integrity

- Minimum price enforcement
- MAP (Minimum Advertised Price) enforcement
- Price tamper detection
- Price expiration checking
- Price mismatch detection

## 📊 Database Schema

### Cart Lines
- `original_unit_price` - Base price before modifications
- `final_unit_price` - Final price after all calculations
- `discount_breakdown` - JSON breakdown of discounts
- `tax_base` - Price used for tax calculation
- `applied_rules` - JSON array of applied rules
- `price_source` - Source: 'base', 'contract', 'promo', 'matrix'
- `price_calculated_at` - Calculation timestamp
- `price_hash` - Hash for tamper detection

### Carts
- `pricing_snapshot` - Complete pricing state JSON
- `last_reprice_at` - Last repricing timestamp
- `pricing_version` - Version counter
- `requires_reprice` - Flag for repricing needed

### MAP Prices
- `product_variant_id` - Variant reference
- `currency_id` - Currency reference
- `channel_id` - Channel reference (nullable)
- `min_price` - Minimum advertised price
- `enforcement_level` - 'strict' or 'warning'
- `valid_from` / `valid_to` - Validity period

## 🔗 Integration Points

- **AdvancedPricingService** - Base price resolution
- **MatrixPricingService** - Quantity tier pricing
- **Lunar Discount System** - Discount application
- **Lunar Tax System** - Tax calculation
- **Lunar Shipping System** - Shipping calculation

## 🐛 Troubleshooting

### Prices Not Updating
1. Check `auto_reprice` config
2. Verify observers registered
3. Check event listeners
4. Verify `requires_reprice` flag

### MAP Not Enforcing
1. Verify MAP prices exist
2. Check `enforce_map` config
3. Verify validity periods
4. Check enforcement level

## 📝 Support

For detailed information, see:
- `CART_PRICING_QUICK_REFERENCE.md` - Usage examples
- `DEPLOYMENT_CHECKLIST.md` - Deployment guide
- `IMPLEMENTATION_SUMMARY.md` - Technical details

---

**Status**: ✅ Production Ready  
**Version**: 1.0.0  
**Last Updated**: Implementation Complete


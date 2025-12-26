# 🚀 Discount Stacking Rules - Quick Start

Quick reference guide for using the discount stacking system.

## Basic Setup

### 1. Run Migrations

```bash
php artisan migrate
```

This creates:
- New fields on `discounts` table
- `discount_audit_trails` table

### 2. Configure (Optional)

Edit `config/discounts.php` to customize defaults.

## Creating Discounts with Stacking

### Stackable Discount (Can Combine)

```php
Discount::create([
    'name' => '10% Off',
    'data' => [
        'percentage' => 10,
        'stacking_mode' => 'stackable',
        'stacking_strategy' => 'cumulative',
    ],
]);
```

### Non-Stackable Discount (Replaces Others)

```php
Discount::create([
    'name' => 'Best Deal',
    'data' => [
        'percentage' => 15,
        'stacking_mode' => 'non_stackable',
        'stacking_strategy' => 'best_of',
    ],
]);
```

### Exclusive Discount (Blocks All Others)

```php
Discount::create([
    'name' => 'Flash Sale',
    'coupon' => 'FLASH50',
    'priority' => 100,
    'data' => [
        'percentage' => 50,
        'stacking_mode' => 'exclusive',
    ],
]);
```

## Stacking Modes

| Mode | Description | Use Case |
|------|-------------|----------|
| `stackable` | Can combine with other stackable discounts | Multiple discounts should add up |
| `non_stackable` | Replaces previous non-stackable of same type | Only one discount per type |
| `exclusive` | Blocks all other discounts | Flash sales, special promotions |

## Stacking Strategies

| Strategy | Description | Use Case |
|----------|-------------|----------|
| `best_of` | Choose highest discount | Customer gets best single discount |
| `priority_first` | Apply by priority order | Default - predictable order |
| `cumulative` | Add all discounts together | Maximum savings for customer |
| `exclusive_override` | Exclusive wins, otherwise cumulative | Flexible strategy |

## Common Patterns

### Pattern 1: Item + Cart Discounts Stack

```php
// Item discount
Discount::create([
    'name' => 'Product Sale',
    'data' => [
        'discount_type' => 'item_level',
        'percentage' => 10,
        'stacking_mode' => 'stackable',
    ],
]);

// Cart discount
Discount::create([
    'name' => 'Cart Discount',
    'coupon' => 'CART5',
    'data' => [
        'discount_type' => 'cart_level',
        'percentage' => 5,
        'stacking_mode' => 'stackable',
    ],
]);

// Result: 10% + 5% = 15% total
```

### Pattern 2: Best Discount Only

```php
Discount::create([
    'name' => 'Best Deal',
    'data' => [
        'percentage' => 20,
        'stacking_strategy' => 'best_of',
    ],
]);

// System chooses best discount from all applicable
```

### Pattern 3: B2B Contract Override

```php
Discount::create([
    'name' => 'B2B Price',
    'data' => [
        'b2b_contract' => true,
        'percentage' => 25,
        'stacking_mode' => 'exclusive',
    ],
]);

// Overrides all regular promotions
```

## Compliance Features

### Enable Audit Trail

```php
Discount::create([
    'name' => 'Audited Discount',
    'data' => [
        'require_audit_trail' => true,
        'track_price_before_discount' => true,
        'log_discount_reason' => true,
    ],
]);
```

### MAP Protection

```php
// On ProductVariant
ProductVariant::create([
    'data' => [
        'map_protected' => true,
    ],
]);

// Discounts will be blocked automatically
```

### Jurisdiction Restriction

```php
Discount::create([
    'name' => 'US Only',
    'data' => [
        'jurisdiction' => 'US',
    ],
]);
```

## Conflict Resolution Rules

1. **Priority**: Higher priority discounts apply first
2. **Manual Coupons**: Override automatic promotions (if enabled)
3. **B2B Contracts**: Override regular promotions (default)
4. **MAP Protection**: Blocks discounts on MAP-protected items
5. **Exclusive**: Blocks all other discounts

## Services

### Apply Discounts

```php
use App\Services\DiscountStacking\DiscountStackingService;

$service = app(DiscountStackingService::class);
$result = $service->applyDiscounts($discounts, $cart, $baseAmount, 'cart');
```

### Audit Trail

```php
use App\Services\DiscountStacking\DiscountAuditService;

$auditService = app(DiscountAuditService::class);
$trails = $auditService->getCartAuditTrail($cart);
```

### Compliance

```php
use App\Services\DiscountStacking\DiscountComplianceService;

$compliance = app(DiscountComplianceService::class);
$violations = $compliance->validateCompliance($discount, $cart);
$report = $compliance->generateComplianceReport($cart);
```

## Testing

### Test Stacking

```php
// Create multiple discounts
$discount1 = Discount::create([...]);
$discount2 = Discount::create([...]);

// Apply to cart
$cart->applyDiscount($discount1);
$cart->applyDiscount($discount2);

// Check result
$pricing = $cartPricingEngine->calculateCartPrices($cart);
// Verify discounts are stacked correctly
```

### Test Conflicts

```php
// Create exclusive discount
$exclusive = Discount::create([
    'stacking_mode' => 'exclusive',
]);

// Apply other discounts
// Verify exclusive blocks others
```

## Troubleshooting

**Discounts not stacking?**
- Check `stacking_mode` is `stackable`
- Verify no exclusive discounts blocking

**Wrong discount applied?**
- Check priorities (higher = more priority)
- Verify conflict resolution rules

**Audit trail missing?**
- Enable `require_audit_trail` on discount
- Check config settings

---

For detailed documentation, see [DISCOUNT_STACKING_RULES.md](./DISCOUNT_STACKING_RULES.md)



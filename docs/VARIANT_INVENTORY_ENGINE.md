# Variant Inventory Engine

Complete variant-centric inventory management system with comprehensive stock tracking.

## Overview

The Variant Inventory Engine is **variant-centric, not product-centric**. Each variant has its own inventory levels across multiple warehouses.

## Core Fields

### Inventory Level Fields

Each `InventoryLevel` record tracks inventory for a specific variant at a specific warehouse:

| Field | Type | Description |
|-------|------|-------------|
| `quantity` | integer | **On-hand quantity** - Total usable stock |
| `reserved_quantity` | integer | **Reserved quantity** - Stock reserved for orders |
| `available_quantity` | computed | **Available quantity** - On-hand - Reserved - Damaged |
| `incoming_quantity` | integer | **Incoming quantity** - Expected stock arrivals |
| `damaged_quantity` | integer | **Damaged quantity** - Unusable stock |
| `preorder_quantity` | integer | **Preorder quantity** - Preorder reservations |
| `backorder_limit` | integer\|null | **Backorder limit** - Warehouse-specific limit |
| `safety_stock_level` | integer | **Safety stock level** - Minimum stock to maintain |
| `reorder_point` | integer | Reorder point - Alert threshold |
| `reorder_quantity` | integer | Suggested reorder quantity |
| `status` | enum | Stock status (in_stock, low_stock, out_of_stock, backorder, preorder) |

### Computed Properties

```php
// Available quantity (computed)
$available = $level->available_quantity;
// = quantity - reserved_quantity - damaged_quantity

// On-hand quantity (computed)
$onHand = $level->on_hand_quantity;
// = quantity - damaged_quantity

// Total quantity (computed)
$total = $level->total_quantity;
// = quantity + incoming_quantity

// Sellable quantity (computed)
$sellable = $level->sellable_quantity;
// = available_quantity + preorder_quantity

// Net quantity (computed)
$net = $level->net_quantity;
// = quantity - reserved_quantity
```

## Usage

### VariantInventoryEngine Service

```php
use App\Services\VariantInventoryEngine;

$engine = app(VariantInventoryEngine::class);
```

### Get Inventory Summary

```php
// Get complete inventory summary
$summary = $engine->getInventorySummary($variant, $warehouseId);

/*
Returns:
[
    'on_hand_quantity' => int,
    'reserved_quantity' => int,
    'available_quantity' => int,
    'incoming_quantity' => int,
    'damaged_quantity' => int,
    'preorder_quantity' => int,
    'safety_stock_level' => int,
    'backorder_limit' => int|null,
    'total_quantity' => int,
    'sellable_quantity' => int,
    'is_below_safety_stock' => bool,
    'is_low_stock' => bool,
    'is_out_of_stock' => bool,
    'can_backorder' => bool,
]
*/
```

### Get Individual Fields

```php
// On-hand quantity
$onHand = $engine->getOnHandQuantity($variant, $warehouseId);

// Reserved quantity
$reserved = $engine->getReservedQuantity($variant, $warehouseId);

// Available quantity (computed)
$available = $engine->getAvailableQuantity($variant, $warehouseId);

// Incoming quantity
$incoming = $engine->getIncomingQuantity($variant, $warehouseId);

// Damaged quantity
$damaged = $engine->getDamagedQuantity($variant, $warehouseId);

// Preorder quantity
$preorder = $engine->getPreorderQuantity($variant, $warehouseId);

// Safety stock level
$safetyStock = $engine->getSafetyStockLevel($variant, $warehouseId);

// Backorder limit
$backorderLimit = $engine->getBackorderLimit($variant, $warehouseId);
```

### Update Inventory

```php
// Update inventory level
$level = $engine->updateInventoryLevel($variant, $warehouseId, [
    'quantity' => 100,
    'reserved_quantity' => 10,
    'damaged_quantity' => 2,
    'incoming_quantity' => 50,
    'preorder_quantity' => 5,
    'safety_stock_level' => 20,
    'backorder_limit' => 100,
]);

// Adjust on-hand quantity
$level = $engine->adjustOnHandQuantity($variant, $warehouseId, 10, 'manual_adjustment');

// Record damaged quantity
$level = $engine->recordDamagedQuantity($variant, $warehouseId, 5, 'damaged');

// Record incoming quantity
$level = $engine->recordIncomingQuantity($variant, $warehouseId, 50, 'purchase_order');

// Receive incoming stock (move from incoming to on-hand)
$level = $engine->receiveIncomingStock($variant, $warehouseId, 50, 'received');
```

### Check Stock Status

```php
// Check if low stock
$isLow = $engine->isLowStock($variant, $warehouseId);

// Check if can backorder
$canBackorder = $engine->canBackorder($variant, $requestedQuantity, $availableQuantity, $warehouseId);
```

## Inventory Level Model

### Direct Access

```php
use App\Models\InventoryLevel;

$level = InventoryLevel::where('product_variant_id', $variant->id)
    ->where('warehouse_id', $warehouseId)
    ->first();

// Access fields
$onHand = $level->quantity;
$reserved = $level->reserved_quantity;
$damaged = $level->damaged_quantity;
$incoming = $level->incoming_quantity;
$preorder = $level->preorder_quantity;
$safetyStock = $level->safety_stock_level;
$backorderLimit = $level->backorder_limit;

// Access computed properties
$available = $level->available_quantity;
$onHand = $level->on_hand_quantity;
$total = $level->total_quantity;
$sellable = $level->sellable_quantity;
$net = $level->net_quantity;

// Check status
$isLow = $level->isLowStock();
$isBelowSafety = $level->isBelowSafetyStock();
$isOutOfStock = $level->isOutOfStock();
```

### Relationships

```php
// Variant
$variant = $level->productVariant;

// Warehouse
$warehouse = $level->warehouse;

// Transactions
$transactions = $level->transactions;

// Stock movements
$movements = $level->stockMovements;

// Reservations
$reservations = $level->reservations;

// Low stock alerts
$alerts = $level->lowStockAlerts;
```

## Stock Calculations

### Available Quantity Formula

```
Available = On-hand - Reserved - Damaged
         = (quantity - damaged_quantity) - reserved_quantity - damaged_quantity
         = quantity - reserved_quantity - damaged_quantity
```

### On-hand Quantity Formula

```
On-hand = Total quantity - Damaged quantity
        = quantity - damaged_quantity
```

### Total Quantity Formula

```
Total = On-hand + Incoming
      = quantity + incoming_quantity
```

### Sellable Quantity Formula

```
Sellable = Available + Preorder capacity
         = available_quantity + preorder_quantity
```

## Stock Status Logic

The `status` field is automatically updated based on:

1. **out_of_stock**: `available_quantity <= 0` and backorder not allowed
2. **backorder**: `available_quantity <= 0` but backorder allowed
3. **preorder**: `preorder_quantity > 0` and `available_quantity <= preorder_quantity`
4. **low_stock**: `available_quantity > 0` but `available_quantity <= reorder_point`
5. **in_stock**: `available_quantity > reorder_point`

## Multi-Warehouse Support

All methods support optional `$warehouseId` parameter:

- **With warehouse ID**: Returns data for specific warehouse
- **Without warehouse ID**: Returns aggregated data across all warehouses

```php
// Single warehouse
$available = $engine->getAvailableQuantity($variant, $warehouseId);

// All warehouses (aggregated)
$available = $engine->getAvailableQuantity($variant);
```

## Virtual Stock

For virtual/digital products (`virtual_stock = true`):

- On-hand quantity: 999999 (unlimited)
- Reserved quantity: 0
- Available quantity: 999999 (unlimited)
- All other quantities: 0

## Best Practices

1. **Always use VariantInventoryEngine** for inventory operations
2. **Use transactions** for multi-step inventory updates
3. **Record transactions** for audit trail
4. **Check safety stock** before allowing sales
5. **Update status** after inventory changes
6. **Use warehouse-specific limits** when needed
7. **Track damaged stock** separately from on-hand

## Integration Examples

### Frontend Availability Check

```php
$engine = app(VariantInventoryEngine::class);
$summary = $engine->getInventorySummary($variant);

if ($summary['is_out_of_stock'] && !$summary['can_backorder']) {
    return 'Out of stock';
}

if ($summary['is_low_stock']) {
    return 'Low stock';
}

return 'In stock';
```

### Admin Inventory Dashboard

```php
$summary = $engine->getInventorySummary($variant, $warehouseId);

return [
    'on_hand' => $summary['on_hand_quantity'],
    'available' => $summary['available_quantity'],
    'reserved' => $summary['reserved_quantity'],
    'damaged' => $summary['damaged_quantity'],
    'incoming' => $summary['incoming_quantity'],
    'status' => $summary['is_out_of_stock'] ? 'Out of Stock' : 'In Stock',
    'below_safety' => $summary['is_below_safety_stock'],
];
```

### Purchase Order Receiving

```php
// Record incoming stock
$engine->recordIncomingQuantity($variant, $warehouseId, 100, 'purchase_order_12345');

// When stock arrives, receive it
$engine->receiveIncomingStock($variant, $warehouseId, 100, 'received');
```

### Damage Reporting

```php
// Record damaged stock
$engine->recordDamagedQuantity($variant, $warehouseId, 5, 'shipping_damage');
```



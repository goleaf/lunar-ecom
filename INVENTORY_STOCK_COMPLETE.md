# Inventory & Stock Management - Complete Implementation

This document describes the complete implementation of the Inventory & Stock Management system with all required features.

## Overview

The Inventory & Stock Management system provides comprehensive inventory tracking with multi-warehouse support, stock reservations, backorders, pre-orders, movement history, manual adjustments, low stock alerts, and out-of-stock visibility rules.

## Features

### ✅ Real Inventory Logic

Central stock tracking with real-time inventory levels per warehouse:

```php
use App\Services\ComprehensiveInventoryService;
use App\Models\ProductVariant;

$service = app(ComprehensiveInventoryService::class);

// Get central stock tracking (aggregated across all warehouses)
$stock = $service->getCentralStock($variant);

// Returns:
// [
//     'variant_id' => 1,
//     'variant_sku' => 'PROD-001',
//     'total_quantity' => 150,
//     'total_reserved' => 25,
//     'total_available' => 125,
//     'total_incoming' => 50,
//     'warehouses' => [...],
//     'status' => 'in_stock',
// ]
```

### ✅ Central Stock Tracking

Aggregated stock tracking across all warehouses:

```php
use App\Models\ProductVariant;

$variant = ProductVariant::find(1);

// Get total available stock
$totalAvailable = $variant->getTotalAvailableStock();

// Get total stock (including reserved)
$totalStock = $variant->getTotalStock();

// Get total reserved stock
$totalReserved = $variant->getTotalReservedStock();

// Get stock breakdown by warehouse
$byWarehouse = $variant->getStockByWarehouse();
```

### ✅ Multi-Warehouse Support

Track stock across multiple warehouses:

```php
use App\Models\Warehouse;
use App\Models\InventoryLevel;

// Create warehouse
$warehouse = Warehouse::create([
    'name' => 'Main Warehouse',
    'code' => 'MAIN',
    'address' => '123 Main St',
    'city' => 'New York',
    'is_active' => true,
    'priority' => 1,
]);

// Get stock per warehouse
$levels = InventoryLevel::where('product_variant_id', $variant->id)
    ->with('warehouse')
    ->get();
```

### ✅ Stock Per Warehouse

Each warehouse maintains its own inventory levels:

```php
use App\Models\InventoryLevel;

$level = InventoryLevel::firstOrCreate(
    [
        'product_variant_id' => $variant->id,
        'warehouse_id' => $warehouse->id,
    ],
    [
        'quantity' => 100,
        'reserved_quantity' => 0,
        'incoming_quantity' => 0,
        'reorder_point' => 10,
        'reorder_quantity' => 50,
    ]
);

// Available quantity = quantity - reserved_quantity
$available = $level->available_quantity;
```

### ✅ Reserved Stock (In Carts / Orders)

Stock reservations during checkout:

```php
use App\Services\ComprehensiveInventoryService;

$service = app(ComprehensiveInventoryService::class);

// Reserve stock for cart
$reservation = $service->reserveStock(
    variant: $variant,
    quantity: 5,
    referenceType: 'cart',
    referenceId: $cart->id,
    warehouseId: null, // Auto-select
    expirationMinutes: 15
);

// Release reservation
$service->releaseReservedStock($reservation);
```

### ✅ Backorder Handling

Support for backorders when stock is depleted:

```php
use App\Services\ComprehensiveInventoryService;

$service = app(ComprehensiveInventoryService::class);

// Handle backorder
$result = $service->handleBackorder(
    variant: $variant,
    quantity: 10,
    warehouseId: null
);

// Returns:
// [
//     'fulfilled' => false,
//     'backorder_required' => true,
//     'quantity_fulfilled' => 5,
//     'backorder_quantity' => 5,
// ]
```

### ✅ Pre-Order Support

Pre-order functionality for upcoming products:

```php
use App\Services\ComprehensiveInventoryService;
use Carbon\Carbon;

$service = app(ComprehensiveInventoryService::class);

// Enable pre-order on variant
$variant->preorder_enabled = true;
$variant->preorder_release_date = Carbon::parse('2024-12-31');
$variant->save();

// Create pre-order
$preorder = $service->createPreorder(
    variant: $variant,
    quantity: 2,
    releaseDate: Carbon::parse('2024-12-31'),
    referenceType: 'order',
    referenceId: $order->id
);

// Fulfill pre-order when stock arrives
$service->fulfillPreorder($preorderReservation, $warehouseId);
```

### ✅ Stock Movement History

Complete audit trail of all stock changes:

```php
use App\Services\ComprehensiveInventoryService;

$service = app(ComprehensiveInventoryService::class);

// Get movement history
$history = $service->getStockMovementHistory(
    variant: $variant,
    warehouseId: null,
    limit: 50
);

// Movement types:
// - in: Stock received
// - out: Stock removed
// - adjustment: Manual adjustment
// - transfer: Transfer between warehouses
// - reservation: Stock reserved
// - release: Reservation released
// - sale: Stock sold
// - return: Stock returned
// - preorder: Pre-order created
// - damage: Stock damaged
// - loss: Stock lost
```

### ✅ Manual Stock Adjustments

Manual stock adjustments with audit trail:

```php
use App\Services\ComprehensiveInventoryService;

$service = app(ComprehensiveInventoryService::class);

// Add stock
$level = $service->adjustStock(
    variant: $variant,
    warehouseId: $warehouse->id,
    quantity: 50, // Positive to add
    reason: 'Restock',
    notes: 'Received new shipment',
    userId: auth()->id()
);

// Remove stock
$level = $service->adjustStock(
    variant: $variant,
    warehouseId: $warehouse->id,
    quantity: -10, // Negative to subtract
    reason: 'Damage',
    notes: 'Damaged items removed',
    userId: auth()->id()
);
```

### ✅ Low Stock Alerts

Automatic low stock detection and alerts:

```php
use App\Services\ComprehensiveInventoryService;

$service = app(ComprehensiveInventoryService::class);

// Get low stock alerts
$alerts = $service->getLowStockAlerts(
    unresolvedOnly: true,
    warehouseId: null
);

// Check and create alerts
$alert = $service->checkLowStock($inventoryLevel);
```

### ✅ Out-of-Stock Visibility Rules

Control visibility when products are out of stock:

```php
use App\Models\ProductVariant;

$variant = ProductVariant::find(1);

// Set visibility rule
$variant->out_of_stock_visibility = 'show_unavailable'; // Options: hide, show_unavailable, show_available
$variant->save();

// Check visibility
if ($variant->isVisible()) {
    // Show variant
}

// Visibility options:
// - 'hide': Hide completely when out of stock
// - 'show_unavailable': Show but mark as unavailable
// - 'show_available': Show as available (for backorders/pre-orders)
```

## Database Schema

### Product Variants Table (Enhanced)

- `out_of_stock_visibility` (enum) - Visibility rule when out of stock
- `preorder_enabled` (boolean) - Enable pre-order support
- `preorder_release_date` (datetime) - Expected release date for pre-orders

### Inventory Levels Table

- `product_variant_id` - Foreign key to product variants
- `warehouse_id` - Foreign key to warehouses
- `quantity` - Available quantity
- `reserved_quantity` - Reserved for orders/carts
- `incoming_quantity` - Expected incoming stock
- `reorder_point` - Alert threshold
- `reorder_quantity` - Suggested reorder quantity
- `status` - in_stock, low_stock, out_of_stock, backorder, preorder

### Stock Reservations Table

- `product_variant_id` - Variant being reserved
- `warehouse_id` - Warehouse (null for pre-orders)
- `quantity` - Reserved quantity
- `expires_at` - Reservation expiration
- `is_released` - Whether reservation is released
- `reference_type` / `reference_id` - Polymorphic reference (cart, order, etc.)

### Stock Movements Table

- Complete audit trail of all stock changes
- Movement types: in, out, adjustment, transfer, reservation, release, sale, return, preorder, damage, loss
- Before/after quantity tracking
- Reference tracking (orders, adjustments, etc.)

## Services

### ComprehensiveInventoryService

Complete inventory management service:

- `getCentralStock()` - Get aggregated stock across warehouses
- `reserveStock()` - Reserve stock for cart/order
- `releaseReservedStock()` - Release reservation
- `handleBackorder()` - Handle backorder scenarios
- `createPreorder()` - Create pre-order
- `fulfillPreorder()` - Fulfill pre-order when stock arrives
- `adjustStock()` - Manual stock adjustment
- `getStockMovementHistory()` - Get movement history
- `getLowStockAlerts()` - Get low stock alerts
- `checkLowStock()` - Check and create alerts
- `transferStock()` - Transfer between warehouses
- `syncVariantStock()` - Sync variant stock from inventory levels
- `getInventorySummary()` - Get inventory summary for dashboard

### InventoryService

Warehouse-specific operations:

- `checkAvailability()` - Check stock availability
- `reserveStock()` - Reserve stock
- `releaseReservedStock()` - Release reservation
- `adjustInventory()` - Adjust inventory
- `transferStock()` - Transfer stock

### StockService

Stock operations:

- `reserveStock()` - Reserve stock
- `confirmReservation()` - Convert reservation to sale
- `adjustStock()` - Adjust stock
- `transferStock()` - Transfer stock
- `checkLowStock()` - Check low stock
- `syncVariantStock()` - Sync variant stock

## Usage Examples

### Central Stock Tracking

```php
use App\Services\ComprehensiveInventoryService;
use App\Models\ProductVariant;

$service = app(ComprehensiveInventoryService::class);
$variant = ProductVariant::find(1);

// Get central stock (aggregated)
$stock = $service->getCentralStock($variant);

// Or use variant methods
$totalAvailable = $variant->getTotalAvailableStock();
$byWarehouse = $variant->getStockByWarehouse();
```

### Reserve Stock During Checkout

```php
use App\Services\ComprehensiveInventoryService;

$service = app(ComprehensiveInventoryService::class);

$reservation = $service->reserveStock(
    variant: $variant,
    quantity: 3,
    referenceType: 'cart',
    referenceId: $cart->id,
    expirationMinutes: 15
);

// On order placement, confirm reservation
// On cart abandonment, release reservation
```

### Pre-Order Management

```php
use App\Services\ComprehensiveInventoryService;
use Carbon\Carbon;

$service = app(ComprehensiveInventoryService::class);

// Enable pre-order
$variant->preorder_enabled = true;
$variant->preorder_release_date = Carbon::parse('2024-12-31');
$variant->save();

// Create pre-order
$preorder = $service->createPreorder(
    variant: $variant,
    quantity: 1,
    releaseDate: Carbon::parse('2024-12-31'),
    referenceType: 'order',
    referenceId: $order->id
);

// When stock arrives, fulfill pre-order
$service->fulfillPreorder($preorderReservation, $warehouseId);
```

### Stock Adjustments

```php
use App\Services\ComprehensiveInventoryService;

$service = app(ComprehensiveInventoryService::class);

// Add stock
$level = $service->adjustStock(
    variant: $variant,
    warehouseId: $warehouse->id,
    quantity: 100,
    reason: 'Restock',
    notes: 'Received shipment #12345',
    userId: auth()->id()
);

// Remove stock
$level = $service->adjustStock(
    variant: $variant,
    warehouseId: $warehouse->id,
    quantity: -5,
    reason: 'Damage',
    notes: 'Damaged items removed',
    userId: auth()->id()
);
```

### Out-of-Stock Visibility

```php
use App\Models\ProductVariant;

$variant = ProductVariant::find(1);

// Hide when out of stock
$variant->out_of_stock_visibility = 'hide';
$variant->save();

// Show but mark unavailable
$variant->out_of_stock_visibility = 'show_unavailable';
$variant->save();

// Show as available (for backorders/pre-orders)
$variant->out_of_stock_visibility = 'show_available';
$variant->save();

// Check visibility
if ($variant->isVisible()) {
    // Display variant
}
```

### Stock Movement History

```php
use App\Services\ComprehensiveInventoryService;

$service = app(ComprehensiveInventoryService::class);

// Get movement history
$history = $service->getStockMovementHistory(
    variant: $variant,
    warehouseId: null,
    limit: 100
);

foreach ($history as $movement) {
    echo "{$movement->type}: {$movement->quantity} on {$movement->movement_date}";
    echo "Reason: {$movement->reason}";
}
```

### Low Stock Alerts

```php
use App\Services\ComprehensiveInventoryService;

$service = app(ComprehensiveInventoryService::class);

// Get active alerts
$alerts = $service->getLowStockAlerts(unresolvedOnly: true);

foreach ($alerts as $alert) {
    echo "Low stock alert for {$alert->productVariant->sku}";
    echo "Current: {$alert->current_quantity}, Reorder point: {$alert->reorder_point}";
}
```

## Summary

✅ Real inventory logic (central stock tracking)
✅ Central stock tracking (aggregated across warehouses)
✅ Multi-warehouse support (Warehouse model)
✅ Stock per warehouse (InventoryLevel model)
✅ Reserved stock (StockReservation model)
✅ Backorder handling (backorder status + methods)
✅ Pre-order support (preorder_enabled + methods)
✅ Stock movement history (StockMovement model)
✅ Manual stock adjustments (adjustStock methods)
✅ Low stock alerts (LowStockAlert model)
✅ Out-of-stock visibility rules (out_of_stock_visibility field)

The Inventory & Stock Management system is now complete with all required features.


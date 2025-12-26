# Product Stock Management System

## Overview

A comprehensive inventory management system with multi-warehouse support, stock tracking, low stock alerts, backorder support, stock reservations during checkout, and complete audit trail.

## Features

### Core Features

1. **Multi-Warehouse Inventory**
   - Track stock across multiple warehouses
   - Warehouse priority-based allocation
   - Stock transfers between warehouses
   - Per-warehouse reorder points and quantities

2. **Stock Tracking**
   - Real-time stock levels per warehouse
   - Reserved quantity tracking
   - Incoming quantity tracking
   - Complete audit trail via StockMovement

3. **Stock Reservations**
   - Automatic reservation during checkout
   - Time-based expiration (default: 15 minutes)
   - Automatic release of expired reservations
   - Reservation confirmation on order placement

4. **Low Stock Alerts**
   - Automatic detection when stock falls below reorder point
   - Email notifications (configurable)
   - Alert resolution tracking
   - Per-warehouse alert management

5. **Stock Movements**
   - Complete audit trail of all stock changes
   - Movement types: in, out, adjustment, transfer, reservation, release, sale, return, damage, loss
   - Reference tracking (orders, adjustments, transfers)
   - Before/after quantity tracking

6. **Backorder Support**
   - Track backorder quantities
   - Support for "always" purchasable mode
   - Backorder fulfillment tracking

## Models

### Warehouse
- **Location**: `app/Models/Warehouse.php`
- **Table**: `lunar_warehouses`
- **Fields**: name, code, address, city, state, postcode, country, phone, email, is_active, priority, notes
- **Relationships**: inventoryLevels, inventoryTransactions, stockReservations

### InventoryLevel
- **Location**: `app/Models/InventoryLevel.php`
- **Table**: `lunar_inventory_levels`
- **Fields**: product_variant_id, warehouse_id, quantity, reserved_quantity, incoming_quantity, reorder_point, reorder_quantity, status
- **Relationships**: productVariant, warehouse, transactions, reservations, lowStockAlerts
- **Computed**: available_quantity (quantity - reserved_quantity), total_quantity (quantity + incoming_quantity)

### StockReservation
- **Location**: `app/Models/StockReservation.php`
- **Table**: `lunar_stock_reservations`
- **Fields**: product_variant_id, warehouse_id, inventory_level_id, quantity, reference_type, reference_id, session_id, user_id, expires_at, is_released, released_at
- **Relationships**: productVariant, warehouse, inventoryLevel, user, reference (polymorphic)

### StockMovement
- **Location**: `app/Models/StockMovement.php`
- **Table**: `lunar_stock_movements`
- **Fields**: product_variant_id, warehouse_id, inventory_level_id, type, quantity, quantity_before, quantity_after, reference_type, reference_id, reference_number, reason, notes, created_by, movement_date
- **Relationships**: productVariant, warehouse, inventoryLevel, creator, reference (polymorphic)

### LowStockAlert
- **Location**: `app/Models/LowStockAlert.php`
- **Table**: `lunar_low_stock_alerts`
- **Fields**: inventory_level_id, product_variant_id, warehouse_id, current_quantity, reorder_point, is_resolved, resolved_at, resolved_by, notification_sent, notification_sent_at
- **Relationships**: inventoryLevel, productVariant, warehouse, resolver

## Services

### StockService
- **Location**: `app/Services/StockService.php`
- **Methods**:
  - `reserveStock()`: Reserve stock for checkout
  - `releaseReservation()`: Release a stock reservation
  - `releaseExpiredReservations()`: Release all expired reservations
  - `confirmReservation()`: Convert reservation to sale
  - `adjustStock()`: Manually adjust stock level
  - `transferStock()`: Transfer stock between warehouses
  - `checkLowStock()`: Check and create low stock alerts
  - `findAvailableWarehouse()`: Find warehouse with available stock
  - `getTotalAvailableStock()`: Get total available stock across warehouses
  - `syncVariantStock()`: Sync variant stock from inventory levels

### InventoryService
- **Location**: `app/Services/InventoryService.php`
- **Methods**:
  - `checkAvailability()`: Check stock availability across warehouses
  - `reserveStock()`: Reserve stock (alternative implementation)
  - Additional warehouse-specific operations

## Pipelines

### ValidateCartLineStock
- **Location**: `app/Lunar/Cart/Pipelines/CartLine/ValidateCartLineStock.php`
- **Purpose**: Validates stock and reserves it when items are added to cart
- **Behavior**:
  - Checks if variant is purchasable
  - Validates stock availability
  - Reserves stock for 15 minutes
  - Throws exception if stock unavailable

### ValidateOrderStock
- **Location**: `app/Lunar/Orders/Pipelines/OrderCreation/ValidateOrderStock.php`
- **Purpose**: Validates and confirms stock reservations when order is placed
- **Behavior**:
  - Finds existing reservations
  - Confirms reservations (converts to sale)
  - Creates new reservations if needed
  - Records stock movements

## Commands

### CheckLowStockAlerts
- **Signature**: `inventory:check-low-stock`
- **Purpose**: Check for low stock items and send alerts
- **Options**: `--send-emails` to send email notifications
- **Schedule**: Run hourly

### ReleaseExpiredStockReservations
- **Signature**: `inventory:release-expired-reservations`
- **Purpose**: Release expired stock reservations
- **Schedule**: Run every 5 minutes

### SyncVariantStock
- **Signature**: `inventory:sync-variant-stock`
- **Purpose**: Sync product variant stock from inventory levels
- **Options**: `--variant-id` to sync specific variant
- **Schedule**: Run hourly

## Admin Interface

### Routes
```php
Route::prefix('admin/stock')->name('admin.stock.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [StockManagementController::class, 'index'])->name('index');
    Route::get('/statistics', [StockManagementController::class, 'statistics'])->name('statistics');
    Route::get('/movements', [StockManagementController::class, 'movements'])->name('movements');
    Route::get('/variants/{variant}', [StockManagementController::class, 'show'])->name('show');
    Route::post('/variants/{variant}/adjust', [StockManagementController::class, 'adjustStock'])->name('adjust');
    Route::post('/variants/{variant}/transfer', [StockManagementController::class, 'transferStock'])->name('transfer');
    Route::post('/alerts/{alert}/resolve', [StockManagementController::class, 'resolveAlert'])->name('resolve-alert');
});
```

### Views
- `resources/views/admin/stock/index.blade.php`: Stock management dashboard
- `resources/views/admin/stock/show.blade.php`: Stock details for a variant

## Setup

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Register Pipelines
Add to `config/lunar/cart.php`:
```php
'pipeline' => [
    // ... other pipelines
    \App\Lunar\Cart\Pipelines\CartLine\ValidateCartLineStock::class,
],
```

Add to `config/lunar/orders.php`:
```php
'pipeline' => [
    // ... other pipelines
    \App\Lunar\Orders\Pipelines\OrderCreation\ValidateOrderStock::class,
],
```

### 3. Schedule Commands
Add to `routes/console.php` or `app/Console/Kernel.php`:
```php
$schedule->command('inventory:check-low-stock --send-emails')->hourly();
$schedule->command('inventory:release-expired-reservations')->everyFiveMinutes();
$schedule->command('inventory:sync-variant-stock')->hourly();
```

### 4. Create Warehouses
```php
use App\Models\Warehouse;

$warehouse = Warehouse::create([
    'name' => 'Main Warehouse',
    'code' => 'MAIN',
    'address' => '123 Main St',
    'city' => 'New York',
    'state' => 'NY',
    'postcode' => '10001',
    'country' => 'US',
    'is_active' => true,
    'priority' => 1,
]);
```

### 5. Set Up Inventory Levels
```php
use App\Models\InventoryLevel;
use App\Services\StockService;

$stockService = app(StockService::class);

// Adjust stock (creates inventory level if doesn't exist)
$inventoryLevel = $stockService->adjustStock(
    $variant,
    $warehouse->id,
    100, // quantity
    'Initial stock',
    'Initial inventory setup'
);
```

## Usage Examples

### Reserve Stock During Checkout
```php
$stockService = app(StockService::class);

$reservation = $stockService->reserveStock(
    $variant,
    $quantity,
    session()->getId(),
    auth()->id(),
    null, // warehouse_id (auto-select)
    15 // expiry minutes
);
```

### Adjust Stock
```php
$inventoryLevel = $stockService->adjustStock(
    $variant,
    $warehouseId,
    50, // positive to add, negative to subtract
    'Restock',
    'Received new shipment'
);
```

### Transfer Stock
```php
$success = $stockService->transferStock(
    $variant,
    $fromWarehouseId,
    $toWarehouseId,
    25,
    'Transfer to fulfill order'
);
```

### Check Availability
```php
$inventoryService = app(InventoryService::class);

$availability = $inventoryService->checkAvailability($variant, $quantity);
// Returns: ['available' => bool, 'total_available' => int, 'warehouses' => [...]]
```

## Stock Status

Inventory levels have the following statuses:
- **in_stock**: Available quantity > 0
- **low_stock**: Quantity < reorder_point
- **out_of_stock**: Available quantity <= 0
- **backorder**: Backorder enabled and stock depleted
- **preorder**: Preorder status

## Stock Movement Types

- **in**: Stock received
- **out**: Stock removed
- **adjustment**: Manual adjustment
- **transfer**: Transfer between warehouses
- **reservation**: Stock reserved for checkout
- **release**: Reservation released
- **sale**: Stock sold (order confirmed)
- **return**: Stock returned
- **damage**: Stock damaged
- **loss**: Stock lost

## Integration with ProductVariant

The system maintains backward compatibility with ProductVariant's `stock` field:
- `syncVariantStock()` aggregates stock from all warehouses
- Variant stock is updated automatically on adjustments
- Variant stock reflects total available across all warehouses

## Best Practices

1. **Regular Sync**: Run `inventory:sync-variant-stock` regularly to keep variant stock in sync
2. **Monitor Alerts**: Check low stock alerts regularly and restock promptly
3. **Clean Reservations**: Expired reservations are auto-released, but monitor for issues
4. **Audit Trail**: All stock changes are recorded in StockMovement for complete audit trail
5. **Warehouse Priority**: Set warehouse priority based on fulfillment speed/cost
6. **Reorder Points**: Set appropriate reorder points per warehouse based on demand

## Troubleshooting

### Stock Not Syncing
- Run `inventory:sync-variant-stock` manually
- Check that inventory levels exist for variants
- Verify warehouse is active

### Reservations Not Releasing
- Check `inventory:release-expired-reservations` is scheduled
- Verify `expires_at` timestamps are correct
- Check for database transaction issues

### Low Stock Alerts Not Sending
- Ensure `--send-emails` flag is used
- Check email configuration
- Verify `SendLowStockAlertNotification` job is configured


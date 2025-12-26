# Stock Movements Ledger

Complete audit trail system that logs every inventory change with full details.

## Overview

The Stock Movements Ledger provides a complete audit trail of all inventory changes:

- ✅ **Sale** - Order fulfillment
- ✅ **Return** - Customer returns
- ✅ **Manual adjustment** - Admin adjustments
- ✅ **Import** - Bulk imports
- ✅ **Damage** - Damaged stock
- ✅ **Transfer** - Warehouse transfers
- ✅ **Correction** - Inventory corrections
- ✅ **Reservation** - Stock reservations
- ✅ **Release** - Reservation releases

Every movement includes:
- **Reason** - Why the movement occurred
- **Actor** - Who made the change (user, system, API, import)
- **Timestamp** - When the change occurred
- **Before/after quantities** - Complete state tracking

## Movement Types

### Sale
Stock sold/fulfilled for an order.

```php
$ledger->recordSale($variant, $quantity, $warehouseId, $order, 'Order fulfillment');
```

### Return
Customer return - stock added back to inventory.

```php
$ledger->recordReturn($variant, $quantity, $warehouseId, $order, 'Customer return');
```

### Manual Adjustment
Admin-created adjustment (increase or decrease).

```php
$ledger->recordManualAdjustment(
    variant: $variant,
    quantity: 10, // Positive for increase, negative for decrease
    warehouseId: $warehouseId,
    reason: 'Stock count correction',
    notes: 'Found additional stock during inventory count'
);
```

### Import
Bulk import of stock.

```php
$ledger->recordImport(
    variant: $variant,
    quantity: 100,
    warehouseId: $warehouseId,
    importBatchId: 'IMPORT-2024-001',
    reason: 'Bulk import from supplier'
);
```

### Damage
Damaged stock removed from inventory.

```php
$ledger->recordDamage(
    variant: $variant,
    quantity: 5,
    warehouseId: $warehouseId,
    reason: 'Shipping damage',
    notes: 'Items damaged during transit',
    metadata: [
        'damage_type' => 'shipping',
        'damage_severity' => 'major',
    ]
);
```

### Transfer
Stock transfer between warehouses.

```php
// Records both outbound and inbound movements
[$outbound, $inbound] = $ledger->recordTransfer(
    variant: $variant,
    quantity: 50,
    fromWarehouseId: $warehouse1->id,
    toWarehouseId: $warehouse2->id,
    transferId: 'TRANSFER-001',
    reason: 'Stock rebalancing'
);
```

### Correction
Inventory correction/adjustment.

```php
$ledger->recordCorrection(
    variant: $variant,
    quantityBefore: 100,
    quantityAfter: 105,
    warehouseId: $warehouseId,
    reason: 'Inventory count correction',
    notes: 'Found 5 additional units during cycle count'
);
```

### Reservation
Stock reservation (from ReservationService).

```php
$ledger->recordReservation($variant, $quantity, $warehouseId, $reservation);
```

### Release
Reservation release (from ReservationService).

```php
$ledger->recordReservationRelease($variant, $quantity, $warehouseId, $reservation);
```

## Usage

### Basic Usage

```php
use App\Services\StockMovementLedger;

$ledger = app(StockMovementLedger::class);

// Record a sale
$movement = $ledger->recordSale(
    variant: $variant,
    quantity: 5,
    warehouseId: $warehouseId,
    order: $order,
    reason: 'Order fulfillment'
);
```

### Get Ledger

```php
// Get complete ledger for variant
$movements = $ledger->getLedger(
    variant: $variant,
    warehouseId: $warehouseId, // Optional
    from: now()->subMonths(3),  // Optional
    to: now(),                  // Optional
    type: 'sale'                // Optional filter
);

foreach ($movements as $movement) {
    echo "Type: {$movement->type}";
    echo "Quantity: {$movement->quantity}";
    echo "Before: {$movement->quantity_before}";
    echo "After: {$movement->quantity_after}";
    echo "Reason: {$movement->reason}";
    echo "Actor: {$movement->actor_name}";
    echo "Date: {$movement->movement_date}";
}
```

### Get Ledger Summary

```php
$summary = $ledger->getLedgerSummary($variant, $warehouseId, $from, $to);

/*
Returns:
[
    'total_movements' => int,
    'total_in' => int,
    'total_out' => int,
    'by_type' => [
        'sale' => ['count' => int, 'total_quantity' => int],
        'return' => [...],
        ...
    ],
    'by_actor' => [
        user_id => ['count' => int, 'actor_name' => string],
        ...
    ],
]
*/
```

## StockMovement Model

### Fields

```php
StockMovement {
    id
    product_variant_id
    warehouse_id
    inventory_level_id
    type                    // Movement type
    quantity                // Quantity change (positive/negative)
    quantity_before         // On-hand quantity before
    quantity_after          // On-hand quantity after
    reserved_quantity_before
    reserved_quantity_after
    available_quantity_before
    available_quantity_after
    reference_type          // Reference model type
    reference_id            // Reference model ID
    reference_number        // Reference number (order #, etc.)
    reason                  // Reason for movement
    notes                   // Additional notes
    metadata                // JSON metadata
    created_by              // Actor (user ID)
    actor_type              // user, system, api, import
    actor_identifier        // Actor identifier
    ip_address              // IP address for audit trail
    movement_date           // Timestamp of movement
    created_at
    updated_at
}
```

### Methods

```php
// Check movement direction
$movement->isIncrease(); // Returns true if incoming stock
$movement->isDecrease(); // Returns true if outgoing stock

// Get actor name
$actorName = $movement->actor_name;
```

### Scopes

```php
// By type
StockMovement::ofType('sale')->get();

// By variant
StockMovement::forVariant($variantId)->get();

// By warehouse
StockMovement::forWarehouse($warehouseId)->get();

// By actor type
StockMovement::byActorType('user')->get();

// By actor (user)
StockMovement::byActor($userId)->get();

// Date range
StockMovement::dateRange($from, $to)->get();

// Recent
StockMovement::recent(30)->get(); // Last 30 days
```

## Integration Examples

### Order Fulfillment

```php
use App\Services\StockMovementLedger;

class OrderFulfillmentService
{
    public function fulfillOrder(Order $order)
    {
        $ledger = app(StockMovementLedger::class);

        foreach ($order->lines as $line) {
            $variant = $line->purchasable;
            $warehouse = $this->selectFulfillmentWarehouse($variant, $line->quantity);

            // Record sale
            $ledger->recordSale(
                variant: $variant,
                quantity: $line->quantity,
                warehouseId: $warehouse->id,
                order: $order,
                reason: "Order fulfillment - Order #{$order->reference}"
            );
        }
    }
}
```

### Return Processing

```php
class ReturnService
{
    public function processReturn(Order $order, array $returnItems)
    {
        $ledger = app(StockMovementLedger::class);

        foreach ($returnItems as $item) {
            $ledger->recordReturn(
                variant: $item['variant'],
                quantity: $item['quantity'],
                warehouseId: $item['warehouse_id'],
                order: $order,
                reason: $item['return_reason']
            );
        }
    }
}
```

### Inventory Adjustment

```php
class InventoryAdjustmentController extends Controller
{
    public function adjustStock(Request $request, ProductVariant $variant)
    {
        $ledger = app(StockMovementLedger::class);

        $movement = $ledger->recordManualAdjustment(
            variant: $variant,
            quantity: $request->input('quantity'), // Can be positive or negative
            warehouseId: $request->input('warehouse_id'),
            reason: $request->input('reason'),
            notes: $request->input('notes')
        );

        return response()->json($movement);
    }
}
```

### Bulk Import

```php
class ImportService
{
    public function importStock(array $importData, string $batchId)
    {
        $ledger = app(StockMovementLedger::class);

        foreach ($importData as $item) {
            $ledger->recordImport(
                variant: $item['variant'],
                quantity: $item['quantity'],
                warehouseId: $item['warehouse_id'],
                importBatchId: $batchId,
                reason: "Bulk import - Batch #{$batchId}",
                metadata: [
                    'import_source' => $item['source'],
                    'supplier' => $item['supplier'],
                ]
            );
        }
    }
}
```

### Warehouse Transfer

```php
class WarehouseTransferService
{
    public function transferStock(
        ProductVariant $variant,
        int $quantity,
        Warehouse $fromWarehouse,
        Warehouse $toWarehouse
    ) {
        $ledger = app(StockMovementLedger::class);

        [$outbound, $inbound] = $ledger->recordTransfer(
            variant: $variant,
            quantity: $quantity,
            fromWarehouseId: $fromWarehouse->id,
            toWarehouseId: $toWarehouse->id,
            transferId: 'TRANSFER-' . now()->format('YmdHis'),
            reason: "Stock transfer from {$fromWarehouse->name} to {$toWarehouse->name}"
        );

        return [$outbound, $inbound];
    }
}
```

## Audit Trail

Every movement includes complete audit information:

- **Actor** - User who made the change (or system/API/import)
- **Timestamp** - Exact time of movement
- **IP Address** - IP address of the request
- **Reason** - Why the movement occurred
- **Before/After State** - Complete quantity state before and after
- **Reference** - Link to related order, transfer, etc.

## Best Practices

1. **Always use StockMovementLedger** - Don't update inventory directly
2. **Provide clear reasons** - Always include a reason for the movement
3. **Include metadata** - Add relevant context in metadata
4. **Link to references** - Link movements to orders, transfers, etc.
5. **Use appropriate types** - Use the correct movement type
6. **Track actors** - System automatically tracks user, but can specify actor type
7. **Review ledger regularly** - Use ledger for inventory audits

## Ledger Queries

### Get All Sales

```php
$sales = StockMovement::ofType('sale')
    ->forVariant($variant->id)
    ->recent(30)
    ->get();
```

### Get Movements by User

```php
$userMovements = StockMovement::byActor($userId)
    ->recent(7)
    ->get();
```

### Get Transfer History

```php
$transfers = StockMovement::ofType('transfer')
    ->forWarehouse($warehouseId)
    ->dateRange($from, $to)
    ->get();
```

### Get Damage Reports

```php
$damages = StockMovement::ofType('damage')
    ->recent(90)
    ->get()
    ->groupBy('product_variant_id')
    ->map(function ($movements) {
        return [
            'total_damaged' => abs($movements->sum('quantity')),
            'incidents' => $movements->count(),
        ];
    });
```



# Variant Inventory & Availability System

Complete inventory and availability management for variants with multi-warehouse support.

## Overview

Real stock logic lives here. This system provides comprehensive inventory management:

1. **Stock Fields** - Quantity, reserved, available, backorder, preorder, min/max quantities
2. **Stock Status** - In stock, low stock, out of stock, backorder, preorder
3. **Multi-Warehouse** - Stock per warehouse, fulfillment priority, geo-based selection
4. **Drop-shipping** - Support for drop-shipping warehouses
5. **Virtual Stock** - Services and digital products

## Stock Fields

### Core Stock Fields

```php
// Stock quantity (total)
$variant->stock; // Integer

// Reserved quantity (for orders)
$variant->reserved_quantity; // Calculated from InventoryLevel

// Available quantity (calculated: quantity - reserved)
$available = $variant->getAvailableStock();

// Backorder configuration
$variant->backorder_allowed; // 'yes', 'no', 'limit'
$variant->backorder_limit; // Integer (if backorder_allowed = 'limit')

// Preorder support
$variant->preorder_enabled; // Boolean
$variant->preorder_release_date; // DateTime

// Order quantity limits
$variant->min_order_quantity; // Integer (default: 1)
$variant->max_order_quantity; // Integer (nullable)

// Low-stock threshold
$variant->low_stock_threshold; // Integer

// Stock status (cached)
$variant->stock_status; // 'in_stock', 'low_stock', 'out_of_stock', 'backorder', 'preorder'

// Virtual stock (services/digital)
$variant->is_virtual; // Boolean
```

### Stock Status Values

- **in_stock**: Available stock > low_stock_threshold
- **low_stock**: Available stock > 0 but <= low_stock_threshold
- **out_of_stock**: Available stock = 0 and no backorder/preorder
- **backorder**: Available stock = 0 but backorder allowed
- **preorder**: Preorder enabled and release date in future

## Multi-Warehouse Inventory

### Warehouse Model

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
    'latitude' => 40.7128,
    'longitude' => -74.0060,
    'priority' => 1, // Lower = higher priority
    'is_active' => true,
    
    // Service areas
    'service_areas' => [
        'countries' => ['US', 'CA'],
        'regions' => ['NY', 'NJ', 'CT'],
        'postal_codes' => ['10001', '10002'],
    ],
    
    // Drop-shipping
    'is_dropship' => false,
    'dropship_provider' => null,
    
    // Fulfillment rules
    'fulfillment_rules' => [
        'auto_fulfill' => true,
    ],
]);
```

### Inventory Level (Stock per Warehouse)

```php
use App\Models\InventoryLevel;

InventoryLevel::create([
    'product_variant_id' => $variant->id,
    'warehouse_id' => $warehouse->id,
    'quantity' => 100, // Total stock
    'reserved_quantity' => 10, // Reserved for orders
    'incoming_quantity' => 50, // Expected incoming stock
    'reorder_point' => 20, // Alert when quantity < this
    'reorder_quantity' => 100, // Suggested reorder quantity
    'status' => 'in_stock', // Auto-calculated
]);

// Available quantity = quantity - reserved_quantity
$available = $level->available_quantity; // 90
```

### Get Stock by Warehouse

```php
// Get stock breakdown
$breakdown = $variant->getStockBreakdown();

// Returns:
// [
//     [
//         'warehouse_id' => 1,
//         'warehouse_name' => 'Main Warehouse',
//         'quantity' => 100,
//         'reserved_quantity' => 10,
//         'available_quantity' => 90,
//         'incoming_quantity' => 50,
//         'status' => 'in_stock',
//         'is_dropship' => false,
//     ],
//     ...
// ]
```

## Service Usage

### VariantInventoryService

```php
use App\Services\VariantInventoryService;

$service = app(VariantInventoryService::class);
```

#### Get Available Stock

```php
// Total available across all warehouses
$available = $service->getAvailableStock($variant);

// Available from specific warehouse
$available = $service->getAvailableStock($variant, $warehouseId);
```

#### Check Stock Sufficiency

```php
// Check if variant has sufficient stock
$hasStock = $service->hasSufficientStock($variant, $quantity, $warehouseId);

// Validates:
// - Min order quantity
// - Max order quantity
// - Available stock
// - Backorder availability
// - Preorder availability
```

#### Reserve Stock

```php
// Reserve stock for order
$reserved = $service->reserveStock(
    $variant,
    quantity: 5,
    warehouseId: $warehouseId, // null = auto-select
    reservationId: 'order-123'
);

// Automatically selects warehouses based on:
// - Priority
// - Geo-location
// - Fulfillment rules
```

#### Release Stock

```php
// Release reserved stock (e.g., order cancelled)
$service->releaseStock($variant, $quantity, $warehouseId);
```

#### Allocate Stock

```php
// Allocate stock (move from reserved to allocated)
$allocated = $service->allocateStock($variant, $quantity, $warehouseId);

// Decrements both reserved_quantity and quantity
```

#### Get Stock Status

```php
// Get stock status
$status = $service->getStockStatus($variant, $warehouseId);

// Returns: 'in_stock', 'low_stock', 'out_of_stock', 'backorder', 'preorder'

// Update cached status
$service->updateStockStatus($variant);
```

#### Get Fulfillment Warehouses

```php
// Get warehouses that can fulfill order
$warehouses = $service->getFulfillmentWarehouses(
    $variant,
    quantity: 10,
    context: [
        'customer_location' => [
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'country' => 'US',
            'region' => 'NY',
        ],
        'order_value' => 50000,
        'customer_group_id' => 1,
        'channel_id' => 1,
    ]
);

// Returns warehouses sorted by:
// - Priority
// - Distance (if geo-location provided)
// - Fulfillment rules
```

## Fulfillment Priority Rules

### Create Fulfillment Rule

```php
use App\Models\WarehouseFulfillmentRule;

WarehouseFulfillmentRule::create([
    'warehouse_id' => $warehouse->id,
    'rule_type' => 'geo_location', // geo_location, product_type, order_value, etc.
    'rule_config' => [
        'max_distance' => 100, // km
        'preferred_countries' => ['US', 'CA'],
    ],
    'priority' => 10, // Lower = higher priority
    'conditions' => [
        'min_order_value' => 10000,
    ],
    'is_active' => true,
]);
```

### Rule Types

1. **geo_location** - Based on customer location
2. **product_type** - Based on product type
3. **order_value** - Based on order value
4. **order_weight** - Based on order weight
5. **customer_group** - Based on customer group
6. **channel** - Based on sales channel
7. **custom** - Custom rule logic

## Geo-Based Warehouse Selection

```php
// Warehouse with geo-location
$warehouse->update([
    'latitude' => 40.7128,
    'longitude' => -74.0060,
    'service_areas' => [
        'countries' => ['US', 'CA'],
        'regions' => ['NY', 'NJ'],
        'postal_codes' => ['10001', '10002'],
    ],
]);

// Check if warehouse serves location
$serves = $warehouse->servesLocation([
    'country' => 'US',
    'region' => 'NY',
    'postcode' => '10001',
]);

// Calculate distance
$distance = $warehouse->distanceTo($customerLat, $customerLng); // km
```

## Drop-shipping Support

```php
// Create drop-shipping warehouse
$dropshipWarehouse = Warehouse::create([
    'name' => 'Supplier Warehouse',
    'code' => 'SUPPLIER-1',
    'is_dropship' => true,
    'dropship_provider' => 'SupplierName',
    'auto_fulfill' => false, // Manual fulfillment
]);

// Set inventory level (virtual stock)
InventoryLevel::create([
    'product_variant_id' => $variant->id,
    'warehouse_id' => $dropshipWarehouse->id,
    'quantity' => 999999, // Unlimited (supplier has stock)
    'status' => 'in_stock',
]);
```

## Virtual Stock (Services/Digital)

```php
// Mark variant as virtual
$variant->update([
    'is_virtual' => true,
]);

// Virtual variants:
// - Always show as "in stock"
// - No actual inventory tracking
// - No warehouse assignment needed
// - Used for services, digital products, subscriptions

// Check if virtual
if ($variant->isVirtual()) {
    // Handle virtual product logic
}
```

## Backorder Management

```php
// Configure backorder
$variant->update([
    'backorder_allowed' => 'limit', // 'yes', 'no', 'limit'
    'backorder_limit' => 50, // Max backorder quantity
]);

// Check backorder availability
$service = app(VariantInventoryService::class);
$canBackorder = $service->canBackorder($variant, $requestedQuantity, $availableQuantity);

// Backorder scenarios:
// - 'yes': Unlimited backorder
// - 'no': No backorder allowed
// - 'limit': Backorder up to limit
```

## Preorder Support

```php
// Enable preorder
$variant->update([
    'preorder_enabled' => true,
    'preorder_release_date' => Carbon::now()->addMonths(2),
]);

// Check preorder availability
if ($variant->isPreorderAvailable()) {
    // Allow preorder purchase
}

// Preorder is available if:
// - preorder_enabled = true
// - release_date is in the future
// - variant is enabled
```

## Order Quantity Limits

```php
// Set min/max order quantities
$variant->update([
    'min_order_quantity' => 2, // Minimum 2 units
    'max_order_quantity' => 10, // Maximum 10 units
]);

// Validation happens in hasSufficientStock()
$service->hasSufficientStock($variant, 1); // false (below min)
$service->hasSufficientStock($variant, 5); // true
$service->hasSufficientStock($variant, 15); // false (above max)
```

## Stock Status Calculation

```php
// Get stock status
$status = $variant->getStockStatus();

// Status calculation logic:
// 1. Virtual stock → 'in_stock'
// 2. Preorder enabled → 'preorder'
// 3. Available = 0 → Check backorder → 'backorder' or 'out_of_stock'
// 4. Available <= threshold → 'low_stock'
// 5. Available > threshold → 'in_stock'

// Update cached status
$service->updateStockStatus($variant);
```

## Model Methods

### ProductVariant Methods

```php
// Get available stock
$available = $variant->getAvailableStock($warehouseId);

// Check sufficient stock
$hasStock = $variant->hasSufficientStock($quantity, $warehouseId);

// Get stock status
$status = $variant->getStockStatus($warehouseId);

// Get stock breakdown
$breakdown = $variant->getStockBreakdown();

// Check if virtual
if ($variant->isVirtual()) {
    // Virtual product logic
}

// Check if backorder allowed
if ($variant->allowsBackorder()) {
    // Backorder logic
}
```

### Warehouse Methods

```php
// Check if serves location
$serves = $warehouse->servesLocation($location);

// Calculate distance
$distance = $warehouse->distanceTo($lat, $lng);

// Get full address
$address = $warehouse->full_address;
```

## Best Practices

1. **Always use service methods** for stock operations
2. **Reserve stock** when order is created
3. **Allocate stock** when order is confirmed
4. **Release stock** when order is cancelled
5. **Update stock status** after stock changes
6. **Use virtual stock** for services/digital products
7. **Set reorder points** for low-stock alerts
8. **Configure fulfillment rules** for optimal warehouse selection
9. **Use geo-based selection** for faster shipping
10. **Track incoming stock** for better planning

## Notes

- Stock quantity = total physical stock
- Reserved quantity = stock reserved for orders
- Available quantity = quantity - reserved_quantity
- Virtual stock = unlimited, no tracking
- Multi-warehouse = stock tracked per warehouse
- Fulfillment rules = determine warehouse priority
- Geo-selection = closest warehouse to customer
- Drop-shipping = supplier fulfills directly
- Backorder = allow orders when out of stock
- Preorder = allow orders before release date


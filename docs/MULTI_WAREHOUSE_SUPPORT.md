# Multi-Warehouse Support

Complete multi-warehouse fulfillment system with unlimited warehouses, priority-based selection, geo-distance rules, channel mapping, drop-shipping, and virtual warehouses.

## Overview

The multi-warehouse system supports:
- ✅ **Unlimited warehouses** - No limit on number of warehouses
- ✅ **Stock per variant per warehouse** - Each variant has inventory levels at each warehouse
- ✅ **Warehouse priority** - Priority-based warehouse selection
- ✅ **Geo-distance rules** - Distance-based fulfillment rules
- ✅ **Channel ↔ warehouse mapping** - Channel-specific warehouse assignments
- ✅ **Drop-shipping warehouses** - Support for drop-shipping providers
- ✅ **Virtual warehouses** - Digital goods fulfillment

## Warehouse Model

### Core Fields

```php
Warehouse {
    id
    name
    code
    address, city, state, postcode, country
    phone, email
    is_active
    priority                    // Lower number = higher priority
    latitude, longitude         // For geo-distance calculations
    service_areas               // JSON: countries, regions, postal_codes
    geo_distance_rules          // JSON: distance-based rules
    max_fulfillment_distance    // Maximum distance in km
    is_dropship                 // Drop-shipping warehouse flag
    dropship_provider           // Drop-shipping provider name
    is_virtual                  // Virtual warehouse flag
    virtual_config              // JSON: Virtual warehouse configuration
    fulfillment_rules           // JSON: Warehouse-specific rules
    auto_fulfill                // Auto-fulfillment flag
}
```

## Channel-Warehouse Mapping

### Mapping Table

The `channel_warehouse` table maps channels to warehouses:

```php
ChannelWarehouse {
    channel_id
    warehouse_id
    priority                    // Lower = higher priority for this channel
    is_default                  // Default warehouse for channel
    is_active
    fulfillment_rules           // Channel-specific fulfillment rules
}
```

### Usage

```php
use App\Services\MultiWarehouseService;

$service = app(MultiWarehouseService::class);

// Map channel to warehouse
$mapping = $service->mapChannelToWarehouse($channel, $warehouse, [
    'priority' => 10,
    'is_default' => true,
    'is_active' => true,
    'fulfillment_rules' => [
        'min_order_value' => 10000, // $100.00
        'max_weight' => 5000,      // 5kg
    ],
]);

// Get warehouses for channel
$warehouses = $service->getWarehousesForChannel($channel);

// Get default warehouse for channel
$defaultWarehouse = $service->getDefaultWarehouseForChannel($channel);

// Remove mapping
$service->unmapChannelFromWarehouse($channel, $warehouse);
```

## Warehouse Priority

Warehouses are selected based on priority (lower number = higher priority):

1. **Channel-specific priority** - Priority within channel mapping
2. **Warehouse priority** - Global warehouse priority
3. **Distance** - Closest warehouse (if geo-distance enabled)
4. **Stock availability** - Warehouse with sufficient stock

```php
// Get fulfillment warehouses (ordered by priority)
$warehouses = $service->getFulfillmentWarehouses(
    variant: $variant,
    quantity: 10,
    channel: $channel,
    context: [
        'customer_location' => [
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'country' => 'US',
            'region' => 'NY',
        ],
    ]
);
```

## Geo-Distance Rules

### Configuration

```php
$warehouse->geo_distance_rules = [
    [
        'type' => 'max_distance',
        'value' => 100, // km
    ],
    [
        'type' => 'distance_range',
        'min' => 10,
        'max' => 50,
    ],
    [
        'type' => 'country_priority',
        'countries' => ['US', 'CA'],
    ],
];

$warehouse->max_fulfillment_distance = 200; // km
```

### Rule Types

- **max_distance** - Maximum distance in km
- **min_distance** - Minimum distance in km
- **distance_range** - Distance range (min/max)
- **country_priority** - Priority countries (always allow)
- **region_priority** - Priority regions (always allow)

### Usage

```php
// Geo-distance rules are automatically applied when customer location is provided
$warehouses = $service->getFulfillmentWarehouses(
    variant: $variant,
    quantity: 1,
    channel: $channel,
    context: [
        'customer_location' => [
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'country' => 'US',
            'region' => 'NY',
            'postcode' => '10001',
        ],
    ]
);

// Warehouses are sorted by distance
foreach ($warehouses as $warehouse) {
    echo "Warehouse: {$warehouse->name}, Distance: {$warehouse->distance} km";
}
```

## Channel ↔ Warehouse Mapping

### Create Mapping

```php
// Map channel to warehouse with priority
$service->mapChannelToWarehouse($channel, $warehouse, [
    'priority' => 10,
    'is_default' => true,
    'is_active' => true,
    'fulfillment_rules' => [
        'min_order_value' => 10000,
        'max_weight' => 5000,
    ],
]);
```

### Get Warehouses for Channel

```php
// Get all warehouses for channel (ordered by priority)
$warehouses = $service->getWarehousesForChannel($channel);

// Get default warehouse
$defaultWarehouse = $service->getDefaultWarehouseForChannel($channel);
```

### Channel Relationships

```php
// Get warehouses for channel
$warehouses = $channel->warehouses;

// Get channels for warehouse
$channels = $warehouse->channels;

// Access pivot data
$mapping = $channel->warehouses()->where('warehouse_id', $warehouse->id)->first();
$priority = $mapping->pivot->priority;
$isDefault = $mapping->pivot->is_default;
```

## Drop-Shipping Warehouses

### Configuration

```php
$warehouse = Warehouse::create([
    'name' => 'Supplier Warehouse',
    'is_dropship' => true,
    'dropship_provider' => 'Supplier Name',
    'priority' => 50, // Lower priority than own warehouses
]);
```

### Usage

```php
// Check if warehouse is drop-shipping
$isDropship = $service->isDropshipWarehouse($warehouse);

// Drop-shipping warehouses are included in fulfillment selection
// but typically have lower priority
```

## Virtual Warehouses (Digital Goods)

### Create Virtual Warehouse

```php
$virtualWarehouse = $service->createVirtualWarehouse([
    'name' => 'Digital Warehouse',
    'code' => 'DIGITAL',
    'virtual_config' => [
        'auto_fulfill' => true,
        'delivery_method' => 'email',
        'license_key_generation' => true,
    ],
]);
```

### Configuration

```php
$warehouse->is_virtual = true;
$warehouse->virtual_config = [
    'auto_fulfill' => true,
    'delivery_method' => 'email', // email, download, api
    'license_key_generation' => true,
    'download_expiry_days' => 30,
];
```

### Usage

```php
// Check if warehouse is virtual
$isVirtual = $service->isVirtualWarehouse($warehouse);

// Virtual warehouses always have stock available
// and are used for digital products
```

## Stock Per Variant Per Warehouse

Each variant has inventory levels at each warehouse:

```php
use App\Models\InventoryLevel;

// Get inventory level for variant at warehouse
$level = InventoryLevel::where('product_variant_id', $variant->id)
    ->where('warehouse_id', $warehouse->id)
    ->first();

// Access stock fields
$onHand = $level->quantity;
$reserved = $level->reserved_quantity;
$available = $level->available_quantity;
$incoming = $level->incoming_quantity;
$damaged = $level->damaged_quantity;
```

### Get Stock Breakdown

```php
// Get stock breakdown by warehouse for variant
$breakdown = $service->getStockBreakdownByWarehouse($variant, $channel);

foreach ($breakdown as $warehouseStock) {
    echo "Warehouse: {$warehouseStock['warehouse_name']}";
    echo "Available: {$warehouseStock['available_quantity']}";
    echo "Priority: {$warehouseStock['priority']}";
}
```

## Fulfillment Warehouse Selection

The system automatically selects the best warehouse based on:

1. **Channel mapping** - Channel-specific warehouses first
2. **Priority** - Lower priority number = higher priority
3. **Stock availability** - Must have sufficient stock
4. **Geo-distance** - Closest warehouse (if location provided)
5. **Fulfillment rules** - Warehouse-specific rules

```php
// Get best fulfillment warehouses
$warehouses = $service->getFulfillmentWarehouses(
    variant: $variant,
    quantity: 10,
    channel: $channel,
    context: [
        'customer_location' => [
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'country' => 'US',
        ],
        'order_value' => 50000, // $500.00
        'customer_group_id' => 1,
    ]
);

// First warehouse is the best match
$bestWarehouse = $warehouses->first();
```

## Integration Examples

### Frontend Fulfillment

```php
use App\Services\MultiWarehouseService;

$service = app(MultiWarehouseService::class);

// Get fulfillment warehouse for order
$warehouses = $service->getFulfillmentWarehouses(
    variant: $variant,
    quantity: $line->quantity,
    channel: $cart->channel,
    context: [
        'customer_location' => [
            'latitude' => $shippingAddress->latitude,
            'longitude' => $shippingAddress->longitude,
            'country' => $shippingAddress->country,
        ],
    ]
);

$fulfillmentWarehouse = $warehouses->first();
```

### Admin Warehouse Management

```php
// Create warehouse
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
    'priority' => 10,
    'is_active' => true,
    'service_areas' => [
        'countries' => ['US'],
        'regions' => ['NY', 'NJ', 'CT'],
    ],
    'max_fulfillment_distance' => 500, // km
]);

// Map to channel
$service->mapChannelToWarehouse($channel, $warehouse, [
    'priority' => 10,
    'is_default' => true,
]);
```

### Virtual Product Fulfillment

```php
// Create virtual warehouse
$virtualWarehouse = $service->createVirtualWarehouse([
    'name' => 'Digital Warehouse',
    'code' => 'DIGITAL',
    'virtual_config' => [
        'auto_fulfill' => true,
        'delivery_method' => 'email',
    ],
]);

// Map to channel
$service->mapChannelToWarehouse($channel, $virtualWarehouse, [
    'priority' => 9999, // Lowest priority
]);
```

## Best Practices

1. **Set warehouse priorities** - Lower number = higher priority
2. **Use channel mapping** - Map channels to specific warehouses
3. **Configure geo-distance rules** - For location-based fulfillment
4. **Set default warehouses** - One default per channel
5. **Use virtual warehouses** - For digital products
6. **Track stock per warehouse** - Maintain accurate inventory levels
7. **Apply fulfillment rules** - Use rules for complex logic

## Database Schema

### warehouses

```sql
CREATE TABLE warehouses (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    code VARCHAR(50) UNIQUE,
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    postcode VARCHAR(20),
    country VARCHAR(2),
    phone VARCHAR(50),
    email VARCHAR(255),
    is_active BOOLEAN DEFAULT true,
    priority INTEGER DEFAULT 0,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    service_areas JSON,
    geo_distance_rules JSON,
    max_fulfillment_distance DECIMAL(10,2),
    is_dropship BOOLEAN DEFAULT false,
    dropship_provider VARCHAR(255),
    is_virtual BOOLEAN DEFAULT false,
    virtual_config JSON,
    fulfillment_rules JSON,
    auto_fulfill BOOLEAN DEFAULT false,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);
```

### channel_warehouse

```sql
CREATE TABLE channel_warehouse (
    id BIGINT PRIMARY KEY,
    channel_id BIGINT,
    warehouse_id BIGINT,
    priority INTEGER DEFAULT 0,
    is_default BOOLEAN DEFAULT false,
    is_active BOOLEAN DEFAULT true,
    fulfillment_rules JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(channel_id, warehouse_id)
);
```



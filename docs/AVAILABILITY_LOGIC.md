# Availability Logic

Complete availability system with hard/soft stops, restrictions, lead time calculation, and "Only X left" messaging.

## Overview

The Variant Availability Service provides comprehensive availability checking:

- ✅ **Hard stop vs soft stop** - Out of stock vs backorder
- ✅ **Country-based availability** - Restrict by country
- ✅ **Channel-based availability** - Restrict by sales channel
- ✅ **Customer-group restrictions** - Restrict by customer group
- ✅ **Lead time calculation** - Calculate estimated ship dates
- ✅ **"Only X left" logic** - Low stock messaging

## Availability Types

### Hard Stop
Product is **not available** - cannot be purchased:
- Out of stock and backorder not allowed
- Restricted by country/channel/customer group
- Product is inactive

### Soft Stop (Backorder)
Product is **available on backorder** - can be purchased but will ship later:
- Out of stock but backorder allowed
- Lead time calculated
- Estimated ship date provided

### In Stock
Product is **available** - can be purchased immediately:
- Sufficient stock available
- No restrictions
- Immediate fulfillment

## Usage

### Check Availability

```php
use App\Services\VariantAvailabilityService;

$service = app(VariantAvailabilityService::class);

$availability = $service->checkAvailability(
    variant: $variant,
    quantity: 5,
    context: [
        'channel' => $channel,
        'customer_group' => $customerGroup,
        'country' => 'US',
        'warehouse_id' => $warehouseId,
    ]
);

/*
Returns:
[
    'available' => bool,
    'type' => 'in_stock' | 'backorder' | 'hard_stop',
    'reason' => string,
    'can_backorder' => bool,
    'lead_time' => [
        'lead_time_days' => int,
        'estimated_ship_date' => string,
        'estimated_ship_datetime' => string,
        'is_backorder' => bool,
        'available_quantity' => int,
    ],
    'available_quantity' => int,
    'stock_status' => 'in_stock' | 'low_stock' | 'out_of_stock' | 'backorder',
    'only_x_left' => [
        'quantity' => int,
        'message' => string,
        'severity' => 'critical' | 'very_low' | 'low',
        'show_warning' => bool,
    ] | null,
    'inventory' => [...],
]
*/
```

### Get Availability Summary

```php
$summary = $service->getAvailabilitySummary($variant, [
    'channel' => $channel,
    'customer_group' => $customerGroup,
    'country' => 'US',
]);

/*
Returns complete availability summary with:
- Availability status
- Stock status
- Lead time
- Restrictions
- Inventory details
*/
```

## Hard Stop vs Soft Stop

### Hard Stop Example

```php
// Variant with no stock and backorder disabled
$variant->backorder_allowed = false;
$variant->stock = 0;

$availability = $service->checkAvailability($variant, 1);

// Returns:
[
    'available' => false,
    'type' => 'hard_stop',
    'reason' => 'Out of stock and backorder not allowed',
    'can_backorder' => false,
]
```

### Soft Stop (Backorder) Example

```php
// Variant with no stock but backorder enabled
$variant->backorder_allowed = true;
$variant->backorder_limit = 100;
$variant->stock = 0;

$availability = $service->checkAvailability($variant, 10);

// Returns:
[
    'available' => true,
    'type' => 'backorder',
    'reason' => 'Available on backorder',
    'can_backorder' => true,
    'backorder_quantity' => 10,
    'lead_time' => [
        'lead_time_days' => 14, // Base + backorder lead time
        'estimated_ship_date' => '2024-01-15',
    ],
]
```

## Country-Based Availability

### Add Country Restriction

```php
// Restrict variant from specific country
$service->addRestriction(
    variant: $variant,
    type: 'country',
    value: 'CN', // Country code
    action: 'deny',
    reason: 'Export restrictions'
);

// Allow variant in specific country
$service->addRestriction(
    variant: $variant,
    type: 'country',
    value: 'US',
    action: 'allow',
    reason: 'US market only'
);
```

### Check Country Availability

```php
// Check if available in country
$isAvailable = $service->isAvailableInCountry($variant, 'US');

// Check availability with country context
$availability = $service->checkAvailability($variant, 1, [
    'country' => 'US',
]);
```

## Channel-Based Availability

### Add Channel Restriction

```php
// Restrict variant from channel
$service->addRestriction(
    variant: $variant,
    type: 'channel',
    value: (string) $channel->id,
    action: 'deny',
    reason: 'Not available on this channel'
);
```

### Check Channel Availability

```php
// Check if available in channel
$isAvailable = $service->isAvailableInChannel($variant, $channel);

// Check availability with channel context
$availability = $service->checkAvailability($variant, 1, [
    'channel' => $channel,
]);
```

## Customer-Group Restrictions

### Add Customer Group Restriction

```php
// Restrict variant from customer group
$service->addRestriction(
    variant: $variant,
    type: 'customer_group',
    value: (string) $customerGroup->id,
    action: 'deny',
    reason: 'Premium product only'
);
```

### Check Customer Group Availability

```php
// Check if available for customer group
$isAvailable = $service->isAvailableForCustomerGroup($variant, $customerGroup);

// Check availability with customer group context
$availability = $service->checkAvailability($variant, 1, [
    'customer_group' => $customerGroup,
]);
```

## Lead Time Calculation

### Calculate Lead Time

```php
$leadTime = $service->calculateLeadTime($variant, $quantity, [
    'warehouse_id' => $warehouseId,
    'country' => 'US',
]);

/*
Returns:
[
    'lead_time_days' => int,
    'estimated_ship_date' => 'YYYY-MM-DD',
    'estimated_ship_datetime' => ISO8601,
    'is_backorder' => bool,
    'available_quantity' => int,
]
*/
```

### Lead Time Factors

1. **Base lead time** - `variant->lead_time_days`
2. **Warehouse lead time** - From warehouse fulfillment rules
3. **Backorder lead time** - Additional time if backorder needed
4. **Country-specific** - Shipping time based on destination

## "Only X Left" Logic

### Automatic Messaging

The system automatically generates "Only X left" messages based on stock levels:

```php
$availability = $service->checkAvailability($variant, 1);

if ($availability['only_x_left']) {
    echo $availability['only_x_left']['message'];
    // "Only 3 left!" (critical)
    // "Only 7 left in stock" (very_low)
    // "Only 12 left" (low)
}
```

### Thresholds

- **Critical**: ≤ 1 unit
- **Very Low**: ≤ 5 units
- **Low**: ≤ 10 units

### Customize Thresholds

```php
// In VariantAvailabilityService
protected array $lowStockThresholds = [
    1 => 'critical',
    5 => 'very_low',
    10 => 'low',
];
```

## Integration Examples

### Storefront Product Page

```php
use App\Services\VariantAvailabilityService;

class ProductController extends Controller
{
    public function show(ProductVariant $variant)
    {
        $service = app(VariantAvailabilityService::class);
        
        $availability = $service->checkAvailability($variant, 1, [
            'channel' => Channel::default()->first(),
            'customer_group' => auth()->user()?->customerGroups->first(),
            'country' => request()->header('X-Country-Code') ?? 'US',
        ]);

        return view('product.show', [
            'variant' => $variant,
            'availability' => $availability,
        ]);
    }
}
```

### Cart Add Validation

```php
class CartController extends Controller
{
    public function addToCart(Request $request, ProductVariant $variant)
    {
        $service = app(VariantAvailabilityService::class);
        
        $availability = $service->checkAvailability($variant, $request->quantity, [
            'channel' => Cart::current()->channel,
            'customer_group' => auth()->user()?->customerGroups->first(),
            'country' => $request->header('X-Country-Code'),
        ]);

        if (!$availability['available']) {
            return response()->json([
                'error' => $availability['reason'],
            ], 400);
        }

        // Add to cart
        // ...
    }
}
```

### Admin Restriction Management

```php
class AdminAvailabilityController extends Controller
{
    public function addRestriction(Request $request, ProductVariant $variant)
    {
        $service = app(VariantAvailabilityService::class);

        $restriction = $service->addRestriction(
            variant: $variant,
            type: $request->input('type'), // country, channel, customer_group
            value: $request->input('value'),
            action: $request->input('action', 'deny'),
            reason: $request->input('reason'),
            priority: $request->input('priority', 0)
        );

        return response()->json($restriction);
    }

    public function removeRestriction(ProductVariant $variant, string $type, string $value)
    {
        $service = app(VariantAvailabilityService::class);
        $service->removeRestriction($variant, $type, $value);

        return response()->json(['success' => true]);
    }
}
```

## Response Format

### Available (In Stock)

```php
[
    'available' => true,
    'type' => 'in_stock',
    'reason' => 'In stock',
    'can_backorder' => false,
    'lead_time' => [
        'lead_time_days' => 3,
        'estimated_ship_date' => '2024-01-10',
        'is_backorder' => false,
    ],
    'available_quantity' => 50,
    'stock_status' => 'in_stock',
    'only_x_left' => null, // or message if low stock
]
```

### Available (Backorder)

```php
[
    'available' => true,
    'type' => 'backorder',
    'reason' => 'Available on backorder',
    'can_backorder' => true,
    'backorder_quantity' => 5,
    'backorder_limit' => 100,
    'lead_time' => [
        'lead_time_days' => 14,
        'estimated_ship_date' => '2024-01-20',
        'is_backorder' => true,
    ],
    'available_quantity' => 0,
    'stock_status' => 'backorder',
]
```

### Not Available (Hard Stop)

```php
[
    'available' => false,
    'type' => 'hard_stop',
    'reason' => 'Out of stock and backorder not allowed',
    'can_backorder' => false,
    'lead_time' => null,
    'available_quantity' => 0,
    'stock_status' => 'out_of_stock',
]
```

### Restricted (Hard Stop)

```php
[
    'available' => false,
    'type' => 'hard_stop',
    'reason' => 'Product not available in CN',
    'can_backorder' => false,
    'lead_time' => null,
    'available_quantity' => 0,
    'stock_status' => 'unavailable',
]
```

## Best Practices

1. **Always check restrictions first** - Restrictions are hard stops
2. **Handle backorders gracefully** - Show lead time and estimated ship date
3. **Display "Only X left"** - Use for urgency messaging
4. **Calculate lead time** - Always show estimated ship date
5. **Check all contexts** - Include channel, country, customer group
6. **Use priority for restrictions** - Higher priority restrictions checked first
7. **Log availability checks** - Track availability queries for analytics

## Configuration

### Low Stock Thresholds

```php
// In VariantAvailabilityService
protected array $lowStockThresholds = [
    1 => 'critical',    // "Only 1 left!"
    5 => 'very_low',    // "Only 5 left in stock"
    10 => 'low',        // "Only 10 left"
];
```

### Default Lead Times

```php
// Variant-level
$variant->lead_time_days = 3;

// Backorder lead time
$variant->backorder_lead_time_days = 7;
```



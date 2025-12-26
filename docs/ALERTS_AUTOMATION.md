# Alerts & Automation

Complete inventory alerts and automation system with low-stock alerts, out-of-stock triggers, auto-disable/enable variants, and supplier reorder hooks.

## Overview

The Inventory Automation System provides:

- ✅ **Low-stock alerts** - Automatic alerts when stock falls below threshold
- ✅ **Out-of-stock triggers** - Track and respond to out-of-stock events
- ✅ **Auto-disable variants** - Automatically disable variants when out of stock
- ✅ **Auto-enable on restock** - Automatically enable variants when restocked
- ✅ **Supplier reorder hooks** - Automated supplier reorder integration

## Features

### Low-Stock Alerts

Automatically create alerts when inventory falls below reorder point:

```php
use App\Services\InventoryAutomationService;

$service = app(InventoryAutomationService::class);

// Process automation (creates alerts automatically)
$results = $service->processAutomation($variant);

// Results include:
// - alerts_created: Number of alerts created
```

### Out-of-Stock Triggers

Track when variants go out of stock and trigger automation:

```php
// Handle out of stock event
$trigger = $service->handleOutOfStock(
    variant: $variant,
    quantityBefore: 5,
    quantityAfter: 0,
    reason: 'sale',
    warehouseId: $warehouseId
);

// Automatically:
// - Creates OutOfStockTrigger record
// - Processes automation rules
// - May disable variant, send alerts, create reorders
```

### Auto-Disable Variants

Automatically disable variants when out of stock:

```php
use App\Models\InventoryAutomationRule;

// Create automation rule
InventoryAutomationRule::create([
    'product_variant_id' => $variant->id,
    'name' => 'Auto-disable on out of stock',
    'trigger_type' => 'out_of_stock',
    'action_type' => 'disable_variant',
    'is_active' => true,
    'priority' => 10,
]);
```

### Auto-Enable on Restock

Automatically enable variants when restocked:

```php
// Create automation rule
InventoryAutomationRule::create([
    'product_variant_id' => $variant->id,
    'name' => 'Auto-enable on restock',
    'trigger_type' => 'restock',
    'action_type' => 'enable_variant',
    'is_active' => true,
    'priority' => 10,
]);

// Handle restock event
$service->handleRestock(
    variant: $variant,
    quantity: 50,
    reason: 'restock',
    warehouseId: $warehouseId
);
```

### Supplier Reorder Hooks

Automated supplier reorder integration:

```php
use App\Models\SupplierReorderHook;

// Create supplier reorder hook
SupplierReorderHook::create([
    'product_variant_id' => $variant->id,
    'warehouse_id' => $warehouseId,
    'supplier_name' => 'ABC Supplier',
    'supplier_sku' => 'SUP-12345',
    'reorder_point' => 10,
    'reorder_quantity' => 100,
    'unit_cost' => 25.50,
    'trigger_type' => 'auto_on_low_stock',
    'integration_type' => 'api',
    'integration_config' => [
        'endpoint' => 'https://api.supplier.com/reorder',
        'api_key' => 'your-api-key',
    ],
    'is_active' => true,
]);
```

## Automation Rules

### Rule Types

**Trigger Types:**
- `low_stock` - Trigger when stock <= threshold
- `out_of_stock` - Trigger when stock = 0
- `restock` - Trigger when stock increases from 0
- `below_safety_stock` - Trigger when below safety stock
- `custom` - Custom trigger logic

**Action Types:**
- `disable_variant` - Disable variant
- `enable_variant` - Enable variant
- `hide_variant` - Hide variant
- `show_variant` - Show variant
- `send_alert` - Send alert notification
- `create_reorder` - Create supplier reorder
- `custom` - Custom action

### Creating Automation Rules

```php
use App\Models\InventoryAutomationRule;

// Auto-disable on out of stock
$rule = InventoryAutomationRule::create([
    'product_variant_id' => $variant->id,
    'name' => 'Auto-disable on out of stock',
    'description' => 'Automatically disable variant when out of stock',
    'trigger_type' => 'out_of_stock',
    'action_type' => 'disable_variant',
    'is_active' => true,
    'priority' => 10,
    'run_once' => false, // Can trigger multiple times
    'cooldown_minutes' => 60, // Cooldown between triggers
]);

// Auto-enable on restock
$rule = InventoryAutomationRule::create([
    'product_variant_id' => $variant->id,
    'name' => 'Auto-enable on restock',
    'trigger_type' => 'restock',
    'action_type' => 'enable_variant',
    'is_active' => true,
    'priority' => 10,
]);

// Low stock alert
$rule = InventoryAutomationRule::create([
    'product_id' => $product->id, // Apply to all variants
    'name' => 'Low stock alert',
    'trigger_type' => 'low_stock',
    'trigger_conditions' => [
        'threshold' => 10, // Custom threshold
    ],
    'action_type' => 'send_alert',
    'is_active' => true,
]);

// Auto-create reorder
$rule = InventoryAutomationRule::create([
    'product_variant_id' => $variant->id,
    'name' => 'Auto-reorder on low stock',
    'trigger_type' => 'low_stock',
    'action_type' => 'create_reorder',
    'is_active' => true,
]);
```

## Supplier Reorder Hooks

### Integration Types

**Supported Integration Types:**
- `api` - API integration
- `email` - Email-based reorders
- `webhook` - Webhook integration
- `csv_export` - CSV export
- `erp` - ERP integration

### API Integration

```php
SupplierReorderHook::create([
    'product_variant_id' => $variant->id,
    'supplier_name' => 'ABC Supplier',
    'supplier_sku' => 'SUP-12345',
    'reorder_point' => 10,
    'reorder_quantity' => 100,
    'unit_cost' => 25.50,
    'trigger_type' => 'auto_on_low_stock',
    'integration_type' => 'api',
    'integration_config' => [
        'endpoint' => 'https://api.supplier.com/reorder',
        'api_key' => 'your-api-key',
    ],
]);
```

### Email Integration

```php
SupplierReorderHook::create([
    'product_variant_id' => $variant->id,
    'supplier_name' => 'ABC Supplier',
    'supplier_email' => 'orders@supplier.com',
    'reorder_point' => 10,
    'reorder_quantity' => 100,
    'trigger_type' => 'auto_on_low_stock',
    'integration_type' => 'email',
    'integration_config' => [
        'email' => 'orders@supplier.com',
    ],
]);
```

### Webhook Integration

```php
SupplierReorderHook::create([
    'product_variant_id' => $variant->id,
    'reorder_point' => 10,
    'reorder_quantity' => 100,
    'trigger_type' => 'auto_on_out_of_stock',
    'integration_type' => 'webhook',
    'integration_config' => [
        'webhook_url' => 'https://supplier.com/webhook/reorder',
    ],
]);
```

## Usage Examples

### Process Automation Manually

```php
use App\Services\InventoryAutomationService;

$service = app(InventoryAutomationService::class);

// Process automation for a variant
$results = $service->processAutomation($variant, $warehouseId);

/*
Returns:
[
    'alerts_created' => 1,
    'variants_disabled' => 1,
    'variants_enabled' => 0,
    'reorders_created' => 1,
    'triggers_created' => 1,
]
*/
```

### Handle Out of Stock Event

```php
// When inventory goes to zero
$service->handleOutOfStock(
    variant: $variant,
    quantityBefore: 5,
    quantityAfter: 0,
    reason: 'sale',
    warehouseId: $warehouseId
);

// Automatically:
// - Creates OutOfStockTrigger record
// - Processes automation rules
// - May disable variant, send alerts, create reorders
```

### Handle Restock Event

```php
// When inventory is restocked
$service->handleRestock(
    variant: $variant,
    quantity: 50,
    reason: 'restock',
    warehouseId: $warehouseId
);

// Automatically:
// - Marks out-of-stock triggers as recovered
// - Processes automation rules
// - May enable variant
```

### Trigger Supplier Reorder

```php
use App\Services\SupplierReorderService;

$reorderService = app(SupplierReorderService::class);

// Trigger reorder for a variant
$success = $reorderService->triggerReorder($variant, $currentQuantity, $warehouseId);
```

## Artisan Commands

### Process Inventory Automation

```bash
# Process automation for all variants with low stock
php artisan inventory:process-automation

# Process automation for specific variant
php artisan inventory:process-automation --variant=123

# Process automation for all variants
php artisan inventory:process-automation --all
```

### Schedule Automation Processing

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Process automation every 5 minutes
    $schedule->command('inventory:process-automation')
        ->everyFiveMinutes();
}
```

## Event Observers

The system uses Laravel observers to automatically trigger automation:

```php
// InventoryLevelObserver automatically:
// - Detects out of stock events
// - Detects restock events
// - Triggers automation rules
```

## Out of Stock Triggers

### View Out of Stock Triggers

```php
use App\Models\OutOfStockTrigger;

// Get unrecovered triggers
$triggers = OutOfStockTrigger::unrecovered()->get();

// Get recovered triggers
$triggers = OutOfStockTrigger::recovered()->get();

// Get triggers for variant
$triggers = OutOfStockTrigger::where('product_variant_id', $variant->id)->get();
```

### Mark Trigger as Recovered

```php
$trigger->markRecovered(
    quantity: 50,
    reason: 'restock'
);
```

## Low Stock Alerts

### View Low Stock Alerts

```php
use App\Models\LowStockAlert;

// Get unresolved alerts
$alerts = LowStockAlert::where('is_resolved', false)->get();

// Get alerts for variant
$alerts = LowStockAlert::where('product_variant_id', $variant->id)->get();
```

## Best Practices

1. **Set appropriate thresholds** - Configure reorder points and low stock thresholds
2. **Use priority** - Higher priority rules are processed first
3. **Configure cooldowns** - Prevent excessive automation triggers
4. **Test automation rules** - Test rules before activating
5. **Monitor automation** - Review automation results regularly
6. **Use run_once sparingly** - Most rules should be able to trigger multiple times
7. **Configure supplier hooks** - Set up supplier integrations properly
8. **Handle errors gracefully** - Supplier integrations may fail

## Configuration

### Automation Rule Priority

Rules are processed in priority order (higher priority first):

```php
$rule->priority = 10; // High priority
$rule->priority = 5;  // Medium priority
$rule->priority = 1;  // Low priority
```

### Cooldown Periods

Prevent excessive triggers with cooldowns:

```php
$rule->cooldown_minutes = 60; // 1 hour cooldown
```

### Run Once

Some rules should only run once:

```php
$rule->run_once = true; // Only trigger once
```

## Integration Examples

### Custom Trigger Logic

```php
// In InventoryAutomationService, extend checkCustomTrigger()
protected function checkCustomTrigger(
    InventoryAutomationRule $rule,
    ProductVariant $variant,
    int $availableQuantity,
    array $inventory
): bool {
    $conditions = $rule->trigger_conditions ?? [];
    
    // Custom logic here
    if (isset($conditions['custom_check'])) {
        // Perform custom check
        return true; // or false
    }
    
    return false;
}
```

### Custom Action Logic

```php
// In InventoryAutomationService, extend executeCustomAction()
protected function executeCustomAction(
    InventoryAutomationRule $rule,
    ProductVariant $variant,
    int $availableQuantity,
    array $inventory,
    array $results
): array {
    $config = $rule->action_config ?? [];
    
    // Custom action logic here
    // Update $results as needed
    
    return $results;
}
```

## Troubleshooting

### Automation Not Triggering

1. Check rule is active: `$rule->is_active === true`
2. Check cooldown: `$rule->canTrigger()`
3. Check trigger condition: Verify condition logic
4. Check priority: Higher priority rules may prevent lower priority rules

### Supplier Reorder Not Working

1. Check hook is active: `$hook->is_active === true`
2. Check integration config: Verify API keys, endpoints, etc.
3. Check logs: Review Laravel logs for errors
4. Test manually: Try triggering reorder manually

### Variants Not Disabling/Enabling

1. Check automation rules: Verify rules exist and are active
2. Check trigger conditions: Verify conditions are met
3. Check lifecycle service: Verify VariantLifecycleService is working
4. Check logs: Review automation logs



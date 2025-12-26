# Variant Lifecycle & Workflows

Complete lifecycle and workflow management for variants.

## Overview

Enterprise-grade control over variant lifecycle:

1. **Draft → Active → Archived flow** - Complete status workflow
2. **Approval workflow** - Submit, approve, reject variants
3. **Scheduled activation/deactivation** - Time-based status changes
4. **Soft-delete with recovery** - Safe deletion with restore capability
5. **Lock variants with active orders** - Prevent changes when orders exist
6. **Clone variant** - Duplicate variants with relationships
7. **Bulk enable / disable** - Mass operations

## Status Flow

### Status States

- **`draft`** - New variant, not yet active
- **`active`** - Variant is live and available
- **`inactive`** - Temporarily disabled
- **`archived`** - Permanently archived (soft-deleted)

### Status Transitions

```
Draft → Active → Inactive → Archived
  ↓       ↓         ↓
  └───────┴─────────┘
    (can transition between active/inactive)
```

## Usage

### VariantLifecycleService

```php
use App\Services\VariantLifecycleService;

$service = app(VariantLifecycleService::class);
```

### Status Transitions

```php
// Transition to active
$service->transitionStatus($variant, 'active');

// Transition to inactive
$service->transitionStatus($variant, 'inactive');

// Transition to archived
$service->transitionStatus($variant, 'archived');

// Force transition (bypasses locks)
$service->transitionStatus($variant, 'archived', ['force' => true]);
```

### Activate Variant

```php
// Activate variant (checks approval and locks)
$service->activate($variant, $approvedBy);

// Using model method
$variant->activate($approvedBy);
```

### Archive Variant

```php
// Archive variant (checks for active orders)
$service->archive($variant);

// Force archive (bypasses active order check)
$service->archive($variant, true);

// Using model method
$variant->archive();
$variant->archive(true); // Force
```

## Approval Workflow

### Approval Status

- **`pending`** - Awaiting approval
- **`approved`** - Approved and ready
- **`rejected`** - Rejected (with reason)
- **`not_required`** - No approval needed (default)

### Submit for Approval

```php
// Submit variant for approval
$service->submitForApproval($variant, $submittedBy);

// Using model method
$variant->submitForApproval($submittedBy);
```

### Approve Variant

```php
// Approve variant
$service->approve($variant, $approvedBy, $autoActivate = false);

// Approve and auto-activate
$service->approve($variant, $approvedBy, true);

// Using model method
$variant->approve($approvedBy);
$variant->approve($approvedBy, true); // Auto-activate
```

### Reject Variant

```php
// Reject variant with reason
$service->reject($variant, 'Missing required images', $rejectedBy);

// Using model method
$variant->reject('Missing required images', $rejectedBy);
```

### Check Approval Status

```php
// Get approval status
$approvalStatus = $variant->approval_status; // pending, approved, rejected, not_required

// Get approval status label
$label = $variant->getApprovalStatusLabel(); // "Pending Approval", "Approved", etc.

// Check if approved
if ($variant->approval_status === 'approved') {
    // Variant is approved
}
```

## Scheduled Activation/Deactivation

### Schedule Activation

```php
// Schedule activation for future date
$service->scheduleActivation($variant, '2025-12-31 00:00:00');

// Using Carbon
$service->scheduleActivation($variant, Carbon::now()->addDays(7));

// Using model method
$variant->scheduleActivation('2025-12-31 00:00:00');
```

### Schedule Deactivation

```php
// Schedule deactivation
$service->scheduleDeactivation($variant, '2025-12-31 23:59:59');

// Using model method
$variant->scheduleDeactivation('2025-12-31 23:59:59');
```

### Process Scheduled Changes

```php
// Process scheduled activations
$activated = $service->processScheduledActivations();

// Process scheduled deactivations
$deactivated = $service->processScheduledDeactivations();

// Run via Artisan command
php artisan variants:process-scheduled
```

### Cron Setup

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('variants:process-scheduled')
        ->everyMinute();
}
```

## Lock Variants with Active Orders

### Lock Variant

```php
// Lock variant (prevents status changes)
$service->lock($variant, 'Has active orders');

// Using model method
$variant->lock('Has active orders');
```

### Unlock Variant

```php
// Unlock variant
$service->unlock($variant);

// Using model method
$variant->unlock();
```

### Check Lock Status

```php
// Check if locked
if ($variant->isLocked()) {
    $reason = $variant->locked_reason;
    $lockedAt = $variant->locked_at;
}

// Check for active orders
if ($variant->hasActiveOrders()) {
    // Variant has active orders
}
```

## Clone Variant

### Clone Variant

```php
// Clone variant
$cloned = $service->clone($variant);

// Clone with overrides
$cloned = $service->clone($variant, [
    'sku' => 'NEW-SKU',
    'status' => 'draft',
    'price_override' => 5000,
]);

// Using model method
$cloned = $variant->clone();
$cloned = $variant->clone(['sku' => 'NEW-SKU']);
```

### Clone Features

- **Relationships**: Clones variant options and media relationships
- **SKU Generation**: Auto-generates new SKU (original-SKU-CLONE-timestamp)
- **Status**: Defaults to 'draft'
- **Tracking**: Stores clone source in `cloned_from_id`

### Access Clone Source

```php
// Get clone source
$source = $cloned->clonedFrom;

// Get all clones of a variant
$clones = $variant->clones;
```

## Bulk Operations

### Bulk Enable

```php
// Enable multiple variants
$enabled = $service->bulkEnable([1, 2, 3, 4, 5]);

// Using collection
$variantIds = collect([1, 2, 3]);
$enabled = $service->bulkEnable($variantIds);
```

### Bulk Disable

```php
// Disable multiple variants
$disabled = $service->bulkDisable([1, 2, 3, 4, 5]);
```

### Bulk Archive

```php
// Archive multiple variants
$archived = $service->bulkArchive([1, 2, 3], $force = false);
```

## Soft-Delete with Recovery

### Soft Delete

```php
// Soft delete variant (checks for active orders)
$service->softDelete($variant);

// Using model method (Laravel's SoftDeletes)
$variant->delete(); // Soft delete
```

### Restore

```php
// Restore soft-deleted variant
$service->restore($variantId);

// Using model method
$variant = ProductVariant::withTrashed()->find($variantId);
$variant->restore();
```

### Check Deleted Status

```php
// Check if deleted
if ($variant->trashed()) {
    // Variant is soft-deleted
}

// Get deleted variants
$deleted = ProductVariant::onlyTrashed()->get();
```

## Model Methods

### ProductVariant Methods

```php
// Status transitions
$variant->activate($approvedBy);
$variant->deactivate();
$variant->archive();
$variant->archive(true); // Force

// Approval workflow
$variant->submitForApproval($submittedBy);
$variant->approve($approvedBy, $autoActivate);
$variant->reject($reason, $rejectedBy);

// Scheduling
$variant->scheduleActivation($date);
$variant->scheduleDeactivation($date);

// Locking
$variant->lock($reason);
$variant->unlock();
$variant->isLocked();

// Cloning
$cloned = $variant->clone($overrides);

// Status labels
$statusLabel = $variant->getStatusLabel();
$approvalLabel = $variant->getApprovalStatusLabel();

// Relationships
$source = $variant->clonedFrom;
$clones = $variant->clones;
$approver = $variant->approver;
```

## Workflow Examples

### Complete Workflow

```php
// 1. Create variant (draft)
$variant = ProductVariant::create([
    'product_id' => 1,
    'sku' => 'TSH-RED-XL',
    'status' => 'draft',
]);

// 2. Submit for approval
$variant->submitForApproval();

// 3. Approve and activate
$variant->approve(auth()->id(), true);

// 4. Schedule deactivation
$variant->scheduleDeactivation('2025-12-31');

// 5. Lock if has orders
if ($variant->hasActiveOrders()) {
    $variant->lock('Has active orders');
}

// 6. Archive when done
$variant->archive();
```

### Approval Workflow

```php
// Submit for approval
$variant->submitForApproval();

// Approve (admin action)
if ($admin->can('approve-variants')) {
    $variant->approve($admin->id, true); // Auto-activate
}

// Or reject
$variant->reject('Missing product images', $admin->id);
```

### Scheduled Launch

```php
// Create variant in draft
$variant = ProductVariant::create([...]);

// Schedule activation for launch date
$variant->scheduleActivation('2025-01-01 00:00:00');

// Process scheduled activations (via cron)
// Variant will automatically activate on launch date
```

### Clone and Modify

```php
// Clone existing variant
$cloned = $variant->clone([
    'sku' => 'TSH-RED-XL-V2',
    'status' => 'draft',
    'price_override' => 5000,
]);

// Modify cloned variant
$cloned->update([
    'meta_title' => 'Updated title',
]);

// Submit for approval
$cloned->submitForApproval();
```

## Best Practices

1. **Use draft status** for new variants
2. **Require approval** for important variants
3. **Schedule activations** for product launches
4. **Lock variants** with active orders
5. **Use soft-delete** instead of hard delete
6. **Clone variants** for similar products
7. **Bulk operations** for efficiency
8. **Track approval** with user IDs
9. **Process scheduled** changes via cron
10. **Check locks** before status changes

## Notes

- **Status flow**: Draft → Active → Inactive → Archived
- **Approval**: Required before activation (if pending)
- **Locks**: Prevent status changes when active orders exist
- **Scheduling**: Processed via cron job
- **Cloning**: Preserves relationships, generates new SKU
- **Soft-delete**: Uses Laravel's SoftDeletes trait
- **Bulk operations**: Efficient for mass updates
- **Force flag**: Bypasses locks and checks



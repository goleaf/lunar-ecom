# Product Lifecycle & Workflows System

This document describes the comprehensive enterprise-grade product lifecycle and workflow management system.

## Overview

The system provides:

1. **Draft → Review → Published workflow** - Multi-stage approval process
2. **Approval system** - Role-based approval workflow
3. **Role-based edit permissions** - Granular access control
4. **Bulk actions** - Mass operations on products
5. **Import / Export** - CSV, XML, API formats
6. **Scheduled updates** - Time-based product changes
7. **Product expiration** - Auto-archive expired products
8. **Automated rules** - Auto-archive when stock = 0, etc.

## Database Structure

### Product Workflows Table

Stores workflow state for each product:

```sql
product_id BIGINT (FK to products)
status ENUM('draft', 'review', 'approved', 'published', 'archived', 'rejected')
previous_status ENUM(...)
submitted_by BIGINT (FK to users)
approved_by BIGINT (FK to users)
rejected_by BIGINT (FK to users)
submitted_at TIMESTAMP
approved_at TIMESTAMP
rejected_at TIMESTAMP
published_at TIMESTAMP
archived_at TIMESTAMP
submission_notes TEXT
approval_notes TEXT
rejection_reason TEXT
expires_at TIMESTAMP
auto_archive_on_expiry BOOLEAN
```

### Product Workflow History Table

Audit trail of all workflow changes:

```sql
product_id BIGINT (FK to products)
workflow_id BIGINT (FK to product_workflows)
action ENUM('created', 'updated', 'submitted', 'approved', 'rejected', ...)
from_status ENUM(...)
to_status ENUM(...)
user_id BIGINT (FK to users)
notes TEXT
metadata JSON
created_at TIMESTAMP
```

### Product Automation Rules Table

Automated rules for products:

```sql
name VARCHAR(255)
description TEXT
trigger_type ENUM('stock_level', 'stock_zero', 'price_change', 'expiration', ...)
conditions JSON
actions JSON
scope ENUM('all', 'category', 'collection', 'brand', 'tag', 'custom')
scope_filters JSON
is_active BOOLEAN
priority INT
execution_count INT
last_executed_at TIMESTAMP
next_execution_at TIMESTAMP
```

### Product Bulk Actions Table

Tracks bulk operations:

```sql
user_id BIGINT (FK to users)
action_type ENUM('publish', 'unpublish', 'archive', 'update_price', ...)
filters JSON
parameters JSON
status ENUM('pending', 'processing', 'completed', 'failed')
total_products INT
processed_products INT
successful_products INT
failed_products INT
product_ids JSON
errors JSON
started_at TIMESTAMP
completed_at TIMESTAMP
```

## Services

### ProductWorkflowService

Manages product workflow states and transitions.

**Location**: `app/Services/ProductWorkflowService.php`

**Key Methods**:

```php
use App\Services\ProductWorkflowService;

$service = app(ProductWorkflowService::class);

// Submit for review
$workflow = $service->submitForReview($product, 'Ready for review');

// Approve
$workflow = $service->approve($product, 'Looks good!');

// Reject
$workflow = $service->reject($product, 'Needs more details');

// Publish
$workflow = $service->publish($product, $expiresAt, $autoArchive);

// Unpublish
$workflow = $service->unpublish($product);

// Archive
$workflow = $service->archive($product);

// Set expiration
$workflow = $service->setExpiration($product, $expiresAt, true);

// Process expired products
$count = $service->processExpiredProducts();

// Get history
$history = $service->getHistory($product, 50);
```

### ProductBulkActionService

Manages bulk operations on products.

**Location**: `app/Services/ProductBulkActionService.php`

**Key Methods**:

```php
use App\Services\ProductBulkActionService;

$service = app(ProductBulkActionService::class);

// Execute bulk action
$bulkAction = $service->execute('publish', [
    'status' => 'approved',
    'category_id' => 5,
], [
    'expires_at' => now()->addDays(30),
]);

// Get status
$status = $bulkAction->status;
$progress = [
    'total' => $bulkAction->total_products,
    'processed' => $bulkAction->processed_products,
    'successful' => $bulkAction->successful_products,
    'failed' => $bulkAction->failed_products,
];
```

**Supported Actions**:

- `publish` - Publish products
- `unpublish` - Unpublish products
- `archive` - Archive products
- `unarchive` - Unarchive products
- `delete` - Delete products
- `update_status` - Update status
- `update_price` - Update prices
- `update_stock` - Update stock levels
- `assign_category` - Assign to category
- `assign_collection` - Assign to collection
- `assign_tag` - Assign tag
- `remove_category` - Remove from category
- `remove_collection` - Remove from collection
- `remove_tag` - Remove tag

### ProductExportService

Exports products in various formats.

**Location**: `app/Services/ProductExportService.php`

**Key Methods**:

```php
use App\Services\ProductExportService;

$service = app(ProductExportService::class);

// Export to CSV
return $service->exportToCSV($products, ['id', 'sku', 'name', 'price']);

// Export to XML
return $service->exportToXML($products, ['id', 'sku', 'name', 'price']);

// Export to JSON (API)
return $service->exportToJSON($products, ['id', 'sku', 'name', 'price']);
```

### ProductAutomationService

Manages automated product rules.

**Location**: `app/Services/ProductAutomationService.php`

**Key Methods**:

```php
use App\Services\ProductAutomationService;

$service = app(ProductAutomationService::class);

// Create rule
$rule = $service->createRule([
    'name' => 'Auto-archive out of stock',
    'trigger_type' => 'stock_zero',
    'conditions' => [
        ['field' => 'stock', 'operator' => 'equals', 'value' => 0],
    ],
    'actions' => [
        ['type' => 'archive'],
        ['type' => 'notify', 'recipients' => ['admin@example.com']],
    ],
    'scope' => 'all',
    'is_active' => true,
]);

// Process due rules
$processed = $service->processDueRules();
```

## Workflow States

### Draft
- Initial state for new products
- Can be edited freely
- Can be submitted for review

### Review
- Product submitted for approval
- Cannot be edited (unless rejected)
- Can be approved or rejected

### Approved
- Product approved by reviewer
- Ready to be published
- Can be published or sent back to draft

### Published
- Product is live
- Can be unpublished or archived
- Can have expiration date

### Archived
- Product is hidden
- Can be unarchived
- Preserves all data

### Rejected
- Product rejected during review
- Can be edited and resubmitted
- Includes rejection reason

## Usage Examples

### Basic Workflow

```php
use App\Models\Product;
use App\Services\ProductWorkflowService;

$product = Product::find(1);
$workflowService = app(ProductWorkflowService::class);

// Submit for review
$workflowService->submitForReview($product, 'Ready for approval');

// Approve (as admin)
$workflowService->approve($product, 'Approved');

// Publish
$workflowService->publish($product, now()->addDays(30), true);
```

### Bulk Actions

```php
use App\Services\ProductBulkActionService;

$bulkService = app(ProductBulkActionService::class);

// Bulk publish approved products
$bulkAction = $bulkService->execute('publish', [
    'status' => 'approved',
]);

// Bulk update prices
$bulkAction = $bulkService->execute('update_price', [
    'category_id' => 5,
], [
    'price' => 99.99,
    'currency_id' => 1,
]);

// Bulk update stock
$bulkAction = $bulkService->execute('update_stock', [
    'collection_id' => 3,
], [
    'stock' => 100,
    'operation' => 'set', // set, add, subtract
]);
```

### Export Products

```php
use App\Models\Product;
use App\Services\ProductExportService;

$products = Product::published()->get();
$exportService = app(ProductExportService::class);

// CSV export
return $exportService->exportToCSV($products);

// XML export
return $exportService->exportToXML($products);

// JSON export (API)
return $exportService->exportToJSON($products);
```

### Automation Rules

```php
use App\Services\ProductAutomationService;

$automationService = app(ProductAutomationService::class);

// Auto-archive when stock = 0
$rule = $automationService->createRule([
    'name' => 'Auto-archive out of stock',
    'description' => 'Automatically archive products when stock reaches zero',
    'trigger_type' => 'stock_zero',
    'conditions' => [
        ['field' => 'stock', 'operator' => 'equals', 'value' => 0],
    ],
    'actions' => [
        ['type' => 'archive'],
    ],
    'scope' => 'all',
    'is_active' => true,
    'priority' => 10,
]);

// Auto-publish approved products after 24 hours
$rule = $automationService->createRule([
    'name' => 'Auto-publish approved',
    'trigger_type' => 'date_range',
    'conditions' => [
        ['field' => 'status', 'operator' => 'equals', 'value' => 'approved'],
        ['field' => 'approved_at', 'operator' => 'less_than', 'value' => now()->subDay()],
    ],
    'actions' => [
        ['type' => 'publish'],
    ],
    'scope' => 'all',
    'is_active' => true,
]);
```

## Role-Based Permissions

### ProductWorkflowPolicy

Controls access to workflow operations:

- **Editors**: Can submit, publish, edit (unlocked products)
- **Managers**: Can approve, reject, publish, edit
- **Admins**: Full access including delete, edit locked products

**Location**: `app/Policies/ProductWorkflowPolicy.php`

**Usage**:

```php
use App\Policies\ProductWorkflowPolicy;

// Check permission
if ($user->can('submitForReview', $product)) {
    $workflowService->submitForReview($product);
}

if ($user->can('approve', $product)) {
    $workflowService->approve($product);
}
```

## Scheduled Commands

### Process Product Automations

Runs automation rules:

```bash
php artisan products:process-automations
```

Schedule in `app/Console/Kernel.php`:

```php
$schedule->command('products:process-automations')
    ->everyFiveMinutes();
```

### Process Product Expirations

Processes expired products:

```bash
php artisan products:process-expirations
```

Schedule:

```php
$schedule->command('products:process-expirations')
    ->hourly();
```

## Import/Export

### CSV Import

Already implemented in `ProductImportService`. Supports:
- Field mapping
- Update existing products
- Error handling
- Progress tracking

### CSV/XML/JSON Export

Use `ProductExportService`:

```php
// Export selected fields
$exportService->exportToCSV($products, ['id', 'sku', 'name', 'price']);

// Export all fields
$exportService->exportToCSV($products);
```

## Best Practices

1. **Workflow States**: Always use workflow service methods, don't update status directly
2. **Bulk Actions**: Queue large bulk operations to avoid timeouts
3. **Automation Rules**: Test rules on small datasets first
4. **Permissions**: Always check permissions before workflow operations
5. **History**: Workflow history is automatically recorded
6. **Expiration**: Set expiration dates for time-sensitive products
7. **Notifications**: Configure notifications for workflow transitions

## API Endpoints

### Workflow

```php
POST /api/products/{id}/workflow/submit
POST /api/products/{id}/workflow/approve
POST /api/products/{id}/workflow/reject
POST /api/products/{id}/workflow/publish
POST /api/products/{id}/workflow/unpublish
POST /api/products/{id}/workflow/archive
GET  /api/products/{id}/workflow/history
```

### Bulk Actions

```php
POST /api/products/bulk-action
GET  /api/products/bulk-actions/{id}/status
```

### Export

```php
GET /api/products/export?format=csv
GET /api/products/export?format=xml
GET /api/products/export?format=json
```

## Notes

- Workflow state is separate from product status
- History is automatically recorded for all workflow changes
- Bulk actions are queued by default
- Automation rules are processed by scheduled command
- Expired products are auto-archived if configured
- All operations respect role-based permissions


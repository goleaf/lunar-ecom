# Product Core Model

This document describes the Product Core Model implementation, which represents the logical product (not the sellable unit yet) with comprehensive business logic for publishing, versioning, locking, and duplication.

## Overview

The Product Core Model extends Lunar's base Product model with:

- **Core Fields**: Product type, status, visibility, descriptions, meta fields, timestamps
- **Business Logic**: Publish scheduling, versioning, duplicate/clone, product locking
- **Audit Logging**: Complete activity tracking with Spatie Activity Log

## Core Fields

### Product Identification

- **Product ID**: UUID/ULID (inherited from Lunar)
- **Product Type**: References `product_types` table (inherited from Lunar)
- **Status**: `draft`, `active`, `archived`, `discontinued`, `published` (inherited from Lunar)
- **Visibility**: `public`, `private`, `scheduled`

### Descriptions

- **Short Description**: Text field for brief product summary
- **Full Description**: Long text field for rich content/blocks
- **Technical Description**: Optional long text for technical specifications

### SEO Meta Fields

- **Meta Title**: SEO title
- **Meta Description**: SEO description
- **Meta Keywords**: SEO keywords

### Brand / Manufacturer

- **Brand**: References `brands` table (inherited from Lunar)
- **Manufacturer Name**: String field (custom attribute)

### Slug

- **Slug**: Handled via Lunar's URL system (polymorphic, language-specific)
- Unique per channel & locale through Lunar's URL management

### Timestamps

- **Created At**: When product was created
- **Updated At**: When product was last updated
- **Published At**: When product was published
- **Scheduled Publish At**: Future publish date/time
- **Scheduled Unpublish At**: Future unpublish date/time

### Soft Delete

- **Deleted At**: Soft delete support (inherited from Lunar)

## Business Logic

### Publish Scheduling

Products can be scheduled for future publish or unpublish:

```php
use App\Models\Product;
use App\Services\ProductCoreService;

$product = Product::find(1);
$service = app(ProductCoreService::class);

// Schedule for future publish
$product->schedulePublish('2025-01-20 10:00:00');
// or
$service->publishProduct($product, '2025-01-20 10:00:00');

// Schedule for future unpublish
$product->scheduleUnpublish('2025-12-31 23:59:59');
// or
$service->unpublishProduct($product, '2025-12-31 23:59:59');

// Publish immediately
$product->publish();
// or
$service->publishProduct($product);

// Unpublish immediately
$product->unpublish();
// or
$service->unpublishProduct($product);
```

**Automated Processing**: The scheduled publish/unpublish actions are processed automatically via a scheduled command that runs every minute:

```php
// routes/console.php
Schedule::command('products:process-scheduled-publishes')->everyMinute();
```

Run manually:
```bash
php artisan products:process-scheduled-publishes
```

### Versioning

Track historical changes to products:

```php
use App\Models\Product;
use App\Models\ProductVersion;

$product = Product::find(1);

// Create a version snapshot
$version = $product->createVersion('Version 1.0', 'Initial release');
// or via service
$service = app(ProductCoreService::class);
$version = $service->createProductVersion($product, 'Version 1.0', 'Initial release');

// Get all versions
$versions = $product->versions;

// Restore to a specific version
$product->restoreVersion($version);
// or by version number
$product->restoreVersion(1);
// or via service
$service->restoreProductVersion($product, $version);
```

### Duplicate / Clone Product

Create a copy of a product with all relationships:

```php
use App\Models\Product;
use App\Services\ProductCoreService;

$product = Product::find(1);
$service = app(ProductCoreService::class);

// Duplicate product
$newProduct = $product->duplicate('New Product Name');
// or via service
$newProduct = $service->duplicateProduct($product, 'New Product Name');

// Duplicate with options
$newProduct = $service->duplicateProduct($product, 'New Name', ['force' => true]);
```

The duplicate includes:
- All product attributes
- Variants
- URLs (with modified slug)
- Collections
- Categories
- Tags

### Product Locking

Prevent edits while live orders exist:

```php
use App\Models\Product;
use App\Services\ProductCoreService;

$product = Product::find(1);
$service = app(ProductCoreService::class);

// Lock product
$product->lock('Product has live orders');
// or with user
$product->lock('Product has live orders', auth()->user());
// or via service
$service->lockProduct($product, 'Product has live orders');

// Automatically lock if has live orders
$service->lockIfHasLiveOrders($product);

// Unlock product
$product->unlock();
// or via service
$service->unlockProduct($product);

// Check if locked
if ($product->isLocked()) {
    // Handle locked product
}

// Check if can edit
if ($service->canEdit($product)) {
    // Allow editing
}
```

**Automatic Locking**: Products are automatically locked when saved if they have live orders (status: pending, processing, shipped, partially_shipped).

### Audit Log

Complete activity tracking using Spatie Activity Log:

```php
use App\Lunar\ActivityLog\ActivityLogHelper;
use App\Models\Product;
use Spatie\Activitylog\Models\Activity;

$product = Product::find(1);

// Get all activity logs for product
$logs = ActivityLogHelper::getForModel($product);

// Get logs by event type
$createdLogs = ActivityLogHelper::getForModelByEvent($product, 'created');
$updatedLogs = ActivityLogHelper::getForModelByEvent($product, 'updated');

// Get logs for specific property changes
$statusLogs = ActivityLogHelper::getForProperty($product, 'status');

// Get latest activity
$latestLog = ActivityLogHelper::getLatestForModel($product);

// Using Spatie Activity directly
$activities = Activity::forSubject($product)->get();
```

**Tracked Fields**: The following fields are automatically logged:
- `status`
- `visibility`
- `short_description`
- `full_description`
- `technical_description`
- `meta_title`
- `meta_description`
- `meta_keywords`
- `published_at`
- `scheduled_publish_at`
- `scheduled_unpublish_at`
- `is_locked`
- `lock_reason`
- `version`

## Query Scopes

### Visibility Scopes

```php
// Public products
Product::public()->get();

// Private products
Product::private()->get();

// Scheduled products
Product::scheduled()->get();

// By visibility
Product::byVisibility('public')->get();
```

### Status Scopes

```php
// Published products
Product::published()->get();

// Draft products
Product::draft()->get();

// Active products
Product::active()->get();

// Archived products
Product::archived()->get();

// Discontinued products
Product::discontinued()->get();
```

### Locking Scopes

```php
// Locked products
Product::locked()->get();

// Unlocked products
Product::unlocked()->get();
```

### Scheduling Scopes

```php
// Products scheduled for publish
Product::scheduledForPublish()->get();

// Products scheduled for unpublish
Product::scheduledForUnpublish()->get();
```

### Combined Scopes

```php
// Published and public products
Product::published()->public()->get();

// Active, unlocked products
Product::active()->unlocked()->get();
```

## Database Schema

### Products Table Extensions

```sql
-- Visibility
visibility ENUM('public', 'private', 'scheduled') DEFAULT 'public'

-- Descriptions
short_description TEXT NULL
full_description LONGTEXT NULL
technical_description LONGTEXT NULL

-- SEO Meta
meta_title VARCHAR(255) NULL
meta_description TEXT NULL
meta_keywords TEXT NULL

-- Publishing
published_at TIMESTAMP NULL
scheduled_publish_at TIMESTAMP NULL
scheduled_unpublish_at TIMESTAMP NULL

-- Locking
is_locked BOOLEAN DEFAULT FALSE
locked_by BIGINT UNSIGNED NULL (FK to users)
locked_at TIMESTAMP NULL
lock_reason TEXT NULL

-- Versioning
version INT UNSIGNED DEFAULT 1
parent_version_id BIGINT UNSIGNED NULL (FK to product_versions)
```

### Product Versions Table

```sql
id BIGINT UNSIGNED PRIMARY KEY
product_id BIGINT UNSIGNED (FK to products)
version_number INT UNSIGNED
version_name VARCHAR(255) NULL
version_notes TEXT NULL
product_data JSON NULL
created_by BIGINT UNSIGNED NULL (FK to users)
created_at TIMESTAMP
updated_at TIMESTAMP
```

## Usage Examples

### Creating a Product

```php
use App\Models\Product;

$product = Product::create([
    'product_type_id' => 1,
    'status' => 'draft',
    'visibility' => 'private',
    'short_description' => 'A great product',
    'full_description' => '<p>Detailed description...</p>',
    'meta_title' => 'Product Name - SEO Title',
    'meta_description' => 'SEO description',
    'meta_keywords' => 'keyword1, keyword2',
]);

// Create version snapshot
$product->createVersion('Initial Draft', 'First version');
```

### Publishing Workflow

```php
use App\Models\Product;
use App\Services\ProductCoreService;

$product = Product::find(1);
$service = app(ProductCoreService::class);

// 1. Create draft
$product->status = 'draft';
$product->visibility = 'private';
$product->save();

// 2. Create version before publishing
$product->createVersion('Pre-Publish', 'Version before going live');

// 3. Schedule publish
$product->schedulePublish('2025-01-20 10:00:00');

// 4. Or publish immediately
$product->publish();
```

### Version Management

```php
use App\Models\Product;

$product = Product::find(1);

// Create versions at key milestones
$v1 = $product->createVersion('Version 1.0', 'Initial release');
// ... make changes ...
$v2 = $product->createVersion('Version 2.0', 'Added new features');
// ... make changes ...
$v3 = $product->createVersion('Version 3.0', 'Bug fixes');

// View all versions
$versions = $product->versions()->orderBy('version_number', 'desc')->get();

// Restore to previous version
$product->restoreVersion($v2);
```

### Product Locking Workflow

```php
use App\Models\Product;
use App\Services\ProductCoreService;

$product = Product::find(1);
$service = app(ProductCoreService::class);

// Check for live orders and lock if needed
$service->lockIfHasLiveOrders($product);

// Try to edit (will fail if locked)
try {
    $product->short_description = 'New description';
    $product->save();
} catch (\Illuminate\Validation\ValidationException $e) {
    // Product is locked
    echo $e->getMessage();
}

// Unlock when safe
$product->unlock();
```

## Migration

Run the migrations:

```bash
php artisan migrate
```

This will create:
1. `product_versions` table
2. Add core model fields to `products` table
3. Add foreign key constraints

## Scheduled Tasks

Add to your cron or scheduler:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

Or use Laravel's scheduler in `routes/console.php` (already configured):

```php
Schedule::command('products:process-scheduled-publishes')->everyMinute();
```

## Activity Logging

Activity logging is automatically enabled via Spatie Activity Log. All changes to tracked fields are logged with:
- Who made the change (`causer`)
- What changed (`properties.old` and `properties.attributes`)
- When it changed (`created_at`)
- Event type (`created`, `updated`, `deleted`)

View logs:
```php
use App\Lunar\ActivityLog\ActivityLogHelper;
use App\Models\Product;

$product = Product::find(1);
$logs = ActivityLogHelper::getForModel($product);
```

## Notes

- **Product Type**: Uses Lunar's `product_types` table. Types like `simple`, `configurable`, `bundle`, `digital`, `service` should be created as ProductType records.
- **Slug Management**: Uses Lunar's URL system for SEO-friendly, language-specific slugs.
- **Soft Deletes**: Products support soft deletes (inherited from Lunar).
- **Locking**: Products are automatically locked when they have live orders. Manual locking is also supported.
- **Versioning**: Each product change increments the version number. Version snapshots can be created manually.
- **Publish Scheduling**: Scheduled publishes/unpublishes are processed every minute via scheduled command.


# Product Variants - Core Model Architecture

## Overview

Each variant represents **one sellable unit** at the SKU level. This document describes the complete variant core model architecture.

## Core Fields

### Identification Fields

#### Variant ID
- **Type**: UUID / ULID (or auto-increment)
- **Field**: `id` (primary key)
- **Field**: `uuid` (unique identifier)
- **Purpose**: Unique identifier for the variant
- **Format**: UUID v4 or ULID
- **Auto-generated**: Yes (if enabled)

```php
$variant->uuid; // "550e8400-e29b-41d4-a716-446655440000"
```

#### Parent Product ID
- **Type**: Big Integer (Foreign Key)
- **Field**: `product_id`
- **Purpose**: Links variant to parent product
- **Required**: Yes
- **Indexed**: Yes

```php
$variant->product_id; // 123
$variant->product; // Product model
```

### SKU & Barcode Fields

#### SKU (Stock Keeping Unit)
- **Type**: String (unique)
- **Field**: `sku`
- **Max Length**: 255
- **Required**: Yes (auto-generated if not provided)
- **Unique**: Yes
- **Indexed**: Yes
- **Format**: Configurable via `sku_format`

**SKU Format Configuration**:
```php
// Default format: {PRODUCT-SKU}-{OPTIONS}
// Available placeholders:
// - {PRODUCT-SKU}: Product SKU
// - {PRODUCT-ID}: Product ID
// - {OPTIONS}: Option values (first 3 chars, uppercase)
// - {VARIANT-ID}: Variant ID
// - {UUID}: First 8 chars of UUID
// - {TIMESTAMP}: Unix timestamp

// Examples:
// "PROD-123-RED-XL"
// "PROD-123-001"
// "SKU-550e8400"
```

**SKU Format Field**:
- **Field**: `sku_format` (JSON)
- **Purpose**: Store custom SKU format for this variant
- **Example**: `{"format": "{PRODUCT-SKU}-{OPTIONS}", "prefix": "VAR", "suffix": null}`

#### GTIN (Global Trade Item Number)
- **Type**: String (14 digits)
- **Field**: `gtin`
- **Max Length**: 14
- **Required**: No
- **Indexed**: Yes
- **Purpose**: International product identifier

#### EAN (European Article Number)
- **Type**: String (13 digits)
- **Field**: `ean`
- **Max Length**: 13
- **Required**: No
- **Indexed**: Yes
- **Validation**: EAN-13 format
- **Purpose**: European barcode standard

#### UPC (Universal Product Code)
- **Type**: String (12 digits)
- **Field**: `upc`
- **Max Length**: 12
- **Required**: No
- **Indexed**: Yes
- **Validation**: UPC-A format
- **Purpose**: North American barcode standard

#### ISBN (International Standard Book Number)
- **Type**: String (13 digits)
- **Field**: `isbn`
- **Max Length**: 13
- **Required**: No
- **Indexed**: Yes
- **Validation**: ISBN-13 format
- **Purpose**: Book identifier

#### Barcode (Legacy/EAN-13)
- **Type**: String (13 digits)
- **Field**: `barcode`
- **Max Length**: 13
- **Required**: No
- **Indexed**: Yes
- **Validation**: EAN-13 format
- **Purpose**: General barcode field

#### Internal Reference Code
- **Type**: String
- **Field**: `internal_reference`
- **Max Length**: 100
- **Required**: No
- **Indexed**: Yes
- **Purpose**: Internal reference code for ERP/PIM systems

```php
$variant->internal_reference; // "INT-REF-2025-001"
```

### Title & Display Fields

#### Variant Title
- **Type**: String
- **Field**: `title`
- **Max Length**: 255
- **Required**: No
- **Purpose**: Manual variant title override
- **Priority**: Highest (used in `getDisplayName()`)

#### Variant Name
- **Type**: String
- **Field**: `variant_name`
- **Max Length**: 255
- **Required**: No
- **Purpose**: Explicit variant name (legacy field)
- **Priority**: Medium

**Title Generation Priority**:
1. `title` (manual override)
2. `variant_name` (explicit name)
3. Generated from option values
4. SKU (fallback)

```php
$variant->getDisplayName(); // Returns title > variant_name > generated > SKU
$variant->getTitle(); // Alias for getDisplayName()
```

### Status & Visibility Fields

#### Status
- **Type**: Enum
- **Field**: `status`
- **Values**: `active`, `inactive`, `archived`
- **Default**: `active`
- **Indexed**: Yes
- **Purpose**: Variant lifecycle status

**Status Values**:
- **active**: Variant is active and can be sold
- **inactive**: Variant is temporarily disabled
- **archived**: Variant is archived (soft delete alternative)

```php
$variant->status; // "active"
$variant->getStatusLabel(); // "Active"
$variant->archive(); // Set to "archived"
$variant->activate(); // Set to "active"
$variant->deactivate(); // Set to "inactive"
```

#### Visibility
- **Type**: Enum
- **Field**: `visibility`
- **Values**: `public`, `hidden`, `channel_specific`
- **Default**: `public`
- **Indexed**: Yes
- **Purpose**: Control variant visibility

**Visibility Values**:
- **public**: Visible to all channels
- **hidden**: Hidden from all channels
- **channel_specific**: Visible only to specified channels

#### Channel Visibility
- **Type**: JSON Array
- **Field**: `channel_visibility`
- **Required**: Yes (if visibility = `channel_specific`)
- **Purpose**: Array of channel IDs where variant is visible

```php
$variant->visibility; // "channel_specific"
$variant->channel_visibility; // [1, 2, 3]
$variant->isVisibleInChannel(1); // true
$variant->isVisibleInChannel(4); // false
```

#### Enabled
- **Type**: Boolean
- **Field**: `enabled`
- **Default**: `true`
- **Purpose**: Quick enable/disable toggle
- **Note**: Works in conjunction with `status`

### Ordering & Priority

#### Position / Sort Order
- **Type**: Integer
- **Field**: `position`
- **Default**: Auto-incremented
- **Purpose**: Control variant display order
- **Indexed**: Yes

```php
$variant->position; // 10
$variant->scopeOrdered(); // Order by position ASC
```

### Timestamps

#### Created At
- **Type**: Timestamp
- **Field**: `created_at`
- **Auto-managed**: Yes
- **Purpose**: Record creation time

#### Updated At
- **Type**: Timestamp
- **Field**: `updated_at`
- **Auto-managed**: Yes
- **Purpose**: Record last update time

#### Deleted At (Soft Delete)
- **Type**: Timestamp
- **Field**: `deleted_at`
- **Auto-managed**: Yes
- **Purpose**: Soft delete support
- **Trait**: `SoftDeletes`

```php
$variant->delete(); // Soft delete (sets deleted_at)
$variant->restore(); // Restore soft deleted variant
$variant->forceDelete(); // Permanently delete
```

## Model Methods

### Identification Methods

```php
// Get variant by SKU
$variant = ProductVariant::where('sku', 'PROD-123-RED-XL')->first();
$variant = VariantCoreService::findBySKU('PROD-123-RED-XL');

// Get variant by UUID
$variant = ProductVariant::where('uuid', $uuid)->first();
$variant = VariantCoreService::findByUUID($uuid);

// Get variant by barcode
$variant = VariantCoreService::findByBarcode('1234567890123');

// Get variant by internal reference
$variant = VariantCoreService::findByInternalReference('INT-REF-001');
```

### Status Methods

```php
// Check status
$variant->status; // "active"
$variant->getStatusLabel(); // "Active"

// Change status
$variant->archive(); // Set to "archived"
$variant->activate(); // Set to "active"
$variant->deactivate(); // Set to "inactive"

// Bulk status update
VariantCoreService::bulkUpdateStatus([1, 2, 3], 'archived');
VariantCoreService::archiveVariants([1, 2, 3]);
VariantCoreService::activateVariants([1, 2, 3]);
```

### Visibility Methods

```php
// Check visibility
$variant->visibility; // "public"
$variant->getVisibilityLabel(); // "Public"
$variant->isVisibleInChannel(1); // true

// Change visibility
$variant->update(['visibility' => 'channel_specific', 'channel_visibility' => [1, 2]]);

// Bulk visibility update
VariantCoreService::bulkUpdateVisibility([1, 2, 3], 'channel_specific', [1, 2]);
```

### Display Methods

```php
// Get display name (priority: title > variant_name > generated > SKU)
$variant->getDisplayName(); // "Red / XL"
$variant->getTitle(); // Alias for getDisplayName()

// Generate title from options
$variant->generateTitle(); // "Red / XL"

// Get option values array
$variant->getOptionValuesArray(); // ["color" => "Red", "size" => "XL"]
```

### SKU Generation Methods

```php
// Generate SKU
$variant->generateSKU(); // "PROD-123-RED-XL"

// Set custom SKU format
$variant->update(['sku_format' => ['format' => '{PRODUCT-SKU}-{UUID}']]);
```

### Validation Methods

```php
// Validate EAN-13
$variant->validateEan13('1234567890123'); // true/false

// Validate UPC
$variant->validateUPC('123456789012'); // true/false

// Validate ISBN
$variant->validateISBN('9781234567890'); // true/false
```

## Scopes

### Status Scopes

```php
// Active variants
ProductVariant::active()->get();

// Inactive variants
ProductVariant::status('inactive')->get();

// Archived variants
ProductVariant::status('archived')->get();
```

### Visibility Scopes

```php
// Public variants
ProductVariant::public()->get();

// Hidden variants
ProductVariant::visibility('hidden')->get();

// Channel-specific variants visible in channel 1
ProductVariant::visibleInChannel(1)->get();
```

### Ordering Scopes

```php
// Ordered by position
ProductVariant::ordered()->get();
ProductVariant::ordered('desc')->get();
```

### Soft Delete Scopes

```php
// Include trashed
ProductVariant::withTrashed()->get();

// Only trashed
ProductVariant::onlyTrashed()->get();

// Exclude trashed (default)
ProductVariant::get();
```

## Service Usage

### Create Variant

```php
use App\Services\VariantCoreService;

$service = app(VariantCoreService::class);

$variant = $service->createVariant($product, [
    'sku' => 'PROD-123-RED-XL', // Optional (auto-generated)
    'title' => 'Red / XL', // Optional (auto-generated)
    'gtin' => '12345678901234',
    'ean' => '1234567890123',
    'upc' => '123456789012',
    'internal_reference' => 'INT-REF-001',
    'status' => 'active',
    'visibility' => 'public',
    'option_values' => [1, 2], // Option value IDs
    'position' => 10,
]);
```

### Update Variant

```php
$service->updateVariant($variant, [
    'status' => 'inactive',
    'visibility' => 'channel_specific',
    'channel_visibility' => [1, 2],
    'title' => 'Updated Title',
]);
```

### Bulk Operations

```php
// Bulk status update
$service->bulkUpdateStatus([1, 2, 3], 'archived');

// Bulk visibility update
$service->bulkUpdateVisibility([1, 2, 3], 'channel_specific', [1, 2]);

// Archive variants
$service->archiveVariants([1, 2, 3]);

// Activate variants
$service->activateVariants([1, 2, 3]);
```

## Configuration

### SKU Format Configuration

**Config File**: `config/lunar/variants.php`

```php
return [
    'default_sku_format' => '{PRODUCT-SKU}-{OPTIONS}',
    'sku_generation' => [
        'max_length' => 100,
        'prefix' => null,
        'suffix' => null,
        'separator' => '-',
        'uppercase' => true,
        'alphanumeric_only' => true,
    ],
    'default_status' => 'active',
    'default_visibility' => 'public',
    'position_increment' => 10,
    'validate_barcodes' => true,
    'auto_generate_uuid' => true,
    'auto_generate_sku' => true,
    'auto_generate_title' => true,
];
```

### Environment Variables

```env
VARIANT_SKU_FORMAT={PRODUCT-SKU}-{OPTIONS}
VARIANT_DEFAULT_STATUS=active
VARIANT_DEFAULT_VISIBILITY=public
VALIDATE_BARCODES=true
AUTO_GENERATE_VARIANT_UUID=true
AUTO_GENERATE_VARIANT_SKU=true
AUTO_GENERATE_VARIANT_TITLE=true
```

## Validation Rules

```php
use App\Models\ProductVariant;

$rules = ProductVariant::getValidationRules($variantId);

// Includes:
// - uuid: UUID format, unique
// - sku: String, unique, max 255
// - gtin: String, max 14
// - ean: EAN-13 format validation
// - upc: UPC-A format validation
// - isbn: ISBN-13 format validation
// - barcode: EAN-13 format validation
// - internal_reference: String, max 100
// - title: String, max 255
// - status: Enum (active, inactive, archived)
// - visibility: Enum (public, hidden, channel_specific)
// - channel_visibility: Array of channel IDs
// - sku_format: Array
```

## Database Schema

```sql
CREATE TABLE lunar_product_variants (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    uuid UUID UNIQUE,
    product_id BIGINT NOT NULL,
    sku VARCHAR(255) UNIQUE NOT NULL,
    sku_format JSON,
    gtin VARCHAR(14) INDEX,
    ean VARCHAR(13) INDEX,
    upc VARCHAR(12) INDEX,
    isbn VARCHAR(13) INDEX,
    barcode VARCHAR(13) INDEX,
    internal_reference VARCHAR(100) INDEX,
    title VARCHAR(255),
    variant_name VARCHAR(255),
    status ENUM('active', 'inactive', 'archived') DEFAULT 'active' INDEX,
    visibility ENUM('public', 'hidden', 'channel_specific') DEFAULT 'public' INDEX,
    channel_visibility JSON,
    position INT DEFAULT 0 INDEX,
    enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    
    FOREIGN KEY (product_id) REFERENCES lunar_products(id),
    INDEX idx_product_status (product_id, status),
    INDEX idx_product_visibility (product_id, visibility),
    INDEX idx_sku (sku),
    INDEX idx_uuid (uuid),
    INDEX idx_barcodes (gtin, ean, upc, isbn, barcode)
);
```

## Best Practices

1. **SKU Format**: Use consistent SKU format across all variants
2. **UUID**: Always generate UUID for external system integration
3. **Barcodes**: Validate barcodes before saving
4. **Status**: Use status field for lifecycle management, not soft delete
5. **Visibility**: Use channel_visibility for multi-channel stores
6. **Title**: Set explicit title for better display control
7. **Position**: Use position for variant ordering
8. **Soft Delete**: Use soft delete for audit trail, status for lifecycle

## Integration Points

### ERP/PIM Integration

```php
// Find variant by internal reference
$variant = VariantCoreService::findByInternalReference($erpReference);

// Find variant by GTIN/EAN/UPC
$variant = VariantCoreService::findByBarcode($gtin);

// Sync variant with ERP
$variant->update([
    'internal_reference' => $erpData['reference'],
    'gtin' => $erpData['gtin'],
    'ean' => $erpData['ean'],
]);
```

### External Systems

```php
// Use UUID for external references
$variant->uuid; // "550e8400-e29b-41d4-a716-446655440000"

// Use SKU for inventory systems
$variant->sku; // "PROD-123-RED-XL"

// Use barcodes for POS systems
$variant->ean; // "1234567890123"
$variant->upc; // "123456789012"
```

## Notes

- Each variant = one sellable unit
- SKU is unique across all variants
- UUID provides external system compatibility
- Status controls lifecycle, visibility controls display
- Soft delete provides audit trail
- All barcode fields are validated
- Title generation is automatic but can be overridden
- Position controls display order


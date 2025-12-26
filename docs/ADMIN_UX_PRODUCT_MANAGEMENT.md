# Admin UX - Product Management UI

This document describes the enhanced admin experience for product management.

## Overview

The system provides:

1. **Product wizard** - Step-by-step product creation
2. **Inline variant editor** - Edit variants directly in table
3. **Bulk attribute editing** - Edit attributes for multiple products
4. **Drag & drop media** - Intuitive media management
5. **Real-time validation** - Instant feedback on form fields
6. **Preview storefront view** - Preview products before publishing
7. **Clone product button** - Duplicate products quickly
8. **Change history timeline** - Track all product changes

## Components

### Product Wizard

Multi-step wizard for creating products.

**Location**: `app/Admin/Livewire/ProductWizard.php`

**Steps**:
1. Basic Information - Product type, name, SKU, status
2. Details & Description - Short, full, technical descriptions
3. Pricing & Inventory - Base price, stock levels
4. Media & Images - Drag & drop image upload
5. Categories & Collections - Organization
6. SEO & Settings - Meta fields, featured status

**Usage**:

```php
// Access via route
Route::get('/admin/products/create-wizard', [ProductWizard::class, 'render']);
```

### Inline Variant Editor

Edit variants directly in a table without page reload.

**Location**: `app/Admin/Livewire/InlineVariantEditor.php`

**Features**:
- Click to edit inline
- Save/Cancel buttons
- Delete variants
- Real-time updates

**Usage**:

```blade
<livewire:admin.livewire.inline-variant-editor :product="$product" />
```

### Bulk Attribute Editor

Edit attributes for multiple products at once.

**Location**: `app/Admin/Livewire/BulkAttributeEditor.php`

**Features**:
- Select multiple products
- Choose attribute to edit
- Set value for all selected
- Batch processing

**Usage**:

```blade
<livewire:admin.livewire.bulk-attribute-editor :product-ids="[1, 2, 3]" />
```

### Drag & Drop Media Manager

Intuitive media management with drag & drop.

**Location**: `app/Admin/Livewire/DragDropMediaManager.php`

**Features**:
- Drag & drop file upload
- Image preview grid
- Set primary image
- Reorder images
- Delete images

**Usage**:

```blade
<livewire:admin.livewire.drag-drop-media-manager :product="$product" />
```

### Preview Storefront Action

Preview products in storefront view.

**Location**: `app/Admin/Actions/PreviewStorefrontAction.php`

**Usage**:

```php
// In Filament resource
protected function getHeaderActions(): array
{
    return [
        \App\Admin\Actions\PreviewStorefrontAction::make(),
    ];
}
```

### Clone Product Action

Duplicate products with one click.

**Location**: `app/Admin/Actions/CloneProductAction.php`

**Features**:
- Duplicates product
- Duplicates variants
- Duplicates prices
- Copies categories/collections
- Sets status to draft
- Appends "-copy" to SKU

**Usage**:

```php
// In Filament resource
protected function getHeaderActions(): array
{
    return [
        \App\Admin\Actions\CloneProductAction::make(),
    ];
}
```

### Change History Timeline

Display product change history in timeline format.

**Location**: `app/Admin/Livewire/ChangeHistoryTimeline.php`

**Features**:
- Workflow history
- Activity log
- User attribution
- Timestamps
- Notes/comments

**Usage**:

```blade
<livewire:admin.livewire.change-history-timeline :product="$product" />
```

## Real-Time Validation

### SKU Validation

```php
TextInput::make('sku')
    ->unique(ignoreRecord: true)
    ->live(onBlur: true)
    ->afterStateUpdated(function ($state, callable $set) {
        // Real-time validation
        if ($state && Product::where('sku', $state)->exists()) {
            // Show error or suggest alternative
        }
    })
```

### Price Validation

```php
TextInput::make('price')
    ->numeric()
    ->live()
    ->rules([
        'min:0',
        'max:999999.99',
    ])
    ->afterStateUpdated(function ($state) {
        // Format price in real-time
    })
```

## Enhanced Product Edit Page

The extended edit page includes:

1. **Tabs**:
   - Basic - Product information
   - Variants - Inline variant editor
   - Media - Drag & drop media manager
   - History - Change history timeline

2. **Header Actions**:
   - Preview button
   - Clone button
   - Save & Continue Editing
   - Save

3. **Real-time Validation**:
   - SKU uniqueness
   - Price formatting
   - Required fields
   - Field dependencies

## Usage Examples

### Create Product via Wizard

```php
// Navigate to: /admin/products/create-wizard
// Follow the wizard steps
// Product is created after final step
```

### Edit Variants Inline

```php
// In product edit page, go to Variants tab
// Click "Edit" on any variant row
// Modify fields inline
// Click "Save" to update
```

### Bulk Edit Attributes

```php
// Select multiple products in list view
// Click "Bulk Edit Attributes"
// Choose attribute and value
// Apply to all selected products
```

### Upload Media

```php
// Go to Media tab in product edit
// Drag & drop images onto drop zone
// Or click to select files
// Images are uploaded automatically
```

### Preview Product

```php
// Click "Preview" button in header
// Opens storefront view in new tab
// Shows product as customers see it
```

### Clone Product

```php
// Click "Clone Product" button
// Confirm action
// Redirected to edit page of cloned product
```

## JavaScript Enhancements

### Real-time Validation

```javascript
// SKU validation
input.addEventListener('blur', function() {
    validateSKU(this.value);
});

// Price validation
input.addEventListener('input', function() {
    const value = parseFloat(this.value);
    if (value < 0) {
        this.setCustomValidity('Price cannot be negative');
    }
});
```

### Drag & Drop

```javascript
zone.addEventListener('drop', function(e) {
    e.preventDefault();
    const files = Array.from(e.dataTransfer.files);
    // Upload files via Livewire
    Livewire.emit('filesDropped', files);
});
```

## Filament Integration

### Register Extensions

In `AppServiceProvider`:

```php
LunarPanel::panel(function ($panel) {
    return $panel
        ->extensions([
            \Lunar\Panel\Filament\Resources\ProductResource::class => 
                \App\Admin\Filament\Resources\ProductResourceExtension::class,
            
            \Lunar\Panel\Filament\Resources\ProductResource\Pages\EditProduct::class => 
                \App\Admin\Filament\Resources\Pages\ProductEditExtension::class,
            
            \Lunar\Panel\Filament\Resources\ProductResource\Pages\ListProducts::class => 
                \App\Admin\Filament\Resources\Pages\ProductListExtension::class,
        ]);
});
```

## Best Practices

1. **Wizard Flow**: Complete all steps before submitting
2. **Inline Editing**: Save frequently when editing variants
3. **Bulk Actions**: Test on small batches first
4. **Media Upload**: Optimize images before upload
5. **Preview**: Always preview before publishing
6. **Validation**: Fix validation errors immediately
7. **History**: Review change history before major edits

## Features

### Product Wizard
- ✅ Multi-step form
- ✅ Progress indicator
- ✅ Step validation
- ✅ Auto-save draft
- ✅ Skip optional steps

### Inline Variant Editor
- ✅ Click to edit
- ✅ Save/Cancel
- ✅ Delete variants
- ✅ Real-time updates
- ✅ Validation feedback

### Bulk Attribute Editing
- ✅ Multi-select products
- ✅ Attribute selection
- ✅ Value input
- ✅ Batch processing
- ✅ Progress tracking

### Drag & Drop Media
- ✅ Drag & drop upload
- ✅ Image preview
- ✅ Set primary
- ✅ Reorder images
- ✅ Delete images
- ✅ Image editor

### Real-time Validation
- ✅ SKU uniqueness
- ✅ Price formatting
- ✅ Required fields
- ✅ Field dependencies
- ✅ Error messages

### Preview Storefront
- ✅ One-click preview
- ✅ New tab
- ✅ Preview mode
- ✅ Exact customer view

### Clone Product
- ✅ One-click clone
- ✅ Duplicates all data
- ✅ Sets to draft
- ✅ Unique SKU
- ✅ Redirects to edit

### Change History
- ✅ Timeline view
- ✅ User attribution
- ✅ Timestamps
- ✅ Notes/comments
- ✅ Status changes

## Notes

- All components use Livewire for reactivity
- Real-time validation uses Filament's `live()` method
- Drag & drop uses Alpine.js
- Preview uses session flag for preview mode
- Clone preserves all relationships
- History combines workflow and activity log


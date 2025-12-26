# Admin UX (Variant Management)

Power tools for variant managers.

## Overview

Comprehensive admin UX for variant management:

1. **Variant matrix editor** - Visual grid for managing variants
2. **Bulk variant generator** - Generate multiple variants at once
3. **Inline price & stock editing** - Edit directly in tables
4. **Bulk updates** - Update multiple variants at once
5. **Drag & drop attribute ordering** - Reorder variants
6. **Variant preview** - Preview variant on frontend
7. **Variant cloning** - Duplicate variants
8. **Change history timeline** - Track changes

## Components

### VariantMatrixEditor

Visual grid editor for managing variants:
- Rows = one attribute (e.g., Size)
- Columns = another attribute (e.g., Color)
- Cells = variants with price/stock editing

**Usage:**

```php
<livewire:variant-matrix-editor :product="$product" />
```

**Features:**
- Visual matrix/grid view
- Inline editing of price, stock, enabled
- Create new variants from matrix
- Delete variants from matrix
- Auto-save on change

### BulkVariantGenerator

Generate multiple variants at once from option combinations.

**Usage:**

```php
<livewire:bulk-variant-generator :product="$product" />
```

**Features:**
- Select multiple option values
- Preview combinations before generation
- Set default stock, price, enabled status
- Skip existing combinations
- Generate all combinations at once

### InlineVariantPriceStockEditor

Edit price and stock directly in table view.

**Usage:**

```php
<livewire:inline-variant-price-stock-editor :product="$product" />
```

**Features:**
- Click to edit inline
- Auto-save on blur
- Real-time updates
- Edit stock, price, enabled status

### BulkVariantUpdater

Update multiple variants at once.

**Usage:**

```php
<livewire:bulk-variant-updater :product="$product" />
```

**Features:**
- Select multiple variants
- Bulk update stock, price, status, enabled
- Leave fields empty to keep current values
- Update all selected variants at once

### DragDropVariantOrder

Reorder variants via drag and drop.

**Usage:**

```php
<livewire:drag-drop-variant-order :product="$product" />
```

**Features:**
- Drag and drop to reorder
- Visual feedback during drag
- Auto-save order
- Update position field

### PreviewVariantAction

Preview variant on frontend.

**Usage:**

```php
use App\Admin\Actions\PreviewVariantAction;

PreviewVariantAction::make()
    ->record($variant)
```

**Features:**
- Open variant in new tab
- Preview mode
- Frontend view

### CloneVariantAction

Clone a variant.

**Usage:**

```php
use App\Admin\Actions\CloneVariantAction;

CloneVariantAction::make()
    ->record($variant)
```

**Features:**
- Clone variant with new SKU
- Copy all attributes
- Copy pricing
- Copy stock settings
- Copy media (optional)

### VariantChangeHistory

Track changes to variants.

**Usage:**

```php
<livewire:variant-change-history :variant="$variant" />
```

**Features:**
- Timeline view
- Track all changes
- User attribution
- Change details
- Timestamp tracking

## Integration with Filament

### Add to Product Resource

```php
use App\Admin\Livewire\VariantMatrixEditor;
use App\Admin\Livewire\BulkVariantGenerator;
use App\Admin\Livewire\InlineVariantPriceStockEditor;
use App\Admin\Actions\PreviewVariantAction;
use App\Admin\Actions\CloneVariantAction;

public static function getRelations(): array
{
    return [
        // Add variant management tabs
        VariantMatrixEditor::class,
        BulkVariantGenerator::class,
        InlineVariantPriceStockEditor::class,
    ];
}

protected function getActions(): array
{
    return [
        PreviewVariantAction::make(),
        CloneVariantAction::make(),
    ];
}
```

### Variant Resource Table

```php
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use App\Admin\Actions\PreviewVariantAction;
use App\Admin\Actions\CloneVariantAction;

public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('sku')
                ->searchable()
                ->sortable(),
            TextColumn::make('stock')
                ->sortable(),
            TextColumn::make('price')
                ->money(),
            TextColumn::make('enabled')
                ->boolean(),
        ])
        ->actions([
            PreviewVariantAction::make(),
            CloneVariantAction::make(),
            Action::make('edit')
                ->url(fn ($record) => route('filament.admin.resources.variants.edit', $record)),
        ])
        ->bulkActions([
            BulkAction::make('updateStock')
                ->form([
                    TextInput::make('stock')
                        ->numeric()
                        ->required(),
                ])
                ->action(function ($records, array $data) {
                    $records->each->update(['stock' => $data['stock']]);
                }),
        ]);
}
```

## Usage Examples

### Variant Matrix Editor

```blade
<!-- In product edit page -->
<div class="space-y-6">
    <h2>Variant Matrix</h2>
    <livewire:variant-matrix-editor :product="$product" />
</div>
```

### Bulk Generator

```blade
<!-- In product edit page -->
<div class="space-y-6">
    <h2>Generate Variants</h2>
    <livewire:bulk-variant-generator :product="$product" />
</div>
```

### Inline Editor

```blade
<!-- In product edit page -->
<div class="space-y-6">
    <h2>Quick Edit Variants</h2>
    <livewire:inline-variant-price-stock-editor :product="$product" />
</div>
```

### Bulk Updates

```blade
<!-- In product edit page -->
<div class="space-y-6">
    <h2>Bulk Update Variants</h2>
    <livewire:bulk-variant-updater :product="$product" />
</div>
```

### Drag & Drop Ordering

```blade
<!-- In product edit page -->
<div class="space-y-6">
    <h2>Reorder Variants</h2>
    <livewire:drag-drop-variant-order :product="$product" />
</div>
```

### Change History

```blade
<!-- In variant edit page -->
<div class="space-y-6">
    <h2>Change History</h2>
    <livewire:variant-change-history :variant="$variant" />
</div>
```

## Best Practices

1. **Use matrix editor** for products with 2 main attributes
2. **Use bulk generator** for products with many combinations
3. **Use inline editor** for quick price/stock updates
4. **Use bulk updater** for mass changes
5. **Use drag & drop** for manual ordering
6. **Track changes** with change history
7. **Preview before publish** with preview action
8. **Clone variants** for similar products
9. **Validate data** before saving
10. **Provide feedback** with notifications

## Notes

- **Matrix editor**: Best for 2-attribute products (e.g., Size × Color)
- **Bulk generator**: Best for products with many option combinations
- **Inline editor**: Fast editing without page reload
- **Bulk updater**: Efficient for mass updates
- **Drag & drop**: Intuitive reordering
- **Preview**: See variant as customers will see it
- **Clone**: Quick way to create similar variants
- **Change history**: Audit trail for compliance




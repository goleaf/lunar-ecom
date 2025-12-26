# Admin Panel Enhancements

This document outlines the admin panel enhancements needed for the Product Organization System.

## Overview

Lunar uses its own admin panel system (not standard Filament resources). To add the new features to the admin panel, you'll need to use Lunar's extension system.

## Required Enhancements

### 1. Category Resource Extensions

**Location**: Create extensions in `app/Admin/Filament/Extensions/`

**Features to add**:
- Channel visibility management tab
- Language/locale visibility management tab
- Per-channel/locale visibility toggles
- Bulk visibility operations

**Example Extension Structure**:
```php
namespace App\Admin\Filament\Extensions;

use Lunar\Admin\Filament\Resources\CategoryResource;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;

class CategoryVisibilityExtension
{
    public static function extend(CategoryResource $resource)
    {
        // Add channel/locale visibility tabs to edit form
        $resource->form([
            // ... existing fields
            Tabs::make('Visibility')
                ->tabs([
                    Tabs\Tab::make('Channels')
                        ->schema([
                            // Channel visibility fields
                        ]),
                    Tabs\Tab::make('Languages')
                        ->schema([
                            // Language visibility fields
                        ]),
                ]),
        ]);
    }
}
```

### 2. Collection Resource Extensions

**Location**: Create extensions in `app/Admin/Filament/Extensions/`

**Features to add**:
- Collection type selector (dropdown with CollectionType enum)
- Scheduling fields (scheduled_publish_at, scheduled_unpublish_at)
- Auto-publish products toggle
- Collection type filtering in list view

**Example Extension Structure**:
```php
namespace App\Admin\Filament\Extensions;

use Lunar\Admin\Filament\Resources\CollectionResource;
use App\Enums\CollectionType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;

class CollectionTypeAndSchedulingExtension
{
    public static function extend(CollectionResource $resource)
    {
        $resource->form([
            // ... existing fields
            Select::make('collection_type')
                ->label('Collection Type')
                ->options(CollectionType::class)
                ->default(CollectionType::STANDARD->value),
            
            Section::make('Scheduling')
                ->schema([
                    DateTimePicker::make('scheduled_publish_at')
                        ->label('Schedule Publish'),
                    DateTimePicker::make('scheduled_unpublish_at')
                        ->label('Schedule Unpublish'),
                    Toggle::make('auto_publish_products')
                        ->label('Auto-publish products')
                        ->default(true),
                ]),
        ]);
        
        // Add type filter to table
        $resource->table([
            // ... existing columns
            Tables\Column::make('collection_type')
                ->label('Type')
                ->badge(),
        ]);
    }
}
```

### 3. Product Resource Extensions

**Location**: Extend existing `app/Admin/Filament/Resources/ProductResourceExtension.php`

**Features to add**:
- Enhanced relations management section
- Grouped relation types (accessories, replacements, related, etc.)
- Bulk relation operations
- Relation priority/ordering

**Example Extension Structure**:
```php
namespace App\Admin\Filament\Extensions;

use Lunar\Admin\Filament\Resources\ProductResource;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;

class ProductRelationsExtension
{
    public static function extend(ProductResource $resource)
    {
        $resource->form([
            // ... existing fields
            Section::make('Product Relations')
                ->schema([
                    Repeater::make('accessories')
                        ->label('Accessories')
                        ->relationship('associations')
                        ->schema([
                            // Relation fields
                        ]),
                    // Similar for other relation types
                ]),
        ]);
    }
}
```

## Implementation Notes

1. **Lunar Extension System**: Use Lunar's extension system to add these features without modifying core files
2. **Register Extensions**: Register extensions in `AppServiceProvider` using Lunar's extension methods
3. **Backward Compatibility**: Ensure all new fields have sensible defaults
4. **Validation**: Add validation rules for scheduling dates and collection types

## References

- [Lunar Admin Extensions Documentation](https://docs.lunarphp.com/1.x/admin/extending)
- [Filament Forms Documentation](https://filamentphp.com/docs/forms)
- [Filament Tables Documentation](https://filamentphp.com/docs/tables)



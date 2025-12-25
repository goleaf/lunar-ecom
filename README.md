# Lunar E-commerce Store

A Laravel 12 application powered by [Lunar PHP](https://docs.lunarphp.com/) e-commerce framework.

## Requirements

- PHP >= 8.2
- Laravel 12
- MySQL 8.0+ or PostgreSQL 9.4+
- Required PHP extensions: exif, intl, bcmath, GD

## Installation

1. Install dependencies:
```bash
composer install
npm install
```

2. Configure your environment:
```bash
cp .env.example .env
php artisan key:generate
```

3. Configure your database connection in `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lunar_ecom
DB_USERNAME=root
DB_PASSWORD=
```

4. Run migrations:
```bash
php artisan migrate
```

5. Import address data (countries and states):
```bash
php artisan lunar:import:address-data
```

6. Seed demo data:
```bash
php artisan db:seed
```

## Admin Panel

Lunar's admin panel is powered by **Filament v3** and provides a comprehensive interface for managing your e-commerce store. It allows you to easily extend the admin panel to suit your project needs.

**Overview**:

The admin panel enables you to administer:
- Products and product variants
- Collections
- Orders and order management
- Customers
- Discounts
- Settings
- And much more

**Registration**:

The admin panel is registered in `AppServiceProvider`:

```php
use Lunar\Admin\Support\Facades\LunarPanel;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        LunarPanel::register();
    }
}
```

**Access**:

Once registered, the admin panel is typically accessible at `/admin` (this may vary based on your route configuration).

**Extending the Admin Panel**:

Lunar's admin panel is highly extensible. You can:
- Create custom pages
- Add custom resources
- Extend existing resources and pages
- Add relation managers
- Add custom navigation items
- Customize access control

#### Extending Pages

Lunar provides several extension types for different page types (Create, Edit, List, View, and Relation pages). Page extensions allow you to customize page behavior, add widgets, modify actions, and hook into lifecycle events.

**Extension Types**:

1. **CreatePageExtension** - For create pages
2. **EditPageExtension** - For edit pages
3. **ListPageExtension** - For list pages
4. **ViewPageExtension** - For view pages
5. **RelationPageExtension** - For relation manager pages

**Creating Page Extensions**:

```php
use Lunar\Admin\Support\Extending\CreatePageExtension;

class MyCreateExtension extends CreatePageExtension
{
    public function heading($title): string
    {
        return $title . ' - Custom';
    }

    public function headerActions(array $actions): array
    {
        return [
            ...$actions,
            Actions\Action::make('Custom Action'),
        ];
    }

    public function beforeCreate(array $data): array
    {
        // Modify data before validation
        return $data;
    }

    public function afterCreation(Model $record, array $data): Model
    {
        // Handle after creation
        return $record;
    }
}
```

**Registering Page Extensions**:

Register extensions in `AppServiceProvider::register()`:

```php
LunarPanel::panel(function ($panel) {
    return $panel;
})
    ->extensions([
        \Lunar\Admin\Filament\Resources\CustomerGroupResource\Pages\CreateCustomerGroup::class => 
            \App\Admin\Extensions\Pages\ExampleCreatePageExtension::class,
    ])
    ->register();
```

**Available Methods**:

- `heading()` / `subheading()` - Customize page titles
- `headerWidgets()` / `footerWidgets()` - Add widgets
- `headerActions()` / `formActions()` - Add actions
- Lifecycle hooks: `beforeCreate()`, `afterCreation()`, `beforeSave()`, `afterUpdate()`, etc.
- `extendsInfolist()` - Extend infolist schema (View pages)
- `extendForm()` - Extend form schema (for addons)
- `paginateTableQuery()` - Customize pagination (List pages)
- `relationManagers()` - Modify relation managers (Edit pages)

**Example Extensions**:

The project includes example extension classes:
- `ExampleCreatePageExtension.php` - Create page extension example
- `ExampleEditPageExtension.php` - Edit page extension example
- `ExampleListPageExtension.php` - List page extension example
- `ExampleViewPageExtension.php` - View page extension example
- `ExampleRelationPageExtension.php` - Relation page extension example

**Documentation**: See [Admin Panel Extending Pages](https://docs.lunarphp.com/1.x/admin/extending/pages)

#### Extending Resources

You can extend Lunar's admin panel resources using the `ResourceExtension` base class. Resource extensions allow you to customize forms, tables, add relation managers, and extend pages and navigation.

**Creating Resource Extensions**:

```php
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Lunar\Admin\Support\Extending\ResourceExtension;

class MyProductResourceExtension extends ResourceExtension
{
    /**
     * Extend the form schema.
     */
    public function extendForm(Form $form): Form
    {
        return $form->schema([
            ...$form->getComponents(withHidden: true), // Get all existing components
            TextInput::make('custom_column')
                ->label('Custom Column'),
        ]);
    }

    /**
     * Extend the table schema.
     */
    public function extendTable(Table $table): Table
    {
        return $table->columns([
            ...$table->getColumns(), // Get all existing columns
            TextColumn::make('product_code')
                ->label('Product Code')
                ->searchable()
                ->sortable(),
        ]);
    }

    /**
     * Add or modify relation managers.
     */
    public function getRelations(array $managers): array
    {
        return [
            ...$managers,
            MyCustomProductRelationManager::class,
        ];
    }

    /**
     * Add or modify pages.
     */
    public function extendPages(array $pages): array
    {
        return [
            ...$pages,
            'my-page-route-name' => MyPage::route('/{record}/my-page'),
        ];
    }

    /**
     * Extend the sub-navigation.
     */
    public function extendSubNavigation(array $nav): array
    {
        return [
            ...$nav,
            MyPage::class,
        ];
    }
}
```

**Registering Resource Extensions**:

Register extensions in `AppServiceProvider::register()`:

```php
LunarPanel::panel(function ($panel) {
    return $panel;
})
    ->extensions([
        \Lunar\Panel\Filament\Resources\ProductResource::class => 
            \App\Admin\Extensions\Resources\ExampleProductResourceExtension::class,
    ])
    ->register();
```

**Available Methods**:

- `extendForm(Form $form)` - Add custom form fields to the resource form
- `extendTable(Table $table)` - Add custom table columns to the resource table
- `getRelations(array $managers)` - Add or modify relation managers (standard Filament relation managers)
- `extendPages(array $pages)` - Add custom pages to the resource (standard Filament pages)
- `extendSubNavigation(array $nav)` - Add custom pages to the sub-navigation menu

**Example Extension**:

The project includes an example resource extension:
- `ExampleProductResourceExtension.php` - Example extension for Product Resource

**Important Notes**:

- Use `$form->getComponents(withHidden: true)` to get all existing form components (including hidden ones)
- Use `$table->getColumns()` to get all existing table columns
- Relation managers and pages are standard Filament components
- See Filament documentation for creating relation managers and pages:
  - [Relation Managers](https://filamentphp.com/docs/3.x/panels/resources/relation-managers#creating-a-relation-manager)
  - [Pages](https://filamentphp.com/docs/3.x/panels/pages#creating-a-page)

**Documentation**: See [Admin Panel Extending Resources](https://docs.lunarphp.com/1.x/admin/extending/resources)

#### Extending Relation Managers

You can extend Lunar's admin panel relation managers using the `RelationManagerExtension` base class. Relation manager extensions allow you to customize forms and tables within relation managers.

**Creating Relation Manager Extensions**:

```php
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Lunar\Admin\Support\Extending\RelationManagerExtension;

class MyCustomerGroupPricingRelationManagerExtension extends RelationManagerExtension
{
    /**
     * Extend the form schema.
     */
    public function extendForm(Form $form): Form
    {
        return $form->schema([
            ...$form->getComponents(withHidden: true), // Get all existing components
            TextInput::make('custom_column')
                ->label('Custom Column'),
        ]);
    }

    /**
     * Extend the table schema.
     */
    public function extendTable(Table $table): Table
    {
        return $table->columns([
            ...$table->getColumns(), // Get all existing columns
            TextColumn::make('product_code')
                ->label('Product Code')
                ->searchable()
                ->sortable(),
        ]);
    }
}
```

**Registering Relation Manager Extensions**:

Register extensions in `AppServiceProvider::register()`:

```php
LunarPanel::panel(function ($panel) {
    return $panel;
})
    ->extensions([
        \Lunar\Admin\Filament\Resources\ProductResource\RelationManagers\CustomerGroupPricingRelationManager::class => 
            \App\Admin\Extensions\RelationManagers\ExampleCustomerGroupPricingRelationManagerExtension::class,
    ])
    ->register();
```

**Available Methods**:

- `extendForm(Form $form)` - Add custom form fields to the relation manager form
- `extendTable(Table $table)` - Add custom table columns to the relation manager table

**Example Extension**:

The project includes an example relation manager extension:
- `ExampleCustomerGroupPricingRelationManagerExtension.php` - Example extension for CustomerGroupPricing Relation Manager

**Important Notes**:

- Use `$form->getComponents(withHidden: true)` to get all existing form components (including hidden ones)
- Use `$table->getColumns()` to get all existing table columns
- Relation manager extensions work similarly to resource extensions but are scoped to specific relation managers

**Documentation**: See [Admin Panel Extending Relation Managers](https://docs.lunarphp.com/1.x/admin/extending/relation-managers)

#### Extending Order Management

You can extend Lunar's order management interface using page extensions for the Manage Order page and component extensions for the Order Items Table. Order management extensions provide many specific methods for customizing different parts of the order view screen.

**Extending Manage Order Page**:

The Manage Order page extension extends `ViewPageExtension` and provides many methods to customize the order view:

```php
use Lunar\Admin\Support\Extending\ViewPageExtension;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Component;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Entry;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\RepeatableEntry;

class MyManageOrderExtension extends ViewPageExtension
{
    public function extendInfolistSchema(): array
    {
        return [
            TextEntry::make('custom_field')
                ->label('Custom Field'),
        ];
    }

    public function extendOrderSummarySchema(): array
    {
        return [
            // Customize order summary entries
        ];
    }

    public function extendOrderTotalsSchema(): array
    {
        return [
            // Customize order totals entries
        ];
    }

    // Many other methods available (see documentation)
}
```

**Registering Manage Order Extension**:

Register in `AppServiceProvider::register()`:

```php
LunarPanel::panel(function ($panel) {
    return $panel;
})
    ->extensions([
        \Lunar\Admin\Filament\Resources\OrderResource\Pages\ManageOrder::class => 
            \App\Admin\Extensions\OrderManagement\ExampleManageOrderExtension::class,
    ])
    ->register();
```

**Available Methods for Manage Order Extension**:

**Main Infolist**:
- `extendInfolistSchema(): array` - Extend main infolist schema
- `extendInfolistAsideSchema(): array` - Extend aside infolist schema

**Aside Sections**:
- `extendCustomerEntry(): Component` - Customize customer entry
- `extendTagsSection(): Component` - Customize tags section
- `extendAdditionalInfoSection(): Component` - Customize additional info section
- `extendShippingAddressInfolist(): Component` - Customize shipping address
- `extendBillingAddressInfolist(): Component` - Customize billing address
- `extendAddressEditSchema(): array` - Customize address edit form

**Order Summary**:
- `extendOrderSummaryInfolist(): Section` - Customize order summary section
- `extendOrderSummarySchema(): array` - Extend order summary schema
- `extendOrderSummaryNewCustomerEntry(): Entry` - Customize new customer entry
- `extendOrderSummaryStatusEntry(): Entry` - Customize status entry
- `extendOrderSummaryReferenceEntry(): Entry` - Customize reference entry
- `extendOrderSummaryCustomerReferenceEntry(): Entry` - Customize customer reference entry
- `extendOrderSummaryChannelEntry(): Entry` - Customize channel entry
- `extendOrderSummaryCreatedAtEntry(): Entry` - Customize created at entry
- `extendOrderSummaryPlacedAtEntry(): Entry` - Customize placed at entry

**Timeline**:
- `extendTimelineInfolist(): Component` - Customize timeline display

**Order Totals Aside**:
- `extendOrderTotalsAsideSchema(): array` - Extend totals aside schema
- `extendDeliveryInstructionsEntry(): TextEntry` - Customize delivery instructions
- `extendOrderNotesEntry(): TextEntry` - Customize order notes

**Order Totals**:
- `extendOrderTotalsInfolist(): Section` - Customize order totals section
- `extendOrderTotalsSchema(): array` - Extend order totals schema
- `extendSubTotalEntry(): TextEntry` - Customize subtotal entry
- `extendDiscountTotalEntry(): TextEntry` - Customize discount total entry
- `extendShippingBreakdownGroup(): Group` - Customize shipping breakdown
- `extendTaxBreakdownGroup(): Group` - Customize tax breakdown
- `extendTotalEntry(): TextEntry` - Customize total entry
- `extendPaidEntry(): TextEntry` - Customize paid entry
- `extendRefundEntry(): TextEntry` - Customize refund entry

**Shipping**:
- `extendShippingInfolist(): Section` - Customize shipping section

**Transactions**:
- `extendTransactionsInfolist(): Component` - Customize transactions display
- `extendTransactionsRepeatableEntry(): RepeatableEntry` - Customize transactions entry

**Extending Order Items Table**:

The Order Items Table extension allows you to customize the order lines table:

```php
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Lunar\Admin\Filament\Resources\OrderResource\Pages\Components\OrderItemsTable;

class MyOrderItemsTableExtension extends OrderItemsTable
{
    public function extendOrderLinesTableColumns(): array
    {
        return [
            TextColumn::make('custom_field')
                ->label('Custom Field')
                ->searchable()
                ->sortable(),
        ];
    }

    public function extendTable(): Table
    {
        // Customize table configuration (filters, actions, etc.)
    }
}
```

**Registering Order Items Table Extension**:

Register in `AppServiceProvider::register()`:

```php
LunarPanel::extensions([
    \Lunar\Admin\Filament\Resources\OrderResource\Pages\Components\OrderItemsTable::class => 
        \App\Admin\Extensions\OrderManagement\ExampleOrderItemsTableExtension::class,
]);
```

**Available Methods for Order Items Table Extension**:

- `extendOrderLinesTableColumns(): array` - Add custom columns to order lines table
- `extendTable(): Table` - Customize table configuration (filters, actions, etc.)

**Example Extensions**:

The project includes example order management extension classes:
- `ExampleManageOrderExtension.php` - Example extension for Manage Order page
- `ExampleOrderItemsTableExtension.php` - Example extension for Order Items Table component

**Documentation**: See [Admin Panel Extending Order Management](https://docs.lunarphp.com/1.x/admin/extending/order-management)

#### Legacy Resource Extensions (Alternative Approach)

You can also extend resources by directly extending the resource class (this is the older approach, but still valid):

**Extending Resources by Extending the Resource Class**:

```php
use Filament\Forms\Form;
use Filament\Tables\Table;
use Lunar\Panel\Filament\Resources\ProductResource;

class ProductResourceExtension extends ProductResource
{
    public function form(Form $form): Form
    {
        return $form->schema([
            ...parent::form($form)->getComponents(),
            // Add custom form fields
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            ...parent::table($table)->getColumns(),
            // Add custom table columns
        ]);
    }
}
```

Register using the same `LunarPanel::extensions()` method:

```php
LunarPanel::extensions([
    ProductResource::class => ProductResourceExtension::class,
]);
```

**Note**: The `ResourceExtension` base class approach (shown above) is the recommended method as it provides more flexibility and cleaner separation of concerns.

**Extendable Resources**:

All Lunar panel resources are extendable, including:
- `ActivityResource`
- `AttributeGroupResource`
- `BrandResource`
- `ChannelResource`
- `CollectionGroupResource`
- `CollectionResource`
- `CurrencyResource`
- `CustomerGroupResource`
- `CustomerResource`
- `DiscountResource`
- `LanguageResource`
- `OrderResource`
- `ProductOptionResource`
- `ProductResource`
- `ProductTypeResource`
- `ProductVariantResource`
- `StaffResource`
- `TagResource`
- `TaxClassResource`
- `TaxZoneResource`

**Example Extensions**:

The project includes example extension classes:
- `ProductEditExtension.php` - Example extension for Edit Product page
- `ProductListExtension.php` - Example extension for List Products page
- `ProductResourceExtension.php` - Example extension for Product Resource

**Registration**: Register extensions in `AppServiceProvider::register()` using `LunarPanel::extensions()`.

For more information, see the [Lunar Admin Panel Extending documentation](https://docs.lunarphp.com/1.x/admin/extending/overview).

**Documentation**: 
- [Admin Panel Introduction](https://docs.lunarphp.com/1.x/admin/introduction)
- [Admin Panel Extending Overview](https://docs.lunarphp.com/1.x/admin/extending/overview)
- [Admin Panel Access Control](https://docs.lunarphp.com/1.x/admin/extending/access-control)

#### Access Control

Lunar uses **Spatie Laravel Permission** package for role and permission management in the admin panel.

**Staff Members**:

Staff members are users who can log in to the admin panel. They are stored in a separate table from regular users (in the `users` table) to ensure customers can never accidentally be given admin access.

**Roles**:

Lunar provides two default roles out of the box:
- `admin` - Full access to the admin panel
- `staff` - Limited access based on assigned permissions

You can create additional roles via the Access Control page in the Staff menu. Note that non-admins cannot assign other admins.

**Permissions**:

Permissions can be assigned to roles or directly to staff members. They control what staff can do or see in the panel. If a staff member doesn't have permission to view a page or perform an action, they will receive an Unauthorized HTTP error and see reduced menu items.

**Creating Permissions**:

Permissions should be created via migrations (not from the admin panel) so they can be deployed to other environments easily:

```php
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// In a migration
public function up(): void
{
    // Create permissions
    Permission::firstOrCreate(['name' => 'custom-action']);
    Permission::firstOrCreate(['name' => 'view-custom-page']);
    
    // Assign permissions to roles
    $adminRole = Role::findByName('admin');
    $adminRole->givePermissionTo('custom-action');
}
```

**Authorization Checks**:

You should implement authorization checks for your custom permissions and pages:

**In Routes/Middleware**:
```php
Route::middleware(['can:permission-handle'])->group(function () {
    // Protected routes
});
```

**In Code**:
```php
use Illuminate\Support\Facades\Auth;

if (Auth::user()->can('permission-handle')) {
    // User has permission
}
```

**Two-Factor Authentication**:

You can enforce or disable Two-Factor Authentication for the admin panel:

```php
// In AppServiceProvider::register()
use Lunar\Admin\Support\Facades\LunarPanel;

// Enforce 2FA
LunarPanel::enforceTwoFactorAuth()->register();

// Or disable 2FA
LunarPanel::disableTwoFactorAuth()->register();
```

**Example Migration**:

The project includes an example migration at `database/migrations/2024_01_01_000001_create_custom_permissions.php` showing how to create custom permissions and roles.

**Documentation**: See [Admin Panel Access Control](https://docs.lunarphp.com/1.x/admin/extending/access-control)

#### Developing Addons

When creating addon packages for Lunar, you may wish to add new screens and functionality to the Filament panel. This can be achieved by creating a Filament plugin.

**Filament Plugins**:

Filament plugins allow you to add additional resources, pages, and widgets to the Lunar admin panel. See the [Filament Plugins documentation](https://filamentphp.com/docs/3.x/panels/plugins) for more information.

**Registering Plugins**:

Addon packages should NOT automatically register Filament plugins in the Lunar panel. Instead, installation instructions should be provided for manual registration in the Laravel app service provider:

```php
use Lunar\Admin\Support\Facades\LunarPanel;
use App\Admin\Plugins\ExamplePlugin;

// In AppServiceProvider::register()
LunarPanel::panel(fn($panel) => $panel->plugin(new ExamplePlugin()))
    ->register();
```

**Example Plugin**:

The project includes an example plugin class at `app/Admin/Plugins/ExamplePlugin.php` showing how to structure a Filament plugin for the Lunar admin panel. This plugin demonstrates:
- Resource discovery
- Page discovery
- Widget discovery

**Important Notes**:

1. **Manual Registration**: Addon packages should provide installation instructions rather than auto-registering plugins
2. **Plugin Structure**: Plugins extend `Filament\PanelProvider` and configure the panel for the plugin
3. **Discovery**: Use Filament's discovery methods to automatically find resources, pages, and widgets

**Documentation**: 
- [Admin Panel Developing Addons](https://docs.lunarphp.com/1.x/admin/extending/addons)
- [Filament Plugins Documentation](https://filamentphp.com/docs/3.x/panels/plugins)

#### Extending Attributes

You can create custom attribute field types for Lunar and control how they are rendered in the admin panel. This allows you to add specialized field types beyond the built-in ones (Text, Number, TranslatedText, etc.).

**Custom Field Types**:

To create a custom field type, you need:

1. **Field Class** - The data structure (extends Lunar's base field types)
2. **Field Type Converter** - Converts the field to a Filament component
3. **Livewire Synthesizer** - Handles hydration/dehydration for Livewire
4. **Registration** - Register with the `AttributeData` facade

**Creating the Field**:

Create a field class that extends one of Lunar's base field types:

```php
namespace App\FieldTypes;

use Lunar\FieldTypes\Text;

class CustomField extends Text
{
    // Add custom field logic here
}
```

**Creating the Field Type Converter**:

The field type converter handles how the field is rendered in the admin panel:

```php
namespace App\Admin\FieldTypes;

use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Lunar\Admin\Support\FieldTypes\BaseFieldType;
use Lunar\Models\Attribute;

class CustomFieldType extends BaseFieldType
{
    protected static string $synthesizer = \App\Admin\Synthesizers\CustomFieldSynth::class;

    public static function getFilamentComponent(Attribute $attribute): Component
    {
        return TextInput::make($attribute->handle);
    }

    // Optional: Add configuration fields
    public static function getConfigurationFields(): array
    {
        return [
            // Configuration fields for the attribute
        ];
    }
}
```

**Adding Settings/Configuration**:

You can add configuration fields that are stored in the attribute's `configuration` JSON column:

```php
public static function getConfigurationFields(): array
{
    return [
        Grid::make(2)->schema([
            TextInput::make('min_length'),
            TextInput::make('max_length'),
        ]),
    ];
}
```

Access configuration when rendering the component:

```php
public static function getFilamentComponent(Attribute $attribute): Component
{
    $min = (int) $attribute->configuration->get('min_length');
    $max = (int) $attribute->configuration->get('max_length');
    
    return TextInput::make($attribute->handle)
        ->minLength($min)
        ->maxLength($max);
}
```

**Creating the Livewire Synthesizer**:

The synthesizer tells Livewire how to handle the field type:

```php
namespace App\Admin\Synthesizers;

use App\FieldTypes\CustomField;
use Lunar\Admin\Support\Synthesizers\AbstractFieldSynth;

class CustomFieldSynth extends AbstractFieldSynth
{
    public static string $key = 'lunar_custom_field_field';
    protected static string $targetClass = CustomField::class;
}
```

**Registering the Field Type**:

Register the field type in your service provider:

```php
use Lunar\Admin\Support\Facades\AttributeData;

// In AppServiceProvider::register()
AttributeData::registerFieldType(
    \App\FieldTypes\CustomField::class,
    \App\Admin\FieldTypes\CustomFieldType::class
);
```

**Example Files**:

The project includes example files:
- `app/FieldTypes/CustomField.php` - Example custom field class
- `app/Admin/FieldTypes/CustomFieldType.php` - Example field type converter
- `app/Admin/Synthesizers/CustomFieldSynth.php` - Example Livewire synthesizer

**Documentation**: 
- [Admin Panel Extending Attributes](https://docs.lunarphp.com/1.x/admin/extending/attributes)
- [Livewire Synthesizers](https://livewire.laravel.com/docs/synthesizers)

#### Extending the Panel

You can customize the Filament panel when registering it in your service provider. Lunar provides a `panel()` method that gives you direct access to the panel to change its properties.

**Customizing Panel Properties**:

```php
use Lunar\Admin\Support\Facades\LunarPanel;

LunarPanel::panel(function ($panel) {
    return $panel
        // Change the panel URL path (default is '/lunar')
        ->path('admin')
        
        // Register standalone Filament Pages
        ->pages([
            \App\Admin\Pages\SalesReport::class,
            \App\Admin\Pages\RevenueReport::class,
        ])
        
        // Register new Filament Resources
        ->resources([
            \App\Admin\Resources\OpeningTimeResource::class,
            \App\Admin\Resources\BannerResource::class,
        ])
        
        // Register Livewire components
        ->livewireComponents([
            \App\Admin\Livewire\OrdersSalesChart::class,
        ])
        
        // Register Filament plugins
        ->plugin(new \App\Admin\Plugins\ExamplePlugin())
        
        // Customize navigation groups
        ->navigationGroups([
            'Catalog',
            'Sales',
            'CMS',
            'Reports',
            'Shipping',
            'Settings',
        ]);
})->register();
```

**Combining with Extensions**:

You can combine panel customization with extensions:

```php
LunarPanel::panel(function ($panel) {
    return $panel->path('admin');
})
    ->extensions([
        // Register panel extensions
    ])
    ->register();
```

**Available Panel Customizations**:

- **Path**: Change the panel URL path (`->path('admin')`)
- **Pages**: Register standalone Filament pages (`->pages([...])`)
- **Resources**: Register new Filament resources (`->resources([...])`)
- **Livewire Components**: Register Livewire components (`->livewireComponents([...])`)
- **Plugins**: Register Filament plugins (`->plugin(...)`)
- **Navigation Groups**: Customize navigation groups (`->navigationGroups([...])`)

All Filament panel customization options are available. For more information, consult the [Filament Panel documentation](https://filamentphp.com/docs/3.x/panels).

**Documentation**: 
- [Admin Panel Extending the Panel](https://docs.lunarphp.com/1.x/admin/extending/panel)
- [Filament Panel Documentation](https://filamentphp.com/docs/3.x/panels)

## Storefront Routes

- Home: `/`
- Products: `/products`
- Product Detail: `/products/{slug}`
- Collections: `/collections`
- Collection Detail: `/collections/{slug}`
- Search: `/search?q=query`
- Cart: `/cart`
- Checkout: `/checkout`

## Attributes

This project implements attributes following the [Lunar Attributes documentation](https://docs.lunarphp.com/1.x/reference/attributes):

- **Field Types**: Text, Number, TranslatedText (with more available)
- **Attribute Groups**: Logical grouping (e.g., "Product" and "SEO" groups)
- **Attribute Data**: Stored using proper FieldType objects (e.g., `new \Lunar\FieldTypes\Text('value')`)
- **Accessing Data**: Use `$product->translateAttribute('handle')` to retrieve attribute values

The `AttributeHelper` class provides convenience methods for working with attributes programmatically.

## Products

This project implements products following the [Lunar Products documentation](https://docs.lunarphp.com/1.x/reference/products):

- **Product Creation**: Products created with `attribute_data` using FieldType objects
- **Product Types**: Products belong to product types which define available attributes
- **Product Identifiers**: SKU, GTIN, MPN, EAN (configurable validation in `config/lunar/products.php`)
- **Product Options**: System-level options (e.g., Color, Size) with translatable values
  - Options are defined at system level and shared across products
  - Option values are translatable
  - Variants can be generated automatically from option values
- **Variants**: Products have variants with SKU, pricing, stock, and option values
  - Products always have at least one variant
  - Variants can have different SKU, GTIN, MPN, EAN identifiers
  - Variants support quantity-based price breaks
- **Customer Groups**: Products can be scheduled for customer groups
- **Pricing**: Uses `Pricing` facade for fetching prices with quantity breaks and customer groups
- **Price Breaks**: Quantity-based pricing supported
- **Customer Group Pricing**: Different prices for different customer groups

The `ProductHelper` and `ProductOptionHelper` classes provide convenience methods for working with products.

Example usage:
```php
use App\Lunar\Products\ProductHelper;
use App\Lunar\Products\ProductOptionHelper;
use Lunar\Facades\Pricing;

// Get price for a variant
$price = ProductHelper::getPrice($variant, 5); // quantity 5

// Get full pricing information
$pricing = ProductHelper::getPricing($variant, 1, $customerGroup);
// $pricing->matched, $pricing->base, $pricing->priceBreaks, $pricing->customerGroupPrices

// Schedule product for customer groups
ProductHelper::scheduleCustomerGroups($product, $customerGroups, now()->addDays(14));

// Get products for customer groups
$products = ProductHelper::forCustomerGroups($customerGroup)->paginate(50);

// Create product option with values
$colorOption = ProductOptionHelper::createOption('Colour', 'Colour', ['Red', 'Blue', 'Green']);

// Generate variants using option values
ProductOptionHelper::generateVariants($product, $optionValueIds);

// Or use Pricing facade directly
use Lunar\Facades\Pricing;

$pricing = Pricing::qty(5)->for($variant)->get();
// $pricing->matched, $pricing->base, $pricing->priceBreaks, $pricing->customerGroupPrices

// Get price for specific customer group
$pricing = Pricing::customerGroup($customerGroup)->for($variant)->get();

// Get price with currency
$pricing = Pricing::currency($currency)->for($variant)->get();
```

## Media

This project implements media handling following the [Lunar Media documentation](https://docs.lunarphp.com/1.x/reference/media):

- **Media Library**: Uses Spatie Laravel Media Library package
- **Supported Models**: Products and Collections support media
- **Custom Media Definitions**: Custom conversions and collections defined in `CustomMediaDefinitions`
- **Fallback Images**: Configured via `lunar/media` config or `.env` (FALLBACK_IMAGE_URL, FALLBACK_IMAGE_PATH)
- **Media Collections**: Uses 'images' collection by default
- **Conversions**: Supports 'small', 'thumb', 'medium', 'large', 'zoom' conversions

**Configuration**:

Add fallback image configuration to your `.env` file:
```env
FALLBACK_IMAGE_URL=https://example.com/images/placeholder.jpg
FALLBACK_IMAGE_PATH=/path/to/placeholder.jpg
```

The `MediaHelper` class provides convenience methods for working with media programmatically.

Example usage:
```php
use App\Lunar\Media\MediaHelper;

// Get images
$images = MediaHelper::getImages($product);

// Get first image URL
$imageUrl = MediaHelper::getFirstImageUrl($product, 'images', 'large');

// Add image
MediaHelper::addImage($product, $request->file('image'));

// Or use directly with Spatie Media Library
$product->addMedia($request->file('image'))->toMediaCollection('images');
$product->getMedia('images');
$product->getFirstMediaUrl('images', 'large');
```

## Collections

This project implements collections following the [Lunar Collections documentation](https://docs.lunarphp.com/1.x/reference/collections):

- **Collection Groups**: Collections belong to collection groups (e.g., "Main Catalogue")
- **Collections**: Created with `attribute_data` using FieldType objects (e.g., `new \Lunar\FieldTypes\TranslatedText(...)`)
- **Nested Collections**: Child collections using nested sets (`appendNode()`)
- **Adding Products**: Products added with positions using `sync()` method
- **Sorting Products**: Collections support multiple sort types:
  - `min_price:asc` / `min_price:desc` - Sort by minimum variant price
  - `sku:asc` / `sku:desc` - Sort by SKU
  - `custom` - Manual position ordering (default)

The `CollectionHelper` class provides convenience methods for working with collections programmatically.

Example usage:
```php
use App\Lunar\Collections\CollectionHelper;

// Get sorted products from a collection
$products = CollectionHelper::getSortedProducts($collection);

// Add products with positions
CollectionHelper::addProducts($collection, [
    1 => ['position' => 1],
    2 => ['position' => 2],
]);

// Create child collection
$child = Collection::create([/*...*/]);
CollectionHelper::addChildCollection($parent, $child);
```

## Product Associations

This project implements product associations as described in the [Lunar Associations documentation](https://docs.lunarphp.com/1.x/reference/associations):

- **Cross-sell**: Complementary products (e.g., headphones with smartphones)
- **Up-sell**: Higher value alternatives (e.g., premium versions)
- **Alternate**: Alternative product options

The storefront displays associations on product detail pages. Associations are managed via:
- `AssociationManager` class for synchronous operations (seeders, commands)
- `Product::associate()` method for asynchronous operations (queued jobs)
- `ProductAssociationController` for API management

Example usage:
```php
use App\Lunar\Associations\AssociationManager;
use Lunar\Base\Enums\ProductAssociation as ProductAssociationEnum;

$manager = new AssociationManager();
$manager->associate($product, $targetProduct, ProductAssociationEnum::CROSS_SELL);
```

## Search

This project implements search following the [Lunar Search documentation](https://docs.lunarphp.com/1.x/reference/search):

- **Laravel Scout**: Uses Laravel Scout package for search functionality
- **Searchable Models**: Products, Collections, Customers, Orders, ProductOptions, and Brands are searchable
- **Engine Mapping**: Different models can use different search engines (e.g., Algolia for Products, Meilisearch for Collections)
- **Indexers**: Custom indexers for each model type handle how data is indexed
- **Soft Deletes**: Scout soft_delete is set to `true` to prevent soft-deleted models from appearing in search results

**Configuration**:

1. Set your Scout driver in `.env`:
```env
SCOUT_DRIVER=database  # or meilisearch, algolia, etc.
SCOUT_SOFT_DELETE=true  # Required by Lunar - prevents soft-deleted models from appearing in search
```

2. If you need to publish Scout config (optional):
```bash
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
```

   Then ensure `soft_delete` is set to `true` in `config/scout.php`:
```php
'soft_delete' => env('SCOUT_SOFT_DELETE', true),
```

3. Configure engine mapping in `config/lunar/search.php`:
```php
'engine_map' => [
    Lunar\Models\Product::class => 'algolia',
    Lunar\Models\Collection::class => 'meilisearch',
    Lunar\Models\Order::class => 'meilisearch',
],
```

4. Index your models:
```bash
# Index all models listed in config/lunar/search.php
php artisan lunar:search:index

# For Meilisearch, set up filterable and searchable attributes
php artisan lunar:meilisearch:setup
```

**Usage**:

```php
use App\Lunar\Search\SearchHelper;
use Lunar\Models\Product;

// Search products using Scout
$products = Product::search('query')
    ->where('status', 'published')
    ->paginate(12);

// Or use the helper
$products = SearchHelper::searchProducts('query', 12, 1);

// Get search results as collection
$results = SearchHelper::searchProductsCollection('query', 10);
```

**Available Drivers**:
- `database` - Basic database search (default, no setup required)
- `meilisearch` - Full-text search engine (requires Meilisearch installation)
- `algolia` - Cloud search service (requires Algolia account)
- Custom drivers can be configured via `config/lunar/search.php`

**Custom Indexers**:

Create custom indexers to control how models are indexed. See the [Extending Search documentation](https://docs.lunarphp.com/1.x/extending/search) and the Extension Points section below for details.

The project includes a `CustomProductIndexer` at `app/Lunar/Search/Indexers/CustomProductIndexer.php` as an example.

## URLs

This project implements URLs following the [Lunar URLs documentation](https://docs.lunarphp.com/1.x/reference/urls):

- **URL Slugs**: Products and Collections use URL slugs instead of IDs (e.g., `/products/apple-iphone` instead of `/products/1`)
- **HasUrls Trait**: Models use the `HasUrls` trait to support URLs (Products and Collections have this by default)
- **Default URLs**: Only one default URL per language per resource
- **Automatic Generation**: URLs are automatically generated using the `UrlGenerator` class (configured in `config/lunar/urls.php`)
- **Language Support**: URLs are language-specific (each language can have its own slug)

**Configuration**:

URLs are configured in `config/lunar/urls.php`:

```php
// Enable/disable automatic URL generation
'generator' => UrlGenerator::class, // or null to disable

// Set whether URLs are required
'required' => true,
```

**Usage**:

```php
use App\Lunar\Urls\UrlHelper;
use Lunar\Models\Product;
use Lunar\Models\Language;

// Create a URL for a model
$url = UrlHelper::create($product, 'apple-iphone', $language, true);

// Get default URL for a model
$defaultUrl = UrlHelper::getDefaultUrl($product, $language);

// Get default slug
$slug = UrlHelper::getDefaultSlug($product);

// Update or create default URL
UrlHelper::updateOrCreateDefault($product, 'new-slug');

// Check if slug is available
$available = UrlHelper::isSlugAvailable('my-slug', $language);

// Get all URLs for a model
$urls = UrlHelper::getUrls($product);

// Using the relationship directly (if model uses HasUrls trait)
$product->urls; // Collection of all URLs
$product->urls->where('default', true)->first(); // Default URL
$product->urls->first()->slug; // Get slug
```

**Adding URL Support to Custom Models**:

```php
use Lunar\Base\Traits\HasUrls;

class MyModel extends Model
{
    use HasUrls;
    
    // Now you can use:
    // $model->urls; // Get all URLs
}
```

**Storefront Usage**:

The storefront uses URLs to generate SEO-friendly links:
- Products: `/products/{slug}`
- Collections: `/collections/{slug}`

Controllers find resources by slug:
```php
$url = Url::where('slug', $slug)
    ->where('element_type', Product::class)
    ->firstOrFail();
    
$product = Product::findOrFail($url->element_id);
```

## Addresses

This project implements addresses following the [Lunar Addresses documentation](https://docs.lunarphp.com/1.x/reference/addresses):

- **Addresses**: Customer addresses with shipping and billing defaults
- **Countries**: Country data with ISO2, ISO3 codes
- **States**: State/province data linked to countries
- **Address Data Import**: Command to import countries and states from external database

**Setup**:

1. Import address data (countries and states):
```bash
php artisan lunar:import:address-data
```

This command imports country and state data from the [countries-states-cities-database](https://github.com/dr5hn/countries-states-cities-database).

**Usage**:

```php
use App\Lunar\Addresses\AddressHelper;
use Lunar\Models\Address;
use Lunar\Models\Country;
use Lunar\Models\State;

// Create an address
$address = AddressHelper::create($customerId, [
    'title' => 'Mr',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'company_name' => 'Acme Inc',
    'line_one' => '123 Main Street',
    'line_two' => 'Suite 100',
    'city' => 'New York',
    'postcode' => '10001',
    'country_id' => 1, // US
    'contact_email' => 'john@example.com',
    'contact_phone' => '+1234567890',
    'shipping_default' => true,
    'billing_default' => false,
]);

// Get addresses for a customer
$addresses = AddressHelper::getForCustomer($customerId);

// Get default shipping/billing addresses
$shippingDefault = AddressHelper::getDefaultShipping($customerId);
$billingDefault = AddressHelper::getDefaultBilling($customerId);

// Set default addresses
AddressHelper::setDefaultShipping($address);
AddressHelper::setDefaultBilling($address);

// Get countries and states
$countries = AddressHelper::getCountries();
$usCountry = AddressHelper::getCountryByIso2('US');
$states = AddressHelper::getStates($usCountry);
$nyState = AddressHelper::getStateByCode($usCountry, 'NY');

// Mark address as used (updates last_used_at)
AddressHelper::markAsUsed($address);

// Format address as string
$formatted = AddressHelper::format($address);
```

**Address Fields**:

- `title` - Title (nullable)
- `first_name` - First name (required)
- `last_name` - Last name (required)
- `company_name` - Company name (nullable)
- `line_one` - Address line one (required)
- `line_two` - Address line two (nullable)
- `line_three` - Address line three (nullable)
- `city` - City (required)
- `state` - State/province (nullable)
- `postcode` - Postal/ZIP code (nullable)
- `country_id` - Country ID (required)
- `delivery_instructions` - Delivery instructions (nullable)
- `contact_email` - Contact email (required)
- `contact_phone` - Contact phone (required)
- `last_used_at` - Timestamp of last use (nullable)
- `meta` - JSON metadata (nullable)
- `shipping_default` - Is default shipping address (boolean)
- `billing_default` - Is default billing address (boolean)

**Country Fields**:

- `name` - Country name
- `iso2` - ISO 3166-1 alpha-2 code (e.g., "US")
- `iso3` - ISO 3166-1 alpha-3 code (e.g., "USA")
- `phonecode` - Phone code
- `capital` - Capital city
- `currency` - Currency code
- `native` - Native name
- `emoji` - Flag emoji
- `emoji_u` - Flag emoji Unicode

**State Fields**:

- `country_id` - Country ID
- `name` - State name
- `code` - State code

## Carts

This project implements carts following the [Lunar Carts documentation](https://docs.lunarphp.com/1.x/reference/carts):

- **Cart Management**: Carts belong to users/customers and contain purchasable items
- **Cart Lines**: Individual items in the cart with quantities and metadata
- **Dynamic Pricing**: Cart prices are calculated dynamically (not stored)
- **Cart Session**: Session-based cart management via `CartSession` facade
- **Validation**: Automatic validation when adding items to cart
- **Tax Calculation**: Tax calculated based on shipping/billing addresses
- **Fingerprinting**: Cart fingerprinting for change detection
- **Address Support**: Shipping and billing addresses for tax and shipping calculation

**Configuration**:

Cart configuration is in `config/lunar/cart.php` and `config/lunar/cart_session.php`:

```php
// config/lunar/cart_session.php
'session_key' => 'lunar_cart', // Session key for cart ID
'auto_create' => false, // Auto-create cart if none exists
'allow_multiple_orders_per_cart' => false, // Multiple orders per cart
```

**Usage**:

```php
use App\Lunar\Carts\CartHelper;
use Lunar\Facades\CartSession;
use Lunar\Models\Cart;
use Lunar\Models\ProductVariant;

// Get current cart
$cart = CartHelper::current();
// or
$cart = CartSession::current();

// Create a new cart
$cart = CartHelper::create($currencyId, $channelId, $userId, $customerId);

// Add item to cart (with validation)
try {
    CartHelper::add($variant, 2, ['gift_message' => 'Happy Birthday']);
} catch (\Lunar\Exceptions\Carts\CartException $e) {
    // Handle validation error
}

// Add multiple lines
CartHelper::addLines([
    ['purchasable' => $variant1, 'quantity' => 1],
    ['purchasable' => $variant2, 'quantity' => 2, 'meta' => ['custom' => 'data']],
]);

// Update line
CartHelper::updateLine($cartLineId, 5);

// Remove line
CartHelper::remove($cartLineId);

// Clear cart
CartHelper::clear();

// Calculate cart totals (hydrate the cart)
$cart->calculate();

// Access calculated values (after calculate())
$cart->total; // Total price
$cart->subTotal; // Subtotal excluding tax
$cart->subTotalDiscounted; // Subtotal minus discounts
$cart->shippingTotal; // Shipping total
$cart->taxTotal; // Tax total
$cart->discountTotal; // Discount total
$cart->taxBreakdown; // Collection of tax breakdowns
$cart->discountBreakdown; // Collection of discount breakdowns
$cart->shippingBreakdown; // Collection of shipping breakdowns

// Cart line values (after calculate())
foreach ($cart->lines as $line) {
    $line->unitPrice; // Price per unit
    $line->unitPriceInclTax; // Price per unit including tax
    $line->total; // Total for line
    $line->subTotal; // Subtotal excluding tax
    $line->subTotalDiscounted; // Subtotal minus discounts
    $line->taxAmount; // Tax amount
    $line->discountTotal; // Discount total
}

// Set addresses for tax calculation
CartHelper::setShippingAddress([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'line_one' => '123 Main St',
    'city' => 'New York',
    'postcode' => '10001',
    'country_id' => 1,
]);

CartHelper::setBillingAddress([/* ... */]);

// Associate cart to user/customer
CartHelper::associateUser($user, 'merge'); // or 'override'
CartHelper::associateCustomer($customer);

// Cart fingerprinting (detect changes)
$fingerprint = CartHelper::getFingerprint($cart);
$hasChanged = !CartHelper::checkFingerprint($fingerprint, $cart);

// Estimated shipping
$shippingOption = CartHelper::getEstimatedShipping([
    'postcode' => '123456',
    'state' => 'Essex',
    'country' => $country,
], setOverride: true);

// Forget cart
CartHelper::forget(); // Removes from session and deletes
CartHelper::forget(delete: false); // Only removes from session
```

**Extending Carts**:

Lunar allows you to extend cart functionality through pipelines and validators:

- **Pipelines**: Modify cart behavior during calculation (e.g., custom discounts, metadata)
- **Validators**: Add validation logic for cart actions (e.g., quantity limits, stock checks)

See the [Extending Carts documentation](https://docs.lunarphp.com/1.x/extending/carts) and the Extension Points section below for examples.

**Cart Fields**:

- `id` - Unique cart ID
- `user_id` - User ID (nullable for guests)
- `customer_id` - Customer ID (nullable)
- `merged_id` - ID of cart this was merged into (nullable)
- `currency_id` - Currency ID
- `channel_id` - Channel ID
- `coupon_code` - Promotional coupon code (nullable)
- `meta` - JSON metadata
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

**Cart Line Fields**:

- `id` - Line ID
- `cart_id` - Cart ID
- `purchasable_type` - Type of purchasable (e.g., ProductVariant)
- `purchasable_id` - ID of purchasable
- `quantity` - Quantity
- `meta` - JSON metadata
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

**Cart Pruning**:

Enable automatic cart cleanup in `config/lunar/cart.php`:

```php
'prune_tables' => [
    'enabled' => true,
    'prune_interval' => 90, // days
    'pipelines' => [
        \Lunar\Pipelines\CartPrune\PruneAfter::class,
        \Lunar\Pipelines\CartPrune\WithoutOrders::class,
    ],
],
```

## Customers

This project implements customers following the [Lunar Customers documentation](https://docs.lunarphp.com/1.x/reference/customers):

- **Customer Model**: Stores customer details separately from Users
- **Customer Groups**: Group customers for pricing and product availability
- **User Association**: Multiple users can be associated with one customer (useful for B2B)
- **Customer Group Scheduling**: Schedule product availability per customer group
- **Impersonation**: Admin can impersonate customers for support

**Usage**:

```php
use App\Lunar\Customers\CustomerHelper;
use Lunar\Models\Customer;
use Lunar\Models\CustomerGroup;
use App\Models\User;

// Create a customer
$customer = CustomerHelper::create([
    'title' => 'Mr.',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'company_name' => 'Acme Inc',
    'vat_no' => 'GB123456789',
    'meta' => [
        'account_no' => 'ACME1234',
    ],
]);

// Attach user to customer
CustomerHelper::attachUser($customer, $user);

// Sync multiple users (useful for B2B where multiple buyers can access the same customer account)
CustomerHelper::syncUsers($customer, [1, 2, 3]);

// Attach customer to customer group
$retailGroup = CustomerHelper::findCustomerGroupByHandle('retail');
CustomerHelper::attachCustomerGroup($customer, $retailGroup);

// Sync customer groups
CustomerHelper::syncCustomerGroups($customer, [1, 2]);

// Create a customer group
$customerGroup = CustomerHelper::createCustomerGroup(
    'Trade',
    'trade',
    default: false
);

// Get all customer groups
$allGroups = CustomerHelper::getAllCustomerGroups();

// Get default customer group
$defaultGroup = CustomerHelper::getDefaultCustomerGroup();

// Get customer for user
$customer = CustomerHelper::getCustomerForUser($user);

// Create or get customer for user
$customer = CustomerHelper::getOrCreateCustomerForUser($user, [
    'first_name' => 'John',
    'last_name' => 'Doe',
]);

// Schedule customer group availability for a product (using HasCustomerGroups trait)
$product->scheduleCustomerGroup($customerGroup);
$product->scheduleCustomerGroup($customerGroup, now()->addDays(14)); // Schedule for future
$product->scheduleCustomerGroup([$group1, $group2], $startDate, $endDate);

// Unschedule customer group
$product->unscheduleCustomerGroup($customerGroup);

// Query products by customer group
$products = Product::customerGroup($customerGroup)->get();
$products = Product::customerGroup([$group1, $group2])->get();
$products = Product::customerGroup($customerGroup, now()->addDay())->get(); // Available tomorrow
$products = Product::customerGroup($customerGroup, $startDate, $endDate)->get(); // Date range
```

**Customer Fields**:

- `id` - Unique customer ID
- `title` - Title (Mr, Mrs, Miss, etc.)
- `first_name` - First name
- `last_name` - Last name
- `company_name` - Company name (nullable)
- `vat_no` - VAT number (nullable)
- `account_ref` - Account reference (nullable)
- `attribute_data` - JSON attribute data
- `meta` - JSON metadata
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

**Customer Group Fields**:

- `id` - Unique customer group ID
- `name` - Group name (e.g., "Retail", "Trade")
- `handle` - Unique handle (e.g., "retail", "trade")
- `default` - Whether this is the default group (boolean)
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

**Relationships**:

- **Users**: `$customer->users()` - Multiple users can be associated with one customer
- **Customer Groups**: `$customer->customerGroups()` - Customer can belong to multiple groups
- **Customer**: `$user->latestCustomer()` - Get the latest customer for a user

**Customer Group Scheduling**:

Models using the `HasCustomerGroups` trait (like Products) can schedule availability:

- `scheduleCustomerGroup($customerGroup, $startDate, $endDate, $pivotData)` - Schedule availability
- `unscheduleCustomerGroup($customerGroup, $pivotData)` - Disable availability
- `customerGroup($customerGroup, $startDate, $endDate)` - Query scope to filter by customer group

**Impersonation**:

Admin can impersonate customers for support. Configure in `config/lunar-hub/customers.php`:

```php
return [
    'impersonate' => App\Auth\Impersonate::class,
    // ...
];
```

The impersonation class generates a signed URL that allows temporary login as the customer.

**Note**: You must have at least one customer group in your store. Lunar provides a default `retail` customer group on installation.

## Discounts

This project implements discounts following the [Lunar Discounts documentation](https://docs.lunarphp.com/1.x/reference/discounts):

- **Discount Model**: Store discount information with scheduling, usage limits, and priority
- **Discount Types**: Built-in types (Coupon, BuyXGetY) and custom discount types
- **Discount Purchasables**: Relate purchasables to discounts as conditions or rewards
- **Discount Scopes**: Query active, usable, and product-specific discounts
- **Discount Cache**: Performance caching with reset capability

**Usage**:

```php
use App\Lunar\Discounts\DiscountHelper;
use Lunar\Models\Discount;
use Lunar\Models\ProductVariant;

// Create a discount
$discount = DiscountHelper::create([
    'name' => '20% Off',
    'handle' => '20_off',
    'type' => 'Lunar\DiscountTypes\Coupon',
    'data' => [
        'coupon' => 'SAVE20',
        'min_prices' => [
            'USD' => 2000, // $20 minimum
        ],
    ],
    'starts_at' => now(),
    'ends_at' => now()->addDays(30),
    'max_uses' => 100,
    'priority' => 1,
    'stop' => false, // Whether to stop other discounts after this one
]);

// Create a coupon discount (convenience method)
$coupon = DiscountHelper::createCoupon(
    '20% Coupon',
    '20_coupon',
    'SAVE20',
    [
        'starts_at' => now(),
        'ends_at' => now()->addDays(30),
        'max_uses' => 100,
        'data' => [
            'min_prices' => ['USD' => 2000],
        ],
    ]
);

// Find discount by handle
$discount = DiscountHelper::findByHandle('20_coupon');

// Get active discounts
$activeDiscounts = DiscountHelper::getActive();

// Get usable discounts (uses < max_uses)
$usableDiscounts = DiscountHelper::getUsable();

// Get available discounts (active and usable)
$availableDiscounts = DiscountHelper::getAvailable();

// Query discounts by products
$productDiscounts = DiscountHelper::getByProducts([1, 2, 3], 'condition');

// Add purchasable conditions (items required to activate discount)
$variant = ProductVariant::find(1);
DiscountHelper::addCondition($discount, $variant);

// Add purchasable rewards (items that get discounted)
DiscountHelper::addReward($discount, $variant);

// Add multiple conditions/rewards
DiscountHelper::addConditions($discount, [$variant1, $variant2]);
DiscountHelper::addRewards($discount, [$variant3, $variant4]);

// Get conditions and rewards
$conditions = DiscountHelper::getConditions($discount);
$rewards = DiscountHelper::getRewards($discount);

// Reset discount cache (after adding/modifying discounts)
DiscountHelper::resetCache();

// Check if discount can be used
if (DiscountHelper::canUse($discount)) {
    // Apply discount
}

// Check if discount is active
if (DiscountHelper::isActive($discount)) {
    // Discount is currently active
}

// Increment uses count
DiscountHelper::incrementUses($discount);

// Register custom discount type (in AppServiceProvider::boot())
// Discounts::addType(\App\Lunar\Discounts\DiscountTypes\CustomPercentageDiscount::class);
```

**Discount Fields**:

- `id` - Unique discount ID
- `name` - Discount name
- `handle` - Unique handle
- `type` - Discount type class (e.g., `Lunar\DiscountTypes\Coupon`)
- `data` - JSON data used by the discount type
- `starts_at` - Start datetime (required)
- `ends_at` - End datetime (nullable, won't expire if null)
- `uses` - Number of times discount has been used
- `max_uses` - Maximum uses storewide (nullable)
- `priority` - Priority order (higher = more priority)
- `stop` - Whether to stop other discounts after this one
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

**Discount Purchasable Fields**:

- `id` - Unique ID
- `discount_id` - Discount ID
- `purchasable_type` - Type of purchasable (e.g., `product_variant`)
- `purchasable_id` - ID of purchasable
- `type` - Either `condition` or `reward`
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

**Discount Scopes**:

- `Discount::active()` - Discounts between starts_at and ends_at
- `Discount::usable()` - Discounts where uses < max_uses or max_uses is null
- `Discount::products($productIds, $type)` - Discounts with associated products

**Custom Discount Types**:

Create custom discount types by extending `AbstractDiscountType`:

```php
<?php

namespace App\Lunar\Discounts\DiscountTypes;

use Lunar\Models\Cart;
use Lunar\DiscountTypes\AbstractDiscountType;

class CustomPercentageDiscount extends AbstractDiscountType
{
    public function getName(): string
    {
        return 'Custom Percentage Discount';
    }

    public function apply(Cart $cart): Cart
    {
        // Apply discount logic here
        // Example: Apply 10% discount
        $discountAmount = (int) ($cart->subTotal->value * 0.10);
        $cart->discount_total = new \Lunar\DataTypes\Price($discountAmount, $cart->currency, 1);
        
        return $cart;
    }
}
```

Register the discount type in `AppServiceProvider::boot()`:

```php
use Lunar\Facades\Discounts;
use App\Lunar\Discounts\DiscountTypes\CustomPercentageDiscount;

public function boot(): void
{
    Discounts::addType(CustomPercentageDiscount::class);
}
```

## Orders

This project implements orders following the [Lunar Orders documentation](https://docs.lunarphp.com/1.x/reference/orders):

- **Order Creation**: Create orders from carts (recommended) or directly
- **Order Lines**: Individual items in the order with pricing and tax information
- **Order Addresses**: Shipping and billing addresses for orders
- **Transactions**: Payment transactions (charges and refunds) linked to orders
- **Order Status**: Track order status (draft/placed) and custom statuses
- **Order Reference**: Automatic reference generation with customizable generators
- **Order Validation**: Validate carts before order creation

**Usage**:

```php
use App\Lunar\Orders\OrderHelper;
use Lunar\Models\Order;
use Lunar\Models\Cart;
use Lunar\Facades\CartSession;

// Create order from cart (recommended)
$cart = CartSession::current();
$order = OrderHelper::createFromCart($cart);

// Check if cart can create order
if (OrderHelper::canCreateOrder($cart)) {
    $order = OrderHelper::createFromCart($cart);
}

// Find order
$order = OrderHelper::find($orderId);
$order = OrderHelper::findByReference('ORD-00000001');

// Get orders for user/customer
$userOrders = OrderHelper::getForUser($userId);
$customerOrders = OrderHelper::getForCustomer($customerId);

// Check order status
if (OrderHelper::isDraft($order)) {
    // Order is still a draft
}

if (OrderHelper::isPlaced($order)) {
    // Order has been placed
}

// Mark order as placed
$order = OrderHelper::markAsPlaced($order);

// Update order status
$order = OrderHelper::updateStatus($order, 'awaiting-payment');

// Get addresses
$shippingAddress = OrderHelper::getShippingAddress($order);
$billingAddress = OrderHelper::getBillingAddress($order);

// Create transaction (charge)
$transaction = OrderHelper::createTransaction($order, [
    'success' => true,
    'refund' => false,
    'driver' => 'stripe',
    'amount' => $order->total->value,
    'reference' => 'ch_1234567890',
    'status' => 'succeeded',
    'card_type' => 'visa',
    'last_four' => '4242',
]);

// Create refund transaction
$refund = OrderHelper::createTransaction($order, [
    'success' => true,
    'refund' => true,
    'driver' => 'stripe',
    'amount' => 1000, // Amount to refund in cents
    'reference' => 're_1234567890',
    'status' => 'succeeded',
]);

// Get transactions
$transactions = OrderHelper::getTransactions($order);
$charges = OrderHelper::getCharges($order);
$refunds = OrderHelper::getRefunds($order);

// Get transaction totals
$totalCharged = OrderHelper::getTotalCharged($order);
$totalRefunded = OrderHelper::getTotalRefunded($order);
$netAmount = OrderHelper::getNetAmount($order); // charged - refunded
```

**Order Fields**:

- `id` - Unique order ID
- `user_id` - User ID (nullable for guest orders)
- `customer_id` - Customer ID (nullable)
- `cart_id` - Related cart ID
- `channel_id` - Channel ID
- `status` - Order status (custom)
- `reference` - Store's order reference (auto-generated)
- `customer_reference` - Customer's own reference (nullable)
- `sub_total` - Subtotal minus discounts, excluding tax
- `discount_breakdown` - JSON discount breakdown
- `discount_total` - Discount amount excluding tax
- `shipping_breakdown` - JSON shipping breakdown
- `shipping_total` - Shipping total with tax
- `tax_breakdown` - JSON tax breakdown
- `tax_total` - Total tax amount
- `total` - Grand total with tax
- `notes` - Additional order notes
- `currency_code` - Currency code
- `compare_currency_code` - Default currency code at time of order
- `exchange_rate` - Exchange rate between currencies
- `placed_at` - Datetime when order was placed (null = draft)
- `meta` - JSON metadata
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

**Order Line Fields**:

- `id` - Line ID
- `order_id` - Order ID
- `purchasable_type` - Type of purchasable (e.g., product_variant)
- `purchasable_id` - ID of purchasable
- `type` - Line type (digital, physical, etc.)
- `description` - Description of the line item
- `option` - Variant option info (if applicable)
- `identifier` - Identifier (usually SKU)
- `unit_price` - Unit price
- `unit_quantity` - Line unit quantity (usually 1)
- `quantity` - Quantity purchased
- `sub_total` - Subtotal minus discounts, excluding tax
- `discount_total` - Discount amount excluding tax
- `tax_breakdown` - JSON tax breakdown
- `tax_total` - Total tax amount
- `total` - Grand total with tax
- `notes` - Additional notes
- `meta` - JSON metadata
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

**Order Address Fields**:

- `id` - Address ID
- `order_id` - Order ID
- `country_id` - Country ID
- `title` - Title (nullable)
- `first_name` - First name
- `last_name` - Last name
- `company_name` - Company name (nullable)
- `line_one` - Address line one
- `line_two` - Address line two (nullable)
- `line_three` - Address line three (nullable)
- `city` - City
- `state` - State/province (nullable)
- `postcode` - Postal code (nullable)
- `delivery_instructions` - Delivery instructions (nullable)
- `contact_email` - Contact email (nullable)
- `contact_phone` - Contact phone (nullable)
- `type` - Address type ('shipping' or 'billing')
- `shipping_option` - Shipping option identifier (nullable)
- `meta` - JSON metadata
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

**Transaction Fields**:

- `id` - Transaction ID
- `order_id` - Order ID
- `success` - Whether transaction was successful (boolean)
- `refund` - Whether this is a refund (boolean)
- `driver` - Payment driver (e.g., 'stripe')
- `amount` - Amount in cents/smallest currency unit
- `reference` - Reference from payment provider
- `status` - Status string (e.g., 'succeeded', 'settled')
- `notes` - Relevant notes
- `card_type` - Card type (e.g., 'visa')
- `last_four` - Last 4 digits of card
- `meta` - JSON metadata
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

**Order Status**:

- `$order->isDraft()` - Check if order is a draft (placed_at is null)
- `$order->isPlaced()` - Check if order is placed (placed_at is not null)

**Order Reference Generation**:

Order references are automatically generated when creating orders from carts. The default format is `{prefix?}{0..0}{orderId}` where `{0..0}` pads the order ID to 8 digits.

Customize the generator in `config/lunar/orders.php`:

```php
return [
    'reference_generator' => App\Generators\MyCustomGenerator::class,
];
```

The generator must implement `Lunar\Base\Contracts\OrderReferenceGeneratorInterface`.

**Order Validation**:

Before creating an order, validate the cart:

```php
if ($cart->canCreateOrder()) {
    $order = $cart->createOrder();
}
```

This uses the `ValidateCartForOrderCreation` validator, which can be customized in `config/lunar/cart.php`.

**Order Notifications**:

Configure order status notifications in `config/lunar/orders.php`:

```php
'statuses' => [
    'awaiting-payment' => [
        'label' => 'Awaiting Payment',
        'color' => '#848a8c',
        'mailers' => [
            App\Mail\OrderConfirmation::class,
        ],
        'notifications' => [],
    ],
],
```

When updating order status in the admin hub, these mailers become available for sending.

## Payments

This project implements payments following the [Lunar Payments documentation](https://docs.lunarphp.com/1.x/reference/payments):

- **Payment Drivers**: Driver-based payment system supporting multiple payment providers
- **Payment Types**: Configure different payment types (card, cash-in-hand, etc.) with different drivers
- **Payment Authorization**: Authorize payments through payment drivers
- **Payment Checks**: Access payment validation checks (3DSecure, credit checks, etc.)
- **Configuration**: Flexible payment configuration per payment type

**Configuration**:

Payment configuration is in `config/lunar/payments.php`:

```php
return [
    'default' => env('PAYMENTS_TYPE', 'offline'),
    
    'types' => [
        'cash-in-hand' => [
            'driver' => 'offline',
            'released' => 'payment-offline',
        ],
        'card' => [
            'driver' => 'stripe',
            'released' => 'payment-received',
        ],
    ],
];
```

**Usage**:

```php
use App\Lunar\Payments\PaymentHelper;
use Lunar\Facades\Payments;
use Lunar\Models\Cart;

// Get a payment driver
$driver = PaymentHelper::driver('card');
// or
$driver = Payments::driver('card');

// Get default payment driver
$defaultDriver = PaymentHelper::defaultDriver();

// Process a payment for a cart
$cart = CartSession::current();
$result = PaymentHelper::processPayment($cart, 'card', [
    'payment_token' => 'tok_1234567890',
]);

// Process payment with token (convenience method)
$result = PaymentHelper::processWithToken($cart, 'card', 'tok_1234567890');

// Manual payment processing
$driver = Payments::driver('card');
$driver->cart($cart);
$driver->withData([
    'payment_token' => $token,
]);
$result = $driver->authorize();

// Access payment checks from transaction
$transaction = $order->transactions()->first();
$checks = PaymentHelper::getPaymentChecks($transaction);

foreach ($checks as $check) {
    $check->successful; // bool
    $check->label;      // string
    $check->message;    // string
}

// Check if all payment checks were successful
if (PaymentHelper::allChecksSuccessful($transaction)) {
    // All checks passed
}

// Get payment type configuration
$config = PaymentHelper::getPaymentTypeConfig('card');
// Returns: ['driver' => 'stripe', 'released' => 'payment-received']

// Get released status for payment type
$releasedStatus = PaymentHelper::getReleasedStatus('card');
// Returns: 'payment-received'

// Check if payment type exists
if (PaymentHelper::paymentTypeExists('card')) {
    // Payment type is configured
}

// Get all available payment types
$types = PaymentHelper::getAvailablePaymentTypes();
// Returns: ['cash-in-hand', 'card']
```

**Payment Flow**:

1. Get payment driver: `Payments::driver($type)`
2. Set cart: `$driver->cart($cart)`
3. Set additional data: `$driver->withData([...])`
4. Authorize payment: `$driver->authorize()`
5. Handle result: The `authorize()` method returns a `PaymentAuthorize` DTO

**Payment Types**:

Each payment type in the configuration specifies:
- `driver`: The payment driver to use (e.g., 'stripe', 'offline')
- `released`: The order status to set when payment is released

**Payment Checks**:

Some payment providers return validation checks (e.g., 3DSecure, credit checks). Access these via:

```php
foreach ($transaction->paymentChecks() as $check) {
    $check->successful; // Whether the check passed
    $check->label;      // Check label
    $check->message;    // Check message/description
}
```

**Payment Addons**:

Lunar supports payment addons for integrating with payment providers. The most common addon is Stripe. See the [Stripe Payment Addon](#stripe-payment-addon) section below for installation and usage.

**Custom Payment Drivers**:

Create custom payment drivers by extending `AbstractPayment`. See the [Extending Lunar Payments documentation](https://docs.lunarphp.com/1.x/extending/payments) and the Extension Points section below for details.

The project includes example payment drivers:
- `CustomPayment.php` - Complete custom payment driver example with proper transaction handling
- `DummyPaymentProvider.php` - Dummy payment for development/testing

### Stripe Payment Addon

The Stripe payment addon enables Stripe payments on your Lunar storefront. This section covers installation, configuration, backend usage, storefront integration, and webhook setup.

**Installation**:

1. **Require the composer package**:

```bash
composer require lunarphp/stripe
```

2. **Publish the configuration**:

```bash
php artisan vendor:publish --tag=lunar.stripe.config
```

This publishes the configuration to `config/lunar/stripe.php`.

3. **Publish the views (optional)**:

If you want to customize the Stripe payment component views:

```bash
php artisan vendor:publish --tag=lunar.stripe.components
```

4. **Enable the driver**:

Uncomment the Stripe payment type in `config/lunar/payments.php`:

```php
'types' => [
    'card' => [
        'driver' => 'stripe',
        'released' => 'payment-received',
    ],
],
```

5. **Add Stripe credentials**:

Add your Stripe credentials to `.env`:

```
STRIPE_SECRET=sk_test_...
STRIPE_PK=pk_test_...
LUNAR_STRIPE_WEBHOOK_SECRET=whsec_...
```

Keys can be found in your Stripe account: https://dashboard.stripe.com/apikeys

The credentials are already configured in `config/services.php`:

```php
'stripe' => [
    'key' => env('STRIPE_SECRET'),
    'public_key' => env('STRIPE_PK'),
    'webhooks' => [
        'lunar' => env('LUNAR_STRIPE_WEBHOOK_SECRET'),
    ],
],
```

**Configuration**:

Configuration options in `config/lunar/stripe.php`:

| Key | Default | Description |
|-----|---------|-------------|
| `policy` | `automatic` | Payment capture policy: `automatic` (capture immediately) or `manual` (capture later) |
| `sync_addresses` | `true` | Sync billing and shipping addresses to Stripe PaymentIntent |
| `webhook_path` | `/stripe/webhook` | Path where Stripe webhooks will be received |

**Backend Usage**:

```php
use Lunar\Stripe\Facades\Stripe;
use Lunar\Models\Cart;
use Lunar\Stripe\Enums\CancellationReason;

// Create a PaymentIntent from a cart
$paymentIntent = Stripe::createIntent($cart, $options = []);

// Get the PaymentIntent ID from cart meta
$paymentIntentId = $cart->meta['payment_intent'];
// or
$paymentIntentId = $cart->meta->payment_intent;

// Fetch an existing PaymentIntent
$paymentIntent = Stripe::fetchIntent($paymentIntentId);

// Sync an existing intent when cart totals change
Stripe::syncIntent($cart);

// Update an existing intent with custom properties
Stripe::updateIntent($cart, [
    'shipping' => [/*...*/]
]);

// Cancel a PaymentIntent
Stripe::cancelIntent($cart, CancellationReason::ABANDONED);
// Available reasons: ABANDONED, DUPLICATE, REQUESTED_BY_CUSTOMER, FRAUDULENT

// Update shipping address on Stripe
Stripe::updateShippingAddress($cart);

// Retrieve a specific charge
$charge = Stripe::getCharge($chargeId);

// Get all charges for a payment intent
$charges = Stripe::getCharges($paymentIntentId);
```

**Storefront Integration**:

First, set up a backend API route to create or fetch the PaymentIntent:

```php
// routes/api.php or routes/web.php
use Lunar\Stripe\Facades\Stripe;
use Lunar\Facades\CartSession;
use Lunar\DataTransferObjects\CartData;

Route::post('/api/payment-intent', function () {
    $cart = CartSession::current();
    
    if (!$cart) {
        return response()->json(['error' => 'No cart found'], 404);
    }
    
    $cartData = CartData::from($cart);
    
    // Check if payment intent already exists
    if ($paymentIntentId = $cartData->meta['payment_intent'] ?? false) {
        $intent = Stripe::fetchIntent($paymentIntentId);
    } else {
        $intent = Stripe::createIntent($cart);
    }
    
    // Sync intent if cart total has changed
    if ($intent->amount != $cart->total->value) {
        Stripe::syncIntent($cart);
        $intent = Stripe::fetchIntent($cart->meta['payment_intent']);
    }
    
    return response()->json($intent);
})->middleware('web');
```

**Vue.js Example** (using Stripe Payment Elements):

```vue
<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { loadStripe } from '@stripe/stripe-js';

const { VITE_STRIPE_PK } = import.meta.env;
const stripe = await loadStripe(VITE_STRIPE_PK);
const stripeElements = ref(null);
const billingAddress = ref({}); // Get from cart/checkout form

const buildForm = async () => {
    const { data } = await axios.post('/api/payment-intent');
    
    stripeElements.value = stripe.elements({
        clientSecret: data.client_secret,
    });
    
    const paymentElement = stripeElements.value.create('payment', {
        layout: 'tabs',
        defaultValues: {
            billingDetails: {
                name: `${billingAddress.value.first_name} ${billingAddress.value?.last_name}`,
                phone: billingAddress.value?.contact_phone,
            },
        },
        fields: {
            billingDetails: 'never',
        },
    });
    
    paymentElement.mount('#payment-element');
};

onMounted(async () => {
    await buildForm();
});

const submit = async () => {
    try {
        const address = billingAddress.value; // Get from form
        
        const { error } = await stripe.confirmPayment({
            elements: stripeElements.value,
            confirmParams: {
                return_url: `${window.location.origin}/checkout/complete`,
                payment_method_data: {
                    billing_details: {
                        name: `${address.first_name} ${address.last_name}`,
                        email: address.contact_email,
                        phone: address.contact_phone,
                        address: {
                            city: address.city,
                            country: address.country.iso2,
                            line1: address.line_one,
                            line2: address.line_two,
                            postal_code: address.postcode,
                            state: address.state,
                        },
                    },
                },
            },
        });
        
        if (error) {
            console.error(error);
        }
    } catch (e) {
        console.error(e);
    }
};
</script>

<template>
    <form @submit.prevent="submit">
        <div id="payment-element">
            <!-- Stripe.js injects the Payment Element -->
        </div>
        <button type="submit">Submit Payment</button>
    </form>
</template>
```

**Webhooks**:

Set up a webhook in your Stripe dashboard to listen for these events:
- `payment_intent.payment_failed`
- `payment_intent.succeeded`

The webhook URL should be: `https://yoursite.com/stripe/webhook`

You can customize the webhook path in `config/lunar/stripe.php`:

```php
'webhook_path' => env('STRIPE_WEBHOOK_PATH', '/stripe/webhook'),
```

Add the webhook signing secret to your `.env`:

```
LUNAR_STRIPE_WEBHOOK_SECRET=whsec_...
```

**Manual Payment Processing** (without webhooks):

If you prefer not to use webhooks or want to manually process payments:

```php
use Lunar\Facades\Payments;
use Lunar\Facades\CartSession;

$cart = CartSession::current();

// With a draft order
$draftOrder = $cart->createOrder();
Payments::driver('stripe')
    ->order($draftOrder)
    ->withData([
        'payment_intent' => $draftOrder->meta['payment_intent'],
    ])
    ->authorize();

// Using just the cart
Payments::driver('stripe')
    ->cart($cart)
    ->withData([
        'payment_intent' => $cart->meta['payment_intent'],
    ])
    ->authorize();
```

**Extending**:

You can customize webhook event parameter processing by overriding the `ProcessesEventParameters` instance:

```php
// app/Providers/AppServiceProvider.php
use Lunar\Stripe\Concerns\ProcessesEventParameters;
use Lunar\Stripe\DataTransferObjects\EventParameters;

public function boot()
{
    $this->app->instance(ProcessesEventParameters::class, new class implements ProcessesEventParameters
    {
        public function handle(\Stripe\Event $event): EventParameters
        {
            $paymentIntentId = $event->data->object->id;
            // Setting $orderId to null will mean a new order is created
            $orderId = null;
            
            return new EventParameters($paymentIntentId, $orderId);
        }
    });
}
```

**Events**:

The Stripe addon dispatches events for various situations:

**CartMissingForIntent Event**:

Dispatched when attempting to process a payment intent, but no matching Order or Cart model can be found:

```php
use Lunar\Stripe\Events\Webhook\CartMissingForIntent;

Event::listen(CartMissingForIntent::class, function (CartMissingForIntent $event) {
    // Handle missing cart/order
    echo $event->paymentIntentId;
});
```

**Documentation**: See [Lunar Stripe Addon documentation](https://docs.lunarphp.com/1.x/addons/payments/stripe)

### PayPal Payment Addon

The PayPal payment addon enables PayPal payments on your Lunar storefront. This section covers installation, configuration, and usage.

**Installation**:

1. **Require the composer package**:

```bash
composer require lunarphp/paypal
```

2. **Enable the driver**:

Uncomment the PayPal payment type in `config/lunar/payments.php`:

```php
'types' => [
    'paypal' => [
        'driver' => 'paypal',
        'released' => 'payment-received',
    ],
],
```

3. **Add PayPal credentials**:

Add your PayPal credentials to `.env`:

```
PAYPAL_ENV=sandbox
PAYPAL_CLIENT_ID=your_client_id
PAYPAL_SECRET=your_secret
```

For production, set `PAYPAL_ENV=live`.

The credentials are already configured in `config/services.php`:

```php
'paypal' => [
    'env' => env('PAYPAL_ENV', 'sandbox'),
    'client_id' => env('PAYPAL_CLIENT_ID'),
    'secret' => env('PAYPAL_SECRET'),
],
```

You can create REST API credentials and Webhooks in the PayPal Developer Dashboard: https://developer.paypal.com/dashboard

**Configuration**:

Configuration options in `config/services.php`:

| Key | Default | Description |
|-----|---------|-------------|
| `env` | `sandbox` | PayPal environment: `sandbox` (testing) or `live` (production) |
| `client_id` | - | PayPal Client ID from developer dashboard |
| `secret` | - | PayPal Secret from developer dashboard |

**Usage**:

```php
use Lunar\Facades\Payments;
use Lunar\Facades\CartSession;

$cart = CartSession::current();

// Process PayPal payment
$response = Payments::driver('paypal')
    ->cart($cart)
    ->withData([
        'paypal_order_id' => $request->get('orderID'),
        'paypal_payment_id' => $request->get('paymentID'),
        'status' => 'payment-received',
    ])
    ->authorize();

if (!$response->success) {
    // Handle payment failure
    abort(401);
}

// Payment successful - order will be created automatically
$order = $response->order;
```

**Storefront Integration**:

1. **Integrate PayPal JavaScript SDK** in your checkout page:

```html
<script src="https://www.paypal.com/sdk/js?client-id=YOUR_CLIENT_ID&currency=USD"></script>
```

2. **Create PayPal buttons** and handle payment approval:

```javascript
paypal.Buttons({
    createOrder: function(data, actions) {
        // Create order on your backend
        return fetch('/api/paypal/create-order', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                cart_id: cartId,
            })
        })
        .then(response => response.json())
        .then(order => order.id);
    },
    onApprove: function(data, actions) {
        // Capture payment on your backend
        return fetch('/api/paypal/capture-order', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                orderID: data.orderID,
                paymentID: data.paymentID,
            })
        })
        .then(response => response.json())
        .then(details => {
            // Redirect to order confirmation page
            window.location.href = '/checkout/complete';
        });
    }
}).render('#paypal-button-container');
```

3. **Backend route to capture payment**:

```php
// routes/web.php or routes/api.php
use Lunar\Facades\Payments;
use Lunar\Facades\CartSession;

Route::post('/api/paypal/capture-order', function (Request $request) {
    $cart = CartSession::current();
    
    if (!$cart) {
        return response()->json(['error' => 'No cart found'], 404);
    }
    
    $response = Payments::driver('paypal')
        ->cart($cart)
        ->withData([
            'paypal_order_id' => $request->get('orderID'),
            'paypal_payment_id' => $request->get('paymentID'),
            'status' => 'payment-received',
        ])
        ->authorize();
    
    if (!$response->success) {
        return response()->json(['error' => 'Payment failed'], 400);
    }
    
    return response()->json([
        'success' => true,
        'order_id' => $response->order->id,
    ]);
})->middleware('web');
```

**Payment Flow**:

1. Customer clicks PayPal button on checkout page
2. PayPal SDK creates an order and redirects to PayPal
3. Customer approves payment on PayPal
4. PayPal redirects back with `orderID` and `paymentID`
5. Backend captures the payment using `Payments::driver('paypal')`
6. Lunar creates the order and transaction automatically
7. Redirect to order confirmation page

**Important Notes**:

- The PayPal addon handles order creation automatically when payment is authorized
- Make sure to validate the cart before processing PayPal payments
- The `orderID` and `paymentID` from PayPal must be included in `withData()`
- Set `status` to `'payment-received'` in the data array
- Use `sandbox` environment for testing with PayPal test accounts
- Switch to `live` environment for production

**Documentation**: See [Lunar PayPal Addon documentation](https://docs.lunarphp.com/1.x/addons/payments/paypal)

## Pricing

This project implements pricing following the [Lunar Pricing documentation](https://docs.lunarphp.com/1.x/reference/pricing):

- **Price Formatting**: Format prices for display using DefaultPriceFormatter or custom formatters
- **Price Data Types**: Work with `Lunar\DataTypes\Price` objects throughout the system
- **Pricing Facade**: Get prices for product variants with quantity and customer group support
- **Unit Quantities**: Support for products with unit quantities (e.g., 10 units = 1 product)
- **Currency Support**: Format prices according to currency decimal places and locale

**Configuration**:

Price formatting configuration is in `config/lunar/pricing.php`:

```php
return [
    'formatter' => \Lunar\Pricing\DefaultPriceFormatter::class,
];
```

**Usage**:

```php
use App\Lunar\Pricing\PricingHelper;
use Lunar\Facades\Pricing;
use Lunar\Models\Price;
use Lunar\Models\ProductVariant;
use Lunar\Models\Currency;

// Format a price value
$formatted = PricingHelper::format(1000, $currency); // e.g., "$10.00"
$formatted = PricingHelper::format(1000, $currency, 1, 'en-gb'); // e.g., "£10.00"
$formatted = PricingHelper::format(1000, $currency, 1, 'fr'); // e.g., "10,00 €"

// Format unit price (takes unit_quantity into account)
$unitFormatted = PricingHelper::formatUnit(1000, $currency, 10); // For 10 units

// Get decimal representation
$decimal = PricingHelper::toDecimal(1000, $currency); // 10.00
$decimal = PricingHelper::toDecimal(1000, $currency, 1, false); // Without rounding

// Get unit decimal (takes unit_quantity into account)
$unitDecimal = PricingHelper::toUnitDecimal(1000, $currency, 10); // 0.10 (for 10 units)

// Get pricing for a product variant using Pricing facade
$pricing = PricingHelper::getPricing($variant, quantity: 5);
$matchedPrice = $pricing->matched?->price; // PriceDataType object
$basePrice = $pricing->base?->price;
$priceBreaks = $pricing->priceBreaks; // Collection of price breaks
$customerGroupPrices = $pricing->customerGroupPrices; // Collection

// Get matched price directly
$price = PricingHelper::getPrice($variant, quantity: 5);

// Format a Price model
$priceModel = Price::find(1);
$formatted = PricingHelper::formatPriceModel($priceModel, 'en-gb');

// Format a PriceDataType instance
$priceDataType = $priceModel->price;
$formatted = PricingHelper::formatPriceDataType($priceDataType, 'en-gb');

// Convert between decimal and integer
$integer = PricingHelper::decimalToInteger(10.50, $currency); // 1050 (cents)
$decimal = PricingHelper::integerToDecimal(1050, $currency); // 10.50

// Using Pricing facade directly
$pricing = Pricing::qty(5)->for($variant)->get();
$price = $pricing->matched?->price;
$formatted = $price->formatted(); // Formatted price string
$decimal = $price->decimal(); // Decimal value
$unitDecimal = $price->unitDecimal(); // Unit decimal (with unit_quantity)
```

**Price Data Type Methods**:

The `Lunar\DataTypes\Price` class provides these methods:

- `value` - Raw integer value as stored in database
- `decimal(rounding: bool)` - Decimal representation
- `unitDecimal(rounding: bool)` - Unit decimal (takes unit_quantity into account)
- `formatted(locale, formatterStyle, decimalPlaces, trimTrailingZeros)` - Formatted currency string
- `unitFormatted(locale, formatterStyle, decimalPlaces, trimTrailingZeros)` - Formatted unit price string

**Price Formatting Examples**:

```php
$price = new \Lunar\DataTypes\Price(1000, $currency, 1);

// Raw value
$price->value; // 1000

// Decimal
$price->decimal(); // 10.00
$price->decimal(rounding: false); // 10.00 (no rounding)

// Unit decimal (for unit_quantity = 10)
$price = new \Lunar\DataTypes\Price(1000, $currency, 10);
$price->decimal(); // 0.10
$price->unitDecimal(); // 0.01 (per unit)

// Formatted
$price->formatted(); // "$10.00"
$price->formatted('en-gb'); // "£10.00"
$price->formatted('fr'); // "10,00 €"
$price->formatted('en-gb', \NumberFormatter::SPELLOUT); // "ten point zero zero"
```

**Models with Price Data Types**:

These models have price attributes that return `PriceDataType` objects:

- **Order**: `subTotal`, `total`, `taxTotal`, `discount_total`, `shipping_total`
- **OrderLine**: `unit_price`, `sub_total`, `tax_total`, `discount_total`, `total`
- **Transaction**: `amount`

**Custom Price Formatter**:

Create a custom price formatter by implementing `PriceFormatterInterface`:

```php
<?php

namespace App\Lunar\Pricing;

use Lunar\Pricing\PriceFormatterInterface;
use Lunar\Models\Currency;

class CustomPriceFormatter implements PriceFormatterInterface
{
    public function __construct(
        public int $value,
        public ?Currency $currency = null,
        public int $unitQty = 1
    ) {
        if (!$this->currency) {
            $this->currency = Currency::getDefault();
        }
    }

    public function decimal(): float
    {
        // Your custom decimal logic
    }

    public function unitDecimal(): float
    {
        // Your custom unit decimal logic
    }

    public function formatted(): mixed
    {
        // Your custom formatting logic
    }

    public function unitFormatted(): mixed
    {
        // Your custom unit formatting logic
    }
}
```

Register in `config/lunar/pricing.php`:

```php
return [
    'formatter' => \App\Lunar\Pricing\CustomPriceFormatter::class,
];
```

**Model Casting**:

Use Lunar's price cast for custom models:

```php
class MyModel extends Model
{
    protected $casts = [
        'price' => \Lunar\Base\Casts\Price::class,
    ];
}
```

This requires the column to return an integer value.

## Activity Log

This project implements activity logging following the [Lunar Activity Log documentation](https://docs.lunarphp.com/1.x/reference/activity-log):

- **Activity Tracking**: Automatic activity logging for Lunar models to track changes
- **Spatie Activity Log**: Uses Spatie's laravel-activitylog package for logging
- **Model History**: Full history of changes to tracked models
- **User Tracking**: Track who made changes to models
- **Custom Models**: Enable activity logging on your own models

**Overview**:

Lunar automatically logs activity for changes happening on Eloquent models throughout the system. This provides valuable insight into what's happening in your store and who is making changes.

**Configuration**:

Activity logging is handled by Spatie's laravel-activitylog package. Configuration is in `config/activitylog.php` (if published) or uses package defaults.

**Usage**:

```php
use App\Lunar\ActivityLog\ActivityLogHelper;
use Lunar\Models\Product;
use Spatie\Activitylog\Models\Activity;

// Get activity logs for a model
$product = Product::find(1);
$logs = ActivityLogHelper::getForModel($product);

// Get activity logs by event type
$createdLogs = ActivityLogHelper::getForModelByEvent($product, 'created');
$updatedLogs = ActivityLogHelper::getForModelByEvent($product, 'updated');
$deletedLogs = ActivityLogHelper::getForModelByEvent($product, 'deleted');

// Get activity logs for a user (causer)
$user = auth()->user();
$userLogs = ActivityLogHelper::getForCauser($user);

// Get activity logs by log name
$productLogs = ActivityLogHelper::getByLogName('product');
$orderLogs = ActivityLogHelper::getByLogName('order');

// Get recent activity logs
$recentLogs = ActivityLogHelper::getRecent(50);

// Check if model has activity logs
if (ActivityLogHelper::hasActivity($product)) {
    // Model has been logged
}

// Get latest activity log for a model
$latestLog = ActivityLogHelper::getLatestForModel($product);

// Get activity logs for a specific property change
$priceLogs = ActivityLogHelper::getForProperty($product, 'price');

// Using Spatie Activity directly
$activities = Activity::forSubject($product)->get();
$activities = Activity::causedBy($user)->get();
$activities = Activity::inLog('default')->get();
```

**Enabling Activity Log on Your Own Models**:

To enable activity logging on your own models, use Spatie's `LogsActivity` trait:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class YourModel extends Model
{
    use LogsActivity;

    /**
     * Configure activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Model {$eventName}");
    }
}
```

**Activity Model Properties**:

The `Activity` model provides several useful properties:

- `subject` - The model that was changed
- `causer` - The user who made the change (nullable)
- `description` - Description of the activity
- `event` - Event type (created, updated, deleted)
- `properties` - JSON containing old and new attributes
- `created_at` - When the activity occurred

**Accessing Activity Data**:

```php
$activity = Activity::find(1);

// Get the model that was changed
$subject = $activity->subject; // Returns the model instance

// Get who made the change
$causer = $activity->causer; // Returns the user model or null

// Get description
$description = $activity->description;

// Get event type
$event = $activity->event; // 'created', 'updated', 'deleted'

// Get old and new attributes
$old = $activity->properties->get('old');
$attributes = $activity->properties->get('attributes');

// Access specific property changes
$oldPrice = $activity->properties->get('old')['price'] ?? null;
$newPrice = $activity->properties->get('attributes')['price'] ?? null;
```

**Log Options**:

When implementing `getActivitylogOptions()` on your models, you can configure:

- `logOnly()` - Only log these attributes
- `logExcept()` - Log all except these attributes
- `logOnlyDirty()` - Only log attributes that actually changed
- `dontSubmitEmptyLogs()` - Skip logging if nothing changed
- `setDescriptionForEvent()` - Custom description for events
- `useLogName()` - Custom log name

**More Information**:

For detailed documentation on Spatie's laravel-activitylog package, visit:
https://spatie.be/docs/laravel-activitylog

## Currencies

This project implements currencies following the [Lunar Currencies documentation](https://docs.lunarphp.com/1.x/reference/currencies):

- **Currency Management**: Create and manage currencies with ISO 4217 codes
- **Exchange Rates**: Set exchange rates relative to the default currency
- **Decimal Places**: Configure decimal places for each currency
- **Default Currency**: Set and get the default currency
- **Currency Conversion**: Convert amounts between currencies

**Overview**:

Currencies allow you to charge different amounts relative to the currency you're targeting. Exchange rates are relative to the default currency, which should have an exchange_rate of 1.0000.

**Configuration**:

Currency configuration is handled through the `lunar_currencies` table and `Lunar\Models\Currency` model. The default currency is set with the `default` flag set to `true`.

**Usage**:

```php
use App\Lunar\Currencies\CurrencyHelper;
use Lunar\Models\Currency;

// Get default currency
$defaultCurrency = CurrencyHelper::getDefault();

// Get all enabled currencies
$enabledCurrencies = CurrencyHelper::getEnabled();

// Get all currencies
$currencies = CurrencyHelper::getAll();

// Find currency by ID or code
$currency = CurrencyHelper::find(1);
$currency = CurrencyHelper::findByCode('GBP');
$currency = CurrencyHelper::findByCode('USD');

// Create a new currency
$currency = CurrencyHelper::create(
    code: 'GBP',
    name: 'British Pound',
    exchangeRate: 1.0000, // Default currency should be 1.0000
    decimalPlaces: 2,
    enabled: true,
    default: true
);

// Create a non-default currency with exchange rate
// If GBP (default) to EUR rate is 1.17, then EUR exchange_rate = 1 / 1.17 = 0.8547
$eur = CurrencyHelper::create(
    code: 'EUR',
    name: 'Euro',
    exchangeRate: 0.8547, // Relative to default currency (GBP)
    decimalPlaces: 2,
    enabled: true,
    default: false
);

// Update exchange rate
CurrencyHelper::updateExchangeRate('EUR', 0.8500);

// Convert amount between currencies
$amountInEur = CurrencyHelper::convert(100, 'GBP', 'EUR');
$amountInGbp = CurrencyHelper::convert(100, 'EUR', 'GBP');

// Or using currency instances
$gbp = CurrencyHelper::findByCode('GBP');
$eur = CurrencyHelper::findByCode('EUR');
$converted = CurrencyHelper::convert(100, $gbp, $eur);

// Enable/disable currency
CurrencyHelper::enable('EUR');
CurrencyHelper::disable('EUR');

// Set default currency (automatically unsets previous default)
CurrencyHelper::setDefault('EUR');

// Check currency status
if (CurrencyHelper::isEnabled($currency)) {
    // Currency is enabled
}

if (CurrencyHelper::isDefault($currency)) {
    // Currency is the default
}
```

**Exchange Rates**:

Exchange rates are relative to the default currency. The default currency should always have an exchange_rate of 1.0000.

Example:
- Default currency: GBP with exchange_rate = 1.0000
- Current GBP to EUR rate: 1.17
- EUR exchange_rate should be: 1 / 1.17 = 0.8547

This means:
- 100 GBP = 100 / 1.0 * 0.8547 = 85.47 EUR (when converted through default)
- Or directly: 100 GBP * 1.17 = 117 EUR (if using direct rate)

**Currency Model Fields**:

- `code` - ISO 4217 currency code (e.g., 'GBP', 'USD', 'EUR')
- `name` - Currency name (e.g., 'British Pound', 'US Dollar')
- `exchange_rate` - Exchange rate relative to default currency (default should be 1.0000)
- `decimal_places` - Number of decimal places (typically 2)
- `enabled` - Whether the currency is enabled
- `default` - Whether this is the default currency

**Creating Currencies**:

```php
// Create default currency
$gbp = Currency::create([
    'code' => 'GBP',
    'name' => 'British Pound',
    'exchange_rate' => 1.0000,
    'decimal_places' => 2,
    'enabled' => true,
    'default' => true,
]);

// Create additional currency
$usd = Currency::create([
    'code' => 'USD',
    'name' => 'US Dollar',
    'exchange_rate' => 1.27, // If 1 GBP = 1.27 USD
    'decimal_places' => 2,
    'enabled' => true,
    'default' => false,
]);
```

**Note**: Exchange rates are independent of product pricing. You can specify prices per currency in Lunar's pricing system. The exchange rate serves as a helper when working with prices.

## Languages

This project implements languages following the [Lunar Languages documentation](https://docs.lunarphp.com/1.x/reference/languages):

- **Multi-language Support**: Support for multiple languages in Lunar
- **Default Language**: Set and get the default language (should only be one)
- **Language Codes**: Use ISO 2 character language codes (e.g., 'en', 'fr', 'de')
- **Translation Support**: Languages enable translation of data in Lunar models (e.g., attributes on Products and Collections)

**Overview**:

Lunar supports multiple languages. By default, Lunar is set to install a default language of "en" (English). Languages allow data in Lunar models to be translated, such as attributes on Products and Collections.

**Configuration**:

Language configuration is handled through the `lunar_languages` table and `Lunar\Models\Language` model. There should only ever be one default language. Setting more than one language as default will likely break things!

**Usage**:

```php
use App\Lunar\Languages\LanguageHelper;
use Lunar\Models\Language;

// Get default language
$defaultLanguage = LanguageHelper::getDefault();

// Get all languages
$languages = LanguageHelper::getAll();

// Find language by ID or code
$language = LanguageHelper::find(1);
$language = LanguageHelper::findByCode('en');
$language = LanguageHelper::findByCode('fr');

// Create a new language
$language = LanguageHelper::create(
    code: 'en',
    name: 'English',
    default: true
);

// Create additional languages
$french = LanguageHelper::create(
    code: 'fr',
    name: 'French',
    default: false
);

$german = LanguageHelper::create(
    code: 'de',
    name: 'German',
    default: false
);

// Set a language as default (automatically unsets previous default)
LanguageHelper::setDefault('fr');

// Check if language is default
if (LanguageHelper::isDefault($language)) {
    // This is the default language
}

// Check if language code exists
if (LanguageHelper::exists('en')) {
    // Language exists
}

// Get enabled languages (all languages by default)
$enabledLanguages = LanguageHelper::getEnabled();
```

**Language Model Fields**:

- `code` - Typically the ISO 2 character language code (e.g., 'en', 'fr', 'de')
- `name` - Descriptive name (e.g., 'English', 'French', 'German')
- `default` - Boolean specifying the default language for Lunar

**Creating Languages**:

```php
// Create default language
$english = Language::create([
    'code' => 'en',
    'name' => 'English',
    'default' => true,
]);

// Create additional languages
$french = Language::create([
    'code' => 'fr',
    'name' => 'French',
    'default' => false,
]);

$spanish = Language::create([
    'code' => 'es',
    'name' => 'Spanish',
    'default' => false,
]);
```

**Important Notes**:

- **Only One Default**: There should only ever be one default language. Setting more than one language as default will likely break things!
- **Default Language**: By default, Lunar installs "en" (English) as the default language
- **Translation Support**: Languages enable translation of attribute data in Lunar models (Products, Collections, etc.)
- **Language Codes**: Typically use ISO 2 character language codes (e.g., 'en', 'fr', 'de', 'es')

**Using Languages for Translations**:

Languages are used throughout Lunar for translating attribute data. For example, when creating products or collections with translated attributes:

```php
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Language;
use Lunar\Models\Product;

$english = Language::where('code', 'en')->first();
$french = Language::where('code', 'fr')->first();

$product = Product::create([
    'attribute_data' => [
        'name' => new TranslatedText([
            'en' => 'Product Name',
            'fr' => 'Nom du Produit',
        ]),
        'description' => new TranslatedText([
            'en' => 'Product Description',
            'fr' => 'Description du Produit',
        ]),
    ],
]);

// Retrieve translated attribute
$nameInEnglish = $product->translateAttribute('name', 'en');
$nameInFrench = $product->translateAttribute('name', 'fr');
```

## Tags

This project implements tags following the [Lunar Tags documentation](https://docs.lunarphp.com/1.x/reference/tags):

- **Tag Management**: Create and manage tags
- **Model Tagging**: Add tags to models using the HasTags trait
- **Tag Syncing**: Sync tags on models (runs via background job)
- **Dynamic Collections**: Tags can be used to create Dynamic Collections
- **Uppercase Conversion**: Tags are automatically converted to uppercase when saved

**Overview**:

Tags serve a simple function in Lunar - you can add tags to models. This is useful for relating otherwise unrelated models in the system. Tags will also impact other parts of the system such as Dynamic Collections. For example, you could have two products "Blue T-Shirt" and "Blue Shoes", which in their nature are unrelated, but you could add a `BLUE` tag to each product and then create a Dynamic Collection to include any products with a `BLUE` tag.

**Important**: Tags are converted to uppercase as they are saved.

**Configuration**:

Tag configuration is handled through the `lunar_tags` table and `Lunar\Models\Tag` model. Tags are automatically converted to uppercase when saved.

**Usage**:

```php
use App\Lunar\Tags\TagHelper;
use Lunar\Models\Tag;
use Lunar\Models\Product;

// Get all tags
$tags = TagHelper::getAll();

// Find tag by ID or name
$tag = TagHelper::find(1);
$tag = TagHelper::findByName('blue'); // Searches for 'BLUE'

// Find or create a tag
$tag = TagHelper::findOrCreate('blue'); // Creates 'BLUE' if it doesn't exist

// Create multiple tags
$tags = TagHelper::createMany(['blue', 'red', 'green']);
// Creates: 'BLUE', 'RED', 'GREEN'

// Sync tags on a model (model must use HasTags trait)
$product = Product::find(1);
TagHelper::syncTags($product, ['Tag One', 'Tag Two', 'Tag Three']);
// Tags are saved as: 'TAG ONE', 'TAG TWO', 'TAG THREE'
// Note: This runs via a job, so it will process in the background if queues are set up

// Add tags without removing existing ones
TagHelper::addTags($product, ['New Tag']);

// Remove specific tags
TagHelper::removeTags($product, ['Tag One']);

// Get tags for a model
$tags = TagHelper::getTagsForModel($product);
$tagNames = TagHelper::getTagNamesForModel($product); // Returns collection of tag values

// Check if model has a specific tag
if (TagHelper::hasTag($product, 'blue')) {
    // Product has the 'BLUE' tag (case-insensitive check)
}

// Clear all tags from a model
TagHelper::clearTags($product);

// Using model's tags relationship directly
$product->tags; // Returns Collection<Tag>
$product->tags->pluck('value'); // Returns Collection of tag values (uppercase)
```

**Enabling Tags on Your Models**:

To enable tagging on a model, add the `HasTags` trait:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Lunar\Base\Traits\HasTags;

class YourModel extends Model
{
    use HasTags;
}
```

Once the trait is added, you can sync tags:

```php
$model = YourModel::first();
$tags = collect(['Tag One', 'Tag Two', 'Tag Three']);

// Sync tags (runs via background job)
$model->syncTags($tags);

// Access tags
$model->tags; // Returns Collection<Tag>
// Tag values are uppercase: ['TAG ONE', 'TAG TWO', 'TAG THREE']
```

**Tag Model**:

The `Tag` model has the following properties:

- `id` - Tag ID
- `value` - Tag value (stored in uppercase)

**Important Notes**:

- **Uppercase Conversion**: Tags are automatically converted to uppercase when saved. "blue" becomes "BLUE"
- **Background Processing**: `syncTags()` runs via a job, so it will process in the background if queues are set up
- **Tag Reuse**: If a tag exists already by name, it will be reused. Otherwise, new tags will be created
- **Dynamic Collections**: Tags can be used to create Dynamic Collections that automatically include models with specific tags

**Using Tags with Dynamic Collections**:

Tags are particularly useful when combined with Dynamic Collections. You can create collections that automatically include products based on their tags:

```php
// Add tags to products
$product1->syncTags(['BLUE', 'SUMMER']);
$product2->syncTags(['BLUE', 'WINTER']);

// Create a Dynamic Collection that includes all products with 'BLUE' tag
// This would be configured in the admin panel or via Collection configuration
```

## Taxation

This project implements taxation following the [Lunar Taxation documentation](https://docs.lunarphp.com/1.x/reference/taxation):

- **Tax Classes**: Classify products into taxable groups with different tax rates
- **Tax Zones**: Geographic zones for tax rates (based on countries, states, or postcodes)
- **Tax Rates**: Multiple tax rates per zone (e.g., State tax + City tax)
- **Tax Rate Amounts**: Specific tax percentages for tax class and tax rate combinations
- **Tax Calculation**: Automatic tax calculation based on shipping/billing addresses

**Overview**:

Lunar provides manual tax rules to implement the correct sales tax for each order. For complex taxation (e.g., US States), you may want to integrate with a service such as TaxJar.

**Configuration**:

Tax configuration is in `config/lunar/taxes.php`:

```php
return [
    'driver' => 'system', // or your custom tax driver
];
```

**Usage**:

```php
use App\Lunar\Taxation\TaxHelper;
use Lunar\Models\TaxClass;
use Lunar\Models\TaxZone;
use Lunar\Models\TaxRate;
use Lunar\Models\Country;
use Lunar\Models\State;

// Get default tax zone
$defaultTaxZone = TaxHelper::getDefaultTaxZone();

// Get all active tax zones
$activeZones = TaxHelper::getActiveTaxZones();

// Create a tax class
$clothingTaxClass = TaxHelper::createTaxClass('Clothing');
$electronicsTaxClass = TaxHelper::createTaxClass('Electronics', default: true); // Optional: set as default

// Get all tax classes
$taxClasses = TaxHelper::getAllTaxClasses();

// Get default tax class
$defaultTaxClass = TaxHelper::getDefaultTaxClass();

// Create a tax zone
$ukTaxZone = TaxHelper::createTaxZone(
    name: 'UK',
    zoneType: 'country',
    priceDisplay: 'tax_inclusive',
    active: true,
    default: true
);

// Add country to tax zone
$country = Country::where('iso2', 'GB')->first();
TaxHelper::addCountryToTaxZone($ukTaxZone, $country);

// Add state to tax zone
$state = State::find(1);
TaxHelper::addStateToTaxZone($ukTaxZone, $state);

// Add postcode to tax zone (with wildcard support)
TaxHelper::addPostcodeToTaxZone($ukTaxZone, $country, '9021*');

// Add customer group to tax zone
$customerGroup = \Lunar\Models\CustomerGroup::find(1);
TaxHelper::addCustomerGroupToTaxZone($ukTaxZone, $customerGroup);

// Create a tax rate for a tax zone
$vatRate = TaxHelper::createTaxRate($ukTaxZone, 'UK VAT');

// Add tax rate amount (percentage) for a tax class
TaxHelper::addTaxRateAmount($vatRate, $clothingTaxClass, 20.0); // 20% VAT for clothing
TaxHelper::addTaxRateAmount($vatRate, $electronicsTaxClass, 20.0); // 20% VAT for electronics

// Get tax rates for a zone
$taxRates = TaxHelper::getTaxRatesForZone($ukTaxZone);

// Get tax rate amounts for a tax rate
$rateAmounts = TaxHelper::getTaxRateAmounts($vatRate);

// Get tax percentage for a specific tax rate and tax class
$percentage = TaxHelper::getTaxPercentage($vatRate, $clothingTaxClass); // Returns 20.0
```

**Tax Classes**:

Tax Classes are assigned to Products and allow classification of products into taxable groups that may have differing tax rates.

```php
// Create tax class
$taxClass = TaxClass::create([
    'name' => 'Clothing',
]);

// Assign to product variant
$variant->update(['tax_class_id' => $taxClass->id]);
```

**Tax Zones**:

Tax Zones specify geographic zones for tax rates to be applied. They can be based on countries, states, or postcodes.

```php
// Create tax zone
$taxZone = TaxZone::create([
    'name' => 'UK',
    'zone_type' => 'country', // 'country', 'states', or 'postcodes'
    'price_display' => 'tax_inclusive', // or 'tax_exclusive'
    'active' => true,
    'default' => true,
]);

// Add country to zone
$taxZone->countries()->create([
    'country_id' => Country::where('iso2', 'GB')->first()->id,
]);

// Add state to zone
$taxZone->states()->create([
    'state_id' => State::find(1)->id,
]);

// Add postcode to zone (wildcards supported)
$taxZone->postcodes()->create([
    'country_id' => Country::where('iso2', 'US')->first()->id,
    'postcode' => '9021*', // Wildcard example
]);

// Add customer group to zone
$taxZone->customerGroups()->create([
    'customer_group_id' => CustomerGroup::find(1)->id,
]);
```

**Tax Rates**:

Tax Zones have one or many tax rates. For example, you might have a tax rate for the State and also the City, which would collectively make up the total tax amount.

```php
// Create tax rate
$taxRate = TaxRate::create([
    'tax_zone_id' => $taxZone->id,
    'name' => 'UK VAT',
]);

// Add tax rate amount for a tax class
$taxRateAmount = TaxRateAmount::create([
    'tax_rate_id' => $taxRate->id,
    'tax_class_id' => $taxClass->id,
    'percentage' => 20.0, // 20% tax
]);
```

**Tax Zone Types**:

- **country**: Tax zone based on countries
- **states**: Tax zone based on states
- **postcodes**: Tax zone based on postcodes (supports wildcards)

**Price Display**:

- **tax_inclusive**: Prices include tax
- **tax_exclusive**: Prices exclude tax (tax added at checkout)

**Tax Calculation**:

Lunar automatically calculates tax based on:
- Shipping address or billing address (configurable in settings)
- Tax zone matching the address
- Tax rate(s) in the matched tax zone
- Tax class assigned to products
- Tax rate amount(s) for the tax class

**Settings**:

Tax settings can be configured in the admin panel:
- Shipping and other specific costs are assigned to tax classes
- Calculate tax based upon Shipping or Billing address
- Default Tax Zone

**Custom Tax Drivers**:

For complex taxation scenarios (e.g., US States), you can implement custom tax drivers. See the [Extending Lunar Taxation documentation](https://docs.lunarphp.com/1.x/extending/taxation) and the Extension Points section below for details.

The project includes example tax drivers and calculators:
- `CustomTaxDriver.php` - Complete custom tax driver example
- `StandardTaxCalculator.php` - Standard tax calculator (for use with system driver)

## Storefront Session

This project implements storefront session following the [Lunar Storefront Session documentation](https://docs.lunarphp.com/1.x/storefront-utils/storefront-session):

- **Channel Management**: Initialize, set, and get the current channel
- **Currency Management**: Initialize, set, and get the current currency
- **Customer Groups Management**: Initialize, set, and get current customer groups
- **Customer Management**: Initialize, set, and get the current customer
- **Session Persistence**: Storefront session state is persisted in the session

**Overview**:

The storefront session facade helps keep certain resources your storefront needs set, such as channel, customer group, customer, and currency. The session state is automatically initialized via middleware.

**Configuration**:

Storefront session is automatically initialized via the `StorefrontSessionMiddleware` registered in `bootstrap/app.php`. The middleware initializes:
- Channel (defaults to 'webstore' if available)
- Currency (defaults to 'USD' if available)
- Customer Groups (defaults to default customer groups)
- Customer (retrieved from logged-in user if available)

**Usage**:

```php
use App\Lunar\StorefrontSession\StorefrontSessionHelper;
use Lunar\Facades\StorefrontSession;
use Lunar\Models\Channel;
use Lunar\Models\Currency;
use Lunar\Models\Customer;
use Lunar\Models\CustomerGroup;

// === Channels ===

// Initialize the channel (sets based on session or uses default)
$channel = StorefrontSessionHelper::initChannel();

// Set the channel
StorefrontSessionHelper::setChannel('webstore');
// or
$channel = Channel::where('handle', 'webstore')->first();
StorefrontSessionHelper::setChannel($channel);

// Get the current channel
$currentChannel = StorefrontSessionHelper::getChannel();
// or directly via facade
$currentChannel = StorefrontSession::getChannel();

// === Currencies ===

// Initialize the currency (sets based on session or uses default)
$currency = StorefrontSessionHelper::initCurrency();

// Set the currency
StorefrontSessionHelper::setCurrency('USD');
// or
$currency = Currency::where('code', 'USD')->first();
StorefrontSessionHelper::setCurrency($currency);

// Get the current currency
$currentCurrency = StorefrontSessionHelper::getCurrency();
// or directly via facade
$currentCurrency = StorefrontSession::getCurrency();

// === Customer Groups ===

// Initialize customer groups (sets based on session or uses default)
$customerGroups = StorefrontSessionHelper::initCustomerGroups();

// Set customer groups (multiple)
$groups = CustomerGroup::whereIn('handle', ['retail', 'wholesale'])->get();
StorefrontSessionHelper::setCustomerGroups($groups);
// or as array
StorefrontSessionHelper::setCustomerGroups([$group1, $group2]);
// or as collection
StorefrontSessionHelper::setCustomerGroups(collect([$group1, $group2]));

// Set a single customer group
$retailGroup = CustomerGroup::where('handle', 'retail')->first();
StorefrontSessionHelper::setCustomerGroup($retailGroup);
// or directly via facade
StorefrontSession::setCustomerGroup($retailGroup);

// Get the current customer groups
$currentGroups = StorefrontSessionHelper::getCustomerGroups();
// or directly via facade
$currentGroups = StorefrontSession::getCustomerGroups();

// === Customer ===

// Initialize the customer (sets based on session or retrieves from logged-in user)
$customer = StorefrontSessionHelper::initCustomer();

// Set the customer
$customer = Customer::find(1);
StorefrontSessionHelper::setCustomer($customer);
// or directly via facade
StorefrontSession::setCustomer($customer);

// Get the current customer
$currentCustomer = StorefrontSessionHelper::getCustomer();
// or directly via facade
$currentCustomer = StorefrontSession::getCustomer();

// === Initialize All ===

// Initialize all storefront session components at once
$session = StorefrontSessionHelper::initAll();
// Returns: ['channel' => Channel, 'currency' => Currency, 'customerGroups' => Collection, 'customer' => Customer]
```

**Direct Facade Usage**:

You can also use the `StorefrontSession` facade directly:

```php
use Lunar\Facades\StorefrontSession;

// Channels
StorefrontSession::initChannel();
StorefrontSession::setChannel('webstore');
$channel = StorefrontSession::getChannel();

// Currencies
StorefrontSession::initCurrency();
StorefrontSession::setCurrency('USD');
$currency = StorefrontSession::getCurrency();

// Customer Groups
StorefrontSession::initCustomerGroups();
StorefrontSession::setCustomerGroups(collect($groups));
StorefrontSession::setCustomerGroup($singleGroup);
$groups = StorefrontSession::getCustomerGroups();

// Customer
StorefrontSession::initCustomer();
StorefrontSession::setCustomer($customer);
$customer = StorefrontSession::getCustomer();
```

**Initialization Behavior**:

- **Channel**: Sets based on session, otherwise uses default channel
- **Currency**: Sets based on session, otherwise uses default currency
- **Customer Groups**: Sets based on session, otherwise uses default customer groups
- **Customer**: Sets based on session, otherwise retrieves the latest customer attached to the logged-in user

**Middleware Integration**:

The `StorefrontSessionMiddleware` automatically initializes the storefront session on each request:

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(\App\Http\Middleware\StorefrontSessionMiddleware::class);
})
```

**Session Persistence**:

Storefront session state is automatically persisted in the Laravel session, so settings persist across requests until explicitly changed or the session expires.

**Common Use Cases**:

- **Currency Switching**: Allow users to switch currencies on the storefront
- **Channel Selection**: Switch between different sales channels (webstore, mobile app, etc.)
- **Customer Group Pricing**: Automatically set customer groups based on user authentication
- **Multi-tenant Stores**: Switch contexts based on subdomain or other routing logic

## Channels

This project implements channels following the [Lunar Channels documentation](https://docs.lunarphp.com/1.x/reference/channels):

- **Channel Assignment**: Assign models to different channels (e.g., webstore, mobile app)
- **Channel Scheduling**: Schedule models to be enabled/disabled on channels at specific dates
- **Channel Scopes**: Query models by channel with date range support
- **HasChannels Trait**: Enable channel functionality on your own models

**Overview**:

Channels allow you to organize and control availability of models (products, collections, etc.) across different channels. The default channel is `webstore`. Models can be assigned to channels permanently or scheduled for specific date ranges.

**Configuration**:

Channel configuration is handled through the `lunar_channels` table and `Lunar\Models\Channel` model. The default channel is typically named "webstore".

**Usage**:

```php
use App\Lunar\Channels\ChannelHelper;
use Lunar\Models\Channel;
use Lunar\Models\Product;

// Get default channel
$defaultChannel = ChannelHelper::getDefault();

// Get all channels
$channels = ChannelHelper::getAll();

// Find channel by ID or handle
$channel = ChannelHelper::find(1);
$channel = ChannelHelper::findByHandle('webstore');

// Schedule a model for a channel (model must use HasChannels trait)
$product = Product::find(1);
$channel = Channel::find(1);

// Schedule for immediate availability
ChannelHelper::scheduleChannelImmediate($product, $channel);

// Schedule for future availability
ChannelHelper::scheduleChannel(
    $product,
    $channel,
    startsAt: now()->addDays(14),
    endsAt: now()->addDays(24)
);

// Schedule for multiple channels
ChannelHelper::scheduleChannel($product, Channel::all());

// Query models by channel
$products = ChannelHelper::queryByChannel(Product::class, $channel)->get();

// Query models by multiple channels
$products = ChannelHelper::queryByChannels(Product::class, [$channel1, $channel2])->get();

// Query models for channel available on a specific date
$products = ChannelHelper::queryByChannel(
    Product::class,
    $channel,
    startDate: now()->addDay(),
    endDate: now()->addDay()
)->get();

// Query models for channel within date range
$products = ChannelHelper::queryByChannel(
    Product::class,
    $channel,
    startDate: now()->addDay(),
    endDate: now()->addDays(2)
)->get();

// Check if model is available for channel
if (ChannelHelper::isAvailableForChannel($product, $channel)) {
    // Product is available on this channel
}

// Get channels for a model
$productChannels = ChannelHelper::getChannelsForModel($product);

// Create a new channel
$newChannel = ChannelHelper::create(
    name: 'Mobile App',
    handle: 'mobile-app',
    default: false
);
```

## Currencies

This project implements currencies following the [Lunar Currencies documentation](https://docs.lunarphp.com/1.x/reference/currencies):

- **Currency Management**: Create and manage currencies with ISO 4217 codes
- **Exchange Rates**: Set exchange rates relative to the default currency
- **Decimal Places**: Configure decimal places for each currency
- **Default Currency**: Set and get the default currency
- **Currency Conversion**: Convert amounts between currencies

**Overview**:

Currencies allow you to charge different amounts relative to the currency you're targeting. Exchange rates are relative to the default currency, which should have an exchange_rate of 1.0000.

**Configuration**:

Currency configuration is handled through the `lunar_currencies` table and `Lunar\Models\Currency` model. The default currency is set with the `default` flag set to `true`.

**Usage**:

```php
use App\Lunar\Channels\ChannelHelper;
use Lunar\Models\Channel;
use Lunar\Models\Product;

// Get default channel
$defaultChannel = ChannelHelper::getDefault();

// Get all channels
$channels = ChannelHelper::getAll();

// Find channel by ID or handle
$channel = ChannelHelper::find(1);
$channel = ChannelHelper::findByHandle('webstore');

// Schedule a model for a channel (model must use HasChannels trait)
$product = Product::find(1);
$channel = Channel::find(1);

// Schedule for immediate availability
ChannelHelper::scheduleChannelImmediate($product, $channel);

// Schedule for future availability
ChannelHelper::scheduleChannel(
    $product,
    $channel,
    startsAt: now()->addDays(14),
    endsAt: now()->addDays(24)
);

// Schedule for multiple channels
ChannelHelper::scheduleChannel($product, Channel::all());

// Query models by channel
$products = ChannelHelper::queryByChannel(Product::class, $channel)->get();

// Query models by multiple channels
$products = ChannelHelper::queryByChannels(Product::class, [$channel1, $channel2])->get();

// Query models for channel available on a specific date
$products = ChannelHelper::queryByChannel(
    Product::class,
    $channel,
    startDate: now()->addDay(),
    endDate: now()->addDay()
)->get();

// Query models for channel within date range
$products = ChannelHelper::queryByChannel(
    Product::class,
    $channel,
    startDate: now()->addDay(),
    endDate: now()->addDays(2)
)->get();

// Check if model is available for channel
if (ChannelHelper::isAvailableForChannel($product, $channel)) {
    // Product is available on this channel
}

// Get channels for a model
$productChannels = ChannelHelper::getChannelsForModel($product);

// Create a new channel
$newChannel = ChannelHelper::create(
    name: 'Mobile App',
    handle: 'mobile-app',
    default: false
);
```

**Enabling Channels on Your Models**:

To enable channel functionality on your own models, add the `HasChannels` trait:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Lunar\Traits\HasChannels;

class YourModel extends Model
{
    use HasChannels;
}
```

Once the trait is added, you can use the `scheduleChannel` method and `channel` scope:

```php
// Schedule channel on the model
$model->scheduleChannel($channel);
$model->scheduleChannel($channel, startsAt: now()->addDays(7));
$model->scheduleChannel($channel, startsAt: now()->addDays(7), endsAt: now()->addDays(30));

// Query by channel using scope
$models = YourModel::channel($channel)->get();
$models = YourModel::channel([$channel1, $channel2])->get();
$models = YourModel::channel($channel, now()->addDay())->get();
$models = YourModel::channel($channel, now()->addDay(), now()->addDays(2))->get();
```

**Channel Scope Parameters**:

The `channel` scope accepts different parameter combinations:

- `channel($channel)` - Single channel, available now
- `channel([$channel1, $channel2])` - Multiple channels, available now
- `channel($channel, $startDate)` - Single channel, available from start date
- `channel($channel, $startDate, $endDate)` - Single channel, available within date range

**Channel Model**:

The `Channel` model has the following properties:

- `id` - Channel ID
- `name` - Channel name (e.g., "Webstore")
- `handle` - Channel handle/slug (e.g., "webstore")
- `default` - Whether this is the default channel
- `url` - Optional URL for the channel

**Default Channel**:

Lunar creates a default channel named "webstore" during installation. You can get it using:

```php
$defaultChannel = Channel::getDefault();
// or
$defaultChannel = ChannelHelper::getDefault();
```

## Extension Points

This project includes scaffolding for extending Lunar's functionality:

### Cart Extensions

Lunar allows you to extend cart functionality through **pipelines** and **validators**.

#### Pipelines

Pipelines modify cart behavior during calculation. They run in order from top to bottom.

**Location**: `app/Lunar/Cart/Pipelines/Cart/` and `app/Lunar/Cart/Pipelines/CartLine/`

**Example Pipeline** (`app/Lunar/Cart/Pipelines/Cart/CustomCartPipeline.php`):

```php
<?php

namespace App\Lunar\Cart\Pipelines\Cart;

use Closure;
use Lunar\Models\Cart;

class CustomCartPipeline
{
    public function handle(Cart $cart, Closure $next): Cart
    {
        // Add custom logic (e.g., apply custom discounts, modify metadata)
        return $next($cart); // Always call $next to continue pipeline
    }
}
```

**Registration**: Add to `config/lunar/cart.php`:

```php
'pipelines' => [
    'cart' => [
        // ... existing pipelines ...
        App\Lunar\Cart\Pipelines\Cart\CustomCartPipeline::class,
        // ... more pipelines ...
    ],
    'cart_lines' => [
        // ... existing pipelines ...
        App\Lunar\Cart\Pipelines\CartLine\ValidateCartLineStock::class,
    ],
],
```

#### Validators

Validators add validation logic for cart actions (e.g., adding items, updating quantities).

**Location**: `app/Lunar/Cart/Validation/CartLine/`

**Example Validator** (`app/Lunar/Cart/Validation/CartLine/CartLineQuantityValidator.php`):

```php
<?php

namespace App\Lunar\Cart\Validation\CartLine;

use Lunar\Validation\BaseValidator;

class CartLineQuantityValidator extends BaseValidator
{
    public function validate(): bool
    {
        $quantity = $this->parameters['quantity'] ?? 0;

        if ($quantity <= 0) {
            return $this->fail('cart', 'Quantity must be greater than zero');
        }

        return $this->pass();
    }
}
```

**Registration**: Add to `config/lunar/cart.php`:

```php
'validators' => [
    'add_to_cart' => [
        // ... existing validators ...
        App\Lunar\Cart\Validation\CartLine\CartLineQuantityValidator::class,
    ],
],
```

**Usage**: Validators automatically run when cart actions are executed. If validation fails, a `Lunar\Exceptions\CartException` is thrown:

```php
try {
    CartSession::add($variant, $quantity);
} catch (\Lunar\Exceptions\CartException $e) {
    $errors = $e->errors()->all();
    // Handle validation errors
}
```

**Available Actions**:
- `add_to_cart` - Validates when adding items to cart
- `update_cart_line` - Validates when updating cart line quantities
- `set_shipping_option` - Validates shipping options
- `order_create` - Validates cart before order creation

**Examples**:
- `CustomCartPipeline.php` - Custom cart pipeline example
- `CartLineQuantityValidator.php` - Cart line quantity validator example
- `ValidateCartLineStock.php` - Stock validation pipeline

**Documentation**: See [Extending Carts documentation](https://docs.lunarphp.com/1.x/extending/carts)

### Discount Extensions

Lunar allows you to create custom discount types to add additional functionality to discounts.

#### Custom Discount Types

**Location**: `app/Lunar/Discounts/DiscountTypes/`

**Example Discount Type** (`app/Lunar/Discounts/DiscountTypes/CustomPercentageDiscount.php`):

```php
<?php

namespace App\Lunar\Discounts\DiscountTypes;

use Lunar\Models\Cart;
use Lunar\DiscountTypes\AbstractDiscountType;

class CustomPercentageDiscount extends AbstractDiscountType
{
    public function getName(): string
    {
        return 'Custom Percentage Discount';
    }

    public function apply(Cart $cart): Cart
    {
        // Apply discount logic here
        return $cart;
    }
}
```

**Registration**: Register in `AppServiceProvider::boot()`:

```php
use Lunar\Facades\Discounts;
use App\Lunar\Discounts\DiscountTypes\CustomPercentageDiscount;

public function boot(): void
{
    Discounts::addType(CustomPercentageDiscount::class);
}
```

#### Admin Panel Integration

To add form fields for your discount in the Lunar admin panel, implement `LunarPanelDiscountInterface`:

**Example with Admin Panel** (`app/Lunar/Discounts/DiscountTypes/CustomPercentageDiscountWithAdmin.php`):

```php
<?php

namespace App\Lunar\Discounts\DiscountTypes;

use Filament\Forms;
use Lunar\Admin\Base\LunarPanelDiscountInterface;
use Lunar\DiscountTypes\AbstractDiscountType;

class CustomPercentageDiscountWithAdmin extends AbstractDiscountType implements LunarPanelDiscountInterface
{
    public function getName(): string
    {
        return 'Custom Percentage Discount (with Admin)';
    }

    public function apply(Cart $cart): Cart
    {
        // Apply discount using data from admin form
        $percentage = $this->discount->data['percentage'] ?? 10;
        // ... apply discount logic
        return $cart;
    }

    public function lunarPanelSchema(): array
    {
        return [
            Forms\Components\TextInput::make('data.percentage')
                ->label('Discount Percentage')
                ->numeric()
                ->required(),
        ];
    }

    public function lunarPanelOnFill(array $data): array
    {
        // Transform data before displaying in form
        return $data;
    }

    public function lunarPanelOnSave(array $data): array
    {
        // Transform data before saving
        return $data;
    }
}
```

**Key Methods**:
- `getName()`: Return the display name of the discount type
- `apply(Cart $cart)`: Apply discount logic to the cart (called before cart totals are calculated)
- `lunarPanelSchema()`: Define form fields for the admin panel (Filament form components)
- `lunarPanelOnFill(array $data)`: Transform data before displaying in admin form
- `lunarPanelOnSave(array $data)`: Transform data before saving to discount model

**Examples**:
- `CustomPercentageDiscount.php` - Basic custom discount type
- `CustomPercentageDiscountWithAdmin.php` - Custom discount type with admin panel integration

**Documentation**: See [Extending Discounts documentation](https://docs.lunarphp.com/1.x/extending/discounts)

### Model Extensions

Lunar provides a number of Eloquent Models and you can extend or replace them to add your own relationships and functionality.

#### Custom Models

All Lunar models are replaceable, meaning you can instruct Lunar to use your own custom model throughout the ecosystem using dependency injection.

**Location**: `app/Models/`

**Example Custom Models**:
- `Product.php` - Custom Product model with example relationships and methods
- `ProductVariant.php` - Custom ProductVariant model with example relationships and methods

#### Registration

Register your custom models in `AppServiceProvider::boot()`:

**Single Model Replacement**:

```php
use Lunar\Facades\ModelManifest;

public function boot(): void
{
    ModelManifest::replace(
        \Lunar\Models\Contracts\Product::class,
        \App\Models\Product::class,
    );
}
```

**Directory-Based Registration** (for multiple models):

If you have multiple models to replace, you can specify a directory. This assumes each model extends its Lunar counterpart:

```php
use Lunar\Facades\ModelManifest;

public function boot(): void
{
    ModelManifest::addDirectory(__DIR__.'/../Models');
}
```

#### Route Binding

Route binding is supported for your own routes by injecting the contract class:

```php
Route::get('products/{id}', function (\Lunar\Models\Contracts\Product $product) {
    // $product will be an instance of \App\Models\Product if registered
    return $product;
});
```

#### Relationship Support

When you replace a model used in relationships, you'll get your custom model back via relationship methods:

```php
// After registering custom ProductVariant model
$product = \Lunar\Models\Product::first();
$variant = $product->variants->first(); // Returns \App\Models\ProductVariant
```

#### Static Call Forwarding

You can call custom methods directly from the Lunar model instance:

```php
// After registering custom ProductVariant model
\Lunar\Models\ProductVariant::someCustomMethod(); // Calls \App\Models\ProductVariant::someCustomMethod()
\App\Models\ProductVariant::someCustomMethod(); // Same result
```

#### Observers

Observers registered on Lunar models will work with your custom models:

```php
\Lunar\Models\Product::observe(ProductObserver::class);
// Observer will work with \App\Models\Product if registered
```

#### Dynamic Eloquent Relationships

If you don't need to completely override Lunar models, you can add dynamic relationships:

```php
use Lunar\Models\Order;
use App\Models\Ticket;

Order::resolveRelationUsing('ticket', function ($orderModel) {
    return $orderModel->belongsTo(Ticket::class, 'ticket_id');
});
```

**Examples**:
- `Product.php` - Custom Product model with example relationships, methods, and scopes
- `ProductVariant.php` - Custom ProductVariant model with example relationships and accessors

**Documentation**: See [Extending Models documentation](https://docs.lunarphp.com/1.x/extending/models)

**Important Note**: Lunar highly suggests using your own Eloquent Models to add additional data, rather than trying to change fields on the core Lunar models.

### Order Extensions

Lunar allows you to extend the order creation process using pipelines.

#### Order Pipelines

Pipelines modify order behavior during creation. They run in order from top to bottom.

**Location**: `app/Lunar/Orders/Pipelines/OrderCreation/`

**Example Pipeline** (`app/Lunar/Orders/Pipelines/OrderCreation/CustomOrderPipeline.php`):

```php
<?php

namespace App\Lunar\Orders\Pipelines\OrderCreation;

use Closure;
use Lunar\Models\Order;

class CustomOrderPipeline
{
    public function handle(Order $order, Closure $next): Order
    {
        // Add custom logic (e.g., add metadata, send notifications, create related records)
        return $next($order); // Always call $next to continue pipeline
    }
}
```

**Registration**: Add to `config/lunar/orders.php`:

```php
'pipelines' => [
    'creation' => [
        // ... existing pipelines ...
        App\Lunar\Orders\Pipelines\OrderCreation\CustomOrderPipeline::class,
        // ... more pipelines ...
    ],
],
```

**Examples**:
- `CustomOrderPipeline.php` - Custom order pipeline example
- `ValidateOrderStock.php` - Stock validation pipeline (already implemented)

**Documentation**: See [Extending Orders documentation](https://docs.lunarphp.com/1.x/extending/orders)

### Payment Extensions

Lunar provides an easy way to add your own payment drivers. Payment drivers should handle capturing payments (immediately or later) and refunding existing payments.

#### Custom Payment Drivers

**Location**: `app/Lunar/Payments/PaymentProviders/`

**Example Payment Driver** (`app/Lunar/Payments/PaymentProviders/CustomPayment.php`):

```php
<?php

namespace App\Lunar\Payments\PaymentProviders;

use Lunar\Base\DataTransferObjects\PaymentAuthorize;
use Lunar\Base\DataTransferObjects\PaymentCapture;
use Lunar\Base\DataTransferObjects\PaymentRefund;
use Lunar\Base\PaymentTypes\AbstractPayment;
use Lunar\Events\PaymentAttemptEvent;
use Lunar\Models\Transaction;

class CustomPayment extends AbstractPayment
{
    public function authorize(): ?PaymentAuthorize
    {
        // Ensure order exists
        if (!$this->order) {
            if (!$this->order = $this->cart->order) {
                $this->order = $this->cart->createOrder();
            }
        }

        // Process payment with external provider
        // Create transaction (use 'intent' if not charging immediately, 'capture' if charging now)
        Transaction::create([
            'order_id' => $this->order->id,
            'success' => true,
            'type' => 'intent', // or 'capture'
            'driver' => 'custom',
            'amount' => $this->order->total->value,
            'reference' => 'CUSTOM_' . uniqid(),
            'status' => 'success',
        ]);

        $response = new PaymentAuthorize(
            success: true,
            message: 'Payment successful',
            orderId: $this->order->id,
            paymentType: 'custom'
        );
        
        PaymentAttemptEvent::dispatch($response);
        return $response;
    }

    public function capture(Transaction $transaction, int $amount = 0): PaymentCapture
    {
        // Capture payment and create capture transaction
        Transaction::create([
            'parent_transaction_id' => $transaction->id,
            'order_id' => $transaction->order_id,
            'type' => 'capture',
            // ... other fields
        ]);
        return new PaymentCapture(true);
    }

    public function refund(Transaction $transaction, int $amount = 0, ?string $notes = null): PaymentRefund
    {
        // Process refund and create refund transaction
        Transaction::create([
            'parent_transaction_id' => $transaction->id,
            'order_id' => $transaction->order_id,
            'type' => 'refund',
            // ... other fields
        ]);
        return new PaymentRefund(true);
    }
}
```

**Registration**: Register your driver in a service provider (e.g., `AppServiceProvider`):

```php
use Lunar\Facades\Payments;

public function boot(): void
{
    Payments::extend('custom', function ($app) {
        return $app->make(\App\Lunar\Payments\PaymentProviders\CustomPayment::class);
    });
}
```

**Configuration**: Configure the payment type in `config/lunar/payments.php`:

```php
'types' => [
    'custom' => [
        'driver' => 'custom', // Must match the driver name used in extend()
        'released' => 'payment-received', // Order status when payment is released
    ],
],
```

#### Available Helper Methods

The `AbstractPayment` class provides helpful methods:

- `cart(Cart $cart)`: Set the cart for the payment
- `order(Order $order)`: Set the order for the payment
- `withData(array $data)`: Add additional data (e.g., payment intent ID)
- `setConfig(array $config)`: Set additional configuration

**Usage Example**:

```php
use Lunar\Facades\Payments;

$driver = Payments::driver('custom')
    ->cart($cart)
    ->withData(['payment_intent' => $paymentIntentId])
    ->authorize();
```

#### Transaction Types

- **intent**: Payment authorized but not charged (will be captured later)
- **capture**: Payment has been charged
- **refund**: Payment has been refunded

#### Best Practices

**Releasing Payments**:
- If not charging immediately, create transaction with type `intent`
- If charging immediately, create transaction with type `capture`

**Capturing Payments**:
- Create a new transaction with type `capture`
- Reference the intent transaction via `parent_transaction_id`
- Even if capturing the full amount, still create a new transaction

**Refunding Payments**:
- Can only refund transactions with type `capture`
- Create a new transaction with type `refund`
- Reference the capture transaction via `parent_transaction_id`

**Examples**:
- `CustomPayment.php` - Complete custom payment driver example
- `DummyPaymentProvider.php` - Dummy payment for development/testing

**Documentation**: See [Extending Payments documentation](https://docs.lunarphp.com/1.x/extending/payments)

### Shipping Extensions

Lunar provides extensible shipping through shipping modifiers. Modifiers determine what shipping options are available for a cart.

#### Shipping Modifiers

**Location**: `app/Lunar/Shipping/Modifiers/`

**Example Modifier** (`app/Lunar/Shipping/Modifiers/CustomShippingModifier.php`):

```php
<?php

namespace App\Lunar\Shipping\Modifiers;

use Closure;
use Lunar\Base\ShippingModifier;
use Lunar\DataTypes\Price;
use Lunar\DataTypes\ShippingOption;
use Lunar\Facades\ShippingManifest;
use Lunar\Models\Cart;
use Lunar\Models\TaxClass;

class CustomShippingModifier extends ShippingModifier
{
    public function handle(Cart $cart, Closure $next): Cart
    {
        // Only add shipping options if cart has shippable items
        if ($cart->lines->isEmpty() || !$cart->hasShippableItems()) {
            return $next($cart);
        }

        $taxClass = TaxClass::first();

        // Add a basic delivery option
        ShippingManifest::addOption(
            new ShippingOption(
                name: 'Basic Delivery',
                description: 'A basic delivery option (5-7 business days)',
                identifier: 'BASDEL',
                price: new Price(500, $cart->currency, 1), // $5.00
                taxClass: $taxClass
            )
        );

        // Add an express delivery option
        ShippingManifest::addOption(
            new ShippingOption(
                name: 'Express Delivery',
                description: 'Express delivery option (1-2 business days)',
                identifier: 'EXDEL',
                price: new Price(1000, $cart->currency, 1), // $10.00
                taxClass: $taxClass
            )
        );

        // Add a pickup option (collect = true means customer picks up)
        ShippingManifest::addOption(
            new ShippingOption(
                name: 'Pick up in store',
                description: 'Pick your order up in store',
                identifier: 'PICKUP',
                price: new Price(0, $cart->currency, 1),
                taxClass: $taxClass,
                collect: true // Indicates this is a collection/pickup option
            )
        );

        // Alternative: Add multiple options at once
        // ShippingManifest::addOptions(collect([...]));

        return $next($cart);
    }
}
```

**Registration**: Register in a service provider (e.g., `AppServiceProvider`):

```php
use Lunar\Base\ShippingModifiers;

public function boot(ShippingModifiers $shippingModifiers)
{
    $shippingModifiers->add(
        \App\Lunar\Shipping\Modifiers\CustomShippingModifier::class
    );
}
```

**ShippingOption Properties**:

- `name`: Display name of the shipping option
- `description`: Description of the shipping option
- `identifier`: Unique identifier (must be unique across all options)
- `price`: `Price` object with amount, currency, and unit quantity
- `taxClass`: Tax class for calculating shipping tax
- `collect` (optional): Set to `true` for pickup/collection options

**Usage**:

Shipping modifiers are automatically called during cart calculation. Available shipping options can be accessed via the `ShippingManifest`:

```php
use Lunar\Facades\ShippingManifest;

// Get all available shipping options
$options = ShippingManifest::getOptions($cart);

// Set a shipping option on the cart
$cart->setShippingOption($option);
```

**Examples**:
- `CustomShippingModifier.php` - Complete custom shipping modifier example
- `FlatRateShippingCalculator.php` - Flat-rate shipping calculator (for tax calculations)

**Documentation**: See [Extending Shipping documentation](https://docs.lunarphp.com/1.x/extending/shipping)

### Taxation Extensions

Lunar provides extensible taxation through custom tax drivers. Tax drivers implement custom tax calculation logic, useful for integrating with external tax services or implementing complex tax rules.

#### Custom Tax Drivers

**Location**: `app/Lunar/Taxation/Drivers/`

**Example Tax Driver** (`app/Lunar/Taxation/Drivers/CustomTaxDriver.php`):

```php
<?php

namespace App\Lunar\Taxation\Drivers;

use Illuminate\Support\Collection;
use Lunar\Base\Purchasable;
use Lunar\Base\TaxDriver;
use Lunar\DataTypes\Address;
use Lunar\Models\Currency;

class CustomTaxDriver implements TaxDriver
{
    protected ?Address $shippingAddress = null;
    protected ?Address $billingAddress = null;
    protected ?Currency $currency = null;
    protected ?Purchasable $purchasable = null;

    public function setShippingAddress(?Address $address): self
    {
        $this->shippingAddress = $address;
        return $this;
    }

    public function setCurrency(Currency $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function setBillingAddress(?Address $address): self
    {
        $this->billingAddress = $address;
        return $this;
    }

    public function setPurchasable(?Purchasable $purchasable): self
    {
        $this->purchasable = $purchasable;
        return $this;
    }

    public function getBreakdown(int $subTotal): Collection
    {
        // Implement your tax calculation logic here
        // Example: Call external tax API, look up tax rates, etc.
        
        $taxRate = 0.20; // 20% tax rate
        $taxAmount = (int) ($subTotal * $taxRate);

        return collect([
            [
                'description' => 'Sales Tax',
                'identifier' => 'sales_tax',
                'percentage' => $taxRate * 100,
                'value' => $taxAmount,
            ],
        ]);
    }
}
```

**Registration**: Register in a service provider (e.g., `AppServiceProvider`):

```php
use Lunar\Facades\Taxes;

public function boot(): void
{
    Taxes::extend('custom', function ($app) {
        return $app->make(\App\Lunar\Taxation\Drivers\CustomTaxDriver::class);
    });
}
```

**Configuration**: Set the driver in `config/lunar/taxes.php`:

```php
return [
    'driver' => 'custom', // Use your custom driver
    // or
    'driver' => env('TAX_DRIVER', 'system'), // Allow environment-based configuration
];
```

#### Required Methods

Tax drivers must implement the `TaxDriver` interface with the following methods:

- `setShippingAddress(?Address $address)`: Set the shipping address for tax calculation
- `setCurrency(Currency $currency)`: Set the currency
- `setBillingAddress(?Address $address)`: Set the billing address
- `setPurchasable(?Purchasable $purchasable)`: Set the purchasable item
- `getBreakdown(int $subTotal)`: Return tax breakdown collection

#### Tax Breakdown Format

The `getBreakdown()` method should return a Collection of arrays, each containing:

- `description`: Description of the tax (e.g., "Sales Tax", "State Tax")
- `identifier`: Unique identifier for the tax (e.g., "sales_tax", "state_tax")
- `percentage`: Tax percentage (e.g., 20.0 for 20%)
- `value`: Tax amount in smallest currency unit (e.g., cents)

#### Use Cases

- **External Tax Services**: Integrate with services like TaxJar, Avalara, or Stripe Tax
- **Complex Tax Rules**: Implement business-specific tax calculations
- **Multi-Jurisdiction**: Handle taxes across multiple jurisdictions with different rules

**Examples**:
- `CustomTaxDriver.php` - Complete custom tax driver example
- `StandardTaxCalculator.php` - Standard tax calculator (for use with system driver)

**Documentation**: See [Extending Taxation documentation](https://docs.lunarphp.com/1.x/extending/taxation)

### Search Extensions

Lunar provides extensible search through custom indexers. Indexers control what fields are indexed for searching, sorting, and filtering.

#### Custom Search Indexers

**Location**: `app/Lunar/Search/Indexers/`

**Example Indexer** (`app/Lunar/Search/Indexers/CustomProductIndexer.php`):

```php
<?php

namespace App\Lunar\Search\Indexers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lunar\Models\Product;
use Lunar\Search\ScoutIndexer;

class CustomProductIndexer extends ScoutIndexer
{
    public function searchableAs(Model $model): string
    {
        return 'products'; // Custom index name
    }

    public function shouldBeSearchable(Model $model): bool
    {
        // Only index published products
        if ($model instanceof Product) {
            return $model->status === 'published';
        }
        return true;
    }

    public function makeAllSearchableUsing(Builder $query): Builder
    {
        // Optimize eager loading when indexing multiple models
        return $query->with([
            'variants',
            'brand',
            'media',
            'collections',
            'tags',
        ]);
    }

    public function getSortableFields(): array
    {
        return [
            'created_at',
            'updated_at',
            'price',
        ];
    }

    public function getFilterableFields(): array
    {
        return [
            '__soft_deleted',
            'status',
            'product_type_id',
            'brand_id',
        ];
    }

    public function toSearchableArray(Model $model, string $engine): array
    {
        if (!$model instanceof Product) {
            return [];
        }

        // Start with searchable attributes from hub
        $array = $this->mapSearchableAttributes($model);

        // Add custom fields
        $array = array_merge($array, [
            'id' => $model->id,
            'status' => $model->status,
            'skus' => $model->variants->pluck('sku')->toArray(),
            'collection_ids' => $model->collections->pluck('id')->toArray(),
            'tags' => $model->tags->pluck('value')->toArray(),
        ]);

        return $array;
    }
}
```

**Registration**: Map your indexer in `config/lunar/search.php`:

```php
'indexers' => [
    \Lunar\Models\Product::class => \App\Lunar\Search\Indexers\CustomProductIndexer::class,
],
```

#### Default Index Values

Lunar provides default indexers for various models. The `ProductIndexer` indexes:
- Model ID
- Searchable attributes (from hub)
- Product status
- Product type
- Brand (if applicable)
- Product variant SKUs
- Created at timestamp

#### Key Methods

- **`searchableAs()`**: Return the index name
- **`shouldBeSearchable()`**: Determine if model should be indexed
- **`makeAllSearchableUsing()`**: Optimize eager loading for batch indexing
- **`getSortableFields()`**: Fields available for sorting search results
- **`getFilterableFields()`**: Fields available for filtering search results
- **`toSearchableArray()`**: Define what data gets indexed
- **`mapSearchableAttributes()`**: Helper method to include searchable attributes from hub

#### Creating Custom Indexers

1. Extend `Lunar\Search\ScoutIndexer` (or implement `Lunar\Search\Interfaces\ModelIndexerInterface`)
2. Implement required methods for your use case
3. Map the indexer in `config/lunar/search.php`
4. Re-index your models: `php artisan scout:import "Lunar\Models\Product"`

**Examples**:
- `CustomProductIndexer.php` - Complete custom product indexer example

**Documentation**: See [Extending Search documentation](https://docs.lunarphp.com/1.x/extending/search)

**Note**: Lunar uses Laravel Scout for search. For custom search engines, you'll typically extend Scout engines rather than creating custom search drivers.

## Adding Real Payment Providers

To add a real payment provider (e.g., Stripe):

1. Install the Lunar Stripe package (if available):
```bash
composer require lunarphp/stripe
```

2. Or create your own provider by extending `AbstractPayment`:
```php
use Lunar\Base\PaymentTypes\AbstractPayment;

class StripePaymentProvider extends AbstractPayment
{
    public function authorize(): bool
    {
        // Implement Stripe authorization
    }
    
    public function refund(?int $amount = null): bool
    {
        // Implement Stripe refund
    }
}
```

3. Register it in `config/lunar/payments.php`

## Demo Data

The `LunarDemoSeeder` creates:
- Channels, Currencies, Languages
- Attribute Groups and Attributes (with proper FieldType objects)
- Product Types
- Collections
- Products with variants, prices, and URLs
- Tags
- Product Associations (cross-sell, up-sell, alternate)

### Attributes

The seeder demonstrates proper attribute usage following the [Lunar Attributes documentation](https://docs.lunarphp.com/1.x/reference/attributes):

- **Product attributes**: Name (TranslatedText), Description (Text), Material (Text), Weight (Number), Meta Title/Description (Text)
- **Variant attributes**: Size (Text), Color (Text) - filterable for faceted search
- **Attribute groups**: Main product group and SEO group

Example of accessing attributes:
```php
// Get translated name
$product->translateAttribute('name'); // Returns current locale
$product->translateAttribute('name', 'fr'); // Returns French translation

// Get other attributes
$product->translateAttribute('description');
$product->translateAttribute('weight'); // Number field type
```

See the [Lunar Attributes documentation](https://docs.lunarphp.com/1.x/reference/attributes) for complete details.

## Development

Run the development server:
```bash
php artisan serve
```

Run code style checks:
```bash
./vendor/bin/pint
./vendor/bin/pint --test
```

Run tests:
```bash
php artisan test
```

## Documentation

- [Lunar PHP Documentation](https://docs.lunarphp.com/)
- [Laravel Documentation](https://laravel.com/docs)

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

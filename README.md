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

This project implements attributes following the [Lunar Attributes documentation](https://docs.lunarphp.com/1.x/reference/attributes). Attributes allow you to associate custom data to Eloquent models, most commonly used with Products to store and present information to visitors.

**Overview**:

Attributes enable you to:
- Store custom data on models (Products, ProductVariants, Collections)
- Use different field types (Text, Number, TranslatedText, ListField)
- Organize attributes into logical groups (e.g., "Product", "SEO")
- Support multi-language content with TranslatedText
- Control searchability and filterability
- Define required fields and default values

**Attribute Model**:

```php
use Lunar\Models\Attribute;
```

| Field                | Description                                                                       |
|---------------------|-----------------------------------------------------------------------------------|
| `attribute_type`    | Morph map of the model type (e.g., 'product', 'product_variant', 'collection')   |
| `attribute_group_id`| The associated attribute group                                                   |
| `position`          | Integer for sorting order within attribute groups                                |
| `name`              | Laravel Collection of translations `{'en': 'Screen Size'}`                        |
| `handle`            | Kebab-cased reference (e.g., 'screen-size')                                      |
| `section`           | Optional name to define where attribute should be used                           |
| `type`              | The field type class (e.g., `Lunar\FieldTypes\Number`)                          |
| `required`          | Boolean indicating if field is required                                          |
| `default_value`     | Default value for the attribute                                                  |
| `configuration`     | Meta data stored as a Laravel Collection                                         |
| `system`            | If `true`, indicates it should not be deleted                                    |
| `searchable`        | Boolean indicating if attribute is searchable                                    |
| `filterable`        | Boolean indicating if attribute can be used for filtering                        |

**Field Types**:

Lunar provides several field types for attributes:

| Type                              | Description                              | Configuration Options                    |
|-----------------------------------|------------------------------------------|-------------------------------------------|
| `Lunar\FieldTypes\Number`         | Integer or Decimal values                | -                                         |
| `Lunar\FieldTypes\Text`           | Single-line, Multi-line, or Rich Text   | `richtext` (boolean)                      |
| `Lunar\FieldTypes\TranslatedText` | Multi-language text (single/multi/rich) | `richtext` (boolean)                      |
| `Lunar\FieldTypes\ListField`      | Re-orderable list of text values         | -                                         |

**Models that use Attributes**:

- `Lunar\Models\Product` - Product-level attributes
- `Lunar\Models\ProductVariant` - Variant-level attributes (e.g., color, size)
- `Lunar\Models\Collection` - Collection-level attributes

**Saving Attribute Data**:

Attribute data must be stored using proper FieldType objects:

```php
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\Number;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Product;

$product = Product::create([
    'product_type_id' => $productType->id,
    'status' => 'published',
    'attribute_data' => collect([
        'meta_title' => new Text('The best screwdriver you will ever buy!'),
        'pack_qty' => new Number(2),
        'description' => new TranslatedText(collect([
            'en' => new Text('Blue'),
            'fr' => new Text('Bleu'),
        ])),
    ]),
]);
```

**Using AttributeHelper**:

The `AttributeHelper` class provides convenience methods:

```php
use App\Lunar\Attributes\AttributeHelper;
use Lunar\Models\Product;

// Create field type values
$text = AttributeHelper::text('Some text');
$number = AttributeHelper::number(42);
$translated = AttributeHelper::translatedText([
    'en' => 'English text',
    'fr' => 'French text',
]);

// Get attribute value
$name = AttributeHelper::get($product, 'name');
$nameFr = AttributeHelper::get($product, 'name', 'fr');

// Check if attribute exists
if (AttributeHelper::has($product, 'weight')) {
    $weight = AttributeHelper::get($product, 'weight');
}

// Get all attributes as array
$allAttributes = AttributeHelper::all($product);
$allAttributesFr = AttributeHelper::all($product, 'fr');
```

**Accessing Attribute Data**:

When you access the `attribute_data` property, it's cast as a collection and resolved into corresponding field types:

```php
$product = Product::find(1);

// Access attribute_data collection
dump($product->attribute_data);

// Output:
// Illuminate\Support\Collection {
//   "name" => Lunar\FieldTypes\TranslatedText {
//     #value: Illuminate\Support\Collection {
//       "en" => Lunar\FieldTypes\Text { #value: "Leather boots" }
//       "fr" => Lunar\FieldTypes\Text { #value: "Bottes en cuires" }
//     }
//   }
//   "description" => Lunar\FieldTypes\Text {
//     #value: "<p>I'm a description!</p>"
//   }
// }
```

**Using translateAttribute()**:

The `translateAttribute()` method retrieves attribute values with automatic locale handling:

```php
// Get translated attribute (defaults to current locale or first available)
$name = $product->translateAttribute('name'); // "Leather boots"

// Get specific locale
$nameFr = $product->translateAttribute('name', 'fr'); // "Bottes en cuires"

// Falls back to default if locale not found
$nameFoo = $product->translateAttribute('name', 'FOO'); // "Leather boots" (fallback)
```

**Adding Attributes to Your Own Model**:

You can add attributes to any Eloquent model:

```php
use Illuminate\Database\Eloquent\Model;
use Lunar\Base\Casts\AsAttributeData;
use Lunar\Base\Traits\HasAttributes;

class MyCustomModel extends Model
{
    use HasAttributes;

    /**
     * Define which attributes should be cast.
     *
     * @var array
     */
    protected $casts = [
        'attribute_data' => AsAttributeData::class,
    ];
}
```

Then ensure your model's table has a JSON column called `attribute_data`:

```php
Schema::table('my_custom_models', function (Blueprint $table) {
    $table->json('attribute_data')->nullable();
});
```

**Advanced Usage**:

Models that use attributes must:

1. **Use the HasAttributes trait**:
```php
use Lunar\Base\Traits\HasAttributes;

class ProductType extends Model
{
    use HasAttributes;
}
```

2. **Cast attribute_data**:
```php
use Lunar\Base\Casts\AsAttributeData;

class Product extends Model
{
    protected $casts = [
        'attribute_data' => AsAttributeData::class,
    ];
}
```

**Attribute Groups**:

Attribute Groups organize attributes logically for display purposes (e.g., "SEO" group with "Meta Title" and "Meta Description").

```php
use Lunar\Models\AttributeGroup;

$seoGroup = AttributeGroup::create([
    'name' => ['en' => 'SEO'],
    'handle' => 'seo',
    'position' => 1,
]);
```

| Field    | Description                                           |
|----------|-------------------------------------------------------|
| `name`   | Laravel Collection of translations `{'en': 'SEO'}`   |
| `handle` | Kebab-cased reference (e.g., 'seo')                  |
| `position` | Integer for sorting order of groups                  |

**Creating Attributes**:

```php
use Lunar\Models\Attribute;
use Lunar\Models\AttributeGroup;

$productGroup = AttributeGroup::where('handle', 'product')->first();

// Create a Text attribute
$description = Attribute::create([
    'attribute_type' => 'product',
    'attribute_group_id' => $productGroup->id,
    'position' => 1,
    'name' => ['en' => 'Description'],
    'handle' => 'description',
    'section' => 'main',
    'type' => \Lunar\FieldTypes\Text::class,
    'required' => false,
    'searchable' => true,
    'filterable' => false,
    'system' => false,
    'default_value' => null,
    'configuration' => [
        'richtext' => true, // Enable rich text editor
    ],
]);

// Create a Number attribute
$weight = Attribute::create([
    'attribute_type' => 'product',
    'attribute_group_id' => $productGroup->id,
    'position' => 2,
    'name' => ['en' => 'Weight (kg)'],
    'handle' => 'weight',
    'section' => 'main',
    'type' => \Lunar\FieldTypes\Number::class,
    'required' => false,
    'searchable' => false,
    'filterable' => true,
    'system' => false,
]);

// Create a TranslatedText attribute
$name = Attribute::create([
    'attribute_type' => 'product',
    'attribute_group_id' => $productGroup->id,
    'position' => 0,
    'name' => ['en' => 'Name'],
    'handle' => 'name',
    'section' => 'main',
    'type' => \Lunar\FieldTypes\TranslatedText::class,
    'required' => true,
    'searchable' => true,
    'filterable' => false,
    'system' => true, // System attributes cannot be deleted
    'configuration' => [
        'richtext' => false,
    ],
]);
```

**Example: Product with Attributes**:

```php
use Lunar\Models\Product;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\Number;
use Lunar\FieldTypes\TranslatedText;

$product = Product::create([
    'product_type_id' => $productType->id,
    'status' => 'published',
    'attribute_data' => collect([
        'name' => new TranslatedText(collect([
            'en' => new Text('Premium Wireless Headphones'),
            'fr' => new Text('Écouteurs sans fil premium'),
        ])),
        'description' => new Text('High-quality wireless headphones with noise cancellation.'),
        'weight' => new Number(0.25), // 250g
        'material' => new Text('Plastic, Metal'),
        'meta_title' => new Text('Premium Wireless Headphones | Noise Cancelling Audio'),
        'meta_description' => new Text('Discover premium wireless headphones with active noise cancellation.'),
    ]),
]);

// Access attributes
$name = $product->translateAttribute('name'); // "Premium Wireless Headphones"
$nameFr = $product->translateAttribute('name', 'fr'); // "Écouteurs sans fil premium"
$weight = $product->translateAttribute('weight'); // 0.25
$description = $product->translateAttribute('description'); // "High-quality wireless headphones..."
```

**Best Practices**:

- **Eager Load Attributes**: When loading models, eager load the attribute data required:
  ```php
  Product::with('attributeData')->get();
  ```

- **Use Appropriate Field Types**: 
  - Use `TranslatedText` for multi-language content (names, descriptions)
  - Use `Text` for single-language content or HTML
  - Use `Number` for numeric values (weight, dimensions, quantities)
  - Use `ListField` for re-orderable lists

- **System Attributes**: Mark core attributes (like 'name') as `system: true` to prevent deletion

- **Searchable vs Filterable**:
  - Set `searchable: true` for attributes that should appear in search results
  - Set `filterable: true` for attributes that can be used as filters (e.g., color, size)

- **Attribute Groups**: Organize related attributes into groups (e.g., "Product", "SEO", "Shipping")

- **Sections**: Use the `section` field to organize attributes within the admin panel

**Documentation**: See [Lunar Attributes documentation](https://docs.lunarphp.com/1.x/reference/attributes)

## Products

This project implements products following the [Lunar Products documentation](https://docs.lunarphp.com/1.x/reference/products). Products are what you sell in your store, with attributes defined against them and variations (variants) available.

**Overview**:

Products in Lunar:
- Always have at least one variant (from a UX perspective, editing a product edits one variant)
- Belong to a `ProductType` which defines available attributes
- Have a base SKU and brand name (in addition to custom attributes)
- Support customer group scheduling
- Support product options for variant generation
- Support multiple pricing strategies (base, customer groups, quantity breaks)

**Creating a Product**:

```php
use Lunar\Models\Product;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;

$product = Product::create([
    'product_type_id' => $productTypeId,
    'status' => 'published', // or 'draft', 'pending'
    'brand_id' => $brandId, // Optional
    'attribute_data' => [
        'name' => new TranslatedText(collect([
            'en' => new Text('FooBar'),
            'fr' => new Text('FooBar FR'),
        ])),
        'description' => new Text('This is a Foobar product.'),
    ],
]);
```

**Customer Groups**:

You can assign customer groups to products, allowing you to either always have that product enabled for the customer group, or schedule specific dates when they should be active.

**Attaching Customer Groups**:

```php
use Lunar\Models\Product;
use Lunar\Models\CustomerGroup;

$product = Product::find(1);
$customerGroup = CustomerGroup::find(1);

// Schedule product to be enabled in 14 days for this customer group
$product->scheduleCustomerGroup($customerGroup, now()->addDays(14));

// Schedule the product to be enabled straight away
$product->scheduleCustomerGroup($customerGroup);

// Schedule multiple customer groups
$product->scheduleCustomerGroup(CustomerGroup::all());

// Schedule with start and end dates
$product->scheduleCustomerGroup($customerGroup, now()->addDays(7), now()->addDays(30));
```

**Retrieving Products for a Customer Group**:

Use the `customerGroup` scope to get all products related to a customer group:

```php
use Lunar\Models\Product;

// Single customer group (ID or model)
$products = Product::customerGroup(1)->paginate(50);

// Multiple customer groups (array or collection)
$products = Product::customerGroup([
    $groupA,
    $groupB,
])->paginate(50);

// Using helper
use App\Lunar\Products\ProductHelper;

$products = ProductHelper::forCustomerGroups($customerGroup)->paginate(50);
```

**Product Types**:

Product Types (e.g., Television, T-Shirt, Book, Phone) assign appropriate attributes to products. Products of the same type share the same attribute structure.

**Creating a Product Type**:

```php
use Lunar\Models\ProductType;

$productType = ProductType::create([
    'name' => 'Boots',
    'handle' => 'boots', // Optional, auto-generated if omitted
]);
```

**Associating Attributes to Product Types**:

Product Types have attributes associated to them, which determine what fields are available when editing products:

```php
$productType->mappedAttributes()->attach([1, 2, 3]); // Attribute IDs

// You can associate both Product and ProductVariant attributes
// Product attributes appear on the product
// ProductVariant attributes appear on the variant
```

**Retrieving the Product Type Relationship**:

```php
$product = Product::find(1);

// Get product type
$productType = $product->productType;

// Eager load
$product = Product::with('productType')->find(1);
```

**Product Identifiers**:

You can add product identifiers to each product variant. These fields allow you to identify products and variants for use in internal systems.

**Available Fields**:

| Field | Description |
|-------|-------------|
| **SKU** | Stock Keeping Unit - usually eight alphanumeric digits for tracking stock levels internally |
| **GTIN** | Global Trade Item Number - unique internationally recognized identifier (often with barcode) |
| **MPN** | Manufacturer Part Number - product identifier from brand/manufacturer |
| **EAN** | European Article Number - series of letters/numbers for inventory identification |

**Validation**:

Configure identifier validation in `config/lunar/products.php`:

```php
return [
    'sku' => [
        'required' => true,  // Set to true if SKU is required
        'unique' => false,   // Set to true if SKU must be unique
    ],
    'gtin' => [
        'required' => false,
        'unique' => false,
    ],
    'mpn' => [
        'required' => false,
        'unique' => false,
    ],
    'ean' => [
        'required' => false,
        'unique' => false,
    ],
];
```

**Product Options**:

Product Options define the different options a product has available (e.g., Color, Size). These are directly related to the different variants a product might have. Each `ProductOption` has multiple `ProductOptionValue` models.

Product options and values are defined at a system level and are translatable.

**Creating a ProductOption**:

```php
use Lunar\Models\ProductOption;

$option = ProductOption::create([
    'name' => [
        'en' => 'Colour',
        'fr' => 'Couleur',
    ],
    'label' => [
        'en' => 'Colour',
        'fr' => 'Couleur',
    ],
]);
```

**Creating Product Option Values**:

```php
use Lunar\Models\ProductOptionValue;

$option->values()->createMany([
    [
        'name' => [
            'en' => 'Blue',
            'fr' => 'Bleu',
        ],
    ],
    [
        'name' => [
            'en' => 'Red',
            'fr' => 'Rouge',
        ],
    ],
]);
```

**Using ProductOptionHelper**:

```php
use App\Lunar\Products\ProductOptionHelper;

// Create option with values
$colorOption = ProductOptionHelper::createOption('Colour', 'Colour', ['Red', 'Blue', 'Green']);

// Create values for existing option
ProductOptionHelper::createValues($option, ['Yellow', 'Purple']);

// Get product options
$options = ProductOptionHelper::getProductOptions($product);

// Get variant values
$values = ProductOptionHelper::getVariantValues($variant);
```

**Product Option Meta**:

Product Option Values can have meta data stored:

```php
$value->update([
    'meta' => [
        'hex' => '#FF0000', // Color hex code
        'image' => 'red.jpg',
    ],
]);
```

**Variants**:

Products always have at least one variant. Variants represent different variations of a product (e.g., different sizes, colors).

**Creating Variants**:

```php
use Lunar\Models\ProductVariant;
use Lunar\Models\Price;
use Lunar\Models\Currency;

$variant = ProductVariant::create([
    'product_id' => $product->id,
    'sku' => 'PROD-001',
    'gtin' => '1234567890123', // Optional
    'mpn' => 'MPN123', // Optional
    'ean' => 'EAN123', // Optional
    'tax_class_id' => $taxClass->id,
    'unit_quantity' => 1,
    'min_quantity' => 1,
    'quantity_increment' => 1,
    'stock' => 100,
    'backorder' => 0,
    'purchasable' => 'always', // 'always', 'in_stock', 'never'
    'shippable' => true,
    'attribute_data' => [
        'color' => new Text('Red'),
        'size' => new Text('Large'),
    ],
]);

// Create price for variant
Price::create([
    'priceable_type' => ProductVariant::class,
    'priceable_id' => $variant->id,
    'price' => 1999, // Price in smallest currency unit (cents for USD)
    'currency_id' => $currency->id,
    'tier' => 1,
]);
```

**Generating Variants from Options**:

You can automatically generate variants from product option values:

```php
use Lunar\Hub\Jobs\Products\GenerateVariants;
use App\Lunar\Products\ProductOptionHelper;

$product = Product::find(1);
$sizeOption = ProductOption::where('name->en', 'Size')->first();
$colorOption = ProductOption::where('name->en', 'Colour')->first();

// Get all option value IDs
$optionValueIds = $sizeOption->values->merge($colorOption->values)->pluck('id');

// Generate variants (dispatches a job)
GenerateVariants::dispatch($product, $optionValueIds->toArray());

// Or using helper
ProductOptionHelper::generateVariants($product, $optionValueIds);
```

When generating variants, the SKU will be derived from the Product's base SKU (if set) and suffixed with `-{count}`.

**Pricing**:

Lunar provides a comprehensive pricing system with support for base pricing, customer group pricing, quantity breaks, and tax-inclusive pricing.

**Overview**:

Pricing in Lunar supports:
- Base pricing per variant
- Customer group-specific pricing
- Quantity-based price breaks
- Tax-inclusive or tax-exclusive pricing
- Multiple currencies
- Custom pricing pipelines

**Price Formatting**:

Prices are stored in the smallest currency unit (e.g., cents for USD). Use the `Price` model's formatting methods:

```php
$price = Price::find(1);

// Get formatted price
$formatted = $price->price->formatted; // "$19.99"

// Get decimal value
$decimal = $price->price->decimal; // 19.99

// Get raw value (in smallest unit)
$raw = $price->price->value; // 1999
```

**Base Pricing**:

Create base prices for variants:

```php
use Lunar\Models\Price;
use Lunar\Models\ProductVariant;
use Lunar\Models\Currency;

Price::create([
    'priceable_type' => ProductVariant::class,
    'priceable_id' => $variant->id,
    'price' => 1999, // $19.99
    'currency_id' => $currency->id,
    'tier' => 1, // Base tier
]);
```

**Customer Group Pricing**:

Create customer group-specific prices:

```php
use Lunar\Models\Price;
use Lunar\Models\CustomerGroup;

Price::create([
    'priceable_type' => ProductVariant::class,
    'priceable_id' => $variant->id,
    'price' => 1799, // $17.99 for this customer group
    'currency_id' => $currency->id,
    'customer_group_id' => $customerGroup->id,
    'tier' => 1,
]);
```

**Price Break Pricing**:

Create quantity-based price breaks:

```php
// Base price (1-9 items)
Price::create([
    'priceable_type' => ProductVariant::class,
    'priceable_id' => $variant->id,
    'price' => 199, // $1.99
    'currency_id' => $currency->id,
    'tier' => 1,
    'min_quantity' => 1,
]);

// Price break (10+ items)
Price::create([
    'priceable_type' => ProductVariant::class,
    'priceable_id' => $variant->id,
    'price' => 150, // $1.50
    'currency_id' => $currency->id,
    'tier' => 1,
    'min_quantity' => 10,
]);
```

**Fetching the Price**:

Use the `Pricing` facade to fetch prices:

**Minimum Example**:

```php
use Lunar\Facades\Pricing;

// Quantity of 1 is implied when not passed
$pricing = Pricing::for($variant)->get();
```

**With Quantities**:

```php
$pricing = Pricing::qty(5)->for($variant)->get();
```

**With Customer Groups**:

```php
// Multiple customer groups
$pricing = Pricing::customerGroups($groups)->for($variant)->get();

// Single customer group
$pricing = Pricing::customerGroup($group)->for($variant)->get();

// If not passed, Lunar uses the default customer group
```

**Specific to a User**:

```php
// Guest price (always return guest price)
$pricing = Pricing::guest()->for($variant)->get();

// Specific user
$pricing = Pricing::user($user)->for($variant)->get();

// Current authenticated user (default behavior)
$pricing = Pricing::for($variant)->get();
```

**With a Specific Currency**:

```php
use Lunar\Models\Currency;

$currency = Currency::where('code', 'USD')->first();
$pricing = Pricing::currency($currency)->for($variant)->get();

// Default currency is implied if not passed
```

**For a Model**:

If your model implements the `HasPrices` trait (like `ProductVariant`):

```php
$pricing = $variant->pricing()->qty(5)->get();
```

**PricingResponse Object**:

The `get()` method returns a `PricingResponse` object:

```php
$pricing = Pricing::for($variant)->get();

// The price that was matched given the criteria
$pricing->matched; // Lunar\Models\Price

// The base price associated to the variant
$pricing->base; // Lunar\Models\Price

// Collection of all price quantity breaks available
$pricing->priceBreaks; // Collection<Price>

// All customer group pricing available
$pricing->customerGroupPrices; // Collection<Price>
```

**Getting All Prices for a Product**:

Instead of loading all variants and fetching prices, use the `prices` relationship:

```php
$product = Product::find(1);
$allPrices = $product->prices; // Collection of all Price models from all variants
```

**Using ProductHelper**:

```php
use App\Lunar\Products\ProductHelper;

// Get price for variant
$price = ProductHelper::getPrice($variant, 5); // quantity 5

// Get full pricing information
$pricing = ProductHelper::getPricing($variant, 1, $customerGroup);
// Returns PricingResponse with matched, base, priceBreaks, customerGroupPrices
```

**Storing Prices Inclusive of Tax**:

Lunar allows you to store pricing inclusive of tax for charm pricing (e.g., $9.99):

1. Set `stored_inclusive_of_tax` to `true` in `config/lunar/pricing.php`:

```php
return [
    'stored_inclusive_of_tax' => true,
    // ...
];
```

2. Ensure your default Tax Zone is set up correctly with tax rates

3. The cart will automatically calculate tax

4. Use these methods on the `Price` model:

```php
$price = Price::find(1);

// Get price including tax
$priceIncTax = $price->priceIncTax();

// Get price excluding tax
$priceExTax = $price->priceExTax();

// Get compare price including tax
$comparePriceIncTax = $price->comparePriceIncTax();
```

**Customising Prices with Pipelines**:

You can customize pricing using pipelines defined in `config/lunar/pricing.php`:

```php
// config/lunar/pricing.php
return [
    'pipelines' => [
        App\Pipelines\Pricing\CustomPricingPipeline::class,
    ],
];
```

**Example Pipeline**:

```php
<?php

namespace App\Pipelines\Pricing;

use Closure;
use Lunar\Base\PricingManagerInterface;

class CustomPricingPipeline
{
    public function handle(PricingManagerInterface $pricingManager, Closure $next)
    {
        $matchedPrice = $pricingManager->pricing->matched;

        // Modify the price
        $matchedPrice->price->value = 200;

        $pricingManager->pricing->matched = $matchedPrice;

        return $next($pricingManager);
    }
}
```

Pipelines run from top to bottom in the configuration order.

**Product Shipping**:

Products and variants can have shipping information (dimensions, weight) for shipping calculations.

**Product Dimensions**:

Configure measurement units in `config/lunar/products.php`:

```php
return [
    'dimensions' => [
        'unit' => 'cm', // or 'in', 'm', 'ft'
        'weight_unit' => 'kg', // or 'g', 'lb', 'oz'
    ],
];
```

**Getting and Converting Measurement Values**:

```php
$variant = ProductVariant::find(1);

// Get dimensions
$length = $variant->length_value; // Raw value
$width = $variant->width_value;
$height = $variant->height_value;
$weight = $variant->weight_value;

// Convert to different units (if conversion methods available)
// Note: Conversion methods depend on Lunar version
```

**Volume Calculation**:

Lunar can calculate volume from dimensions for shipping calculations.

**Full Example: Creating a Product with Variants**:

Here's a complete example creating Dr. Martens boots with multiple variants:

```php
use Lunar\Models\Product;
use Lunar\Models\ProductType;
use Lunar\Models\ProductOption;
use Lunar\Models\ProductOptionValue;
use Lunar\Models\ProductVariant;
use Lunar\Models\Price;
use Lunar\Models\Currency;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Hub\Jobs\Products\GenerateVariants;

// 1. Set up the product type
$productType = ProductType::create([
    'name' => 'Boots',
]);

// Associate attributes to product type (assuming attributes exist)
$productType->mappedAttributes()->attach([1, 2, 3]); // Attribute IDs

// 2. Create the initial product
$product = Product::create([
    'product_type_id' => $productType->id,
    'status' => 'published',
    'brand_id' => $brandId, // Optional
    'attribute_data' => [
        'name' => new TranslatedText(collect([
            'en' => new Text('1460 PATENT LEATHER BOOTS'),
        ])),
        'description' => new Text('Even more shades from our archive...'),
    ],
]);

// 3. Create Product Options
$colour = ProductOption::create([
    'name' => ['en' => 'Colour'],
    'label' => ['en' => 'Colour'],
]);

$size = ProductOption::create([
    'name' => ['en' => 'Size'],
    'label' => ['en' => 'Size'],
]);

// 4. Create Product Option Values
$colour->values()->createMany([
    ['name' => ['en' => 'Black']],
    ['name' => ['en' => 'White']],
    ['name' => ['en' => 'Pale Pink']],
    ['name' => ['en' => 'Mid Blue']],
]);

$size->values()->createMany([
    ['name' => ['en' => '3']],
    ['name' => ['en' => '6']],
    // ... more sizes
]);

// 5. Generate the variants
$optionValueIds = $size->values->merge($colour->values)->pluck('id');
GenerateVariants::dispatch($product, $optionValueIds->toArray());

// After variants are generated, set pricing for each variant
$currency = Currency::where('code', 'USD')->first();
$variants = $product->variants;

foreach ($variants as $variant) {
    Price::create([
        'priceable_type' => ProductVariant::class,
        'priceable_id' => $variant->id,
        'price' => 14999, // $149.99
        'currency_id' => $currency->id,
        'tier' => 1,
    ]);
}
```

**Best Practices**:

- **Always Create Variants**: Products must have at least one variant
- **Use Product Types**: Organize products by type for consistent attribute management
- **Eager Load Relationships**: When loading products, eager load variants, prices, and options:
  ```php
  Product::with(['variants.prices', 'productType', 'options.values'])->get();
  ```
- **Validate Identifiers**: Configure SKU/GTIN/MPN/EAN validation based on your needs
- **Use Price Breaks**: Implement quantity-based pricing for bulk discounts
- **Customer Group Pricing**: Use customer group pricing for B2B scenarios
- **Generate Variants**: Use the GenerateVariants job for products with multiple options

**Documentation**: See [Lunar Products documentation](https://docs.lunarphp.com/1.x/reference/products)

## Media

This project implements media handling following the [Lunar Media documentation](https://docs.lunarphp.com/1.x/reference/media). Lunar uses the [Spatie Laravel Media Library](https://spatie.be/docs/laravel-medialibrary) package for handling media across the platform.

**Overview**:

Lunar's media system provides:
- Image uploads and management using Spatie Media Library
- Automatic image conversions (thumbnails, sizes)
- Fallback images for models without media
- Custom media definitions for different models
- Support for Products and Collections
- FilePond integration for admin panel uploads

**Base Configuration**:

Configuration is managed by the Spatie Media Library package. You can optionally publish the configuration:

```bash
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="config"
```

**Supported Models**:

The following models currently support media:
- `Lunar\Models\Product`
- `Lunar\Models\Collection`

**Adding Media to Models**:

If you've used the Spatie Media Library package before, you'll feel right at home:

```php
use Lunar\Models\Product;

$product = Product::find(123);

// Add media from uploaded file
$product->addMedia($request->file('image'))->toMediaCollection('images');

// Add media from path
$product->addMediaFromUrl('https://example.com/image.jpg')->toMediaCollection('images');

// Add media with custom properties
$product->addMedia($file)
    ->withCustomProperties(['alt' => 'Product image'])
    ->toMediaCollection('images');
```

For more information, see [Associating files](https://spatie.be/docs/laravel-medialibrary/v10/basic-usage/associating-files) in the Spatie Media Library documentation.

**Fetching Images**:

```php
use Lunar\Models\Product;

$product = Product::find(123);

// Get all images from collection
$images = $product->getMedia('images');

// Get first image
$firstImage = $product->getFirstMedia('images');

// Get first image URL
$imageUrl = $product->getFirstMediaUrl('images');

// Get first image URL with conversion
$thumbnailUrl = $product->getFirstMediaUrl('images', 'thumb');

// Get first image path
$imagePath = $product->getFirstMediaPath('images');
```

For more information, see [Retrieving media](https://spatie.be/docs/laravel-medialibrary/v10/basic-usage/retrieving-media) in the Spatie Media Library documentation.

**Fallback Images**:

If your model does not contain any images, calling `getFirstMediaUrl()` or `getFirstMediaPath()` will return `null`. You can provide fallback paths/URLs in the config or `.env`:

**Configuration** (`config/lunar/media.php`):

```php
'fallback' => [
    'url' => env('FALLBACK_IMAGE_URL', null),
    'path' => env('FALLBACK_IMAGE_PATH', null),
]
```

**Environment Variables** (`.env`):

```env
FALLBACK_IMAGE_URL=https://example.com/images/placeholder.jpg
FALLBACK_IMAGE_PATH=/path/to/placeholder.jpg
```

**Media Collections & Conversions**:

Lunar provides a way to customize media collections and conversions for each model that implements the `HasMedia` trait. Default settings are in `config/lunar/media.php`.

**Custom Media Definitions**:

To create custom media definitions, implement the `MediaDefinitionsInterface`:

```php
use Lunar\Base\MediaDefinitionsInterface;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class CustomMediaDefinitions implements MediaDefinitionsInterface
{
    public function registerMediaConversions(HasMedia $model, Media $media = null): void
    {
        // Add a conversion for the admin panel
        $model->addMediaConversion('small')
            ->fit(Fit::Fill, 300, 300)
            ->sharpen(10)
            ->keepOriginalImageFormat();

        // Additional conversions for storefront
        $model->addMediaConversion('thumb')
            ->fit(Fit::Fill, 400, 400)
            ->sharpen(10)
            ->keepOriginalImageFormat();

        $model->addMediaConversion('medium')
            ->fit(Fit::Fill, 800, 800)
            ->sharpen(10)
            ->keepOriginalImageFormat();

        $model->addMediaConversion('large')
            ->fit(Fit::Fill, 1200, 1200)
            ->sharpen(10)
            ->keepOriginalImageFormat();
    }

    public function registerMediaCollections(HasMedia $model): void
    {
        $fallbackUrl = config('lunar.media.fallback.url');
        $fallbackPath = config('lunar.media.fallback.path');

        // Reset to avoid duplication
        $model->mediaCollections = [];

        $collection = $model->addMediaCollection('images');

        if ($fallbackUrl) {
            $collection = $collection->useFallbackUrl($fallbackUrl);
        }

        if ($fallbackPath) {
            $collection = $collection->useFallbackPath($fallbackPath);
        }

        $this->registerCollectionConversions($collection, $model);
    }

    protected function registerCollectionConversions(MediaCollection $collection, HasMedia $model): void
    {
        $conversions = [
            'zoom' => ['width' => 500, 'height' => 500],
            'large' => ['width' => 800, 'height' => 800],
            'medium' => ['width' => 500, 'height' => 500],
        ];

        $collection->registerMediaConversions(function (Media $media) use ($model, $conversions) {
            foreach ($conversions as $key => $conversion) {
                $model->addMediaConversion($key)
                    ->fit(Fit::Fill, $conversion['width'], $conversion['height'])
                    ->keepOriginalImageFormat();
            }
        });
    }

    public function getMediaCollectionTitles(): array
    {
        return [
            'images' => 'Images',
        ];
    }

    public function getMediaCollectionDescriptions(): array
    {
        return [
            'images' => '',
        ];
    }
}
```

**Register Custom Definitions**:

Register your custom class in `config/lunar/media.php`:

```php
return [
    'definitions' => [
        \Lunar\Models\Product::class => CustomMediaDefinitions::class,
        \Lunar\Models\Collection::class => CustomMediaDefinitions::class,
    ],
    
    'collection' => 'images',
    
    'fallback' => [
        'url' => env('FALLBACK_IMAGE_URL', null),
        'path' => env('FALLBACK_IMAGE_PATH', null),
    ],
];
```

**Generate Media Conversions**:

To regenerate conversions (e.g., if you've changed the configuration), run:

```bash
php artisan media-library:regenerate
```

This creates queue jobs for each media entry to be re-processed. More information can be found on the [Spatie Media Library website](https://spatie.be/docs/laravel-medialibrary/v10/converting-images/regenerating-images).

**Using MediaHelper**:

The `MediaHelper` class provides convenience methods:

```php
use App\Lunar\Media\MediaHelper;
use Lunar\Models\Product;

$product = Product::find(1);

// Get all images
$images = MediaHelper::getImages($product);

// Get first image URL
$imageUrl = MediaHelper::getFirstImageUrl($product, 'images', 'large');

// Get first image path
$imagePath = MediaHelper::getFirstImagePath($product, 'images', 'thumb');

// Add image
MediaHelper::addImage($product, $request->file('image'));

// Get thumbnail URL
$thumbnail = MediaHelper::getThumbnailUrl($product, 'thumb');

// Check if model has images
if (MediaHelper::hasImages($product)) {
    // Model has images
}

// Get all image URLs
$allUrls = MediaHelper::getAllImageUrls($product, 'images', 'large');
```

**Extending Your Own Models**:

You can extend your own models to use media, either by using Lunar's implementation or by implementing Spatie Media Library directly.

**Extending with Lunar**:

To enable image transformations on your models within Lunar, simply add the `HasMedia` trait:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Lunar\Base\Traits\HasMedia;

class YourCustomModel extends Model
{
    use HasMedia;
}
```

Now your models will auto-generate transforms as defined in your configuration and still use Spatie Media Library under the hood.

**Using Spatie Media Library Directly**:

If you want to use Spatie Media Library directly, follow their guides. However, you will not have access to Lunar's transformations or any other Lunar features.

**Example: Complete Media Workflow**:

```php
use Lunar\Models\Product;
use App\Lunar\Media\MediaHelper;

$product = Product::find(1);

// Add multiple images
foreach ($request->file('images') as $file) {
    MediaHelper::addImage($product, $file);
}

// Get all images with conversions
$images = $product->getMedia('images')->map(function ($media) {
    return [
        'id' => $media->id,
        'original' => $media->getUrl(),
        'thumb' => $media->getUrl('thumb'),
        'medium' => $media->getUrl('medium'),
        'large' => $media->getUrl('large'),
        'name' => $media->name,
    ];
});

// Get first image with fallback
$primaryImage = MediaHelper::getFirstImageUrl($product, 'images', 'large')
    ?? config('lunar.media.fallback.url');

// Delete specific image
$product->clearMediaCollection('images');
$product->deleteMedia($mediaId);
```

**Best Practices**:

- **Eager Load Media**: When loading models, eager load media for better performance:
  ```php
  Product::with('media')->get();
  ```

- **Use Conversions**: Always use conversions for different display sizes (thumb, medium, large) instead of resizing in the browser

- **Fallback Images**: Always configure fallback images for better UX when products don't have images

- **Custom Definitions**: Create custom media definitions for different model types if you need different conversions

- **Regenerate Conversions**: After changing conversion settings, run `php artisan media-library:regenerate`

- **File Validation**: Validate uploaded files before adding to models:
  ```php
  $request->validate([
      'image' => 'required|image|max:2048',
  ]);
  ```

**Documentation**: 
- See [Lunar Media documentation](https://docs.lunarphp.com/1.x/reference/media)
- See [Spatie Media Library documentation](https://spatie.be/docs/laravel-medialibrary)

## Collections

This project implements collections following the [Lunar Collections documentation](https://docs.lunarphp.com/1.x/reference/collections). Collections are similar to Categories and allow you to organize products either explicitly or via criteria (like tags) for use on your storefront.

**Overview**:

Collections enable you to:
- Organize products into logical groups (similar to categories)
- Create nested hierarchies with parent and child collections
- Add products explicitly or via criteria (e.g., tags)
- Sort products using various criteria
- Group collections into collection groups for flexibility (menus, landing pages)

**Collection Groups**:

Collection Groups organize collections logically. A collection must belong to a collection group.

**Create a Collection Group**:

```php
use Lunar\Models\CollectionGroup;

$group = CollectionGroup::create([
    'name' => 'Main Catalogue',
    'handle' => 'main-catalogue', // Will auto-generate if omitted
]);
```

**Collections**:

Collections are hierarchical models that have products associated with them. They use the Laravel Nested Set package for hierarchy management.

**Create a Collection**:

```php
use Lunar\Models\Collection;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;

$collection = Collection::create([
    'collection_group_id' => $group->id,
    'type' => 'static', // 'static' or 'dynamic' (for tag-based collections)
    'sort' => 'custom', // Sort type (see Sorting Products below)
    'attribute_data' => [
        'name' => new TranslatedText(collect([
            'en' => new Text('Clearance'),
            'fr' => new Text('Liquidation'),
        ])),
    ],
]);
```

**Add a Child Collection**:

Collections can have child collections, forming a nested hierarchy:

```php
use Lunar\Models\Collection;

// Create child collection
$child = Collection::create([
    'collection_group_id' => $group->id,
    'attribute_data' => [
        'name' => new TranslatedText(collect([
            'en' => new Text('Child Collection'),
        ])),
    ],
]);

// Add child to parent using appendNode (Laravel Nested Set)
$collection->appendNode($child);
```

This results in:
```
- Clearance
    - Child Collection
```

Lunar uses the [Laravel Nested Set](https://github.com/lazychaser/laravel-nestedset) package, so you can use all its methods for managing hierarchies:
- `appendNode()` - Add as last child
- `prependNode()` - Add as first child
- `insertAfter()` - Insert after sibling
- `insertBefore()` - Insert before sibling
- `children` - Get child collections
- `parent` - Get parent collection
- `ancestors` - Get all ancestors
- `descendants` - Get all descendants
- `breadcrumb` - Get breadcrumb path

**Adding Products**:

Products are related to collections using a `BelongsToMany` relationship with a pivot column for `position`:

```php
use Lunar\Models\Collection;
use Lunar\Models\Product;

$collection = Collection::find(1);

// Add products with positions
$products = [
    1 => ['position' => 1], // Product ID 1 at position 1
    2 => ['position' => 2], // Product ID 2 at position 2
    3 => ['position' => 3], // Product ID 3 at position 3
];

$collection->products()->sync($products);

// Or use syncWithoutDetaching to add without removing existing
$collection->products()->syncWithoutDetaching([
    4 => ['position' => 4],
]);

// Or attach a single product
$collection->products()->attach($productId, ['position' => 5]);

// Remove products
$collection->products()->detach([1, 2]);
```

**Sorting Products**:

Lunar provides several built-in sorting criteria for products in collections:

| Sort Type        | Description                                    |
|------------------|------------------------------------------------|
| `min_price:asc`  | Sort by minimum variant price (ascending)      |
| `min_price:desc` | Sort by minimum variant price (descending)     |
| `sku:asc`        | Sort by SKU (ascending)                        |
| `sku:desc`       | Sort by SKU (descending)                       |
| `custom`         | Manual position ordering (uses pivot position) |

**Setting Sort Type**:

```php
$collection->sort = 'min_price:asc';
$collection->save();
```

When you update products in a collection, Lunar automatically sorts them according to the collection's sort type.

**Using CollectionHelper**:

The `CollectionHelper` class provides convenience methods:

```php
use App\Lunar\Collections\CollectionHelper;
use Lunar\Models\Collection;

$collection = Collection::find(1);

// Get sorted products (respects collection's sort type)
$products = CollectionHelper::getSortedProducts($collection);

// Add products with positions
CollectionHelper::addProducts($collection, [
    1 => ['position' => 1],
    2 => ['position' => 2],
]);

// Create and add child collection
$child = Collection::create([/*...*/]);
CollectionHelper::addChildCollection($collection, $child);

// Get child collections
$children = CollectionHelper::getChildren($collection);

// Get breadcrumb path
$breadcrumb = CollectionHelper::getBreadcrumb($collection);
```

**Storefront Integration**:

The `CollectionController` demonstrates how to display collections:

```php
use Lunar\Models\Collection;
use Lunar\Models\Url;
use App\Lunar\Collections\CollectionHelper;

// Find collection by slug
$url = Url::where('slug', $slug)
    ->where('element_type', Collection::class)
    ->firstOrFail();

$collection = Collection::with(['group', 'children', 'media', 'urls'])
    ->findOrFail($url->element_id);

// Get sorted products
$products = CollectionHelper::getSortedProducts($collection);

// Get breadcrumb for navigation
$breadcrumb = $collection->breadcrumb;
```

**Collection Types**:

Collections can be:
- **Static**: Products are explicitly added to the collection
- **Dynamic**: Products are automatically included based on criteria (e.g., tags)

**Example: Complete Collection Setup**:

```php
use Lunar\Models\CollectionGroup;
use Lunar\Models\Collection;
use Lunar\Models\Product;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;

// 1. Create collection group
$group = CollectionGroup::create([
    'name' => 'Main Catalogue',
    'handle' => 'main-catalogue',
]);

// 2. Create parent collection
$electronics = Collection::create([
    'collection_group_id' => $group->id,
    'type' => 'static',
    'sort' => 'min_price:asc',
    'attribute_data' => [
        'name' => new TranslatedText(collect([
            'en' => new Text('Electronics'),
        ])),
    ],
]);

// 3. Create child collection
$phones = Collection::create([
    'collection_group_id' => $group->id,
    'type' => 'static',
    'sort' => 'sku:asc',
    'attribute_data' => [
        'name' => new TranslatedText(collect([
            'en' => new Text('Phones'),
        ])),
    ],
]);

// 4. Add child to parent
$electronics->appendNode($phones);

// 5. Add products with positions
$products = Product::whereIn('id', [1, 2, 3])->get();
$phones->products()->sync([
    1 => ['position' => 1],
    2 => ['position' => 2],
    3 => ['position' => 3],
]);

// 6. Get sorted products
$sortedProducts = CollectionHelper::getSortedProducts($phones);
```

**Best Practices**:

- **Collection Groups**: Use collection groups to organize collections for different purposes (menus, landing pages, navigation)
- **Nested Collections**: Use child collections to create category hierarchies
- **Sort Types**: Choose appropriate sort types based on your storefront needs
- **Eager Loading**: Always eager load relationships when displaying collections:
  ```php
  Collection::with(['group', 'children', 'products.variants.prices', 'urls'])->get();
  ```
- **Position Management**: Use positions for custom sorting to control product order
- **Dynamic Collections**: Consider using dynamic collections with tags for automatic product inclusion

**Database Schema**:

| Field                | Description                                    |
|----------------------|------------------------------------------------|
| `id`                 | Primary key                                    |
| `collection_group_id`| Foreign key to collection groups               |
| `type`               | Collection type ('static' or 'dynamic')        |
| `sort`               | Sort type (min_price:asc, sku:asc, custom, etc.) |
| `attribute_data`     | JSON attribute data                            |
| `_lft`, `_rgt`       | Nested set left/right values                  |
| `parent_id`          | Parent collection ID (for nested sets)         |
| `created_at`         | Timestamp                                      |
| `updated_at`         | Timestamp                                      |
| `deleted_at`         | Soft delete timestamp                          |

**Documentation**: See [Lunar Collections documentation](https://docs.lunarphp.com/1.x/reference/collections)

## Product Associations

This project implements product associations as described in the [Lunar Associations documentation](https://docs.lunarphp.com/1.x/reference/associations). Associations allow you to relate products to each other for cross-selling, up-selling, and showing alternatives.

**Overview**:

Associations enable you to:
- **Cross-sell**: Suggest complementary products (e.g., headphones with smartphones, cases with phones)
- **Up-sell**: Promote higher-value alternatives (e.g., premium versions, larger storage)
- **Alternate**: Show alternative product options (e.g., when out of stock or similar products)
- **Custom types**: Create your own association types for specific use cases

**Loading Associations**:

```php
use Lunar\Models\Product;

$product = Product::find(1);

// Get all associations
$associations = $product->associations;

// Each association provides:
$association->parent;  // The owning product
$association->target; // The associated product
$association->type;   // The association type (cross-sell, up-sell, alternate)
```

**Types of Association**:

### Cross Sell

Cross-selling encourages customers to purchase complementary products in addition to their original purchase.

**Creating Cross-sell Associations**:

```php
use Lunar\Models\Product;
use Lunar\Base\Enums\ProductAssociation as ProductAssociationEnum;

// Single product
$product->associate(
    $crossSellProduct,
    ProductAssociationEnum::CROSS_SELL
);

// Multiple products at once
$product->associate(
    [$productA, $productB],
    ProductAssociationEnum::CROSS_SELL
);
```

**Fetching Cross-sell Products**:

```php
// Via relationship scope
$crossSellProducts = $product->associations()
    ->crossSell()
    ->with('target.variants.prices', 'target.images')
    ->get()
    ->pluck('target');

// Via type scope
$crossSellProducts = $product->associations()
    ->type(ProductAssociationEnum::CROSS_SELL)
    ->get()
    ->pluck('target');
```

### Up Sell

Upselling encourages customers to upgrade or include add-ons to increase order value.

**Creating Up-sell Associations**:

```php
// Single product
$product->associate(
    $upSellProduct,
    ProductAssociationEnum::UP_SELL
);

// Multiple products
$product->associate(
    [$productA, $productB],
    ProductAssociationEnum::UP_SELL
);
```

**Fetching Up-sell Products**:

```php
// Via relationship scope
$upSellProducts = $product->associations()
    ->upSell()
    ->with('target.variants.prices', 'target.images')
    ->get()
    ->pluck('target');

// Via type scope
$upSellProducts = $product->associations()
    ->type(ProductAssociationEnum::UP_SELL)
    ->get()
    ->pluck('target');
```

### Alternate

Alternate products are alternatives to the current product, useful when products are out of stock or you want to show similar options.

**Creating Alternate Associations**:

```php
// Single product
$product->associate(
    $alternateProduct,
    ProductAssociationEnum::ALTERNATE
);

// Multiple products
$product->associate(
    [$productA, $productB],
    ProductAssociationEnum::ALTERNATE
);
```

**Fetching Alternate Products**:

```php
// Via relationship scope
$alternateProducts = $product->associations()
    ->alternate()
    ->with('target.variants.prices', 'target.images')
    ->get()
    ->pluck('target');

// Via type scope
$alternateProducts = $product->associations()
    ->type(ProductAssociationEnum::ALTERNATE)
    ->get()
    ->pluck('target');
```

### Custom Types

You can create custom association types beyond the built-in ones:

```php
// Create custom association type
$product->associate(
    $targetProduct,
    'my-custom-type'
);

// Fetch custom type associations
$customAssociations = $product->associations()
    ->type('my-custom-type')
    ->get()
    ->pluck('target');
```

**Removing Associations**:

```php
use Lunar\Base\Enums\ProductAssociation as ProductAssociationEnum;

// Remove all associations for a product
$product->dissociate($associatedProduct);

// Remove from multiple products (array or collection)
$product->dissociate([$productA, $productB]);

// Remove only specific association type
$product->dissociate(
    $associatedProduct,
    ProductAssociationEnum::CROSS_SELL
);
```

**Synchronous vs Asynchronous Operations**:

Lunar provides two ways to create associations:

1. **Asynchronous (Queued)**: `Product::associate()` dispatches a job (recommended for web requests)
2. **Synchronous**: Use `AssociationManager` for immediate creation (recommended for seeders, commands)

**Using AssociationManager (Synchronous)**:

```php
use App\Lunar\Associations\AssociationManager;
use Lunar\Base\Enums\ProductAssociation as ProductAssociationEnum;

$manager = new AssociationManager();

// Associate single product
$manager->associate(
    $product,
    $targetProduct,
    ProductAssociationEnum::CROSS_SELL
);

// Associate multiple products
$manager->associate(
    $product,
    [$productA, $productB],
    ProductAssociationEnum::UP_SELL
);

// Dissociate products
$manager->dissociate($product, $targetProduct);
$manager->dissociate($product, $targetProduct, ProductAssociationEnum::CROSS_SELL);
```

**Storefront Integration**:

The `ProductController` demonstrates how to load and display associations:

```php
use Lunar\Models\Product;

$product = Product::with([
    'associations.target.variants.prices',
    'associations.target.images',
])->find($id);

// Get associations by type
$crossSell = $product->associations()
    ->crossSell()
    ->with('target.variants.prices', 'target.images')
    ->get()
    ->pluck('target');

$upSell = $product->associations()
    ->upSell()
    ->with('target.variants.prices', 'target.images')
    ->get()
    ->pluck('target');

$alternate = $product->associations()
    ->alternate()
    ->with('target.variants.prices', 'target.images')
    ->get()
    ->pluck('target');
```

**API Management**:

The `ProductAssociationController` provides API endpoints for managing associations:

- `GET /api/products/{product}/associations` - Get all associations
- `POST /api/products/{product}/associations` - Create association
- `DELETE /api/products/{product}/associations/{targetProduct}` - Remove association

**Database Schema**:

| Field                    | Description                      |
|--------------------------|----------------------------------|
| `id`                     | Primary key                      |
| `product_parent_id`      | The owning product               |
| `product_target_id`      | The associated product           |
| `type`                   | Association type (cross-sell, up-sell, alternate, or custom) |
| `created_at`             | Timestamp                        |
| `updated_at`             | Timestamp                        |

**Example Use Cases**:

1. **E-commerce Storefront**: Display "Customers also bought" (cross-sell), "Upgrade to Pro" (up-sell), "Similar products" (alternate)
2. **Product Recommendations**: Use associations to power recommendation engines
3. **Bundle Suggestions**: Cross-sell complementary products to create bundles
4. **Stock Alternatives**: Show alternate products when items are out of stock
5. **Upsell Campaigns**: Promote premium versions or add-ons during checkout

**Best Practices**:

- Use cross-sell for complementary products that enhance the original purchase
- Use up-sell for higher-value alternatives that increase order value
- Use alternate for similar products or when showing alternatives
- Eager load associations with `->with('target.variants.prices', 'target.images')` for better performance
- Use `AssociationManager` in seeders and commands to avoid queued jobs
- Use `Product::associate()` in web requests for better performance (queued)

## Search

This project implements search following the [Lunar Search documentation](https://docs.lunarphp.com/1.x/reference/search). Search is configured using the Laravel Scout package, which provides search out of the box and makes it easy to customize and tailor searching to your needs.

**Overview**:

Lunar's search system:
- Uses Laravel Scout for search functionality
- Supports multiple search engines (database, Meilisearch, Algolia, etc.)
- Allows different models to use different search engines
- Provides custom indexers for controlling how data is indexed
- Handles soft deletes automatically

**Initial Setup**:

The database driver provides basic search to get you up and running, but you'll likely want to implement something with more power, such as Meilisearch or Algolia.

**Configuration**:

1. **Set Scout Driver in `.env`**:

```env
SCOUT_DRIVER=database  # or meilisearch, algolia, etc.
SCOUT_SOFT_DELETE=true  # Required by Lunar - prevents soft-deleted models from appearing in search
```

2. **Publish Scout Config (Optional)**:

```bash
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
```

Then ensure `soft_delete` is set to `true` in `config/scout.php`:

```php
'soft_delete' => env('SCOUT_SOFT_DELETE', true),
```

**Important**: By default, Scout has `soft_delete` set to `false`. You **must** set this to `true` otherwise you will see soft-deleted models appear in your search results.

3. **Configure Models for Indexing**:

In `config/lunar/search.php`, you can specify which models should be indexed:

```php
'models' => [
    // These models are required by the system, do not change them.
    \Lunar\Models\Collection::class,
    \Lunar\Models\Product::class,
    \Lunar\Models\ProductOption::class,
    \Lunar\Models\Order::class,
    \Lunar\Models\Customer::class,
    \Lunar\Models\Brand::class,
    
    // Below you can add your own models for indexing
    // App\Models\Example::class,
],
```

4. **Configure Indexers**:

Map model classes to their indexer classes in `config/lunar/search.php`:

```php
'indexers' => [
    \Lunar\Models\Product::class => \Lunar\Search\ProductIndexer::class,
    \Lunar\Models\Collection::class => \Lunar\Search\CollectionIndexer::class,
    // Use custom indexer for products
    // \Lunar\Models\Product::class => \App\Lunar\Search\Indexers\CustomProductIndexer::class,
],
```

**Index Records**:

If you installed Lunar in an existing project and want to use the database records with the search engine, or you need to do maintenance on the indexes, use the index command:

```bash
php artisan lunar:search:index
```

This command will import the records of the models listed in `config/lunar/search.php`. Use `--help` to see available options:

```bash
php artisan lunar:search:index --help
```

**Meilisearch Setup**:

If you're using the Meilisearch package, use the command to create filterable and searchable attributes on Meilisearch indexes:

```bash
php artisan lunar:meilisearch:setup
```

This sets up the proper configuration for Meilisearch indexes, including:
- Searchable attributes
- Filterable attributes
- Sortable attributes
- Ranking rules

**Engine Mapping**:

By default, Scout will use the driver defined in your `.env` file as `SCOUT_DRIVER`. So if that's set to `meilisearch`, all your models will be indexed via the Meilisearch driver.

This can present issues if you want to use a service like Algolia for Products - you wouldn't want all your Orders being indexed there since it will ramp up the record count and the cost.

Lunar allows you to define what driver you want to use per model. It's all defined in `config/lunar/search.php`:

```php
'engine_map' => [
    \Lunar\Models\Product::class => 'algolia',
    \Lunar\Models\Order::class => 'meilisearch',
    \Lunar\Models\Collection::class => 'meilisearch',
    \Lunar\Models\Customer::class => 'database',
],
```

If a model class isn't added to the config, it will use the Scout default (from `SCOUT_DRIVER`).

**Usage**:

**Basic Search**:

```php
use Lunar\Models\Product;

// Search products using Scout
$products = Product::search('query')
    ->where('status', 'published')
    ->paginate(12);
```

**Using SearchHelper**:

```php
use App\Lunar\Search\SearchHelper;

// Search products with pagination
$products = SearchHelper::searchProducts('query', 12, 1);

// Get search results as collection (without pagination)
$results = SearchHelper::searchProductsCollection('query', 10);

// Check if search is configured
if (SearchHelper::isConfigured()) {
    // Search is ready to use
}

// Get current Scout driver
$driver = SearchHelper::getDriver();
```

**Advanced Search**:

```php
use Lunar\Models\Product;

// Search with filters (Meilisearch/Algolia)
$products = Product::search('query')
    ->where('status', 'published')
    ->where('product_type_id', 1)
    ->where('brand_id', 5)
    ->paginate(12);

// Search with sorting
$products = Product::search('query')
    ->orderBy('created_at', 'desc')
    ->paginate(12);

// Search with eager loading
$products = Product::search('query')
    ->with(['variants.prices', 'media', 'collections'])
    ->paginate(12);
```

**Storefront Integration**:

The `SearchController` demonstrates how to implement search in your storefront:

```php
use App\Http\Controllers\Storefront\SearchController;
use Illuminate\Http\Request;

// In your routes
Route::get('/search', [SearchController::class, 'index']);

// The controller handles:
// - Query parameter extraction
// - Scout search with fallback to database
// - Pagination
// - Error handling
```

**Available Drivers**:

| Driver | Description | Setup Required |
|--------|-------------|----------------|
| `database` | Basic database search (default) | No setup required |
| `meilisearch` | Full-text search engine | Requires Meilisearch installation |
| `algolia` | Cloud search service | Requires Algolia account and API keys |
| Custom | Custom search implementation | Requires custom driver implementation |

**Meilisearch Setup**:

1. Install Meilisearch:
```bash
# Using Docker
docker run -d -p 7700:7700 getmeili/meilisearch:latest

# Or install via package manager
# See: https://www.meilisearch.com/docs/learn/getting_started/installation
```

2. Configure in `.env`:
```env
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=your_master_key  # Optional, for production
```

3. Set up indexes:
```bash
php artisan lunar:meilisearch:setup
```

**Algolia Setup**:

1. Get Algolia credentials from your Algolia dashboard

2. Configure in `.env`:
```env
SCOUT_DRIVER=algolia
ALGOLIA_APP_ID=your_app_id
ALGOLIA_SECRET=your_secret_key
```

3. Install Algolia Scout driver:
```bash
composer require algolia/algoliasearch-client-php
```

**Custom Indexers**:

Create custom indexers to control how models are indexed. The project includes a `CustomProductIndexer` at `app/Lunar/Search/Indexers/CustomProductIndexer.php` as an example.

**Register Custom Indexer**:

In `config/lunar/search.php`:

```php
'indexers' => [
    \Lunar\Models\Product::class => \App\Lunar\Search\Indexers\CustomProductIndexer::class,
],
```

**Custom Indexer Example**:

```php
use Lunar\Search\ScoutIndexer;
use Lunar\Models\Product;

class CustomProductIndexer extends ScoutIndexer
{
    public function searchableAs(Model $model): string
    {
        return 'products';
    }

    public function shouldBeSearchable(Model $model): bool
    {
        // Only index published products
        return $model->status === 'published';
    }

    public function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with(['variants', 'media', 'collections', 'tags']);
    }

    public function getSortableFields(): array
    {
        return ['created_at', 'updated_at', 'price'];
    }

    public function getFilterableFields(): array
    {
        return ['status', 'product_type_id', 'brand_id'];
    }

    public function toSearchableArray(Model $model, string $engine): array
    {
        // Define what data gets indexed
        return [
            'id' => $model->id,
            'name' => $model->translateAttribute('name'),
            'status' => $model->status,
            // ... more fields
        ];
    }
}
```

**Searchable Models**:

The following models are searchable by default:
- `Lunar\Models\Product`
- `Lunar\Models\Collection`
- `Lunar\Models\Customer`
- `Lunar\Models\Order`
- `Lunar\Models\ProductOption`
- `Lunar\Models\Brand`

**Adding Your Own Models**:

To make your own models searchable:

1. Add the model to `config/lunar/search.php`:

```php
'models' => [
    // ... existing models ...
    App\Models\YourModel::class,
],
```

2. Make your model use Scout's `Searchable` trait:

```php
use Laravel\Scout\Searchable;

class YourModel extends Model
{
    use Searchable;
    
    // Implement toSearchableArray() if needed
    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            // ... more fields
        ];
    }
}
```

3. Optionally create a custom indexer (see above)

4. Index your models:

```bash
php artisan lunar:search:index
```

**Best Practices**:

- **Always Set `soft_delete` to `true`**: Prevents soft-deleted models from appearing in search results
- **Use Engine Mapping**: Use different engines for different models to optimize costs and performance
- **Eager Load Relationships**: In custom indexers, use `makeAllSearchableUsing()` to eager load relationships
- **Index Regularly**: Run `php artisan lunar:search:index` after bulk imports or updates
- **Use Appropriate Drivers**: 
  - Use `database` for development and small datasets
  - Use `meilisearch` for full-text search with good performance
  - Use `algolia` for cloud-based search with advanced features
- **Custom Indexers**: Create custom indexers to control exactly what gets indexed and how
- **Filter Published Content**: Always filter by status in your search queries (e.g., `->where('status', 'published')`)

**Documentation**: See [Lunar Search documentation](https://docs.lunarphp.com/1.x/reference/search)

## URLs

This project implements URLs following the [Lunar URLs documentation](https://docs.lunarphp.com/1.x/reference/urls). URLs are not to be confused with Routes in Laravel. They allow you to create vanity URLs for resources without having to use IDs.

**Overview**:

URLs in Lunar:
- Provide SEO-friendly slugs instead of IDs (e.g., `/products/apple-iphone` instead of `/products/1`)
- Are language-specific (each language can have its own slug)
- Support default URLs (only one default URL per language per resource)
- Can be automatically generated from model attributes
- Are polymorphic (can be attached to any model using the `HasUrls` trait)

**Creating a URL**:

```php
use Lunar\Models\Url;
use Lunar\Models\Product;
use Lunar\Models\Language;

$product = Product::find(1);
$language = Language::where('code', 'en')->first();

// Create a URL
$url = Url::create([
    'slug' => 'apple-iphone',
    'language_id' => $language->id,
    'element_type' => Product::class,
    'element_id' => $product->id,
    'default' => true,
]);
```

**Default URLs**:

A URL cannot share the same `slug` and `language_id` columns. You can only have one `default` URL per language for that resource.

If you add a new default URL for a language which already has one, the new URL will override and become the new default:

```php
use Lunar\Models\Url;
use Lunar\Models\Language;

$language = Language::find(1);

// Create first default URL
$urlA = Url::create([
    'slug' => 'apple-iphone',
    'language_id' => $language->id,
    'element_type' => Product::class,
    'element_id' => 1,
    'default' => true,
]);

$urlA->default; // true

// Create second default URL (same language)
$urlB = Url::create([
    'slug' => 'apple-iphone-26',
    'language_id' => $language->id,
    'element_type' => Product::class,
    'element_id' => 1,
    'default' => true,
]);

$urlA->refresh();
$urlA->default; // false (automatically updated)
$urlB->default; // true (new default)

// Create default URL for different language (no conflict)
$urlC = Url::create([
    'slug' => 'apple-iphone-french',
    'language_id' => 2, // Different language
    'element_type' => Product::class,
    'element_id' => 1,
    'default' => true,
]);

$urlA->default; // false
$urlB->default; // true (still default for language 1)
$urlC->default; // true (default for language 2)
```

**Deleting a URL**:

When you delete a URL, if it was the default, Lunar will automatically look for a non-default URL of the same language and assign that as the new default:

```php
use Lunar\Models\Url;

$urlA = Url::create([
    'slug' => 'apple-iphone',
    'language_id' => 1,
    'element_type' => Product::class,
    'element_id' => 1,
    'default' => true,
]);

$urlB = Url::create([
    'slug' => 'apple-iphone-26',
    'language_id' => 1,
    'element_type' => Product::class,
    'element_id' => 1,
    'default' => false,
]);

$urlB->default; // false

// Delete the default URL
$urlA->delete();

// Lunar automatically promotes the non-default URL
$urlB->refresh();
$urlB->default; // true (automatically updated)
```

**Adding URL Support to Models**:

Out of the box, Lunar has pre-configured models which have URLs:
- `Lunar\Models\Product`
- `Lunar\Models\Collection`

You can add URLs to your own models by using the `HasUrls` trait:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Lunar\Base\Traits\HasUrls;

class MyModel extends Model
{
    use HasUrls;
    
    // ... rest of your model
}
```

You will then have access to the `urls` relationship which is polymorphic:

```php
$myModel = MyModel::find(1);

// Get all URLs for the model
$myModel->urls; // Collection of Url models

// Get default URL
$defaultUrl = $myModel->urls->where('default', true)->first();

// Get slug
$slug = $myModel->urls->first()->slug;
```

**Using UrlHelper**:

The `UrlHelper` class provides convenience methods:

```php
use App\Lunar\Urls\UrlHelper;
use Lunar\Models\Product;
use Lunar\Models\Language;

$product = Product::find(1);
$language = Language::where('code', 'en')->first();

// Create a URL for a model
$url = UrlHelper::create($product, 'apple-iphone', $language, true);

// Get default URL for a model
$defaultUrl = UrlHelper::getDefaultUrl($product, $language);

// Get default slug (convenience method)
$slug = UrlHelper::getDefaultSlug($product);

// Get all URLs for a model
$urls = UrlHelper::getUrls($product);

// Update or create default URL
$url = UrlHelper::updateOrCreateDefault($product, 'new-slug', $language);
```

**Automatically Generating URLs**:

You can tell Lunar to generate URLs for models that use the `HasUrls` trait automatically by setting the `generator` config option in `config/lunar/urls.php`.

**Configuration**:

```php
// config/lunar/urls.php
return [
    // Set whether URLs are required
    'required' => true,
    
    // URL generator class (or null to disable)
    'generator' => \Lunar\Generators\UrlGenerator::class,
];
```

By default, this will use the default language and take the `name` attribute as the slug. The default generator uses `Str::slug()` to create URL-friendly slugs.

**Disable Automatic Generation**:

To disable automatic URL generation:

```php
return [
    'generator' => null,
];
```

**Custom URL Generator**:

You can create your own URL generator class. It just needs to have a `handle` method which accepts a `Model`:

```php
<?php

namespace App\Generators;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Lunar\Models\Language;
use Lunar\Models\Url;

class MyCustomUrlGenerator
{
    public function handle(Model $model): void
    {
        // Get default language
        $language = Language::where('default', true)->first();
        
        if (!$language) {
            return;
        }
        
        // Generate slug from model's name attribute
        $name = $model->translateAttribute('name');
        $slug = Str::slug($name);
        
        // Ensure slug is unique for this language
        $uniqueSlug = $this->makeUniqueSlug($slug, $language->id, $model);
        
        // Create or update the default URL
        Url::updateOrCreate(
            [
                'element_type' => $model->getMorphClass(),
                'element_id' => $model->id,
                'language_id' => $language->id,
                'default' => true,
            ],
            [
                'slug' => $uniqueSlug,
            ]
        );
    }
    
    protected function makeUniqueSlug(string $slug, int $languageId, Model $model): string
    {
        $baseSlug = $slug;
        $counter = 1;
        
        while (Url::where('slug', $slug)
            ->where('language_id', $languageId)
            ->where('element_type', '!=', $model->getMorphClass())
            ->where('element_id', '!=', $model->id)
            ->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
}
```

Then register it in `config/lunar/urls.php`:

```php
return [
    'generator' => \App\Generators\MyCustomUrlGenerator::class,
];
```

**Storefront Usage**:

The storefront uses URLs to generate SEO-friendly links and find resources by slug:

**Finding Products by Slug**:

```php
use Lunar\Models\Url;
use Lunar\Models\Product;

// In your controller
public function show(string $slug)
{
    // Find product by URL slug
    $url = Url::where('slug', $slug)
        ->where('element_type', Product::class)
        ->firstOrFail();
    
    $product = Product::with(['variants.prices', 'media', 'collections'])
        ->findOrFail($url->element_id);
    
    return view('storefront.products.show', compact('product'));
}
```

**Finding Collections by Slug**:

```php
use Lunar\Models\Url;
use Lunar\Models\Collection;

// In your controller
public function show(string $slug)
{
    // Find collection by URL slug
    $url = Url::where('slug', $slug)
        ->where('element_type', Collection::class)
        ->firstOrFail();
    
    $collection = Collection::with(['group', 'children', 'media', 'urls'])
        ->findOrFail($url->element_id);
    
    return view('storefront.collections.show', compact('collection'));
}
```

**Generating Links**:

```php
use Lunar\Models\Product;
use Lunar\Models\Collection;

$product = Product::find(1);
$collection = Collection::find(1);

// Get default URL slug
$productSlug = $product->urls->where('default', true)->first()?->slug;
$collectionSlug = $collection->urls->where('default', true)->first()?->slug;

// Generate URLs
$productUrl = route('products.show', $productSlug);
$collectionUrl = route('collections.show', $collectionSlug);
```

**Route Examples**:

```php
// routes/web.php
Route::get('/products/{slug}', [ProductController::class, 'show'])
    ->name('products.show');

Route::get('/collections/{slug}', [CollectionController::class, 'show'])
    ->name('collections.show');
```

**Database Schema**:

| Field         | Description                                    |
|---------------|------------------------------------------------|
| `id`          | Primary key                                    |
| `language_id` | Foreign key to languages                       |
| `element_type`| Polymorphic type (e.g., Product, Collection)   |
| `element_id`  | Polymorphic ID                                 |
| `slug`        | URL slug (unique per language)                 |
| `default`     | Boolean indicating if this is the default URL  |
| `created_at`  | Timestamp                                      |
| `updated_at`  | Timestamp                                      |

**Best Practices**:

- **Use Slugs Instead of IDs**: Always use slugs in your storefront URLs for SEO and user-friendliness
- **Language-Specific URLs**: Create separate URLs for each language if you support multiple languages
- **Default URLs**: Always have a default URL for each language to ensure resources are accessible
- **Unique Slugs**: Ensure slugs are unique per language (Lunar handles this automatically)
- **Eager Load URLs**: When loading models, eager load URLs if you'll need them:
  ```php
  Product::with('urls')->get();
  ```
- **Automatic Generation**: Use automatic URL generation for convenience, but customize the generator if needed
- **Slug Validation**: Validate slugs when creating/updating URLs to ensure they're URL-friendly

**Documentation**: See [Lunar URLs documentation](https://docs.lunarphp.com/1.x/reference/urls)

## Addresses

This project implements addresses following the [Lunar Addresses documentation](https://docs.lunarphp.com/1.x/reference/addresses). Customers may save addresses to make checking-out easier and quicker.

**Overview**:

Lunar's address system provides:
- Customer address management with shipping and billing defaults
- Country data with ISO2, ISO3 codes, phone codes, and flags
- State/province data linked to countries
- Address data import from external database
- Support for delivery instructions and contact information

**Addresses**:

The `Address` model stores customer addresses with the following fields:

| Field                  | Description                                               |
|------------------------|-----------------------------------------------------------|
| `id`                   | Primary key                                               |
| `customer_id`          | Foreign key to customers (nullable)                       |
| `title`                | Title (Mr, Mrs, etc.) - nullable                          |
| `first_name`           | First name                                                |
| `last_name`            | Last name                                                 |
| `company_name`         | Company name - nullable                                   |
| `line_one`             | First address line                                        |
| `line_two`             | Second address line - nullable                           |
| `line_three`           | Third address line - nullable                            |
| `city`                 | City                                                     |
| `state`                | State/province - nullable                                |
| `postcode`             | Postal/ZIP code - nullable                               |
| `country_id`           | Foreign key to countries                                 |
| `delivery_instructions`| Delivery instructions - nullable                         |
| `contact_email`        | Contact email - nullable                                 |
| `contact_phone`        | Contact phone - nullable                                 |
| `last_used_at`         | Timestamp for when the address was last used in an order |
| `meta`                 | JSON metadata - nullable                                 |
| `shipping_default`     | Boolean - default shipping address                        |
| `billing_default`      | Boolean - default billing address                        |
| `created_at`           | Timestamp                                                |
| `updated_at`           | Timestamp                                                |

**Creating an Address**:

```php
use Lunar\Models\Address;
use Lunar\Models\Country;
use Lunar\Models\Customer;

$customer = Customer::find(1);
$country = Country::where('iso2', 'US')->first();

$address = Address::create([
    'customer_id' => $customer->id,
    'title' => 'Mr',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'company_name' => 'Acme Corp',
    'line_one' => '123 Main Street',
    'line_two' => 'Suite 100',
    'line_three' => null,
    'city' => 'New York',
    'state' => 'NY',
    'postcode' => '10001',
    'country_id' => $country->id,
    'delivery_instructions' => 'Leave at front door',
    'contact_email' => 'john@example.com',
    'contact_phone' => '+1-555-123-4567',
    'shipping_default' => true,
    'billing_default' => true,
    'meta' => [
        'custom_field' => 'value',
    ],
]);
```

**Using AddressHelper**:

The `AddressHelper` class provides convenience methods:

```php
use App\Lunar\Addresses\AddressHelper;
use Lunar\Models\Address;
use Lunar\Models\Country;
use Lunar\Models\Customer;

$customer = Customer::find(1);

// Create an address
$address = AddressHelper::create($customer->id, [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'line_one' => '123 Main Street',
    'city' => 'New York',
    'country_id' => $country->id,
    'shipping_default' => true,
]);

// Get all addresses for a customer
$addresses = AddressHelper::getForCustomer($customer->id);

// Get default shipping address
$shippingAddress = AddressHelper::getDefaultShipping($customer->id);

// Get default billing address
$billingAddress = AddressHelper::getDefaultBilling($customer->id);

// Set an address as default shipping
AddressHelper::setDefaultShipping($address);

// Set an address as default billing
AddressHelper::setDefaultBilling($address);

// Mark address as used (updates last_used_at)
AddressHelper::markAsUsed($address);

// Format address as string
$formatted = AddressHelper::format($address);
```

**Countries**:

The `Country` model stores country data:

| Field       | Description |
|-------------|-------------|
| `id`        | Primary key |
| `name`      | Country name |
| `iso3`      | ISO 3166-1 alpha-3 code (3 letters) |
| `iso2`      | ISO 3166-1 alpha-2 code (2 letters) |
| `phonecode` | Phone country code |
| `capital`   | Capital city |
| `currency`  | Currency code |
| `native`    | Native name |
| `emoji`     | Flag emoji |
| `emoji_u`   | Flag emoji Unicode |
| `created_at`| Timestamp |
| `updated_at`| Timestamp |

**Working with Countries**:

```php
use Lunar\Models\Country;
use App\Lunar\Addresses\AddressHelper;

// Get all countries
$countries = Country::orderBy('name')->get();

// Or using helper
$countries = AddressHelper::getCountries();

// Get country by ISO2 code
$country = Country::where('iso2', 'US')->first();
// Or using helper
$country = AddressHelper::getCountryByIso2('US');

// Get country by ISO3 code
$country = Country::where('iso3', 'USA')->first();
// Or using helper
$country = AddressHelper::getCountryByIso3('USA');

// Access country properties
$country->name;      // "United States"
$country->iso2;      // "US"
$country->iso3;      // "USA"
$country->phonecode; // "+1"
$country->currency;  // "USD"
$country->emoji;     // "🇺🇸"
```

**States**:

The `State` model stores state/province data linked to countries:

| Field       | Description |
|-------------|-------------|
| `id`        | Primary key |
| `country_id`| Foreign key to countries |
| `name`      | State/province name |
| `code`      | State/province code (e.g., "NY", "CA") |
| `created_at`| Timestamp |
| `updated_at`| Timestamp |

**Working with States**:

```php
use Lunar\Models\State;
use Lunar\Models\Country;
use App\Lunar\Addresses\AddressHelper;

$country = Country::where('iso2', 'US')->first();

// Get all states for a country
$states = State::where('country_id', $country->id)
    ->orderBy('name')
    ->get();

// Or using helper
$states = AddressHelper::getStates($country);
$states = AddressHelper::getStates($country->id);

// Get state by code
$state = State::where('country_id', $country->id)
    ->where('code', 'NY')
    ->first();

// Or using helper
$state = AddressHelper::getStateByCode($country, 'NY');
$state = AddressHelper::getStateByCode($country->id, 'NY');

// Access state properties
$state->name;        // "New York"
$state->code;        // "NY"
$state->country_id;  // 1
```

**Address Data Import**:

Data for Countries and States is provided by the [countries-states-cities-database](https://github.com/dr5hn/countries-states-cities-database). You can use the following command to import countries and states:

```bash
php artisan lunar:import:address-data
```

This command will:
- Import all countries with ISO2, ISO3, phone codes, currencies, and flags
- Import all states/provinces linked to their countries
- Update existing records if they already exist

**Setup**:

1. **Import address data** (countries and states):
```bash
php artisan lunar:import:address-data
```

This is a one-time setup command that populates your database with country and state data.

**Example: Complete Address Workflow**:

```php
use Lunar\Models\Address;
use Lunar\Models\Country;
use Lunar\Models\State;
use Lunar\Models\Customer;
use App\Lunar\Addresses\AddressHelper;

// 1. Get or import countries/states (run import command first)
$country = Country::where('iso2', 'US')->first();
$state = State::where('country_id', $country->id)
    ->where('code', 'NY')
    ->first();

// 2. Create an address for a customer
$customer = Customer::find(1);

$address = Address::create([
    'customer_id' => $customer->id,
    'first_name' => 'John',
    'last_name' => 'Doe',
    'line_one' => '123 Main Street',
    'city' => 'New York',
    'state' => $state->code, // "NY"
    'postcode' => '10001',
    'country_id' => $country->id,
    'shipping_default' => true,
    'billing_default' => true,
]);

// 3. Get customer's addresses
$addresses = $customer->addresses; // Relationship

// 4. Get default addresses
$shippingAddress = AddressHelper::getDefaultShipping($customer->id);
$billingAddress = AddressHelper::getDefaultBilling($customer->id);

// 5. Update default addresses
AddressHelper::setDefaultShipping($address);
AddressHelper::setDefaultBilling($address);

// 6. Format address for display
$formatted = AddressHelper::format($address);
// Output:
// John Doe
// 123 Main Street
// New York, NY 10001
// United States
```

**Using Addresses in Carts and Orders**:

Addresses are used in carts and orders for shipping and billing:

```php
use Lunar\Models\Cart;
use Lunar\Models\Address;

$cart = Cart::find(1);
$address = Address::find(1);

// Set shipping address on cart
$cart->setShippingAddress($address);

// Set billing address on cart
$cart->setBillingAddress($address);

// Get addresses from cart
$shippingAddress = $cart->shippingAddress;
$billingAddress = $cart->billingAddress;
```

**Address Relationships**:

```php
use Lunar\Models\Address;
use Lunar\Models\Customer;
use Lunar\Models\Country;
use Lunar\Models\State;

$address = Address::find(1);

// Get customer
$customer = $address->customer;

// Get country
$country = $address->country;

// Get state (if using State model relationship)
// Note: The state field is stored as a string, not a relationship
// You can manually look up the State model if needed:
$state = State::where('country_id', $address->country_id)
    ->where('code', $address->state)
    ->first();
```

**Best Practices**:

- **Import Address Data First**: Run `php artisan lunar:import:address-data` before creating addresses
- **Use Country IDs**: Always use `country_id` instead of country names for consistency
- **Set Defaults**: Set default shipping and billing addresses for better UX
- **Track Usage**: Use `last_used_at` to show recently used addresses first
- **Validate Addresses**: Validate address data before saving (country exists, state valid for country, etc.)
- **Format Consistently**: Use `AddressHelper::format()` for consistent address display
- **Eager Load**: When loading addresses, eager load country relationship:
  ```php
  Address::with('country')->get();
  ```
- **Handle Null States**: Some countries don't have states - handle this in your forms
- **Meta Field**: Use the `meta` JSON field for custom address data if needed

**Database Schema**:

**Addresses Table**:
- Foreign keys: `customer_id`, `country_id`
- Indexes: `shipping_default`, `billing_default`
- JSON field: `meta` for custom data

**Countries Table**:
- Unique indexes: `iso2`, `iso3`
- Contains comprehensive country data

**States Table**:
- Foreign key: `country_id`
- Contains state/province data linked to countries

**Documentation**: See [Lunar Addresses documentation](https://docs.lunarphp.com/1.x/reference/addresses)
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

This project implements carts following the [Lunar Carts documentation](https://docs.lunarphp.com/1.x/reference/carts). Carts are a collection of products (or other custom purchasable types) that you would like to order.

**Overview**:

Carts in Lunar:
- Belong to Users (which relate to Customers)
- Contain purchasable items with quantities
- Have dynamically calculated prices (not stored, unlike Orders)
- Support guest and authenticated users
- Can be merged when users log in
- Support shipping and billing addresses for tax calculation
- Include validation when adding items

**Carts**:

The `Cart` model has the following fields:

| Field                      | Description                                                                     |
|----------------------------|---------------------------------------------------------------------------------|
| `id`                       | Unique ID for the cart                                                         |
| `user_id`                  | User ID - can be null for guest users                                           |
| `customer_id`              | Customer ID - can be null                                                       |
| `merged_id`                | If a cart was merged with another cart, defines the cart it was merged into    |
| `currency_id`              | Carts can only be for a single currency                                         |
| `channel_id`               | Channel ID                                                                      |
| `coupon_code`              | Can be null, stores a promotional coupon code (e.g., SALE20)                   |
| `created_at`               | Creation timestamp                                                              |
| `updated_at`               | Last update timestamp (when an order was created from the cart via checkout)    |
| `meta`                     | JSON data for saving any custom information                                     |

**Creating a Cart**:

```php
use Lunar\Models\Cart;
use Lunar\Models\Currency;
use Lunar\Models\Channel;

$currency = Currency::getDefault();
$channel = Channel::getDefault();

$cart = Cart::create([
    'currency_id' => $currency->id,
    'channel_id' => $channel->id,
]);
```

**Cart Lines**:

The `CartLine` model represents individual items in the cart:

| Field             | Description                                  |
|-------------------|----------------------------------------------|
| `id`              | Primary key                                   |
| `cart_id`         | Foreign key to cart                           |
| `purchasable_type`| Morph type (e.g., ProductVariant)             |
| `purchasable_id`  | Morph ID                                      |
| `quantity`        | Quantity of items                             |
| `meta`            | JSON data for saving any custom information   |
| `created_at`      | Creation timestamp                            |
| `updated_at`      | Last update timestamp                         |

**Creating Cart Lines**:

```php
use Lunar\Models\Cart;
use Lunar\Models\CartLine;
use Lunar\Models\ProductVariant;

$purchasable = ProductVariant::find(1);

// Create cart line directly
$cartLine = new CartLine([
    'cart_id' => $cart->id,
    'purchasable_type' => $purchasable->getMorphClass(),
    'purchasable_id' => $purchasable->id,
    'quantity' => 2,
    'meta' => [
        'personalization' => 'Love you mum xxx',
    ],
]);
$cartLine->save();

// Or use the relationship on the cart
$cart->lines()->create([
    'purchasable_type' => $purchasable->getMorphClass(),
    'purchasable_id' => $purchasable->id,
    'quantity' => 2,
    'meta' => ['custom' => 'data'],
]);
```

**Validation**:

When adding items to a cart, there are a series of validation actions which are run, defined in `config/lunar/cart.php`. These actions will throw a `Lunar\Exceptions\Carts\CartException`:

```php
use Lunar\Facades\CartSession;
use Lunar\Models\ProductVariant;
use Lunar\Exceptions\Carts\CartException;

$purchasable = ProductVariant::find(1);

try {
    CartSession::add($purchasable, 500);
} catch (CartException $e) {
    $error = $e->getMessage();
    // Handle validation error (e.g., insufficient stock, invalid quantity)
}
```

**Hydrating the Cart Totals**:

To get all calculated totals and tax, call `calculate()` on the cart:

```php
$cart = CartSession::current();
$cart->calculate();
```

This creates a "hydrated" version of your cart with the following properties. All values return a `Lunar\DataTypes\Price` object with `value`, `formatted`, and `decimal` properties:

**Cart Properties**:

```php
// Cart totals
$cart->total;                    // Total price value for the cart
$cart->subTotal;                 // Cart sub total, excluding tax
$cart->subTotalDiscounted;       // Cart sub total, minus the discount amount
$cart->shippingTotal;            // Monetary value for the shipping total (if applicable)
$cart->taxTotal;                 // Monetary value for the amount of tax applied
$cart->discountTotal;            // Monetary value for the discount total

// Breakdowns (collections)
$cart->taxBreakdown;             // Collection of all taxes applied across all lines
$cart->discountBreakdown;        // Collection of how discounts were calculated
$cart->shippingBreakdown;        // Collection of the shipping breakdown for the cart
$cart->shippingSubTotal;         // Shipping total, excluding tax

// Access breakdown data
foreach ($cart->taxBreakdown as $taxRate) {
    $taxRate->name;              // Tax rate name
    $taxRate->total->value;      // Tax amount
    $taxRate->total->formatted;  // Formatted tax amount
}

foreach ($cart->shippingBreakdown->items as $shippingBreakdown) {
    $shippingBreakdown->name;        // Shipping option name
    $shippingBreakdown->identifier;  // Shipping option identifier
    $shippingBreakdown->price->formatted(); // Formatted price
}

foreach ($cart->discountBreakdown as $discountBreakdown) {
    $discountBreakdown->discount_id; // Discount ID
    foreach ($discountBreakdown->lines as $discountLine) {
        $discountLine->quantity;  // Quantity affected
        $discountLine->line;      // Cart line reference
    }
    $discountBreakdown->total->value; // Discount amount
}
```

**Cart Line Properties**:

```php
foreach ($cart->lines as $cartLine) {
    $cartLine->unitPrice;            // Monetary value for a single item
    $cartLine->unitPriceInclTax;     // Monetary value for a single item, including tax
    $cartLine->total;                // Total price value for the line
    $cartLine->subTotal;             // Sub total, excluding tax
    $cartLine->subTotalDiscounted;   // Sub total, minus the discount amount
    $cartLine->taxAmount;            // Monetary value for the amount of tax applied
    $cartLine->taxBreakdown;         // Collection of all taxes applied to this line
    $cartLine->discountTotal;        // Monetary value for the discount total
}
```

**Modifying Carts**:

If you need to programmatically change Cart values (e.g., custom discounts or prices), you will want to extend the Cart using pipelines. See the [Extending Carts documentation](https://docs.lunarphp.com/1.x/extending/carts) and the Extension Points section below.

**Calculating Tax**:

During the cart's lifetime, you may not have access to address information, which can be a pain when you want to accurately display the amount of tax applied. Some countries don't even show tax until they reach checkout.

When you calculate the cart totals, you can set the billing and/or shipping address on the cart, which will then be used when calculating which tax breakdowns should be applied:

```php
use Lunar\Models\Country;

$shippingAddress = [
    'country_id' => Country::where('iso2', 'US')->first()->id,
    'title' => null,
    'first_name' => 'John',
    'last_name' => 'Doe',
    'company_name' => null,
    'line_one' => '123 Main Street',
    'line_two' => null,
    'line_three' => null,
    'city' => 'New York',
    'state' => 'NY',
    'postcode' => '10001',
    'delivery_instructions' => null,
    'contact_email' => 'john@example.com',
    'contact_phone' => '+1-555-123-4567',
];

$cart->setShippingAddress($shippingAddress);
$cart->setBillingAddress($shippingAddress); // Can be different
$cart->calculate(); // Tax will now be calculated based on addresses
```

**Cart Session Manager**:

Lunar provides a `CartSession` facade for managing carts in sessions. This handles cart persistence, user association, and cart merging.

**Available Config**:

Configuration is in `config/lunar/cart_session.php`:

| Config Option                    | Description                                                                     | Default     |
|----------------------------------|---------------------------------------------------------------------------------|-------------|
| `session_key`                    | What key to use when storing the cart id in the session                        | `lunar_cart`|
| `auto_create`                    | If no current basket exists, should we create one in the database?             | `false`     |
| `allow_multiple_orders_per_cart`| Whether carts can have multiple orders associated to them                      | `false`     |
| `delete_on_forget`               | Whether the cart should be soft deleted when the user logs out                 | `true`      |

**Getting the Cart Session Instance**:

You can either use the facade or inject the `CartSession` into your code:

```php
use Lunar\Facades\CartSession;

// Using facade
$cart = CartSession::current();

// Or inject into constructor
use Lunar\Base\CartSessionInterface;

public function __construct(
    protected CartSessionInterface $cartSession
) {
    // ...
}

$cart = $this->cartSession->current();
```

**Fetching the Current Cart**:

```php
use Lunar\Facades\CartSession;

$cart = CartSession::current();
```

When you call `current()`, you have two options: return `null` if they don't have a cart, or create one straight away. By default, carts are not created initially as this could lead to many cart models being created for no good reason. If you want to enable this functionality, adjust the config in `config/lunar/cart_session.php`:

```php
'auto_create' => true, // Auto-create cart if none exists
```

**Forgetting the Cart**:

Forgetting the cart will remove it from the user session and also soft-delete the cart in the database:

```php
CartSession::forget();
```

If you don't want to delete the cart, you can pass the following parameter:

```php
CartSession::forget(delete: false); // Only removes from session, doesn't delete
```

**Using a Specific Cart**:

You may want to manually specify which cart should be used for the session:

```php
use Lunar\Facades\CartSession;
use Lunar\Models\Cart;

$cart = Cart::find(1);
CartSession::use($cart);
```

**Add a Cart Line**:

```php
use Lunar\Facades\CartSession;
use Lunar\Models\ProductVariant;

$purchasable = ProductVariant::find(1);
CartSession::add($purchasable, $quantity);
```

**Add Multiple Lines**:

```php
CartSession::addLines([
    [
        'purchasable' => ProductVariant::find(123),
        'quantity' => 25,
        'meta' => ['foo' => 'bar'],
    ],
    [
        'purchasable' => ProductVariant::find(456),
        'quantity' => 10,
        'meta' => ['custom' => 'data'],
    ],
]);
```

Accepts a `collection` or an `array`.

**Update a Single Line**:

```php
CartSession::updateLine($cartLineId, $quantity, $meta);
```

**Update Multiple Lines**:

```php
CartSession::updateLines(collect([
    [
        'id' => 1,
        'quantity' => 25,
        'meta' => ['foo' => 'bar'],
    ],
    [
        'id' => 2,
        'quantity' => 10,
        'meta' => ['custom' => 'data'],
    ],
]));
```

**Remove a Line**:

```php
CartSession::remove($cartLineId);
```

**Clear a Cart**:

This will remove all lines from the cart:

```php
CartSession::clear();
```

**Associating a Cart to a User**:

You can easily associate a cart to a user:

```php
use App\Models\User;

$user = User::find(1);
CartSession::associate($user, 'merge'); // or 'override'
```

The second parameter determines the policy:
- `'merge'` - Merge guest cart with user's existing cart (default)
- `'override'` - Replace user's cart with guest cart

**Associating a Cart to a Customer**:

You can easily associate a cart to a customer:

```php
use Lunar\Models\Customer;

$customer = Customer::find(1);
CartSession::setCustomer($customer);
```

**Adding Shipping/Billing Address**:

As outlined above, you can add shipping/billing addresses to the cart:

```php
$cart->setShippingAddress([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'line_one' => '123 Main Street',
    'line_two' => null,
    'line_three' => null,
    'city' => 'New York',
    'state' => 'NY',
    'postcode' => '10001',
    'country_id' => 1,
]);

$cart->setBillingAddress([
    // Same structure as shipping address
]);
```

You can easily retrieve these addresses:

```php
$cart->shippingAddress; // CartAddress model or null
$cart->billingAddress;  // CartAddress model or null
```

**ShippingOption Override**:

In some cases you might want to present an estimated shipping cost without users having to fill out a full shipping address. This is where the `ShippingOptionOverride` comes in. If set on the cart, it can be used to calculate shipping for a single request:

```php
use Lunar\Models\Country;

$shippingOption = $cart->getEstimatedShipping([
    'postcode' => '123456',
    'state' => 'Essex',
    'country' => Country::first(),
]);
```

This will return an estimated (cheapest) shipping option for the cart, based on its current totals. By default this will not be taken into account when calculating shipping in the cart pipelines. To enable that, pass an extra parameter:

```php
$shippingOption = $cart->getEstimatedShipping([
    'postcode' => '123456',
    'state' => 'Essex',
    'country' => Country::first(),
], setOverride: true);
```

Now when the pipelines are run, the option returned by `getEstimatedShipping` will be used when calculating shipping totals, bypassing any other logic. Note this will only happen for that one request.

If you are using the `CartSession` manager, you can easily set the parameters you want to estimate shipping so you don't need to pass them each time:

```php
CartSession::estimateShippingUsing([
    'postcode' => '123456',
    'state' => 'Essex',
    'country' => Country::first(),
]);
```

You can also manually set the shipping method override directly on the cart:

```php
use Lunar\DataTypes\ShippingOption;

$cart->shippingOptionOverride = new ShippingOption(
    name: 'Estimated Shipping',
    description: 'Estimated shipping cost',
    identifier: 'ESTIMATED',
    price: new Price(1000, $cart->currency, 1),
    taxClass: $taxClass
);
```

Calling `CartSession::current()` by itself won't trigger the shipping override, but you can pass the `estimateShipping` parameter to enable it:

```php
// Will not use the shipping override, default behaviour
CartSession::current();

// Will use the shipping override, based on what is set using `estimateShippingUsing`
CartSession::current(estimateShipping: true);
```

**Handling User Login**:

When a user logs in, you will likely want to check if they have a cart associated to their account and use that, or if they have started a cart as a guest and logged in, you will likely want to handle this.

Lunar takes the pain out of this by listening to the authentication events and responding automatically by associating any previous guest cart they may have had and, depending on your `auth_policy` (in `config/lunar/cart_session.php`), merge or override the basket on their account.

**Determining Cart Changes**:

Carts by nature are dynamic, which means anything can change at any moment. This means it can be quite challenging to determine whether a cart has changed from the one currently loaded. For example, if the user goes to checkout and changes their cart on another tab, how does the checkout know there has been a change?

To help this, a cart will have a fingerprint generated which you can check to determine whether there have been any changes and if so, refresh the cart:

```php
$cart = CartSession::current();
$cart->calculate();

// Get fingerprint
$fingerprint = $cart->fingerprint();

// Later, check if cart has changed
try {
    $cart->checkFingerprint($fingerprint);
    // Cart hasn't changed
} catch (\Lunar\Exceptions\FingerprintMismatchException $e) {
    // Cart has changed, refresh it
    $cart = CartSession::current();
    $cart->calculate();
}
```

**Changing the Underlying Class**:

The class which generates the fingerprint is referenced in `config/lunar/cart.php`:

```php
return [
    // ...
    'fingerprint_generator' => Lunar\Actions\Carts\GenerateFingerprint::class,
];
```

In most cases you won't need to change this.

**Pruning Cart Data**:

Over time you will experience a build up of carts in your database that you may want to regularly remove. You can enable automatic removal of these carts using the `lunar.carts.prune_tables.enabled` config. By setting this to `true`, any carts without an order associated will be removed after 90 days (configurable).

You can change the number of days carts are retained for using the `lunar.carts.prune_tables.prune_interval` config. If you have specific needs around pruning, you can also change the `lunar.carts.prune_tables.pipelines` array to determine what carts should be removed:

```php
// config/lunar/cart.php
return [
    // ...
    'prune_tables' => [
        'enabled' => false, // Set to true to enable pruning
        
        'pipelines' => [
            Lunar\Pipelines\CartPrune\PruneAfter::class,
            Lunar\Pipelines\CartPrune\WithoutOrders::class,
        ],
        
        'prune_interval' => 90, // days
    ],
];
```

**Using CartHelper**:

The `CartHelper` class provides convenience methods:

```php
use App\Lunar\Carts\CartHelper;
use Lunar\Models\ProductVariant;

// Get current cart
$cart = CartHelper::current();

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

// Update multiple lines
CartHelper::updateLines([
    ['id' => 1, 'quantity' => 3],
    ['id' => 2, 'quantity' => 5],
]);

// Remove line
CartHelper::remove($cartLineId);

// Clear cart
CartHelper::clear();

// Calculate cart totals (hydrate the cart)
CartHelper::calculate($cart);

// Get cart totals as array
$totals = CartHelper::getTotals($cart);

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

// Set shipping estimation parameters
CartHelper::estimateShippingUsing([
    'postcode' => '123456',
    'state' => 'Essex',
    'country' => $country,
]);

// Get current cart with shipping estimation
$cart = CartHelper::currentWithShipping(true);

// Forget cart
CartHelper::forget(); // Removes from session and deletes
CartHelper::forget(delete: false); // Only removes from session

// Use a specific cart
CartHelper::use($cart);
```

**Example: Complete Cart Workflow**:

```php
use Lunar\Facades\CartSession;
use Lunar\Models\ProductVariant;
use Lunar\Models\Country;
use Lunar\Exceptions\Carts\CartException;

// 1. Get or create cart
$cart = CartSession::current();
if (!$cart) {
    $cart = Cart::create([
        'currency_id' => Currency::getDefault()->id,
        'channel_id' => Channel::getDefault()->id,
    ]);
    CartSession::use($cart);
}

// 2. Add items to cart
$variant = ProductVariant::find(1);
try {
    CartSession::add($variant, 2, [
        'gift_message' => 'Happy Birthday!',
    ]);
} catch (CartException $e) {
    // Handle error (e.g., insufficient stock)
    return back()->with('error', $e->getMessage());
}

// 3. Set addresses for tax calculation
$country = Country::where('iso2', 'US')->first();
$cart->setShippingAddress([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'line_one' => '123 Main Street',
    'city' => 'New York',
    'state' => 'NY',
    'postcode' => '10001',
    'country_id' => $country->id,
]);

// 4. Calculate cart totals
$cart->calculate();

// 5. Access calculated values
$total = $cart->total->formatted; // "$199.99"
$subTotal = $cart->subTotal->formatted; // "$179.99"
$taxTotal = $cart->taxTotal->formatted; // "$20.00"
$shippingTotal = $cart->shippingTotal->formatted; // "$10.00"

// 6. Get breakdowns
foreach ($cart->taxBreakdown as $tax) {
    echo $tax->name . ': ' . $tax->total->formatted;
}

// 7. Check fingerprint for changes
$fingerprint = $cart->fingerprint();
// Store fingerprint in session or pass to frontend

// Later, check if cart changed
try {
    $cart->checkFingerprint($fingerprint);
    // Cart hasn't changed
} catch (\Lunar\Exceptions\FingerprintMismatchException $e) {
    // Cart has changed, refresh
    $cart = CartSession::current();
    $cart->calculate();
}
```

**Best Practices**:

- **Always Calculate**: Call `$cart->calculate()` before accessing cart totals
- **Handle Validation**: Wrap cart operations in try-catch for `CartException`
- **Set Addresses**: Set shipping/billing addresses before calculating tax
- **Use Fingerprinting**: Use cart fingerprints to detect changes in multi-tab scenarios
- **Eager Load**: When loading carts, eager load relationships:
  ```php
  Cart::with(['lines.purchasable.prices', 'currency', 'channel'])->get();
  ```
- **Cart Pruning**: Enable cart pruning to clean up old carts automatically
- **Session Management**: Use `CartSession` facade for consistent cart management
- **Guest Carts**: Allow guest carts but associate them when users log in
- **Error Handling**: Always handle `CartException` when adding/updating cart items

**Documentation**: See [Lunar Carts documentation](https://docs.lunarphp.com/1.x/reference/carts)

## Customers

This project implements customers following the [Lunar Customers documentation](https://docs.lunarphp.com/1.x/reference/customers). We use Customers in Lunar to store the customer details, rather than Users. We do this for a few reasons: one, so that we leave your User models well alone, and two, because it provides flexibility.

**Overview**:

Customers in Lunar:
- Store customer details separately from Users
- Can have multiple users associated (useful for B2B where multiple buyers can access the same customer account)
- Belong to Customer Groups for pricing and product availability
- Support customer group scheduling for products
- Can be impersonated by admins for support

**Customers**:

The `Customer` model has the following fields:

| Field           | Description        |
|-----------------|--------------------|
| `id`            | Primary key        |
| `title`         | Mr, Mrs, Miss, etc |
| `first_name`    | First name         |
| `last_name`     | Last name          |
| `company_name`  | Company name (nullable) |
| `vat_no`        | VAT number (nullable) |
| `account_ref`   | Account reference (nullable) |
| `attribute_data`| JSON attribute data |
| `meta`          | JSON metadata      |
| `created_at`    | Creation timestamp |
| `updated_at`    | Last update timestamp |

**Creating a Customer**:

```php
use Lunar\Models\Customer;

$customer = Customer::create([
    'title' => 'Mr.',
    'first_name' => 'Tony',
    'last_name' => 'Stark',
    'company_name' => 'Stark Enterprises',
    'vat_no' => null,
    'meta' => [
        'account_no' => 'TNYSTRK1234'
    ],
]);
```

**Relationships**:

Customers have the following relationships:
- **Customer Groups**: `customer_customer_group` pivot table
- **Users**: `customer_user` pivot table (many-to-many)

**Users**:

Customers will typically be associated with a user, so they can place orders. But it is also possible to have multiple users associated with a customer. This can be useful on B2B e-commerce where a customer may have multiple buyers.

**Attaching Users to a Customer**:

```php
use Lunar\Models\Customer;
use App\Models\User;

$customer = Customer::create([/* ... */]);
$user = User::find(1);

// Attach a single user
$customer->users()->attach($user);
// Or by ID
$customer->users()->attach($user->id);

// Sync multiple users (replaces all existing associations)
$customer->users()->sync([1, 2, 3]);

// Detach a user
$customer->users()->detach($user);
```

**Attaching a Customer to a Customer Group**:

```php
use Lunar\Models\Customer;
use Lunar\Models\CustomerGroup;

$customer = Customer::create([/* ... */]);
$customerGroup = CustomerGroup::find(1);

// Attach to a customer group
$customer->customerGroups()->attach($customerGroup);
// Or by ID
$customer->customerGroups()->attach($customerGroup->id);

// Sync customer groups (replaces all existing associations)
$customer->customerGroups()->sync([4, 5, 6]);

// Detach from a customer group
$customer->customerGroups()->detach($customerGroup);
```

**Impersonating Users**:

When a customer needs help with their account, it's useful to be able to log in as that user so you can help diagnose the issue they're having. Lunar allows you to specify your own method of how you want to impersonate users, usually this is in the form of a signed URL an admin can go to in order to log in as the user.

**Creating the Impersonate Class**:

This project includes a custom impersonation class at `app/Auth/Impersonate.php`:

```php
<?php

namespace App\Auth;

use Lunar\Hub\Auth\Impersonate as LunarImpersonate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\URL;

class Impersonate extends LunarImpersonate
{
    /**
     * Return the URL for impersonation.
     *
     * @return string
     */
    public function getUrl(Authenticatable $authenticatable): string
    {
        return URL::temporarySignedRoute('impersonate.link', now()->addMinutes(5), [
            'user' => $authenticatable->getAuthIdentifier(),
        ]);
    }
}
```

**Registering the Impersonate Class**:

If you're using Lunar Hub, register this in `config/lunar-hub/customers.php`:

```php
return [
    'impersonate' => App\Auth\Impersonate::class,
    // ...
];
```

Once added, you will see an option to impersonate the user when viewing a customer in the admin panel. This will then go to the URL specified in your class where you will be able to handle the impersonation logic.

**Customer Groups**:

Customer groups allow you to group your customers into logical segments which enables you to define different criteria on models based on what customer belongs to that group. These criteria include things like:

**Pricing**:

Specify different pricing per customer group. For example, you may have certain prices for customers that are in the `trade` customer group. See the [Products documentation](#products) for more details on customer group pricing.

**Product Availability**:

You can turn product visibility off depending on the customer group. This would mean only certain products would show depending on the group they belong to. This will also include scheduling availability so you can release products earlier or later to different groups.

**Important**: You must have at least one customer group in your store. When you install Lunar, you will be given a default one to get you started named `retail`.

**Creating a Customer Group**:

```php
use Lunar\Models\CustomerGroup;

$customerGroup = CustomerGroup::create([
    'name' => 'Retail',
    'handle' => 'retail', // Must be unique
    'default' => false,
]);
```

You can only have one default at a time. If you create a customer group and pass `default` to `true`, then the existing default will be set to `false`.

**Scheduling Availability**:

If you would like to add customer group availability to your own models, you can use the `HasCustomerGroups` trait:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Lunar\Base\Traits\HasCustomerGroups;

class MyModel extends Model
{
    use HasCustomerGroups;
    
    // Define the relationship for customer groups
    public function customerGroups()
    {
        return $this->belongsToMany(
            \Lunar\Models\CustomerGroup::class,
            'my_model_customer_group' // Your pivot table name
        )->withTimestamps()->withPivot([
            'enabled',
            'starts_at',
            'ends_at',
        ]);
    }
}
```

You will then have access to the following methods:

**Scheduling Customer Groups**:

```php
use Lunar\Models\Product;
use Lunar\Models\CustomerGroup;
use Carbon\Carbon;

$product = Product::find(1);
$customerGroup = CustomerGroup::find(1);

// Schedule the product to be enabled straight away
$product->scheduleCustomerGroup($customerGroup);

// Will schedule for this product to be enabled in 14 days for this customer group
$product->scheduleCustomerGroup(
    $customerGroup,
    Carbon::now()->addDays(14)
);

// Schedule with start and end dates
$product->scheduleCustomerGroup(
    $customerGroup,
    Carbon::now()->addDays(7),  // Start date
    Carbon::now()->addDays(30)  // End date
);

// Schedule with pivot data
$product->scheduleCustomerGroup(
    $customerGroup,
    null, // Start date (null = now)
    null, // End date (null = no end)
    ['enabled' => true, 'priority' => 1] // Pivot data
);

// The schedule method will accept an array or collection of customer groups
$product->scheduleCustomerGroup(CustomerGroup::all());
```

**Unscheduling Customer Groups**:

If you do not want a model to be enabled for a customer group, you can unschedule it. This will keep any previous `start` and `end` dates but will toggle the `enabled` column:

```php
$product->unscheduleCustomerGroup($customerGroup);

// With pivot data
$product->unscheduleCustomerGroup($customerGroup, ['reason' => 'Out of stock']);
```

**Parameters**:

| Field         | Description                                                                                    | Type     | Default |
|---------------|------------------------------------------------------------------------------------------------|----------|---------|
| `customerGroup`| A collection of CustomerGroup models or id's                                                   | mixed    |         |
| `startDate`   | The date the customer group will be active from                                               | DateTime |         |
| `endDate`     | The date the customer group will be active until                                              | DateTime |         |
| `pivotData`   | Any additional pivot data you may have on your link table (not including scheduling defaults)  | array    |         |

**Pivot Data**: By default the following values are used for `$pivotData`:
- `enabled` - Whether the customer group is enabled, defaults to `true` when scheduling and `false` when unscheduling.

You can override any of these yourself as they are merged behind the scenes.

**Retrieving the Relationship**:

The `HasCustomerGroups` trait adds a `customerGroup` scope to the model. This lets you query based on availability for a specific or multiple customer groups. The scope will accept either a single ID or instance of `CustomerGroup` and will accept an array:

```php
use Lunar\Models\Product;
use Lunar\Models\CustomerGroup;
use Carbon\Carbon;

// Query for products available to a single customer group
$results = Product::customerGroup(1)->paginate();

// Query for products available to multiple customer groups
$results = Product::customerGroup([
    $groupA,
    $groupB,
])->paginate(50);

// Query with start date (products available after this date)
$results = Product::customerGroup(1, Carbon::now()->addDay())->get();

// Query with start and end dates (products available within this date range)
$results = Product::customerGroup(
    1,
    Carbon::now()->addDay(),
    Carbon::now()->addDays(7)
)->get();
```

The start and end dates should be `DateTime` objects which will query for the existence of a customer group association with the start and end dates between those given. These are optional and the following happens in certain situations:

- **Pass neither `startDate` or `endDate`**: Will query for customer groups which are enabled and the `startDate` is after `now()`
- **Pass only `startDate`**: Will query for customer groups which are enabled, the start date is after the given date and the end date is either null or before `now()`
- **Pass both `startDate` and `endDate`**: Will query for customer groups which are enabled, the start date is after the given date and the end date is before the given date
- **Pass `endDate` without `startDate`**: Will query for customer groups which are enabled, the start date is after `now()` and the end date is before the given date

If you omit the second parameter, the scope will take the current date and time.

A model will only be returned if the `enabled` column is positive, regardless of whether the start and end dates match.

**Limit by Customer Group**:

Eloquent models which use the `HasCustomerGroups` trait have a useful scope available:

```php
use Lunar\Models\Product;
use Lunar\Models\CustomerGroup;

// Limit products available to a single customer group
Product::customerGroup($customerGroup)->get();

// Limit products available to multiple customer groups
Product::customerGroup([$groupA, $groupB])->get();

// Limit to products which are available the next day
Product::customerGroup($customerGroup, now()->addDay())->get();

// Limit to products which are available within a date range
Product::customerGroup(
    $customerGroup,
    now()->addDay(),
    now()->addDays(2)
)->get();
```

**Using CustomerHelper**:

The `CustomerHelper` class provides convenience methods:

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

// Find a customer
$customer = CustomerHelper::find(1);

// Get all customers
$allCustomers = CustomerHelper::all();

// Attach user to customer
CustomerHelper::attachUser($customer, $user);
CustomerHelper::attachUser($customer, $user->id);

// Sync multiple users (useful for B2B where multiple buyers can access the same customer account)
CustomerHelper::syncUsers($customer, [1, 2, 3]);

// Detach user from customer
CustomerHelper::detachUser($customer, $user);

// Get users for a customer
$users = CustomerHelper::getUsers($customer);

// Attach customer to customer group
$retailGroup = CustomerHelper::findCustomerGroupByHandle('retail');
CustomerHelper::attachCustomerGroup($customer, $retailGroup);

// Sync customer groups
CustomerHelper::syncCustomerGroups($customer, [1, 2]);

// Detach customer group from customer
CustomerHelper::detachCustomerGroup($customer, $retailGroup);

// Get customer groups for a customer
$groups = CustomerHelper::getCustomerGroups($customer);

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

// Find customer group by handle
$retailGroup = CustomerHelper::findCustomerGroupByHandle('retail');

// Get customer for user
$customer = CustomerHelper::getCustomerForUser($user);

// Create or get customer for user
$customer = CustomerHelper::getOrCreateCustomerForUser($user, [
    'first_name' => 'John',
    'last_name' => 'Doe',
]);

// Schedule customer group availability for a product (using HasCustomerGroups trait)
CustomerHelper::scheduleCustomerGroup($product, $customerGroup);
CustomerHelper::scheduleCustomerGroup($product, $customerGroup, now()->addDays(14));
CustomerHelper::scheduleCustomerGroup($product, [$group1, $group2], $startDate, $endDate);

// Unschedule customer group
CustomerHelper::unscheduleCustomerGroup($product, $customerGroup);
```

**Example: Complete Customer Workflow**:

```php
use Lunar\Models\Customer;
use Lunar\Models\CustomerGroup;
use Lunar\Models\Product;
use App\Models\User;
use Carbon\Carbon;

// 1. Create a customer group (if not exists)
$retailGroup = CustomerGroup::firstOrCreate(
    ['handle' => 'retail'],
    [
        'name' => 'Retail',
        'default' => true,
    ]
);

$tradeGroup = CustomerGroup::firstOrCreate(
    ['handle' => 'trade'],
    [
        'name' => 'Trade',
        'default' => false,
    ]
);

// 2. Create a customer
$customer = Customer::create([
    'title' => 'Mr.',
    'first_name' => 'John',
    'last_name' => 'Doe',
    'company_name' => 'Acme Inc',
    'vat_no' => 'GB123456789',
    'meta' => [
        'account_no' => 'ACME1234',
    ],
]);

// 3. Attach user to customer
$user = User::find(1);
$customer->users()->attach($user->id);

// 4. Attach customer to customer groups
$customer->customerGroups()->attach([$retailGroup->id, $tradeGroup->id]);

// 5. Schedule product availability for customer groups
$product = Product::find(1);

// Available immediately for retail group
$product->scheduleCustomerGroup($retailGroup);

// Available in 7 days for trade group
$product->scheduleCustomerGroup(
    $tradeGroup,
    Carbon::now()->addDays(7)
);

// 6. Query products by customer group
$retailProducts = Product::customerGroup($retailGroup)->get();
$tradeProducts = Product::customerGroup($tradeGroup)->get();

// 7. Get customer's groups
$customerGroups = $customer->customerGroups;

// 8. Get customer's users
$users = $customer->users;

// 9. Get user's customer
$userCustomer = $user->latestCustomer();
```

**Best Practices**:

- **Separate Customers from Users**: Keep customer details separate from user authentication data
- **Multiple Users per Customer**: Use this for B2B scenarios where multiple buyers can access the same account
- **Customer Groups**: Use customer groups for pricing and product availability segmentation
- **Default Group**: Always have at least one default customer group (Lunar provides `retail` by default)
- **Scheduling**: Use customer group scheduling to control when products become available to different groups
- **Eager Load**: When loading customers, eager load relationships:
  ```php
  Customer::with(['users', 'customerGroups'])->get();
  ```
- **Impersonation**: Use impersonation for customer support, but ensure proper security (signed URLs with expiration)
- **Meta Field**: Use the `meta` JSON field for custom customer data if needed
- **Attribute Data**: Use `attribute_data` for structured customer attributes

**Documentation**: See [Lunar Customers documentation](https://docs.lunarphp.com/1.x/reference/customers)

## Discounts

This project implements discounts following the [Lunar Discounts documentation](https://docs.lunarphp.com/1.x/reference/discounts). Discounts allow you to apply various promotional offers to carts, including coupons, buy-X-get-Y offers, and custom discount types.

**Overview**:

Discounts in Lunar:
- Support scheduling with start and end dates
- Have usage limits (max uses storewide)
- Support priority ordering
- Can stop other discounts from applying
- Support conditions and rewards via purchasables
- Are cached for performance
- Support custom discount types

**Discounts**:

The `Discount` model has the following fields:

| Field       | Description                                                | Example                               |
|-------------|------------------------------------------------------------|---------------------------------------|
| `id`        | Primary key                                                |                                       |
| `name`      | The given name for the discount                            | "20% Off Sale"                        |
| `handle`    | The unique handle for the discount                         | "20_off_sale"                         |
| `type`      | The type of discount                                       | `Lunar\DiscountTypes\Coupon`          |
| `data`      | JSON data to be used by the type class                     | `{"coupon": "SAVE20", "min_prices": {...}}` |
| `starts_at` | The datetime the discount starts (required)                | `2022-06-17 13:30:55`                 |
| `ends_at`   | The datetime the discount expires (if NULL it won't expire)| `2022-07-17 13:30:55` or `null`       |
| `uses`      | How many uses the discount has had                         | `42`                                  |
| `max_uses`  | The maximum times this discount can be applied storewide  | `100` or `null`                       |
| `priority`  | The order of priority (higher = more priority)             | `1`, `2`, `3`                         |
| `stop`      | Whether this discount will stop others after propagating   | `true` or `false`                     |
| `created_at`| Creation timestamp                                          |                                       |
| `updated_at`| Last update timestamp                                       |                                       |

**Creating a Discount**:

```php
use Lunar\Models\Discount;

$discount = Discount::create([
    'name' => '20% Coupon',
    'handle' => '20_coupon',
    'type' => 'Lunar\DiscountTypes\Coupon',
    'data' => [
        'coupon' => '20OFF',
        'min_prices' => [
            'USD' => 2000 // $20 minimum
        ],
    ],
    'starts_at' => '2022-06-17 13:30:55',
    'ends_at' => null, // Won't expire
    'max_uses' => null, // Unlimited uses
    'priority' => 1,
    'stop' => false,
]);
```

**Fetching a Discount**:

The following scopes are available:

```php
use Lunar\Models\Discount;

/**
 * Query for discounts using the starts_at and ends_at dates.
 * Returns discounts that are currently active (between start and end dates).
 */
$activeDiscounts = Discount::active()->get();

/**
 * Query for discounts where the uses column is less than the max_uses column
 * or max_uses is null (unlimited).
 */
$usableDiscounts = Discount::usable()->get();

/**
 * Query for discounts where the associated products are of a certain type,
 * based on given product ids.
 * 
 * @param array|Collection $productIds Product IDs to search for
 * @param string $type 'condition' or 'reward' (default: 'condition')
 */
$productDiscounts = Discount::products([1, 2, 3], 'condition')->get();
$rewardDiscounts = Discount::products([1, 2, 3], 'reward')->get();

// Combine scopes
$availableDiscounts = Discount::active()->usable()->get();
```

**Resetting the Discount Cache**:

For performance reasons, the applicable discounts are cached per request. If you need to reset this cache (for example, after adding a discount code), you should call `resetDiscounts()`:

```php
use Lunar\Models\Discount;

Discount::resetDiscounts();
```

This is useful when:
- Adding a new discount programmatically
- Modifying discount data
- Testing discount functionality
- After bulk discount imports

**Discount Purchasable**:

You can relate a purchasable to a discount via the `DiscountPurchasable` model. Each has a type for whether it's a `condition` or `reward`:

- **`condition`**: If your discount requires these purchasable models to be in the cart to activate
- **`reward`**: Once the conditions are met, discount one or more of these purchasable models

The `DiscountPurchasable` model has the following fields:

| Field             | Description         | Example           |
|-------------------|---------------------|-------------------|
| `id`              | Primary key         |                   |
| `discount_id`     | Foreign key to discount |              |
| `purchasable_type`| Morph type          | `product_variant` |
| `purchasable_id`  | Morph ID            |                   |
| `type`            | `condition` or `reward` | `condition`    |
| `created_at`      | Creation timestamp  |                   |
| `updated_at`      | Last update timestamp |                 |

**Relationships**:

Discounts have the following relationships:
- **Purchasables**: `discount_purchasables` pivot table (many-to-many polymorphic)
- **Users**: `discount_user` pivot table (many-to-many)
- **Customer Groups**: `customer_group_discount` pivot table (many-to-many)
- **Brands**: `brand_discount` pivot table (many-to-many)
- **Collections**: `discount_collections` pivot table (many-to-many)

**Adding Your Own Discount Type**:

You can create custom discount types by extending `AbstractDiscountType`:

```php
<?php

namespace App\Lunar\Discounts\DiscountTypes;

use Lunar\Models\Cart;
use Lunar\DiscountTypes\AbstractDiscountType;
use Lunar\DataTypes\Price;

class MyCustomDiscountType extends AbstractDiscountType
{
    /**
     * Return the name of the discount.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Custom Discount Type';
    }

    /**
     * Called just before cart totals are calculated.
     * 
     * Apply the discount logic to the cart.
     *
     * @param Cart $cart
     * @return Cart
     */
    public function apply(Cart $cart): Cart
    {
        // Access discount data via $this->discount
        // Access discount purchasables via $this->discount->purchasables
        
        // Example: Apply 10% discount
        $percentage = $this->discount->data['percentage'] ?? 10;
        $discountAmount = (int) ($cart->subTotal->value * ($percentage / 100));
        
        // Apply discount to cart
        $cart->discount_total = new Price($discountAmount, $cart->currency, 1);
        
        return $cart;
    }
}
```

**Registering Custom Discount Types**:

Register your custom discount type in `AppServiceProvider::boot()`:

```php
use Lunar\Facades\Discounts;

public function boot(): void
{
    Discounts::addType(\App\Lunar\Discounts\DiscountTypes\MyCustomDiscountType::class);
}
```

Or use the helper:

```php
use App\Lunar\Discounts\DiscountHelper;

DiscountHelper::registerType(\App\Lunar\Discounts\DiscountTypes\MyCustomDiscountType::class);
```

**Using DiscountHelper**:

The `DiscountHelper` class provides convenience methods:

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
    'stop' => false,
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

// Find discount by ID
$discount = DiscountHelper::find(1);

// Find discount by handle
$discount = DiscountHelper::findByHandle('20_coupon');

// Get active discounts (between starts_at and ends_at)
$activeDiscounts = DiscountHelper::getActive();

// Get usable discounts (uses < max_uses or max_uses is null)
$usableDiscounts = DiscountHelper::getUsable();

// Get available discounts (active and usable)
$availableDiscounts = DiscountHelper::getAvailable();

// Query discounts by associated products
$productDiscounts = DiscountHelper::getByProducts([1, 2, 3], 'condition');
$rewardDiscounts = DiscountHelper::getByProducts([1, 2, 3], 'reward');

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

// Register custom discount type
DiscountHelper::registerType(\App\Lunar\Discounts\DiscountTypes\MyCustomDiscountType::class);
```

**Example: Complete Discount Workflow**:

```php
use Lunar\Models\Discount;
use Lunar\Models\ProductVariant;
use Carbon\Carbon;

// 1. Create a discount
$discount = Discount::create([
    'name' => 'Summer Sale',
    'handle' => 'summer_sale',
    'type' => 'Lunar\DiscountTypes\Coupon',
    'data' => [
        'coupon' => 'SUMMER20',
        'min_prices' => [
            'USD' => 5000, // $50 minimum
        ],
    ],
    'starts_at' => Carbon::now(),
    'ends_at' => Carbon::now()->addDays(30),
    'max_uses' => 1000,
    'priority' => 1,
    'stop' => false,
]);

// 2. Add conditions (products required in cart)
$variant1 = ProductVariant::find(1);
$variant2 = ProductVariant::find(2);
$discount->purchasables()->create([
    'purchasable_type' => $variant1->getMorphClass(),
    'purchasable_id' => $variant1->id,
    'type' => 'condition',
]);
$discount->purchasables()->create([
    'purchasable_type' => $variant2->getMorphClass(),
    'purchasable_id' => $variant2->id,
    'type' => 'condition',
]);

// 3. Add rewards (products that get discounted)
$variant3 = ProductVariant::find(3);
$discount->purchasables()->create([
    'purchasable_type' => $variant3->getMorphClass(),
    'purchasable_id' => $variant3->id,
    'type' => 'reward',
]);

// 4. Query active and usable discounts
$availableDiscounts = Discount::active()->usable()->get();

// 5. Query discounts by products
$productDiscounts = Discount::products([1, 2, 3], 'condition')->get();

// 6. Reset cache after modifications
Discount::resetDiscounts();

// 7. Check if discount can be used
if ($discount->uses < $discount->max_uses || $discount->max_uses === null) {
    // Discount can be used
}

// 8. Increment uses when applied
$discount->increment('uses');
```

**Discount Priority and Stop**:

- **Priority**: Higher priority discounts are applied first. If multiple discounts can apply, priority determines the order.
- **Stop**: When `stop` is `true`, this discount will prevent other discounts from being applied after it. This is useful for exclusive offers.

**Example with Priority and Stop**:

```php
// High priority discount that stops others
$exclusiveDiscount = Discount::create([
    'name' => 'Exclusive 50% Off',
    'handle' => 'exclusive_50',
    'type' => 'Lunar\DiscountTypes\Coupon',
    'data' => ['coupon' => 'EXCLUSIVE50'],
    'starts_at' => now(),
    'ends_at' => now()->addDays(7),
    'priority' => 10, // High priority
    'stop' => true,    // Stops other discounts
]);

// Lower priority discount (won't apply if exclusive is used)
$regularDiscount = Discount::create([
    'name' => 'Regular 10% Off',
    'handle' => 'regular_10',
    'type' => 'Lunar\DiscountTypes\Coupon',
    'data' => ['coupon' => 'REGULAR10'],
    'starts_at' => now(),
    'ends_at' => now()->addDays(30),
    'priority' => 1,  // Lower priority
    'stop' => false,
]);
```

**Best Practices**:

- **Use Handles**: Always use unique handles for discounts to make them easy to reference
- **Set Expiration Dates**: Use `ends_at` to prevent discounts from running indefinitely
- **Limit Uses**: Set `max_uses` to control how many times a discount can be applied
- **Use Priority**: Set appropriate priorities to control which discounts apply first
- **Use Stop Flag**: Set `stop` to `true` for exclusive discounts that shouldn't stack
- **Reset Cache**: Always reset the discount cache after programmatically creating or modifying discounts
- **Check Active/Usable**: Always check if a discount is active and usable before applying
- **Track Uses**: Increment the `uses` counter when a discount is successfully applied
- **Conditions vs Rewards**: Use conditions for required items, rewards for items that get discounted
- **Test Discounts**: Test discount logic thoroughly, especially with multiple discounts and priorities
- **Eager Load**: When loading discounts, eager load relationships:
  ```php
  Discount::with(['purchasables.purchasable'])->get();
  ```

**Documentation**: See [Lunar Discounts documentation](https://docs.lunarphp.com/1.x/reference/discounts)

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

This project implements orders following the [Lunar Orders documentation](https://docs.lunarphp.com/1.x/reference/orders). As you'd expect, orders on an online system show what users have purchased. Orders are linked to Carts and although you would generally only have one Order per cart, the system will support multiple if your store requires it.

**Overview**:

Orders in Lunar:
- Are linked to Carts (typically one order per cart, but multiple supported)
- Support guest and authenticated users
- Store all pricing, tax, discount, and shipping information
- Include order lines for individual items
- Support shipping and billing addresses
- Track payment transactions (charges and refunds)
- Support draft and placed states
- Generate unique order references automatically
- Support custom order statuses with notifications

**Orders**:

The `Order` model has the following fields:

| Field                   | Description                                                                                                                                       |
|-------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------|
| `id`                    | Primary key                                                                                                                                       |
| `user_id`               | If this is not a guest order, this will have the user's id                                                                                       |
| `customer_id`           | Can be null, stores customer                                                                                                                      |
| `cart_id`               | The related cart                                                                                                                                  |
| `channel_id`            | Which channel this was purchased through                                                                                                         |
| `status`                | A status that makes sense to you as the store owner                                                                                               |
| `reference`             | Your store's own reference                                                                                                                        |
| `customer_reference`    | If you want customers to add their own reference, it goes here                                                                                   |
| `sub_total`             | The sub total minus any discounts, excl. tax                                                                                                     |
| `discount_breakdown`    | A JSON field for the discount breakdown e.g. `[{"discount_id": 1, "lines": [{"id": 1, "qty": 1}], "total": 200}]`                               |
| `discount_total`        | Any discount amount excl. tax                                                                                                                     |
| `shipping_breakdown`    | A JSON field for the shipping breakdown e.g. `[{"name": "Standard Delivery", "identifier": "STD", "price": 123}]`                                |
| `shipping_total`        | The shipping total with tax                                                                                                                       |
| `tax_breakdown`         | A JSON field for the tax breakdown e.g. `[{"description": "VAT", "identifier": "vat", "value": 123, "percentage": 20, "currency_code": "GBP"}]` |
| `tax_total`             | The total amount of tax applied                                                                                                                   |
| `total`                 | The grand total with tax                                                                                                                          |
| `notes`                 | Any additional order notes                                                                                                                        |
| `currency_code`         | The code of the currency the order was placed in                                                                                                  |
| `compare_currency_code` | The code of the default currency at the time                                                                                                       |
| `exchange_rate`         | The exchange rate between currency_code and compare_currency_code                                                                                  |
| `placed_at`             | The datetime the order was considered placed (null = draft)                                                                                      |
| `meta`                  | Any additional meta info you wish to store                                                                                                        |
| `created_at`            | Creation timestamp                                                                                                                                |
| `updated_at`            | Last update timestamp                                                                                                                             |

**Create an Order**:

You can either create an order directly, or the recommended way is via a `Cart` model:

```php
use Lunar\Models\Order;
use Lunar\Models\Cart;

// Direct creation (not recommended)
$order = Order::create([/* ... */]);

// Recommended way - create from cart
$cart = Cart::first();
$order = $cart->createOrder(
    allowMultipleOrders: false,
    orderIdToUpdate: null,
);
```

**Parameters**:

- `allowMultipleOrders`: Generally carts will only have one draft order associated. However, if you want to allow carts to have multiple, you can pass `true` here.
- `orderIdToUpdate`: You can optionally pass the ID of an order to update instead of attempting to create a new order. This must be a draft order (i.e., a null `placed_at`) and related to the cart.

**Customizing Order Creation**:

The underlying class for creating an order is `Lunar\Actions\Carts\CreateOrder`. You are free to override this in the config file `config/lunar/cart.php`:

```php
return [
    // ...
    'actions' => [
        // ...
        'order_create' => CustomCreateOrder::class
    ]
];
```

At minimum your class should look like the following:

```php
<?php

namespace App\Actions;

use Lunar\Actions\AbstractAction;
use Lunar\Models\Cart;

final class CustomCreateOrder extends AbstractAction
{
    /**
     * Execute the action.
     */
    public function execute(
        Cart $cart,
        bool $allowMultipleOrders = false,
        int $orderIdToUpdate = null
    ): self {
        // Your custom order creation logic
        return $this;
    }
}
```

**Validating a Cart Before Creation**:

If you also want to check before you attempt this if the cart is ready to create an order, you can call the helper method:

```php
use Lunar\Models\Cart;

$cart = Cart::first();

if ($cart->canCreateOrder()) {
    $order = $cart->createOrder();
} else {
    // Cart is not ready (e.g., missing shipping address, invalid items, etc.)
}
```

Under the hood, this will use the `ValidateCartForOrderCreation` class which Lunar provides and throw any validation exceptions with helpful messages if the cart isn't ready to create an order. You can specify your own class to handle this in `config/lunar/cart.php`:

```php
return [
    // ...
    'validators' => [
        'order_create' => MyCustomValidator::class,
    ]
];
```

Which may look something like:

```php
<?php

namespace App\Validation;

use Lunar\Validation\BaseValidator;

class MyCustomValidator extends BaseValidator
{
    public function validate(): bool
    {
        $cart = $this->parameters['cart'];

        if ($somethingWentWrong) {
            return $this->fail('There was an issue');
        }

        return $this->pass();
    }
}
```

**Order Reference Generating**:

By default, Lunar will generate a new order reference for you when you create an order from a cart. The format for this is:

```
{prefix?}{0..0}{orderId}
```

`{0..0}` indicates the order id will be padded until the length is 8 digits (not including the prefix). The prefix is optional and defined in the `config/lunar/orders.php` config file:

```php
return [
    'reference_format' => [
        'prefix' => null, // Optional prefix (e.g., 'ORD-')
        'padding_direction' => STR_PAD_LEFT, // STR_PAD_LEFT, STR_PAD_RIGHT, or STR_PAD_BOTH
        'padding_character' => '0', // Character to use for padding
        'length' => 8, // Length to pad to
    ],
];
```

**Custom Generators**:

If your store has a specific requirement for how references are generated, you can easily swap out the Lunar one for your own in `config/lunar/orders.php`:

```php
return [
    'reference_generator' => App\Generators\MyCustomGenerator::class,
];
```

Or, if you don't want references at all (not recommended), you can simply set it to `null`.

Here's the underlying class for the custom generator:

```php
<?php

namespace App\Generators;

use Lunar\Models\Order;
use Lunar\Base\Contracts\OrderReferenceGeneratorInterface;

class MyCustomGenerator implements OrderReferenceGeneratorInterface
{
    /**
     * {@inheritDoc}
     */
    public function generate(Order $order): string
    {
        // Generate your custom reference
        // Example: ORD-2024-0001
        return 'ORD-' . date('Y') . '-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
    }
}
```

**Modifying Orders**:

If you need to programmatically change the Order values or add in new behaviour, you will want to extend the Order system. You can find out more in the [Extending Orders documentation](https://docs.lunarphp.com/1.x/extending/orders) and the Extension Points section below.

**Order Lines**:

The `OrderLine` model represents individual items in the order:

| Field             | Description                                                                                                                                       |
|-------------------|---------------------------------------------------------------------------------------------------------------------------------------------------|
| `id`              | Primary key                                                                                                                                       |
| `order_id`        | Foreign key to order                                                                                                                              |
| `purchasable_type`| Morph reference for the purchasable item (e.g., `product_variant`)                                                                                |
| `purchasable_id`  | Morph ID                                                                                                                                          |
| `type`            | Whether digital, physical, etc                                                                                                                    |
| `description`     | A description of the line item                                                                                                                     |
| `option`          | If this was a variant, the option info is here                                                                                                    |
| `identifier`      | Something to identify the purchasable item, usually an SKU                                                                                        |
| `unit_price`      | The unit price of the line                                                                                                                        |
| `unit_quantity`   | The line unit quantity, usually this is 1                                                                                                         |
| `quantity`        | The amount of this item purchased                                                                                                                 |
| `sub_total`       | The sub total minus any discounts, excl. tax                                                                                                      |
| `discount_total`  | Any discount amount excl. tax                                                                                                                     |
| `tax_breakdown`   | A JSON field for the tax breakdown e.g. `[{"description": "VAT", "identifier": "vat", "value": 123, "percentage": 20, "currency_code": "GBP"}]` |
| `tax_total`       | The total amount of tax applied                                                                                                                   |
| `total`           | The grand total with tax                                                                                                                          |
| `notes`           | Any additional order notes                                                                                                                        |
| `meta`            | Any additional meta info you wish to store                                                                                                        |
| `created_at`      | Creation timestamp                                                                                                                                |
| `updated_at`      | Last update timestamp                                                                                                                             |

**Create an Order Line**:

If you are using the `createOrder` method on a cart, this is all handled for you automatically. However, you can create order lines manually:

```php
use Lunar\Models\OrderLine;
use Lunar\Models\Order;
use Lunar\Models\ProductVariant;

$order = Order::find(1);
$variant = ProductVariant::find(1);

// Create order line directly
OrderLine::create([
    'order_id' => $order->id,
    'purchasable_type' => $variant->getMorphClass(),
    'purchasable_id' => $variant->id,
    'type' => 'physical',
    'description' => $variant->product->translateAttribute('name'),
    'option' => 'Size: Large, Color: Red',
    'identifier' => $variant->sku,
    'unit_price' => 2000, // $20.00 in cents
    'unit_quantity' => 1,
    'quantity' => 2,
    'sub_total' => 4000,
    'discount_total' => 0,
    'tax_breakdown' => [],
    'tax_total' => 0,
    'total' => 4000,
]);

// Or via the relationship
$order->lines()->create([
    // ... same data
]);
```

**Order Addresses**:

An order can have many addresses. Typically you would just have one for billing and one for shipping.

If you are using the `createOrder` method on a cart, this is all handled for you automatically. However, you can create order addresses manually:

```php
use Lunar\Models\OrderAddress;
use Lunar\Models\Order;
use Lunar\Models\Country;

$order = Order::find(1);
$country = Country::where('iso2', 'GB')->first();

// Create order address directly
OrderAddress::create([
    'order_id' => $order->id,
    'country_id' => $country->id,
    'title' => null,
    'first_name' => 'Jacob',
    'last_name' => 'Smith',
    'company_name' => null,
    'line_one' => '123 Foo Street',
    'line_two' => null,
    'line_three' => null,
    'city' => 'London',
    'state' => null,
    'postcode' => 'NW1 1WN',
    'delivery_instructions' => null,
    'contact_email' => 'jacob@example.com',
    'contact_phone' => '+44 20 1234 5678',
    'type' => 'shipping', // or 'billing'
    'shipping_option' => 'STD', // A unique code for you to identify shipping
]);

// Or via the relationship
$order->addresses()->create([
    // ... same data
]);
```

You can then use some relationship helpers to fetch the address you need:

```php
$order = Order::find(1);

$order->shippingAddress; // Get shipping address
$order->billingAddress;  // Get billing address
```

**Shipping Options**:

A Shipping Tables addon is planned to make setting up shipping in the admin hub easy for most scenarios.

To add Shipping Options, you will need to extend Lunar to add in your own logic. Then in your checkout, or wherever you want, you can fetch these options:

```php
use Lunar\Facades\ShippingManifest;
use Lunar\Models\Cart;

$cart = Cart::first();
$shippingOptions = ShippingManifest::getOptions($cart);
```

This will return a collection of `Lunar\DataTypes\ShippingOption` objects.

**Adding the Shipping Option to the Cart**:

Once the user has selected the shipping option they want, you will need to add this to the cart so it can calculate the new totals:

```php
use Lunar\DataTypes\ShippingOption;
use Lunar\DataTypes\Price;
use Lunar\Models\Cart;
use Lunar\Models\TaxClass;

$cart = Cart::first();
$taxClass = TaxClass::first();

$shippingOption = new ShippingOption(
    name: 'Standard Delivery',
    description: '5-7 business days',
    identifier: 'STD',
    price: new Price(500, $cart->currency, 1), // $5.00
    taxClass: $taxClass
);

$cart->setShippingOption($shippingOption);
$cart->calculate(); // Recalculate totals with shipping
```

**Transactions**:

The `Transaction` model stores payment transactions:

| Field       | Description                                                                                   |
|-------------|----------------------------------------------------------------------------------------------|
| `id`        | Primary key                                                                                   |
| `order_id`  | Foreign key to order                                                                           |
| `success`   | Whether the transaction was successful                                                        |
| `refund`    | `true` if this was a refund                                                                  |
| `driver`    | The payment driver used (e.g., `stripe`)                                                     |
| `amount`    | An integer amount (in cents/smallest currency unit)                                           |
| `reference` | The reference returned from the payment Provider. Used to identify the transaction with them |
| `status`    | A string representation of the status, unlinked to Lunar (e.g., `succeeded`, `settled`)      |
| `notes`     | Any relevant notes for the transaction                                                        |
| `card_type` | e.g., `visa`                                                                                  |
| `last_four` | Last 4 digits of the card                                                                     |
| `meta`      | Any additional meta info you wish to store                                                    |
| `created_at`| Creation timestamp                                                                            |
| `updated_at`| Last update timestamp                                                                         |

**Create a Transaction**:

Just because an order has a transaction does not mean it has been placed. Lunar determines whether an order is considered placed when the `placed_at` column has a datetime, regardless if any transactions exist or not.

Most stores will likely want to store a transaction against the order. This helps determine how much has been paid, how it was paid, and gives a clue on the best way to issue a refund if needed:

```php
use Lunar\Models\Transaction;
use Lunar\Models\Order;

$order = Order::find(1);

// Create transaction directly
Transaction::create([
    'order_id' => $order->id,
    'success' => true,
    'refund' => false,
    'driver' => 'stripe',
    'amount' => $order->total,
    'reference' => 'ch_1234567890',
    'status' => 'succeeded',
    'card_type' => 'visa',
    'last_four' => '4242',
    'notes' => 'Payment processed successfully',
]);

// Or via the order relationship
$order->transactions()->create([
    // ... same data
]);
```

These can then be returned via the relationship:

```php
$order = Order::find(1);

$order->transactions; // Get all transactions
$order->charges;      // Get all transactions that are charges (refund = false)
$order->refunds;      // Get all transactions that are refunds (refund = true)
```

**Payments**:

Lunar will be looking to add support for the most popular payment providers, so keep an eye out here as they will list them all out. In the meantime, you can absolutely still get a storefront working. At the end of the day, Lunar doesn't really mind what payment provider you use or plan to use.

In terms of an order, all it's worried about is whether or not the `placed_at` column is populated on the orders table. The rest is completely up to you how you want to handle that. We have some helper utilities to make such things easier for you as laid out above.

**Order Status**:

The `placed_at` field determines whether an Order is considered draft or placed. The Order model has two helper methods to determine the status of an Order:

```php
use Lunar\Models\Order;

$order = Order::find(1);

$order->isDraft();  // Returns true if placed_at is null
$order->isPlaced(); // Returns true if placed_at is not null
```

**Order Notifications**:

Lunar allows you to specify what Laravel mailers/notifications should be available for sending when you update an order's status. These are configured in the `config/lunar/orders.php` config file and are defined like so:

```php
return [
    'statuses' => [
        'awaiting-payment' => [
            'label' => 'Awaiting Payment',
            'color' => '#848a8c',
            'mailers' => [
                App\Mail\OrderAwaitingPayment::class,
                App\Mail\OrderConfirmation::class,
            ],
            'notifications' => [],
        ],
        'payment-received' => [
            'label' => 'Payment Received',
            'color' => '#6a67ce',
            'mailers' => [
                App\Mail\OrderPaymentReceived::class,
            ],
            'notifications' => [],
        ],
        'dispatched' => [
            'label' => 'Dispatched',
            'mailers' => [
                App\Mail\OrderDispatched::class,
            ],
            'notifications' => [],
        ],
    ],
];
```

Now when you update an order's status in the hub, you will have these mailers available if the new status is `awaiting-payment`. You can then choose the email addresses which the email should be sent to and also add an additional email address if required. Once updated, Lunar will keep a render of the email sent out in the activity log so you have a clear history of what's been sent out.

**Important**: These email notifications do not get sent out automatically if you update the status outside of the hub.

**Mailer Template**:

When building out the template for your mailer, you should assume you have access to the `$order` model. When the status is updated, this is passed through to the view data for the mailer, along with any additional content entered. Since you may not always have additional content when sending out the mailer, you should check the existence first. Here's an example of what the template could look like:

```blade
{{-- resources/views/mail/order-status.blade.php --}}
<h1>It's on the way!</h1>

<p>Your order with reference {{ $order->reference }} has been dispatched!</p>

<p>{{ $order->total->formatted() }}</p>

@if($content ?? null)
    <h2>Additional notes</h2>
    <p>{{ $content }}</p>
@endif

@foreach($order->lines as $line)
    <div>
        <strong>{{ $line->description }}</strong>
        <p>Quantity: {{ $line->quantity }}</p>
        <p>Price: {{ $line->total->formatted() }}</p>
    </div>
@endforeach
```

**Order Invoice PDF**:

By default, when you click "Download PDF" in the admin panel when viewing an order, you will get a basic PDF generated for you to download. You can publish the view that powers this to create your own PDF template:

```bash
php artisan vendor:publish --tag=lunarpanel.pdf
```

This will create a view called `resources/vendor/lunarpanel/pdf/order.blade.php`, where you will be able to freely customise the PDF you want displayed on download.

**Using OrderHelper**:

The `OrderHelper` class provides convenience methods:

```php
use App\Lunar\Orders\OrderHelper;
use Lunar\Models\Order;
use Lunar\Models\Cart;
use Lunar\Facades\CartSession;

// Create order from cart (recommended)
$cart = CartSession::current();
$order = OrderHelper::createFromCart($cart);

// Create with options
$order = OrderHelper::createFromCart(
    $cart,
    allowMultipleOrders: false,
    orderIdToUpdate: null
);

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

// Create order line
$orderLine = OrderHelper::createLine($order, [
    'purchasable_type' => $variant->getMorphClass(),
    'purchasable_id' => $variant->id,
    'type' => 'physical',
    'description' => $variant->product->translateAttribute('name'),
    'identifier' => $variant->sku,
    'unit_price' => 2000,
    'quantity' => 2,
    'sub_total' => 4000,
    'tax_total' => 0,
    'total' => 4000,
]);

// Create order address
$address = OrderHelper::createAddress($order, [
    'country_id' => $country->id,
    'first_name' => 'John',
    'last_name' => 'Doe',
    'line_one' => '123 Main Street',
    'city' => 'London',
    'postcode' => 'NW1 1WN',
    'type' => 'shipping',
]);

// Get addresses
$shippingAddress = OrderHelper::getShippingAddress($order);
$billingAddress = OrderHelper::getBillingAddress($order);

// Create transaction (charge)
$transaction = OrderHelper::createTransaction($order, [
    'success' => true,
    'refund' => false,
    'driver' => 'stripe',
    'amount' => $order->total,
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

**Example: Complete Order Workflow**:

```php
use Lunar\Models\Cart;
use Lunar\Models\Order;
use Lunar\Models\Transaction;
use Lunar\Facades\CartSession;
use Lunar\Facades\ShippingManifest;

// 1. Get current cart
$cart = CartSession::current();
$cart->calculate();

// 2. Validate cart can create order
if (!$cart->canCreateOrder()) {
    throw new \Exception('Cart is not ready to create order');
}

// 3. Create order from cart
$order = $cart->createOrder(
    allowMultipleOrders: false,
    orderIdToUpdate: null
);

// 4. Order is created as draft (placed_at is null)
$order->isDraft(); // true
$order->isPlaced(); // false

// 5. Process payment (example with Stripe)
// ... payment processing logic ...

// 6. Create transaction
$transaction = $order->transactions()->create([
    'success' => true,
    'refund' => false,
    'driver' => 'stripe',
    'amount' => $order->total,
    'reference' => $stripePaymentIntent->id,
    'status' => 'succeeded',
    'card_type' => 'visa',
    'last_four' => '4242',
]);

// 7. Mark order as placed
$order->update(['placed_at' => now()]);

// 8. Update order status
$order->update(['status' => 'payment-received']);

// 9. Access order data
$order->reference;           // Auto-generated reference
$order->sub_total;            // Subtotal excluding tax
$order->discount_total;       // Discount amount
$order->shipping_total;       // Shipping total
$order->tax_total;           // Tax total
$order->total;               // Grand total

// 10. Access order lines
foreach ($order->lines as $line) {
    $line->description;       // Product description
    $line->quantity;          // Quantity
    $line->unit_price;        // Unit price
    $line->total;             // Line total
}

// 11. Access addresses
$shippingAddress = $order->shippingAddress;
$billingAddress = $order->billingAddress;

// 12. Access transactions
$charges = $order->charges;   // All charge transactions
$refunds = $order->refunds;   // All refund transactions
$allTransactions = $order->transactions; // All transactions

// 13. Calculate payment totals
$totalCharged = $order->charges->sum('amount');
$totalRefunded = $order->refunds->sum('amount');
$netAmount = $totalCharged - $totalRefunded;
```

**Best Practices**:

- **Create from Cart**: Always use `$cart->createOrder()` instead of creating orders directly
- **Validate Before Creating**: Always check `$cart->canCreateOrder()` before creating an order
- **Mark as Placed**: Set `placed_at` only after successful payment confirmation
- **Store Transactions**: Always create transaction records for payment tracking
- **Use Order References**: Use the auto-generated reference for customer-facing order numbers
- **Track Status**: Use order status to track order lifecycle (awaiting-payment, payment-received, dispatched, etc.)
- **Store Breakdowns**: The JSON breakdown fields (discount_breakdown, shipping_breakdown, tax_breakdown) provide detailed information for reporting
- **Eager Load**: When loading orders, eager load relationships:
  ```php
  Order::with(['lines.purchasable', 'addresses', 'transactions', 'user', 'customer'])->get();
  ```
- **Draft Orders**: Keep orders as drafts until payment is confirmed
- **Transaction Tracking**: Track all payment attempts, not just successful ones
- **Refund Tracking**: Always create refund transactions when processing refunds
- **Status Notifications**: Configure status mailers for automated customer communication
- **Custom Generators**: Use custom reference generators if you need specific formats
- **Order Pipelines**: Use order pipelines to add custom logic during order creation

**Documentation**: See [Lunar Orders documentation](https://docs.lunarphp.com/1.x/reference/orders)

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

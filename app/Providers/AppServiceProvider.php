<?php

namespace App\Providers;

use App\Models\InventoryLevel;
use App\Observers\CartObserver;
use App\Observers\CartLineObserver;
use App\Observers\InventoryLevelObserver;
use Illuminate\Support\ServiceProvider;
use Lunar\Admin\Support\Facades\AttributeData;
use Lunar\Admin\Support\Facades\LunarPanel;
use Lunar\Facades\Discounts;
use Lunar\Models\Cart;
use Lunar\Models\CartLine;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register admin panel with custom configuration
        // See: https://docs.lunarphp.com/1.x/admin/extending/panel
        LunarPanel::panel(function ($panel) {
            return $panel
                // Customize panel path (default is '/lunar')
                // ->path('admin')
                
                // Register standalone Filament Pages
                // ->pages([
                //     \App\Admin\Pages\SalesReport::class,
                //     \App\Admin\Pages\RevenueReport::class,
                // ])
                
                // Register new Filament Resources
                // ->resources([
                //     \App\Admin\Resources\OpeningTimeResource::class,
                //     \App\Admin\Resources\BannerResource::class,
                // ])
                
                // Register Livewire components
                // ->livewireComponents([
                //     \App\Admin\Livewire\OrdersSalesChart::class,
                // ])
                
                // Register Filament plugins
                // ->plugin(new \App\Admin\Plugins\ExamplePlugin())
                
                // Customize navigation groups
                // ->navigationGroups([
                //     'Catalog',
                //     'Sales',
                //     'CMS',
                //     'Reports',
                //     'Shipping',
                //     'Settings',
                // ])
            ;
        })
            // Register admin panel extensions (pages, resources, relation managers, and order management)
            // See: https://docs.lunarphp.com/1.x/admin/extending/pages
            // See: https://docs.lunarphp.com/1.x/admin/extending/resources
            // See: https://docs.lunarphp.com/1.x/admin/extending/relation-managers
            // See: https://docs.lunarphp.com/1.x/admin/extending/order-management
            // ->extensions([
            //     // Resource extensions
            //     \Lunar\Panel\Filament\Resources\ProductResource::class => 
            //         \App\Admin\Extensions\Resources\ExampleProductResourceExtension::class,
            //
            //     // Relation manager extensions
            //     \Lunar\Admin\Filament\Resources\ProductResource\RelationManagers\CustomerGroupPricingRelationManager::class => 
            //         \App\Admin\Extensions\RelationManagers\ExampleCustomerGroupPricingRelationManagerExtension::class,
            //
            //     // Create page extensions
            //     \Lunar\Admin\Filament\Resources\CustomerGroupResource\Pages\CreateCustomerGroup::class => 
            //         \App\Admin\Extensions\Pages\ExampleCreatePageExtension::class,
            //
            //     // Edit page extensions
            //     \Lunar\Panel\Filament\Resources\ProductResource\Pages\EditProduct::class => 
            //         \App\Admin\Extensions\Pages\ExampleEditPageExtension::class,
            //
            //     // List page extensions
            //     \Lunar\Panel\Filament\Resources\ProductResource\Pages\ListProducts::class => 
            //         \App\Admin\Extensions\Pages\ExampleListPageExtension::class,
            //
            //     // View page extensions
            //     \Lunar\Admin\Filament\Resources\OrderResource\Pages\ManageOrder::class => 
            //         \App\Admin\Extensions\Pages\ExampleViewPageExtension::class,
            //
            //     // Order management extensions
            //     \Lunar\Admin\Filament\Resources\OrderResource\Pages\ManageOrder::class => 
            //         \App\Admin\Extensions\OrderManagement\ExampleManageOrderExtension::class,
            //
            //     // Order items table extensions
            //     \Lunar\Admin\Filament\Resources\OrderResource\Pages\Components\OrderItemsTable::class => 
            //         \App\Admin\Extensions\OrderManagement\ExampleOrderItemsTableExtension::class,
            //
            //     // Relation page extensions
            //     \Lunar\Panel\Filament\Resources\ProductResource\Pages\ManageProductMedia::class => 
            //         \App\Admin\Extensions\Pages\ExampleRelationPageExtension::class,
            // ])
            ->register();

        // Register custom attribute field types
        // See: https://docs.lunarphp.com/1.x/admin/extending/attributes#register-the-field
        // AttributeData::registerFieldType(
        //     \App\FieldTypes\CustomField::class,
        //     \App\Admin\FieldTypes\CustomFieldType::class
        // );

        // Two-Factor Authentication configuration
        // See: https://docs.lunarphp.com/1.x/admin/extending/access-control#two-factor-authentication
        // To enable 2FA, use the panel() method before ->register():
        // LunarPanel::panel(fn($panel) => $panel)->enforceTwoFactorAuth()->register();
        // LunarPanel::panel(fn($panel) => $panel)->disableTwoFactorAuth()->register();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(\Lunar\Base\ShippingModifiers $shippingModifiers = null): void
    {
        // Register custom payment drivers
        // See: https://docs.lunarphp.com/1.x/extending/payments#registering-your-driver
        // \Lunar\Facades\Payments::extend('custom', function ($app) {
        //     return $app->make(\App\Lunar\Payments\PaymentProviders\CustomPayment::class);
        // });

        // Register custom shipping modifiers
        // See: https://docs.lunarphp.com/1.x/extending/shipping#adding-a-shipping-modifier
        // When uncommenting, remove the nullable type hint and use:
        // public function boot(\Lunar\Base\ShippingModifiers $shippingModifiers): void
        // $shippingModifiers->add(
        //     \App\Lunar\Shipping\Modifiers\CustomShippingModifier::class
        // );

        // Register custom tax drivers
        // See: https://docs.lunarphp.com/1.x/extending/taxation#writing-your-own-driver
        // \Lunar\Facades\Taxes::extend('custom', function ($app) {
        //     return $app->make(\App\Lunar\Taxation\Drivers\CustomTaxDriver::class);
        // });

        // Register custom Lunar models
        // Register the extended Product model with custom attributes
        \Lunar\Facades\ModelManifest::replace(
            \Lunar\Models\Contracts\Product::class,
            \App\Models\Product::class,
        );
        
        // Register the extended ProductVariant model
        \Lunar\Facades\ModelManifest::replace(
            \Lunar\Models\Contracts\ProductVariant::class,
            \App\Models\ProductVariant::class,
        );
        
        // Register extended Channel model (if Lunar supports Channel contracts)
        // Note: Channel may not have a contract, so this might not be needed
        // The extended Channel model will be used automatically when imported
        
        // Or register all models in a directory:
        // \Lunar\Facades\ModelManifest::addDirectory(__DIR__.'/../Models');
        
        // See: https://docs.lunarphp.com/1.x/extending/models

        // Register custom discount types
        Discounts::addType(\App\Lunar\Discounts\DiscountTypes\PercentageDiscount::class);
        Discounts::addType(\App\Lunar\Discounts\DiscountTypes\FixedAmountDiscount::class);
        Discounts::addType(\App\Lunar\Discounts\DiscountTypes\BOGODiscount::class);
        Discounts::addType(\App\Lunar\Discounts\DiscountTypes\CategoryDiscount::class);
        Discounts::addType(\App\Lunar\Discounts\DiscountTypes\ProductDiscount::class);

        // Register observers
        InventoryLevel::observe(InventoryLevelObserver::class);
        Cart::observe(CartObserver::class);
        CartLine::observe(CartLineObserver::class);
        \Lunar\Models\Order::observe(\App\Observers\OrderObserver::class);

        // Schedule product schedule processing (runs every hour)
        if (app()->runningInConsole()) {
            $this->app->booted(function () {
                $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
                $schedule->job(\App\Jobs\ProcessProductSchedules::class)->hourly();
            });
        }
    }
}

<?php

namespace App\Providers;

use App\Models\InventoryLevel;
use App\Models\User;
use App\Observers\CartObserver;
use App\Observers\CartLineObserver;
use App\Observers\InventoryLevelObserver;
use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Lunar\Facades\Discounts;
use App\Models\Cart;
use App\Models\CartLine;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // NOTE: We intentionally do NOT register the package-provided Filament panel here.
        // This project uses its own Filament panel at `/admin` (see `App\Providers\Filament\AdminPanelProvider`).

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
        \Lunar\Facades\ModelManifest::replace(
            \Lunar\Models\Contracts\Cart::class,
            \App\Models\Cart::class,
        );
        \Lunar\Facades\ModelManifest::replace(
            \Lunar\Models\Contracts\CartLine::class,
            \App\Models\CartLine::class,
        );
        \Lunar\Facades\ModelManifest::replace(
            \Lunar\Models\Contracts\Attribute::class,
            \App\Models\Attribute::class,
        );

        // Register the extended Collection model (scopes, scheduling, homepage fields, etc.)
        \Lunar\Facades\ModelManifest::replace(
            \Lunar\Models\Contracts\Collection::class,
            \App\Models\Collection::class,
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
        User::observe(UserObserver::class);

        // Add ordered() macro to Builder as fallback for models that don't have scopeOrdered
        // This prevents "Call to undefined method ordered()" errors
        // Note: In Laravel, macros are checked before scopes, so we need to delegate to scope if it exists
        Builder::macro('ordered', function ($direction = 'asc') {
            $model = $this->getModel();
            
            // Check if the model has a scopeOrdered method and delegate to it
            if (method_exists($model, 'scopeOrdered')) {
                // Get reflection to check method signature
                $reflection = new \ReflectionMethod($model, 'scopeOrdered');
                $params = $reflection->getParameters();
                
                // If scope accepts direction parameter, pass it; otherwise don't
                if (count($params) >= 2) {
                    return $model->scopeOrdered($this, $direction);
                } else {
                    return $model->scopeOrdered($this);
                }
            }
            
            // Fallback: try to order by common ordering fields
            $orderFields = ['display_order', 'position', 'sort_order', 'order'];
            
            foreach ($orderFields as $field) {
                try {
                    $schema = $model->getConnection()->getSchemaBuilder();
                    if ($schema->hasColumn($model->getTable(), $field)) {
                        return $this->orderBy($field, $direction)->orderBy('id', $direction);
                    }
                } catch (\Exception $e) {
                    // Continue to next field if column check fails
                    continue;
                }
            }
            
            // Final fallback: order by id
            return $this->orderBy('id', $direction);
        });

        // Schedule product schedule processing (runs every hour)
        if (app()->runningInConsole()) {
            $this->app->booted(function () {
                $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
                $schedule->job(\App\Jobs\ProcessProductSchedules::class)->hourly();
            });
        }
    }
}

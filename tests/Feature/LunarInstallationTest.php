<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LunarInstallationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that Lunar service provider is registered
     */
    public function test_lunar_service_provider_is_registered(): void
    {
        // Check if Lunar core service provider is registered
        $providers = App::getLoadedProviders();
        
        $this->assertArrayHasKey('Lunar\LunarServiceProvider', $providers);
        $this->assertTrue($providers['Lunar\LunarServiceProvider']);
    }

    /**
     * Test that Lunar panel service provider is registered
     */
    public function test_lunar_panel_service_provider_is_registered(): void
    {
        // Check if Lunar admin panel service provider is registered
        $providers = App::getLoadedProviders();
        
        $this->assertArrayHasKey('Lunar\Admin\LunarPanelProvider', $providers);
        $this->assertTrue($providers['Lunar\Admin\LunarPanelProvider']);
    }

    /**
     * Test that configuration files are published correctly
     */
    public function test_configuration_files_are_published_correctly(): void
    {
        // Check that main Lunar configuration files exist
        $configFiles = [
            'lunar/cart.php',
            'lunar/cart_session.php',
            'lunar/database.php',
            'lunar/discounts.php',
            'lunar/media.php',
            'lunar/orders.php',
            'lunar/payments.php',
            'lunar/pricing.php',
            'lunar/products.php',
            'lunar/search.php',
            'lunar/shipping.php',
            'lunar/taxes.php',
            'lunar/urls.php',
            'lunar/panel.php',
        ];

        foreach ($configFiles as $configFile) {
            $this->assertTrue(
                File::exists(config_path($configFile)),
                "Configuration file {$configFile} should exist"
            );
        }
    }

    /**
     * Test that configuration values are accessible
     */
    public function test_configuration_values_are_accessible(): void
    {
        // Test that we can access Lunar configuration values
        $this->assertNotNull(Config::get('lunar.cart'));
        $this->assertNotNull(Config::get('lunar.database'));
        $this->assertNotNull(Config::get('lunar.products'));
        $this->assertNotNull(Config::get('lunar.panel'));
        
        // Test specific configuration values
        $this->assertIsArray(Config::get('lunar.cart'));
        $this->assertIsArray(Config::get('lunar.database'));
    }

    /**
     * Test that database tables are created
     */
    public function test_database_tables_are_created(): void
    {
        // Test core Lunar tables exist
        $coreTables = [
            'lunar_channels',
            'lunar_languages',
            'lunar_currencies',
            'lunar_products',
            'lunar_product_variants',
            'lunar_product_types',
            'lunar_customers',
            'lunar_customer_groups',
            'lunar_carts',
            'lunar_cart_lines',
            'lunar_orders',
            'lunar_order_lines',
            'lunar_collections',
            'lunar_attributes',
            'lunar_prices',
            'lunar_addresses',
            'lunar_countries',
            'lunar_states',
            'lunar_tax_classes',
            'lunar_tax_zones',
            'lunar_tax_rates',
            'lunar_staff',
        ];

        foreach ($coreTables as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Table {$table} should exist in the database"
            );
        }
    }

    /**
     * Test that User model implements LunarUser interface
     */
    public function test_user_model_implements_lunar_user_interface(): void
    {
        $user = new \App\Models\User();
        
        $this->assertInstanceOf(\Lunar\Base\LunarUser::class, $user);
        
        // Test that the User model has the required methods from LunarUser trait
        $this->assertTrue(method_exists($user, 'customers'));
        $this->assertTrue(method_exists($user, 'carts'));
        $this->assertTrue(method_exists($user, 'latestCustomer'));
        $this->assertTrue(method_exists($user, 'orders'));
    }

    /**
     * Test that Lunar models are accessible
     */
    public function test_lunar_models_are_accessible(): void
    {
        // Test that we can instantiate core Lunar models
        $models = [
            \Lunar\Models\Product::class,
            \Lunar\Models\ProductVariant::class,
            \Lunar\Models\Customer::class,
            \Lunar\Models\Cart::class,
            \Lunar\Models\Order::class,
            \Lunar\Models\Collection::class,
            \Lunar\Models\Currency::class,
            \Lunar\Models\Channel::class,
        ];

        foreach ($models as $modelClass) {
            $this->assertTrue(
                class_exists($modelClass),
                "Model {$modelClass} should be accessible"
            );
            
            // Test that we can create an instance
            $model = new $modelClass();
            $this->assertInstanceOf($modelClass, $model);
        }
    }

    /**
     * Test that Filament admin panel is configured
     */
    public function test_filament_admin_panel_is_configured(): void
    {
        // Test that Filament panel classes are available
        $this->assertTrue(class_exists(\Filament\Panel::class));
        $this->assertTrue(class_exists(\Filament\PanelProvider::class));
        
        // Test that our admin panel provider exists
        $this->assertTrue(class_exists(\App\Providers\Filament\AdminPanelProvider::class));
    }
}
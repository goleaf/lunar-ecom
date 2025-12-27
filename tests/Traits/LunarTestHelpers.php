<?php

namespace Tests\Traits;

use App\Models\Product;
use App\Models\Collection;
use App\Models\ProductType;
use App\Models\Attribute;
use Lunar\Models\Currency;
use Lunar\Models\TaxZone;
use Lunar\Models\TaxClass;
use Lunar\Models\AttributeGroup;
use Lunar\Models\CollectionGroup;
use Lunar\FieldTypes\Text;

trait LunarTestHelpers
{
    /**
     * Create a test product with proper attribute_data
     */
    protected function createTestProduct(array $overrides = []): Product
    {
        $productType = ProductType::firstOrCreate(
            ['name' => 'Test Product Type'],
            ['name' => 'Test Product Type']
        );

        return \Database\Factories\ProductFactory::new()->create(array_merge([
            'product_type_id' => $productType->id,
            'status' => 'published',
            'attribute_data' => collect([
                'name' => new Text('Test Product ' . uniqid()),
                'description' => new Text('Test product description'),
            ]),
        ], $overrides));
    }

    /**
     * Create a test collection with proper attribute_data
     */
    protected function createTestCollection(array $overrides = []): Collection
    {
        $collectionGroup = CollectionGroup::first();
        
        $collection = Collection::create(array_merge([
            'collection_group_id' => $collectionGroup->id,
            'sort' => 0,
            'attribute_data' => collect([
                'name' => new Text('Test Collection ' . uniqid()),
                'description' => new Text('Test collection description'),
            ]),
        ], $overrides));

        return $collection;
    }

    /**
     * Seed all required Lunar data for testing
     */
    protected function seedLunarTestData(): void
    {
        // Create default language first
        \Lunar\Models\Language::firstOrCreate(
            ['code' => 'en'],
            [
                'name' => 'English',
                'default' => true,
            ]
        );

        // Create default channel
        \Lunar\Models\Channel::firstOrCreate([
            'handle' => 'webstore',
        ], [
            'name' => 'Webstore',
            'default' => true,
        ]);

        // Create currency
        Currency::firstOrCreate([
            'code' => 'USD',
        ], [
            'name' => 'US Dollar',
            'exchange_rate' => 1,
            'decimal_places' => 2,
            'enabled' => true,
            'default' => true,
        ]);

        // Create tax class
        TaxClass::firstOrCreate([
            'name' => 'Default Tax Class',
        ], [
            'default' => true,
        ]);

        // Create a default tax zone so SystemTaxDriver can always resolve a zone.
        TaxZone::firstOrCreate([
            'name' => 'Default Tax Zone',
        ], [
            'zone_type' => 'country',
            'price_display' => 'inclusive',
            'active' => true,
            'default' => true,
        ]);

        // Create customer group
        \Lunar\Models\CustomerGroup::firstOrCreate([
            'handle' => 'retail',
        ], [
            'name' => 'Retail',
            'default' => true,
        ]);

        // Create attribute group for products
        $productAttributeGroup = AttributeGroup::firstOrCreate([
            'handle' => 'product_attributes',
        ], [
            'attributable_type' => 'product',
            'name' => 'Product Attributes',
            'position' => 1,
        ]);

        // Create attribute group for collections
        $collectionAttributeGroup = AttributeGroup::firstOrCreate([
            'handle' => 'collection_attributes',
        ], [
            'attributable_type' => 'collection',
            'name' => 'Collection Attributes',
            'position' => 1,
        ]);

        // Create product type
        ProductType::firstOrCreate([
            'name' => 'Test Product Type',
        ]);

        // Create basic attributes
        Attribute::firstOrCreate([
            'handle' => 'name',
        ], [
            'attribute_type' => 'product',
            'attribute_group_id' => $productAttributeGroup->id,
            'position' => 1,
            'name' => 'Name',
            'type' => 'text',
            'required' => true,
            'filterable' => true,
            'searchable' => true,
            'configuration' => '{}',
            'system' => true,
        ]);

        Attribute::firstOrCreate([
            'handle' => 'description',
        ], [
            'attribute_type' => 'product',
            'attribute_group_id' => $productAttributeGroup->id,
            'position' => 2,
            'name' => 'Description',
            'type' => 'richtext',
            'required' => false,
            'filterable' => false,
            'searchable' => true,
            'configuration' => '{}',
            'system' => true,
        ]);

        // Create collection attributes
        Attribute::firstOrCreate([
            'handle' => 'collection_name',
        ], [
            'attribute_type' => 'collection',
            'attribute_group_id' => $collectionAttributeGroup->id,
            'position' => 1,
            'name' => 'Collection Name',
            'type' => 'text',
            'required' => true,
            'filterable' => true,
            'searchable' => true,
            'configuration' => '{}',
            'system' => true,
        ]);

        // Create collection group
        CollectionGroup::firstOrCreate([
            'handle' => 'test_collection_group',
        ], [
            'name' => 'Test Collection Group',
        ]);

        // Create some test collections
        if (Collection::count() < 2) {
            $collectionGroup = CollectionGroup::first();
            
            try {
                Collection::create([
                    'collection_group_id' => $collectionGroup->id,
                    'sort' => 1,
                    'attribute_data' => collect([
                        'collection_name' => new Text('Test Collection 1'),
                    ]),
                ]);

                Collection::create([
                    'collection_group_id' => $collectionGroup->id,
                    'sort' => 2,
                    'attribute_data' => collect([
                        'collection_name' => new Text('Test Collection 2'),
                    ]),
                ]);
            } catch (\Exception $e) {
                // Skip collection creation on error
                // This is fine for factory tests
            }
        }
    }

    /**
     * Generate random attribute data for products
     */
    protected function generateRandomProductAttributeData(): \Illuminate\Support\Collection
    {
        return collect([
            'name' => new Text('Test Product ' . uniqid()),
            'description' => new Text('Random test product description ' . rand(1000, 9999)),
        ]);
    }

    /**
     * Generate random attribute data for collections
     */
    protected function generateRandomCollectionAttributeData(): \Illuminate\Support\Collection
    {
        return collect([
            'collection_name' => new Text('Test Collection ' . uniqid()),
            'description' => new Text('Random test collection description ' . rand(1000, 9999)),
        ]);
    }
}

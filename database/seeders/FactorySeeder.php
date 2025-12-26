<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Database\Factories\AttributeGroupFactory;
use Database\Factories\ChannelFactory;
use Database\Factories\CollectionGroupFactory;
use Database\Factories\CurrencyFactory;
use Database\Factories\LanguageFactory;
use Database\Factories\PriceFactory;
use Database\Factories\TaxClassFactory;
use Lunar\Models\AttributeGroup;
use Lunar\Models\Channel;
use Lunar\Models\CollectionGroup;
use Lunar\Models\Currency;
use Lunar\Models\CustomerGroup;
use Lunar\Models\Language;
use Lunar\Models\Price;
use Lunar\Models\TaxClass;

class FactorySeeder extends Seeder
{
    /**
     * Run the database seeds using factories.
     */
    public function run(): void
    {
        $this->command->info('Seeding database with factories...');

        // Step 1: Create essential Lunar setup (channels, currencies, languages, etc.)
        $this->command->info('Setting up Lunar essentials...');
        $channel = $this->getOrCreateChannel();
        $currency = $this->getOrCreateCurrency();
        $language = $this->getOrCreateLanguage();
        $customerGroups = CustomerGroupSeeder::seed();
        $customerGroup = $customerGroups[CustomerGroupSeeder::DEFAULT_HANDLE] ?? CustomerGroup::where('default', true)->first();
        $taxClass = $this->getOrCreateTaxClass();

        // Step 2: Create attribute groups and attributes
        $this->command->info('Creating attributes...');
        $attributeGroup = $this->getOrCreateAttributeGroup();
        $attributes = Attribute::factory()
            ->count(5)
            ->create([
                'attribute_group_id' => $attributeGroup->id,
            ]);

        // Step 3: Create product types
        $this->command->info('Creating product types...');
        $productTypes = collect(ProductTypeSeeder::seed())->values();

        // Step 4: Create collections
        $this->command->info('Creating collections...');
        $collectionGroup = $this->getOrCreateCollectionGroup();
        $collections = Collection::factory()
            ->count(5)
            ->create([
                'collection_group_id' => $collectionGroup->id,
            ]);

        // Step 5: Create products with variants and prices
        $this->command->info('Creating products with variants...');
        $products = Product::factory()
            ->count(20)
            ->published()
            ->withBrand()
            ->state(fn () => [
                'product_type_id' => $productTypes->random()->id,
            ])
            ->create();

        // Attach products to channels
        foreach ($products as $product) {
            $product->channels()->sync([$channel->id]);
        }

        // Create version snapshots for products.
        foreach ($products as $product) {
            $product->createVersion('Seed v1', 'Initial seeded version');
            if (fake()->boolean(30)) {
                $product->short_description = fake()->sentence();
                $product->save();
                $product->createVersion('Seed v2', 'Seeded revision');
            }
        }

        // Create variants and prices for each product using factory relationships
        foreach ($products as $index => $product) {
            // Create 1-4 variants per product
            $variantCount = fake()->numberBetween(1, 4);
            
            $variants = ProductVariant::factory()
                ->count($variantCount)
                ->create([
                    'product_id' => $product->id,
                    'tax_class_id' => $taxClass->id,
                ]);

            // Create prices for each variant
            foreach ($variants as $variant) {
                PriceFactory::new()
                    ->forVariant($variant)
                    ->create([
                        'price' => fake()->numberBetween(1000, 100000),
                        'compare_price' => fake()->optional(0.3)->numberBetween(100000, 200000),
                        'currency_id' => $currency->id,
                        'customer_group_id' => $customerGroup?->id,
                    ]);
            }

            // Attach products to random collections with positions
            $selectedCollections = $collections->random(fake()->numberBetween(1, 3));
            $collectionData = [];
            foreach ($selectedCollections as $position => $collection) {
                $collectionData[$collection->id] = ['position' => $position + 1];
            }
            $product->collections()->sync($collectionData);
        }

        $this->command->info('Factory seeding completed successfully!');
        $this->command->info("Created: {$products->count()} products, {$collections->count()} collections");
    }

    protected function getOrCreateChannel(): Channel
    {
        $factoryData = ChannelFactory::new()
            ->state([
                'handle' => 'webstore',
                'name' => 'Web Store',
                'url' => 'http://localhost',
                'default' => true,
            ])
            ->make()
            ->getAttributes();

        return Channel::firstOrCreate(
            ['handle' => 'webstore'],
            Arr::only($factoryData, ['name', 'handle', 'url', 'default'])
        );
    }

    protected function getOrCreateCurrency(): Currency
    {
        $factoryData = CurrencyFactory::new()
            ->state([
                'code' => 'USD',
                'name' => 'US Dollar',
                'exchange_rate' => 1.00,
                'decimal_places' => 2,
                'default' => true,
                'enabled' => true,
            ])
            ->make()
            ->getAttributes();

        return Currency::firstOrCreate(
            ['code' => 'USD'],
            Arr::only($factoryData, ['name', 'exchange_rate', 'decimal_places', 'default', 'enabled'])
        );
    }

    protected function getOrCreateLanguage(): Language
    {
        $factoryData = LanguageFactory::new()
            ->state([
                'code' => 'en',
                'name' => 'English',
                'default' => true,
            ])
            ->make()
            ->getAttributes();

        return Language::firstOrCreate(
            ['code' => 'en'],
            Arr::only($factoryData, ['name', 'default'])
        );
    }

    protected function getOrCreateTaxClass(): TaxClass
    {
        $factoryData = TaxClassFactory::new()
            ->defaultClass()
            ->state([
                'name' => 'Standard Tax',
            ])
            ->make()
            ->getAttributes();

        return TaxClass::firstOrCreate(
            ['name' => 'Standard Tax'],
            Arr::only($factoryData, ['name', 'default'])
        );
    }

    protected function getOrCreateAttributeGroup(): AttributeGroup
    {
        $factoryData = AttributeGroupFactory::new()
            ->state([
                'handle' => 'product',
                'name' => [
                    'en' => 'Product',
                ],
                'position' => 0,
            ])
            ->make()
            ->getAttributes();

        return AttributeGroup::firstOrCreate(
            ['handle' => 'product'],
            Arr::only($factoryData, ['name', 'attributable_type', 'position'])
        );
    }

    protected function getOrCreateCollectionGroup(): CollectionGroup
    {
        $factoryData = CollectionGroupFactory::new()
            ->state([
                'handle' => 'default',
                'name' => 'Default',
            ])
            ->make()
            ->getAttributes();

        return CollectionGroup::firstOrCreate(
            ['handle' => 'default'],
            Arr::only($factoryData, ['name', 'handle'])
        );
    }
}

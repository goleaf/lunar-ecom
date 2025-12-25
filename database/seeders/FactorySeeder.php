<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
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
        $customerGroup = $this->getOrCreateCustomerGroup();
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
        $productTypes = ProductType::factory()
            ->count(3)
            ->create();

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
            ->create();

        // Attach products to channels
        foreach ($products as $product) {
            $product->channels()->sync([$channel->id]);
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
                Price::create([
                    'price' => fake()->randomFloat(2, 10, 1000),
                    'compare_price' => fake()->optional(0.3)->randomFloat(2, 1000, 2000),
                    'currency_id' => $currency->id,
                    'priceable_type' => ProductVariant::class,
                    'priceable_id' => $variant->id,
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
        return Channel::firstOrCreate(
            ['handle' => 'webstore'],
            [
                'name' => 'Web Store',
                'url' => 'http://localhost',
                'default' => true,
            ]
        );
    }

    protected function getOrCreateCurrency(): Currency
    {
        return Currency::firstOrCreate(
            ['code' => 'USD'],
            [
                'name' => 'US Dollar',
                'exchange_rate' => 1.00,
                'decimal_places' => 2,
                'default' => true,
                'enabled' => true,
            ]
        );
    }

    protected function getOrCreateLanguage(): Language
    {
        return Language::firstOrCreate(
            ['code' => 'en'],
            [
                'name' => 'English',
                'default' => true,
            ]
        );
    }

    protected function getOrCreateCustomerGroup(): CustomerGroup
    {
        return CustomerGroup::firstOrCreate(
            ['handle' => 'default'],
            [
                'name' => 'Default',
                'default' => true,
            ]
        );
    }

    protected function getOrCreateTaxClass(): TaxClass
    {
        return TaxClass::firstOrCreate(
            ['name' => 'Standard Tax'],
            [
                'name' => 'Standard Tax',
                'default' => true,
            ]
        );
    }

    protected function getOrCreateAttributeGroup(): AttributeGroup
    {
        return AttributeGroup::firstOrCreate(
            ['handle' => 'product'],
            [
                'name' => [
                    'en' => 'Product',
                ],
                'position' => 0,
            ]
        );
    }

    protected function getOrCreateCollectionGroup(): CollectionGroup
    {
        return CollectionGroup::firstOrCreate(
            ['handle' => 'default'],
            [
                'name' => [
                    'en' => 'Default',
                ],
            ]
        );
    }
}


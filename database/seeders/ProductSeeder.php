<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Database\Factories\PriceFactory;
use Database\Seeders\ProductTypeSeeder;
use Lunar\Models\Channel;
use Lunar\Models\Collection;
use Lunar\Models\Currency;
use Lunar\Models\TaxClass;

class ProductSeeder extends Seeder
{
    /**
     * Seed products with variants and prices.
     * 
     * This seeder creates a complete product catalog with:
     * - Products with proper attribute data
     * - Multiple variants per product
     * - Prices for each variant
     * - Channel associations
     * - Collection associations
     */
    public function run(): void
    {
        $this->command->info('Seeding products...');

        // Get required dependencies
        $channel = Channel::where('default', true)->first();
        $currency = Currency::where('default', true)->first();
        $taxClass = TaxClass::first();
        $collections = Collection::take(5)->get();

        if (!$channel || !$currency || !$taxClass) {
            $this->command->error('Required dependencies not found. Please run FactorySeeder first.');
            return;
        }

        $productTypes = collect(ProductTypeSeeder::seed())->values();

        // Create products with variants
        $products = Product::factory()
            ->count(10)
            ->published()
            ->withBrand()
            ->state(fn () => [
                'product_type_id' => $productTypes->random()->id,
            ])
            ->create();

        foreach ($products as $product) {
            // Attach to channel
            $product->channels()->syncWithoutDetaching([$channel->id]);

            // Create variants
            $variants = ProductVariant::factory()
                ->count(fake()->numberBetween(2, 5))
                ->create([
                    'product_id' => $product->id,
                    'tax_class_id' => $taxClass->id,
                ]);

            // Create prices for variants
            foreach ($variants as $variant) {
                PriceFactory::new()
                    ->forVariant($variant)
                    ->create([
                        'price' => random_int(1000, 50000),
                        'compare_price' => random_int(0, 1) ? random_int(50000, 100000) : null,
                        'currency_id' => $currency->id,
                    ]);
            }

            // Attach to collections
            if ($collections->isNotEmpty()) {
                $selectedCollections = $collections->random(fake()->numberBetween(1, 3));
                $collectionData = [];
                foreach ($selectedCollections as $position => $collection) {
                    $collectionData[$collection->id] = ['position' => $position + 1];
                }
                $product->collections()->sync($collectionData);
            }

            // Create at least one version snapshot per product.
            $product->createVersion('Seed v1', 'Initial seeded version');
        }

        $this->command->info("Created {$products->count()} products with variants and prices.");
    }
}

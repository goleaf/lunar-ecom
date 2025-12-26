<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Lunar\Models\Channel;
use Lunar\Models\Collection;
use Lunar\Models\Currency;
use Lunar\Models\Price;
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

        // Create products with variants
        $products = Product::factory()
            ->count(10)
            ->published()
            ->withBrand()
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
                Price::create([
                    'price' => fake()->randomFloat(2, 10, 500),
                    'compare_price' => fake()->optional(0.4)->randomFloat(2, 500, 1000),
                    'currency_id' => $currency->id,
                    'priceable_type' => ProductVariant::class,
                    'priceable_id' => $variant->id,
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
        }

        $this->command->info("Created {$products->count()} products with variants and prices.");
    }
}

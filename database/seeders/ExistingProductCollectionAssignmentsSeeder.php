<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Lunar\Models\Collection;
use Lunar\Models\Product;

/**
 * Ensures every product is assigned to at least one collection.
 *
 * Storefront /collections page uses `Collection::whereHas('products')`, so without pivot
 * assignments collections won't appear.
 */
class ExistingProductCollectionAssignmentsSeeder extends Seeder
{
    public int $minCollectionsPerProduct = 1;
    public int $maxCollectionsPerProduct = 3;

    public function run(): void
    {
        $this->command?->info('Assigning products to collections...');

        $collections = Collection::query()->select(['id'])->get();
        if ($collections->isEmpty()) {
            $this->command?->warn('No collections found. Skipping product->collection assignments.');
            return;
        }

        $assignedProducts = 0;

        Product::query()
            ->select(['id'])
            ->orderBy('id')
            ->chunkById(200, function ($products) use ($collections, &$assignedProducts) {
                foreach ($products as $product) {
                    $existing = $product->collections()->count();
                    if ($existing >= $this->minCollectionsPerProduct) {
                        continue;
                    }

                    $attachCount = random_int($this->minCollectionsPerProduct, $this->maxCollectionsPerProduct);
                    $selected = $collections->random(min($attachCount, $collections->count()));

                    $pivot = [];
                    foreach ($selected as $idx => $collection) {
                        $pivot[$collection->id] = ['position' => $idx + 1];
                    }

                    $product->collections()->syncWithoutDetaching($pivot);
                    $assignedProducts++;
                }
            });

        $this->command?->info("✅ Assigned collections for {$assignedProducts} products (that were missing assignments).");
    }
}



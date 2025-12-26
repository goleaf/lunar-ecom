<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\VariantRelationshipService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Seed "shared variants" for each product by creating cross-variant relationships
 * between all variants belonging to the same product.
 *
 * This powers UX like "available in other options" (color/size) by allowing
 * each variant to link to its siblings.
 *
 * Safe to run multiple times (uses unique constraint + service-level de-dupe).
 */
class SharedProductVariantsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Seeding shared variants (cross-variant relationships) for products...');

        $table = config('lunar.database.table_prefix') . 'variant_relationships';
        if (!Schema::hasTable($table)) {
            $this->command?->warn("Skipping: missing table `{$table}`. Run migrations first.");
            return;
        }

        $service = app(VariantRelationshipService::class);

        $relationshipsCreated = 0;
        $relationshipsFailed = 0;
        $firstFailure = null;
        $productsProcessed = 0;
        $productsSkipped = 0;

        Product::query()
            ->select(['id'])
            ->orderBy('id')
            ->chunkById(100, function ($products) use ($service, &$relationshipsCreated, &$relationshipsFailed, &$firstFailure, &$productsProcessed, &$productsSkipped) {
                foreach ($products as $product) {
                    $productsProcessed++;

                    // We want to relate all variants (including draft/inactive) to be robust in dev.
                    // If you only want active variants, change this to ->where('status', 'active').
                    // IMPORTANT: Use the app's extended ProductVariant model so it matches
                    // VariantRelationshipService's type-hints.
                    $variants = ProductVariant::query()
                        ->where('product_id', $product->id)
                        ->select(['id'])
                        ->orderBy('id')
                        ->get();

                    $count = $variants->count();
                    if ($count < 2) {
                        $productsSkipped++;
                        continue;
                    }

                    // Create a complete graph of cross_variant relationships:
                    // for each unique pair (i < j), create bidirectional relationship.
                    for ($i = 0; $i < $count; $i++) {
                        for ($j = $i + 1; $j < $count; $j++) {
                            $a = $variants[$i];
                            $b = $variants[$j];

                            try {
                                $service->createRelationship(
                                    $a,
                                    $b,
                                    'cross_variant',
                                    [
                                        'is_bidirectional' => true,
                                        'is_active' => true,
                                        'label' => 'Same product, different options',
                                    ]
                                );
                                $relationshipsCreated++;
                            } catch (\Throwable $e) {
                                $relationshipsFailed++;
                                if ($firstFailure === null) {
                                    $firstFailure = $e->getMessage();
                                }
                                // Ignore and continue (unique constraints, missing data, etc).
                                \Log::debug('Skipping shared variant relationship creation', [
                                    'product_id' => $product->id,
                                    'variant_id' => $a->id,
                                    'related_variant_id' => $b->id,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }
                    }
                }
            });

        $this->command?->info("✅ Processed {$productsProcessed} products ({$productsSkipped} skipped: <2 variants).");
        $this->command?->info("✅ Created {$relationshipsCreated} cross-variant relationship pairs (bidirectional).");
        if ($relationshipsFailed > 0) {
            $this->command?->warn("⚠️ Failed {$relationshipsFailed} relationship creates. First error: {$firstFailure}");
        }
    }
}



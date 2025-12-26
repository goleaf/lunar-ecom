<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PriceMatrix;
use App\Models\Product;
use Carbon\Carbon;
use Database\Factories\PriceMatrixFactory;
use Illuminate\Support\Facades\Schema;

class PricingMatrixSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get a product to use for examples
        $product = Product::first();

        if (!$product) {
            $this->command->warn('No products found. Please seed products first.');
            return;
        }

        $this->command->info('Creating pricing matrices...');

        $table = config('lunar.database.table_prefix') . 'price_matrices';
        $endColumn = Schema::hasColumn($table, 'expires_at')
            ? 'expires_at'
            : (Schema::hasColumn($table, 'ends_at') ? 'ends_at' : null);

        // Example 1: Quantity-based tiered pricing
        PriceMatrixFactory::new()->create([
            'product_id' => $product->id,
            'matrix_type' => 'quantity',
            'rules' => [
                'tiers' => [
                    ['min_quantity' => 1, 'max_quantity' => 10, 'price' => 10000],   // $100.00
                    ['min_quantity' => 11, 'max_quantity' => 50, 'price' => 9000],   // $90.00
                    ['min_quantity' => 51, 'max_quantity' => 100, 'price' => 8000],  // $80.00
                    ['min_quantity' => 101, 'price' => 7500],                        // $75.00 (no max)
                ],
                'mix_and_match' => true, // Allow mix-and-match across variants
            ],
            'is_active' => true,
            'priority' => 0,
            'description' => 'Standard volume discount pricing',
        ]);

        // Example 2: Customer group pricing
        PriceMatrixFactory::new()->create([
            'product_id' => $product->id,
            'matrix_type' => 'customer_group',
            'rules' => [
                'customer_groups' => [
                    'retail' => ['price' => 10000],      // $100.00
                    'wholesale' => ['price' => 8000],    // $80.00
                    'vip' => ['price' => 7500],          // $75.00
                ],
            ],
            'is_active' => true,
            'priority' => 0,
            'description' => 'Customer group specific pricing',
        ]);

        // Example 3: Regional pricing
        PriceMatrixFactory::new()->create([
            'product_id' => $product->id,
            'matrix_type' => 'region',
            'rules' => [
                'regions' => [
                    'US' => ['price' => 10000],  // $100.00
                    'EU' => ['price' => 9000],   // $90.00 (€90.00)
                    'UK' => ['price' => 8500],   // $85.00 (£85.00)
                    'CA' => ['price' => 9500],   // $95.00 (CAD)
                ],
            ],
            'is_active' => true,
            'priority' => 0,
            'description' => 'Regional pricing by country',
        ]);

        // Example 4: Mixed pricing with complex conditions
        PriceMatrixFactory::new()->create([
            'product_id' => $product->id,
            'matrix_type' => 'mixed',
            'rules' => [
                'conditions' => [
                    [
                        'quantity' => ['min' => 11, 'max' => 50],
                        'customer_group' => 'wholesale',
                        'region' => 'US',
                        'price' => 7500,  // $75.00
                    ],
                    [
                        'quantity' => ['min' => 51],
                        'customer_group' => 'wholesale',
                        'price' => 7000,  // $70.00
                    ],
                    [
                        'quantity' => ['min' => 1],
                        'customer_group' => 'vip',
                        'price' => 7500,  // $75.00
                    ],
                ],
            ],
            'is_active' => true,
            'priority' => 1, // Higher priority
            'description' => 'Complex mixed pricing rules',
        ]);

        // Example 5: Promotional pricing with date range
        $promoData = [
            'product_id' => $product->id,
            'matrix_type' => 'quantity',
            'rules' => [
                'tiers' => [
                    ['min_quantity' => 1, 'price' => 8000],  // 20% off
                ],
            ],
            'starts_at' => Carbon::now()->startOfMonth(),
            'is_active' => true,
            'priority' => 10, // High priority for promotional pricing
            'description' => 'Monthly promotional pricing',
        ];

        if ($endColumn) {
            $promoData[$endColumn] = Carbon::now()->endOfMonth();
        }

        PriceMatrixFactory::new()->create($promoData);

        $this->command->info('Pricing matrices created successfully!');
        $this->command->info('Created 5 example pricing matrices for product ID: ' . $product->id);
    }
}

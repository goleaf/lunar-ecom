<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductImport;
use App\Models\ProductImportRollback;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductImportRollback>
 */
class ProductImportRollbackFactory extends Factory
{
    protected $model = ProductImportRollback::class;

    public function definition(): array
    {
        return [
            'product_import_id' => ProductImport::factory(),
            'product_id' => Product::factory(),
            'original_data' => [],
            'action' => fake()->randomElement(['created', 'updated']),
            'rolled_back_by' => null,
            'rolled_back_at' => now(),
        ];
    }
}


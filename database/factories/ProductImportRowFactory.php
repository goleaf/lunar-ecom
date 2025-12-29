<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductImport;
use App\Models\ProductImportRow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductImportRow>
 */
class ProductImportRowFactory extends Factory
{
    protected $model = ProductImportRow::class;

    public function definition(): array
    {
        return [
            'product_import_id' => ProductImport::factory(),
            'row_number' => fake()->numberBetween(2, 200),
            'status' => fake()->randomElement(['pending', 'success', 'failed', 'skipped']),
            'raw_data' => [],
            'mapped_data' => [],
            'validation_errors' => null,
            'product_id' => null,
            'sku' => fake()->optional()->bothify('SKU-####'),
            'error_message' => null,
            'success_message' => null,
        ];
    }

    public function successForProduct(Product $product, array $mappedData = []): static
    {
        return $this->state(fn () => [
            'status' => 'success',
            'product_id' => $product->id,
            'mapped_data' => array_merge(['action' => 'created'], $mappedData),
        ]);
    }
}


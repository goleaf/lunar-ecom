<?php

namespace Database\Factories;

use App\Models\ProductImport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductImport>
 */
class ProductImportFactory extends Factory
{
    protected $model = ProductImport::class;

    public function definition(): array
    {
        $original = fake()->unique()->bothify('products-import-####.csv');

        return [
            'user_id' => null,
            'file_path' => 'imports/'.$original,
            'file_name' => $original,
            'original_filename' => $original,
            'file_size' => fake()->numberBetween(1000, 500000),
            'file_type' => 'csv',
            'status' => fake()->randomElement(['pending', 'processing', 'completed', 'failed', 'cancelled']),
            'total_rows' => fake()->numberBetween(0, 50),
            'processed_rows' => 0,
            'successful_rows' => 0,
            'failed_rows' => 0,
            'skipped_rows' => 0,
            'field_mapping' => [],
            'options' => ['action' => 'create_or_update'],
            'validation_errors' => null,
            'import_report' => null,
            'error_summary' => null,
            'error_message' => null,
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'started_at' => now()->subMinutes(2),
            'completed_at' => now()->subMinute(),
        ]);
    }
}


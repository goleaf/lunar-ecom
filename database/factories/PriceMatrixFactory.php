<?php

namespace Database\Factories;

use App\Models\PriceMatrix;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Schema;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PriceMatrix>
 */
class PriceMatrixFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = PriceMatrix::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $matrixType = fake()->randomElement(['quantity', 'customer_group', 'region', 'mixed']);

        $rules = match ($matrixType) {
            'customer_group' => [
                'customer_groups' => [
                    'default' => ['price' => 10000],
                    'wholesale' => ['price' => 8000],
                ],
            ],
            'region' => [
                'regions' => [
                    'US' => ['price' => 10000],
                    'EU' => ['price' => 9000],
                ],
            ],
            'mixed' => [
                'conditions' => [
                    [
                        'quantity' => ['min' => 10],
                        'price' => 9000,
                    ],
                ],
            ],
            default => [
                'tiers' => [
                    ['min_quantity' => 1, 'price' => 10000],
                    ['min_quantity' => 10, 'price' => 9000],
                ],
            ],
        };

        $data = [
            'product_id' => Product::factory(),
            'matrix_type' => $matrixType,
            'rules' => $rules,
            'starts_at' => null,
            'is_active' => true,
            'priority' => 0,
            'description' => fake()->sentence(),
        ];

        $table = config('lunar.database.table_prefix') . 'price_matrices';

        if (Schema::hasColumn($table, 'ends_at')) {
            $data['ends_at'] = null;
        }

        if (Schema::hasColumn($table, 'expires_at')) {
            $data['expires_at'] = null;
        }

        if (Schema::hasColumn($table, 'product_variant_id')) {
            $data['product_variant_id'] = null;
        }

        if (Schema::hasColumn($table, 'name')) {
            $data['name'] = fake()->words(2, true);
        }

        if (Schema::hasColumn($table, 'requires_approval')) {
            $data['requires_approval'] = false;
        }

        if (Schema::hasColumn($table, 'allow_mix_match')) {
            $data['allow_mix_match'] = false;
        }

        if (Schema::hasColumn($table, 'approval_status')) {
            $data['approval_status'] = 'approved';
        }

        return $data;
    }
}

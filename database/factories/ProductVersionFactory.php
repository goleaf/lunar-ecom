<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVersion>
 */
class ProductVersionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = ProductVersion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $versionNumber = fake()->numberBetween(1, 5);

        return [
            'product_id' => Product::factory(),
            'version_number' => $versionNumber,
            'version_name' => 'Version ' . $versionNumber,
            'version_notes' => fake()->sentence(),
            'product_data' => [
                'short_description' => fake()->sentence(),
                'full_description' => fake()->paragraphs(2, true),
                'technical_description' => fake()->optional()->paragraph(),
                'meta_title' => fake()->optional()->sentence(6),
                'meta_description' => fake()->optional()->sentence(12),
                'meta_keywords' => fake()->optional()->words(6, true),
                'visibility' => Product::VISIBILITY_PUBLIC,
                'status' => Product::STATUS_PUBLISHED,
            ],
            'created_by' => User::factory(),
        ];
    }
}

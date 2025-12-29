<?php

namespace Database\Factories;

use App\Models\FitFinderQuiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FitFinderQuiz>
 */
class FitFinderQuizFactory extends Factory
{
    protected $model = FitFinderQuiz::class;

    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'category_type' => fake()->randomElement(['clothing', 'shoes', 'accessories', 'other']),
            'gender' => fake()->optional()->randomElement(['men', 'women', 'unisex', 'kids']),
            'is_active' => true,
            'display_order' => fake()->numberBetween(0, 50),
            'size_guide_id' => null,
            'recommendation_logic' => null,
        ];
    }
}


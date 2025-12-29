<?php

namespace Database\Factories;

use App\Models\FitFeedback;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FitFeedback>
 */
class FitFeedbackFactory extends Factory
{
    protected $model = FitFeedback::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'size_guide_id' => null,
            'fit_finder_quiz_id' => null,
            'customer_id' => null,
            'order_id' => null,
            'purchased_size' => fake()->randomElement(['XS', 'S', 'M', 'L', 'XL']),
            'recommended_size' => fake()->randomElement(['XS', 'S', 'M', 'L', 'XL']),
            'actual_fit' => fake()->randomElement(['perfect', 'too_small', 'too_large', 'too_tight', 'too_loose']),
            'fit_rating' => fake()->numberBetween(1, 5),
            'body_measurements' => null,
            'feedback_text' => fake()->optional()->sentence(),
            'would_exchange' => fake()->boolean(20),
            'would_return' => fake()->boolean(15),
            'is_helpful' => false,
            'is_public' => false,
        ];
    }
}


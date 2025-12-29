<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductQuestion>
 */
class ProductQuestionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = ProductQuestion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $askedAt = fake()->dateTimeBetween('-30 days', 'now');

        return [
            'product_id' => Product::factory()->published(),
            'customer_id' => null,
            'customer_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'question' => fake()->sentence(12),
            'question_original' => null,
            'status' => 'pending',
            'is_public' => true,
            'is_answered' => false,
            'views_count' => fake()->numberBetween(0, 50),
            'helpful_count' => fake()->numberBetween(0, 10),
            'not_helpful_count' => fake()->numberBetween(0, 5),
            'moderated_by' => null,
            'moderated_at' => null,
            'moderation_notes' => null,
            'asked_at' => $askedAt,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => 'pending',
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => 'approved',
            'is_public' => true,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => 'rejected',
        ]);
    }

    public function spam(): static
    {
        return $this->state(fn () => [
            'status' => 'spam',
            'is_public' => false,
        ]);
    }

    public function answered(): static
    {
        return $this->state(fn () => [
            'is_answered' => true,
        ]);
    }
}


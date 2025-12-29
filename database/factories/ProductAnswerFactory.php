<?php

namespace Database\Factories;

use App\Models\ProductAnswer;
use App\Models\ProductQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductAnswer>
 */
class ProductAnswerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = ProductAnswer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(['approved', 'pending', 'rejected']);
        $isApproved = $status === 'approved';

        return [
            'question_id' => ProductQuestion::factory(),
            'answerer_type' => 'admin',
            'answerer_id' => null,
            'answer' => fake()->paragraph(),
            'answer_original' => null,
            'is_official' => fake()->boolean(60),
            'is_approved' => $isApproved,
            'status' => $status,
            'helpful_count' => fake()->numberBetween(0, 10),
            'not_helpful_count' => fake()->numberBetween(0, 5),
            'moderated_by' => null,
            'moderated_at' => null,
            'moderation_notes' => null,
            'answered_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => 'pending',
            'is_approved' => false,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => 'approved',
            'is_approved' => true,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => 'rejected',
            'is_approved' => false,
        ]);
    }

    public function official(): static
    {
        return $this->state(fn () => [
            'is_official' => true,
        ]);
    }
}


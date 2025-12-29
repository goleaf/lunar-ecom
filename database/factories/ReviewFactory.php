<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Review::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'customer_id' => \Lunar\Models\Customer::factory(),
            'order_id' => null, // Will be set optionally in afterCreating if needed
            'rating' => fake()->numberBetween(1, 5),
            'title' => fake()->sentence(),
            'content' => fake()->paragraph(),
            'pros' => fake()->optional(0.5)->randomElements([fake()->sentence(), fake()->sentence()], fake()->numberBetween(1, 2)),
            'cons' => fake()->optional(0.5)->randomElements([fake()->sentence(), fake()->sentence()], fake()->numberBetween(1, 2)),
            'recommended' => fake()->boolean(80),
            'is_verified_purchase' => fake()->boolean(70),
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => fake()->optional(0.1)->randomElement([User::factory()->create()->id, null]),
            'helpful_count' => fake()->numberBetween(0, 20),
            'not_helpful_count' => fake()->numberBetween(0, 5),
            'admin_response' => fake()->optional(0.1)->paragraph(),
            'responded_at' => fake()->optional(0.1)->dateTimeBetween('-1 month', 'now'),
            'responded_by' => fake()->optional(0.1)->randomElement([User::factory()->create()->id, null]),
            'report_count' => fake()->numberBetween(0, 3),
            'is_reported' => fake()->boolean(10),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    /**
     * Indicate that the review is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => true,
        ]);
    }

    /**
     * Indicate that the review is pending approval.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => false,
            'approved_at' => null,
            'approved_by' => null,
        ]);
    }

    /**
     * Indicate that the review is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => true,
            'approved_at' => now(),
        ]);
    }

    /**
     * Indicate that the review is from a verified purchase.
     */
    public function verifiedPurchase(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified_purchase' => true,
        ]);
    }

    /**
     * Set a specific rating.
     */
    public function rating(int $rating): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => max(1, min(5, $rating)),
        ]);
    }

    /**
     * Indicate that the review has helpful votes.
     */
    public function withHelpfulVotes(int $count = 0): static
    {
        $voteCount = $count > 0 ? $count : fake()->numberBetween(1, 50);
        
        return $this->state(fn (array $attributes) => [
            'helpful_count' => $voteCount,
        ]);
    }
}


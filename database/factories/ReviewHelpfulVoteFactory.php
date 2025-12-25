<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\ReviewHelpfulVote;
use Illuminate\Database\Eloquent\Factories\Factory;
use Lunar\Models\Customer;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReviewHelpfulVote>
 */
class ReviewHelpfulVoteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = ReviewHelpfulVote::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'review_id' => Review::factory(),
            'customer_id' => fake()->optional(0.7)->randomElement([Customer::factory()->create()->id, null]),
            'session_id' => fake()->optional(0.5)->uuid(),
            'ip_address' => fake()->optional(0.5)->ipv4(),
            'is_helpful' => true,
        ];
    }

    /**
     * Indicate that the vote is helpful.
     */
    public function helpful(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_helpful' => true,
        ]);
    }

    /**
     * Indicate that the vote is not helpful.
     */
    public function notHelpful(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_helpful' => false,
        ]);
    }

    /**
     * Set a specific customer.
     */
    public function forCustomer(Customer $customer): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => $customer->id,
            'session_id' => null,
            'ip_address' => null,
        ]);
    }

    /**
     * Set a specific session.
     */
    public function forSession(string $sessionId): static
    {
        return $this->state(fn (array $attributes) => [
            'session_id' => $sessionId,
            'customer_id' => null,
            'ip_address' => null,
        ]);
    }

    /**
     * Set a specific IP address.
     */
    public function forIpAddress(string $ipAddress): static
    {
        return $this->state(fn (array $attributes) => [
            'ip_address' => $ipAddress,
            'customer_id' => null,
            'session_id' => null,
        ]);
    }
}


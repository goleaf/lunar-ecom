<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductView;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductView>
 */
class ProductViewFactory extends Factory
{
    protected $model = ProductView::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'user_id' => fake()->optional(0.4)->randomElement([User::factory()->create()->id, null]),
            'session_id' => fake()->optional(0.6)->uuid(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'referrer' => fake()->optional(0.5)->url(),
            'viewed_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function forUser(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user ? $user->id : User::factory()->create()->id,
            'session_id' => null,
        ]);
    }

    public function forSession(string $sessionId): static
    {
        return $this->state(fn (array $attributes) => [
            'session_id' => $sessionId,
            'user_id' => null,
        ]);
    }

    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'viewed_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    public function withReferrer(): static
    {
        return $this->state(fn (array $attributes) => [
            'referrer' => fake()->url(),
        ]);
    }
}


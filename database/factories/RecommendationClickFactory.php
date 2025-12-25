<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\RecommendationClick;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Lunar\Models\Order;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecommendationClick>
 */
class RecommendationClickFactory extends Factory
{
    protected $model = RecommendationClick::class;

    public function definition(): array
    {
        return [
            'source_product_id' => Product::factory(),
            'recommended_product_id' => Product::factory(),
            'user_id' => fake()->optional(0.4)->randomElement([User::factory()->create()->id, null]),
            'session_id' => fake()->optional(0.6)->uuid(),
            'recommendation_type' => fake()->randomElement(['similar', 'complementary', 'upsell', 'cross_sell', 'trending', 'personalized']),
            'recommendation_algorithm' => fake()->randomElement(['collaborative', 'content_based', 'hybrid', 'manual']),
            'display_location' => fake()->randomElement(['product_page', 'cart', 'checkout', 'homepage', 'category_page']),
            'converted' => fake()->boolean(25),
            'order_id' => null,
            'clicked_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public function converted(): static
    {
        return $this->state(function (array $attributes) {
            $order = Order::factory()->create();
            
            return [
                'converted' => true,
                'order_id' => $order->id,
            ];
        });
    }

    public function notConverted(): static
    {
        return $this->state(fn (array $attributes) => [
            'converted' => false,
            'order_id' => null,
        ]);
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
            'clicked_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }
}


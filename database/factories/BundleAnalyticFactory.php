<?php

namespace Database\Factories;

use App\Models\Bundle;
use App\Models\BundleAnalytic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BundleAnalytic>
 */
class BundleAnalyticFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = BundleAnalytic::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $eventType = fake()->randomElement(['view', 'add_to_cart', 'purchase']);
        $bundlePrice = fake()->randomFloat(2, 20, 300);
        $originalPrice = $bundlePrice + fake()->randomFloat(2, 5, 120);
        $savings = max(0, $originalPrice - $bundlePrice);

        return [
            'bundle_id' => Bundle::factory(),
            'order_id' => null,
            'event_type' => $eventType,
            'user_id' => null,
            'session_id' => fake()->uuid(),
            'selected_items' => null,
            'bundle_price' => $bundlePrice,
            'original_price' => $originalPrice,
            'savings_amount' => $savings,
            'savings_percentage' => $originalPrice > 0 ? round(($savings / $originalPrice) * 100, 2) : 0,
            'event_at' => now()->subDays(fake()->numberBetween(0, 30)),
        ];
    }
}

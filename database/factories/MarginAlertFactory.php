<?php

namespace Database\Factories;

use App\Models\MarginAlert;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MarginAlert>
 */
class MarginAlertFactory extends Factory
{
    protected $model = MarginAlert::class;

    public function definition(): array
    {
        $cost = fake()->numberBetween(200, 2000);
        $price = fake()->numberBetween(200, 4000);

        $marginPct = $price > 0 ? round((($price - $cost) / $price) * 100, 2) : 0.0;

        return [
            'product_variant_id' => ProductVariant::factory(),
            'alert_type' => fake()->randomElement(['low_margin', 'negative_margin', 'margin_threshold']),
            'current_margin_percentage' => $marginPct,
            'threshold_margin_percentage' => fake()->optional(0.6)->randomFloat(2, 1, 40),
            'current_price' => $price,
            'cost_price' => $cost,
            'message' => fake()->sentence(),
            'is_resolved' => false,
            'resolved_at' => null,
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn () => [
            'is_resolved' => true,
            'resolved_at' => now(),
        ]);
    }
}


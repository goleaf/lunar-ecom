<?php

namespace Database\Factories;

use App\Models\AvailabilityRule;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AvailabilityRule>
 */
class AvailabilityRuleFactory extends Factory
{
    protected $model = AvailabilityRule::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'rule_type' => fake()->randomElement([
                'minimum_rental_period',
                'maximum_rental_period',
                'lead_time',
                'buffer_time',
                'cancellation_policy',
                'blackout_date',
                'special_pricing',
            ]),
            'rule_config' => [],
            'rule_start_date' => null,
            'rule_end_date' => null,
            'priority' => fake()->numberBetween(0, 50),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}


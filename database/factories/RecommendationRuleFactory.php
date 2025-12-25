<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\RecommendationRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RecommendationRule>
 */
class RecommendationRuleFactory extends Factory
{
    protected $model = RecommendationRule::class;

    public function definition(): array
    {
        $displayCount = fake()->numberBetween(0, 1000);
        $clickCount = fake()->numberBetween(0, $displayCount);
        $conversionRate = $displayCount > 0 ? round($clickCount / $displayCount, 4) : 0;

        return [
            'source_product_id' => Product::factory(),
            'recommended_product_id' => Product::factory(),
            'rule_type' => fake()->randomElement(['manual', 'similar', 'complementary', 'upsell', 'cross_sell']),
            'name' => fake()->sentence(3),
            'description' => fake()->optional(0.7)->paragraph(),
            'conditions' => fake()->optional(0.3)->randomElements([
                ['category' => 'electronics'],
                ['price_range' => ['min' => 100, 'max' => 500]],
                ['brand' => 'Apple'],
            ], fake()->numberBetween(1, 2)),
            'priority' => fake()->numberBetween(0, 100),
            'is_active' => true,
            'display_count' => $displayCount,
            'click_count' => $clickCount,
            'conversion_rate' => $conversionRate,
            'ab_test_variant' => fake()->optional(0.2)->randomElement(['A', 'B', 'C']),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => fake()->numberBetween(80, 100),
        ]);
    }

    public function withProducts(Product $source, Product $recommended): static
    {
        return $this->state(fn (array $attributes) => [
            'source_product_id' => $source->id,
            'recommended_product_id' => $recommended->id,
        ]);
    }

    public function withHighConversion(): static
    {
        $displayCount = fake()->numberBetween(100, 1000);
        $clickCount = fake()->numberBetween((int)($displayCount * 0.3), $displayCount);
        
        return $this->state(fn (array $attributes) => [
            'display_count' => $displayCount,
            'click_count' => $clickCount,
            'conversion_rate' => round($clickCount / $displayCount, 4),
        ]);
    }
}


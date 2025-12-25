<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\SearchAnalytic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SearchAnalytic>
 */
class SearchAnalyticFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = SearchAnalytic::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'search_term' => fake()->words(2, true),
            'result_count' => fake()->numberBetween(0, 100),
            'zero_results' => fake()->boolean(20),
            'clicked_product_id' => fake()->optional(0.4)->randomElement([Product::factory()->create()->id, null]),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'user_id' => fake()->optional(0.3)->randomElement([\App\Models\User::factory()->create()->id, null]),
            'filters' => fake()->optional(0.3)->randomElements([
                ['price' => ['min' => 10, 'max' => 100]],
                ['brand' => ['Apple', 'Samsung']],
            ], fake()->numberBetween(1, 2)),
            'session_id' => fake()->uuid(),
            'searched_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Indicate that the search had results.
     */
    public function withResults(int $count = 0): static
    {
        $resultCount = $count > 0 ? $count : fake()->numberBetween(1, 100);
        
        return $this->state(fn (array $attributes) => [
            'result_count' => $resultCount,
        ]);
    }

    /**
     * Indicate that the search had no results.
     */
    public function noResults(): static
    {
        return $this->state(fn (array $attributes) => [
            'result_count' => 0,
            'zero_results' => true,
        ]);
    }

    /**
     * Indicate that the user clicked on a result.
     */
    public function clickedProduct(?Product $product = null): static
    {
        return $this->state(fn (array $attributes) => [
            'clicked_product_id' => $product ? $product->id : Product::factory()->create()->id,
        ]);
    }

    /**
     * Indicate that the user did not click on a result.
     */
    public function notClicked(): static
    {
        return $this->state(fn (array $attributes) => [
            'clicked_product_id' => null,
        ]);
    }
}


<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Lunar\Models\Discount;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Lunar\Models\Discount>
 */
class DiscountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Discount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);
        $handle = str($name)->slug()->toString();
        
        return [
            'name' => $name,
            'handle' => $handle,
            'coupon' => fake()->optional(0.5)->bothify('CODE-####'),
            'type' => fake()->randomElement(['percentage', 'fixed']),
            'starts_at' => now()->subDays(fake()->numberBetween(1, 30)),
            'ends_at' => fake()->optional(0.7)->dateTimeBetween('now', '+30 days'),
            'uses' => 0,
            'max_uses' => fake()->optional(0.5)->numberBetween(100, 1000),
            'priority' => fake()->numberBetween(1, 10),
            'stop' => false,
            'restriction' => null,
            'data' => [],
        ];
    }

    /**
     * Indicate that the discount is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->subDays(1),
            'ends_at' => now()->addDays(30),
        ]);
    }

    /**
     * Indicate that the discount is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'starts_at' => now()->subDays(60),
            'ends_at' => now()->subDays(30),
        ]);
    }

    /**
     * Indicate that the discount is a percentage discount.
     */
    public function percentage(int $percentage = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'percentage',
            'data' => ['percentage' => $percentage],
        ]);
    }

    /**
     * Indicate that the discount is a fixed amount discount.
     */
    public function fixed(int $amount = 1000): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'fixed',
            'data' => ['fixed_value' => $amount],
        ]);
    }

    /**
     * Set the discount with a coupon code.
     */
    public function withCoupon(string $coupon = null): static
    {
        return $this->state(fn (array $attributes) => [
            'coupon' => $coupon ?? fake()->bothify('CODE-####'),
        ]);
    }
}


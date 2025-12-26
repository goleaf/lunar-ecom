<?php

namespace Database\Factories;

use App\Models\Bundle;
use App\Models\BundleItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BundleItem>
 */
class BundleItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = BundleItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bundle_id' => Bundle::factory(),
            'product_id' => Product::factory(),
            'product_variant_id' => null,
            'quantity' => fake()->numberBetween(1, 3),
            'min_quantity' => 1,
            'max_quantity' => fake()->optional(0.3)->numberBetween(2, 5),
            'is_required' => fake()->boolean(70),
            'is_default' => fake()->boolean(40),
            'price_override' => fake()->optional(0.2)->numberBetween(500, 5000),
            'discount_amount' => fake()->optional(0.2)->numberBetween(100, 1500),
            'display_order' => 0,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Force the bundle item to be required.
     */
    public function requiredItem(): static
    {
        return $this->state(fn () => [
            'is_required' => true,
        ]);
    }

    /**
     * Force the bundle item to be optional.
     */
    public function optionalItem(): static
    {
        return $this->state(fn () => [
            'is_required' => false,
            'min_quantity' => 0,
        ]);
    }
}

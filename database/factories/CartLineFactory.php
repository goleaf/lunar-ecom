<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Lunar\Models\Cart;
use Lunar\Models\CartLine;
use Lunar\Models\ProductVariant;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Lunar\Models\CartLine>
 */
class CartLineFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = CartLine::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cart_id' => Cart::factory(),
            'purchasable_type' => ProductVariant::class,
            'purchasable_id' => ProductVariant::factory(),
            'quantity' => fake()->numberBetween(1, 10),
            'meta' => [],
        ];
    }

    /**
     * Set the quantity.
     */
    public function quantity(int $quantity): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $quantity,
        ]);
    }

    /**
     * Set the purchasable (product variant).
     */
    public function forPurchasable(ProductVariant $variant): static
    {
        return $this->state(fn (array $attributes) => [
            'purchasable_type' => ProductVariant::class,
            'purchasable_id' => $variant->id,
        ]);
    }
}


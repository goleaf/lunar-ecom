<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Lunar\Models\Order;
use Lunar\Models\OrderLine;
use Lunar\Models\ProductVariant;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Lunar\Models\OrderLine>
 */
class OrderLineFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = OrderLine::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPrice = fake()->numberBetween(1000, 50000); // In cents
        $quantity = fake()->numberBetween(1, 10);
        $subTotal = $unitPrice * $quantity;
        $discountTotal = fake()->numberBetween(0, (int) ($subTotal * 0.2));
        $taxTotal = (int) (($subTotal - $discountTotal) * 0.2);
        $total = $subTotal - $discountTotal + $taxTotal;

        return [
            'order_id' => Order::factory(),
            'purchasable_type' => ProductVariant::class,
            'purchasable_id' => ProductVariant::factory(),
            'type' => 'physical',
            'description' => fake()->sentence(),
            'option' => fake()->optional(0.3)->sentence(),
            'identifier' => fake()->optional(0.5)->bothify('SKU-####'),
            'unit_price' => $unitPrice,
            'unit_quantity' => 1,
            'quantity' => $quantity,
            'sub_total' => $subTotal,
            'discount_total' => $discountTotal,
            'tax_breakdown' => [],
            'tax_total' => $taxTotal,
            'total' => $total,
            'notes' => fake()->optional(0.2)->sentence(),
            'meta' => [],
        ];
    }

    /**
     * Set the quantity.
     */
    public function quantity(int $quantity): static
    {
        return $this->state(function (array $attributes) use ($quantity) {
            $unitPrice = $attributes['unit_price'] ?? 1000;
            $subTotal = $unitPrice * $quantity;
            $discountTotal = $attributes['discount_total'] ?? 0;
            $taxTotal = (int) (($subTotal - $discountTotal) * 0.2);
            $total = $subTotal - $discountTotal + $taxTotal;

            return [
                'quantity' => $quantity,
                'sub_total' => $subTotal,
                'total' => $total,
            ];
        });
    }

    /**
     * Set the purchasable (product variant).
     */
    public function forPurchasable(ProductVariant $variant): static
    {
        return $this->state(fn (array $attributes) => [
            'purchasable_type' => ProductVariant::class,
            'purchasable_id' => $variant->id,
            'identifier' => $variant->sku,
        ]);
    }
}


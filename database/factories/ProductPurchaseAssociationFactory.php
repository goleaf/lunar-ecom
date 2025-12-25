<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductPurchaseAssociation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductPurchaseAssociation>
 */
class ProductPurchaseAssociationFactory extends Factory
{
    protected $model = ProductPurchaseAssociation::class;

    public function definition(): array
    {
        $coPurchaseCount = fake()->numberBetween(1, 100);
        $support = fake()->randomFloat(4, 0.01, 0.5);
        $confidence = fake()->randomFloat(4, 0.1, 0.9);
        $lift = $confidence / $support;

        return [
            'product_id' => Product::factory(),
            'associated_product_id' => Product::factory(),
            'co_purchase_count' => $coPurchaseCount,
            'confidence' => $confidence,
            'support' => $support,
            'lift' => $lift,
        ];
    }

    public function highConfidence(): static
    {
        return $this->state(fn (array $attributes) => [
            'confidence' => fake()->randomFloat(4, 0.5, 0.95),
            'support' => fake()->randomFloat(4, 0.1, 0.4),
        ]);
    }

    public function highSupport(): static
    {
        return $this->state(fn (array $attributes) => [
            'support' => fake()->randomFloat(4, 0.2, 0.5),
            'confidence' => fake()->randomFloat(4, 0.3, 0.8),
        ]);
    }

    public function withProducts(Product $product, Product $associatedProduct): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
            'associated_product_id' => $associatedProduct->id,
        ]);
    }
}


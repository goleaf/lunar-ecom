<?php

namespace Database\Factories;

use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PriceHistory>
 */
class PriceHistoryFactory extends Factory
{
    protected $model = PriceHistory::class;

    public function definition(): array
    {
        return [
            // Legacy price change tracking
            'product_id' => Product::factory(),
            'product_variant_id' => ProductVariant::factory(),
            'price_matrix_id' => null,
            'old_price' => fake()->optional()->randomFloat(2, 10, 200),
            'new_price' => fake()->randomFloat(2, 10, 200),
            'currency_code' => fake()->randomElement(['EUR', 'USD', 'GBP']),
            'change_type' => fake()->randomElement(['manual', 'matrix', 'import', 'bulk', 'scheduled']),
            'change_reason' => fake()->optional()->sentence(),
            'change_notes' => fake()->optional()->paragraph(),
            'context' => null,
            'changed_by' => null,
            'changed_at' => now()->subDays(fake()->numberBetween(0, 30)),

            // Advanced/normalized history (nullable for legacy-only rows)
            'currency_id' => null,
            'price' => null,
            'compare_at_price' => null,
            'channel_id' => null,
            'customer_group_id' => null,
            'pricing_layer' => null,
            'pricing_rule_id' => null,
            'change_metadata' => null,
            'effective_from' => null,
            'effective_to' => null,
        ];
    }

    public function forVariant(ProductVariant $variant): static
    {
        return $this->state(fn () => [
            'product_variant_id' => $variant->id,
            'product_id' => $variant->product_id,
        ]);
    }

    public function forProduct(Product $product): static
    {
        return $this->state(fn () => [
            'product_id' => $product->id,
        ]);
    }
}


<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Lunar\Models\TaxClass;
use Lunar\FieldTypes\Text;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = ProductVariant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####-???')),
            'tax_class_id' => function () {
                return TaxClass::firstOrCreate(
                    ['name' => 'Standard Tax'],
                    ['name' => 'Standard Tax']
                )->id;
            },
            'attribute_data' => collect([
                'size' => new Text(fake()->randomElement(['S', 'M', 'L', 'XL', 'XXL'])),
                'color' => new Text(fake()->colorName()),
            ]),
            'stock' => fake()->numberBetween(0, 1000),
            'backorder' => 0,
            'purchasable' => 'always',
            'shippable' => true,
            'unit_quantity' => 1,
            'weight_value' => fake()->randomFloat(2, 0.1, 10),
            'weight_unit' => 'kg',
            'height_value' => fake()->randomFloat(2, 1, 50),
            'height_unit' => 'cm',
            'width_value' => fake()->randomFloat(2, 1, 50),
            'width_unit' => 'cm',
            'length_value' => fake()->randomFloat(2, 1, 50),
            'length_unit' => 'cm',
        ];
    }

    /**
     * Indicate that the variant is in stock.
     */
    public function inStock(int $quantity = 100): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => $quantity,
        ]);
    }

    /**
     * Indicate that the variant is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 0,
        ]);
    }

    /**
     * Indicate that the variant has low stock.
     */
    public function lowStock(int $quantity = 5): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => $quantity,
        ]);
    }

    /**
     * Add custom variant attributes.
     */
    public function withAttributes(array $attributes): static
    {
        return $this->state(function (array $defaultAttributes) use ($attributes) {
            $attributeData = $defaultAttributes['attribute_data'] ?? collect();
            
            foreach ($attributes as $key => $value) {
                if (is_string($value)) {
                    $attributeData[$key] = new Text($value);
                } elseif (is_object($value) && method_exists($value, 'getValue')) {
                    // Check if it's a FieldType by checking for getValue method
                    $attributeData[$key] = $value;
                }
            }
            
            return [
                'attribute_data' => $attributeData,
            ];
        });
    }

    /**
     * Configure the factory to create variants with prices.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (ProductVariant $variant) {
            // Create a default price if currency and customer group exist
            $currency = \Lunar\Models\Currency::where('default', true)->first();
            $customerGroup = \Lunar\Models\CustomerGroup::where('default', true)->first();
            
            if ($currency && $customerGroup) {
                // Check if price already exists
                $existingPrice = $variant->prices()
                    ->where('currency_id', $currency->id)
                    ->where('customer_group_id', $customerGroup->id)
                    ->first();
                
                if (!$existingPrice) {
                    \Lunar\Models\Price::create([
                        'price' => fake()->numberBetween(1000, 100000), // In cents
                        'compare_price' => fake()->optional(0.3)->numberBetween(100000, 200000),
                        'currency_id' => $currency->id,
                        'customer_group_id' => $customerGroup->id,
                        'priceable_type' => ProductVariant::class,
                        'priceable_id' => $variant->id,
                    ]);
                }
            }
        });
    }

    /**
     * Set a custom SKU.
     */
    public function withSku(string $sku): static
    {
        return $this->state(fn (array $attributes) => [
            'sku' => $sku,
        ]);
    }

    /**
     * Set variant dimensions.
     */
    public function withDimensions(
        float $weight = null,
        float $height = null,
        float $width = null,
        float $length = null
    ): static {
        return $this->state(fn (array $attributes) => [
            'weight_value' => $weight ?? fake()->randomFloat(2, 0.1, 10),
            'height_value' => $height ?? fake()->randomFloat(2, 1, 50),
            'width_value' => $width ?? fake()->randomFloat(2, 1, 50),
            'length_value' => $length ?? fake()->randomFloat(2, 1, 50),
        ]);
    }
}


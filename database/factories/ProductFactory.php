<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(3, true);
        
        return [
            'product_type_id' => ProductType::factory(),
            'status' => fake()->randomElement(['published', 'draft', 'scheduled']),
            'attribute_data' => collect([
                'name' => new TranslatedText(collect([
                    'en' => new Text($name),
                ])),
                'description' => new Text(fake()->paragraph()),
            ]),
            'brand_id' => null, // Brands are optional, can be set via withBrand() method
        ];
    }

    /**
     * Indicate that the product is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
        ]);
    }

    /**
     * Indicate that the product is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Add additional attributes to the product.
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
     * Configure the factory to create products with variants.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Product $product) {
            // Attach to default channel if it exists
            $channel = \Lunar\Models\Channel::where('default', true)->first();
            if ($channel) {
                $product->channels()->syncWithoutDetaching([$channel->id]);
            }
        });
    }

    /**
     * Indicate that the product has a brand.
     */
    public function withBrand($brand = null): static
    {
        return $this->state(function (array $attributes) use ($brand) {
            if ($brand instanceof \Lunar\Models\Brand) {
                return ['brand_id' => $brand->id];
            }
            
            // Create or get brand by name
            $brandModel = \Lunar\Models\Brand::firstOrCreate(
                ['name' => $brand ?? fake()->company()]
            );
            
            return ['brand_id' => $brandModel->id];
        });
    }

    /**
     * Indicate that the product is scheduled.
     */
    public function scheduled(?\DateTime $date = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
        ]);
    }
}


<?php

namespace Database\Factories;

use App\Models\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Lunar\Models\AttributeGroup;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attribute>
 */
class AttributeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Attribute::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();
        $handle = str($name)->snake()->toString();
        $attributeType = fake()->randomElement(['product', 'collection']);
        
        return [
            'attribute_type' => $attributeType,
            'attribute_group_id' => function () use ($attributeType) {
                return AttributeGroup::firstOrCreate(
                    ['handle' => $attributeType],
                    [
                        'name' => [
                            'en' => ucfirst($attributeType),
                        ],
                        'attributable_type' => $attributeType === 'product' 
                            ? \App\Models\Product::class 
                            : \App\Models\Collection::class,
                        'position' => 0,
                    ]
                )->id;
            },
            'position' => fake()->numberBetween(0, 100),
            'name' => [
                'en' => ucfirst($name),
            ],
            'handle' => $handle,
            'section' => 'main',
            'type' => \Lunar\FieldTypes\Text::class,
            'required' => false,
            'default_value' => null,
            'configuration' => [],
            'system' => false,
        ];
    }

    /**
     * Indicate that the attribute is required.
     */
    public function required(): static
    {
        return $this->state(fn (array $attributes) => [
            'required' => true,
        ]);
    }

    /**
     * Indicate that the attribute is filterable.
     */
    public function filterable(): static
    {
        return $this->state(fn (array $attributes) => [
            'filterable' => true,
        ]);
    }

    /**
     * Indicate that the attribute is a system attribute.
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'system' => true,
        ]);
    }

    /**
     * Set the attribute type.
     */
    public function type(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'attribute_type' => $type,
        ]);
    }
}


<?php

namespace Database\Factories;

use App\Models\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Lunar\Models\CollectionGroup;
use Lunar\FieldTypes\Text;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Collection>
 */
class CollectionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Collection::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(2, true);
        
        return [
            'collection_group_id' => function () {
                return CollectionGroup::firstOrCreate(
                    ['handle' => 'default'],
                    [
                        'name' => 'Default',
                    ]
                )->id;
            },
            // Lunar expects `sort` in the format "<key>:<direction>" (e.g. "sku:asc").
            // Use a safe default that doesn't require extra data.
            'sort' => 'position:asc',
            'collection_type' => \App\Enums\CollectionType::STANDARD->value,
            'attribute_data' => collect([
                'name' => new Text($name),
                'description' => new Text(fake()->optional()->paragraph()),
            ]),
        ];
    }

    /**
     * Add additional attributes to the collection.
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
     * Set the sort position.
     */
    public function withPosition(int $position): static
    {
        return $this->state(fn (array $attributes) => [
            'sort' => $position,
        ]);
    }
}

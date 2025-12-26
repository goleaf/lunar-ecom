<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Lunar\Models\AttributeGroup;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Lunar\Models\AttributeGroup>
 */
class AttributeGroupFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = AttributeGroup::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $label = fake()->unique()->words(2, true);

        return [
            'attributable_type' => \App\Models\Product::class,
            'name' => ['en' => Str::title($label)],
            'handle' => Str::slug($label),
            'position' => 0,
        ];
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Lunar\Models\CollectionGroup;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Lunar\Models\CollectionGroup>
 */
class CollectionGroupFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = CollectionGroup::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $label = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($label),
            'handle' => Str::slug($label),
        ];
    }
}

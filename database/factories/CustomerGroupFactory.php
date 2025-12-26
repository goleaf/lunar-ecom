<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Lunar\Models\CustomerGroup;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Lunar\Models\CustomerGroup>
 */
class CustomerGroupFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = CustomerGroup::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $label = fake()->unique()->words(2, true);
        $handle = Str::slug($label);

        return [
            'name' => Str::title($label),
            'handle' => $handle,
            'default' => false,
        ];
    }

    /**
     * Mark the customer group as default.
     */
    public function defaultGroup(): static
    {
        return $this->state(fn () => [
            'default' => true,
        ]);
    }
}

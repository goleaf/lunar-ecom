<?php

namespace Database\Factories;

use App\Models\Collection;
use App\Models\SmartCollectionRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SmartCollectionRule>
 */
class SmartCollectionRuleFactory extends Factory
{
    protected $model = SmartCollectionRule::class;

    public function definition(): array
    {
        return [
            'collection_id' => Collection::factory(),
            'field' => 'tag',
            'operator' => 'equals',
            'value' => fake()->word(),
            'logic_group' => null,
            'group_operator' => 'and',
            'priority' => fake()->numberBetween(0, 50),
            'is_active' => true,
            'description' => fake()->optional(0.4)->sentence(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function forCollection(Collection $collection): static
    {
        return $this->state(fn () => ['collection_id' => $collection->id]);
    }

    public function priceBetween(float $min, float $max): static
    {
        return $this->state(fn () => [
            'field' => 'price',
            'operator' => 'between',
            'value' => ['min' => $min, 'max' => $max],
        ]);
    }
}


<?php

namespace Database\Factories;

use App\Models\SearchSynonym;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SearchSynonym>
 */
class SearchSynonymFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = SearchSynonym::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $term = fake()->unique()->word();
        $synonyms = collect(fake()->words(2))->map(fn($word) => strtolower($word))->toArray();
        
        return [
            'term' => $term,
            'synonyms' => $synonyms, // Stored as JSON array
            'is_active' => true,
            'priority' => fake()->numberBetween(0, 100),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * Indicate that the synonym is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set specific synonyms.
     */
    public function withSynonyms(array $synonyms): static
    {
        return $this->state(fn (array $attributes) => [
            'synonyms' => $synonyms,
        ]);
    }

    /**
     * Set priority.
     */
    public function withPriority(int $priority): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => $priority,
        ]);
    }
}


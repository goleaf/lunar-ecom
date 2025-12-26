<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Lunar\Models\Language;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Lunar\Models\Language>
 */
class LanguageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Language::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = fake()->unique()->languageCode();

        return [
            'code' => $code,
            'name' => ucfirst($code),
            'default' => false,
        ];
    }

    /**
     * Mark the language as default.
     */
    public function defaultLanguage(): static
    {
        return $this->state(fn () => [
            'default' => true,
        ]);
    }
}

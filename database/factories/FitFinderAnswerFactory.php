<?php

namespace Database\Factories;

use App\Models\FitFinderAnswer;
use App\Models\FitFinderQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FitFinderAnswer>
 */
class FitFinderAnswerFactory extends Factory
{
    protected $model = FitFinderAnswer::class;

    public function definition(): array
    {
        return [
            'fit_finder_question_id' => FitFinderQuestion::factory(),
            'answer_text' => fake()->words(3, true),
            'answer_value' => fake()->optional()->word(),
            'display_order' => fake()->numberBetween(0, 50),
            'size_adjustment' => null,
        ];
    }
}


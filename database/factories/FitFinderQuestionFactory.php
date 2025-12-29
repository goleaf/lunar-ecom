<?php

namespace Database\Factories;

use App\Models\FitFinderQuestion;
use App\Models\FitFinderQuiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FitFinderQuestion>
 */
class FitFinderQuestionFactory extends Factory
{
    protected $model = FitFinderQuestion::class;

    public function definition(): array
    {
        return [
            'fit_finder_quiz_id' => FitFinderQuiz::factory(),
            'question_text' => fake()->sentence(),
            'question_type' => fake()->randomElement(['single_choice', 'multiple_choice', 'text', 'number']),
            'display_order' => fake()->numberBetween(0, 50),
            'is_required' => true,
            'help_text' => fake()->optional()->sentence(),
        ];
    }
}


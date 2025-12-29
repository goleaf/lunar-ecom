<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\FitFinderQuestionResource;
use App\Filament\Resources\FitFinderQuestionResource\Pages\CreateFitFinderQuestion;
use App\Filament\Resources\FitFinderQuestionResource\Pages\EditFitFinderQuestion;
use App\Filament\Resources\FitFinderQuestionResource\RelationManagers\AnswersRelationManager;
use App\Models\FitFinderAnswer;
use App\Models\FitFinderQuestion;
use App\Models\FitFinderQuiz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentFitFinderQuestionResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_fit_finder_question_index_create_and_edit_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $question = FitFinderQuestion::factory()->create();

        $slug = FitFinderQuestionResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.create"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.edit", ['record' => $question->getKey()]))->assertOk();
    }

    public function test_fit_finder_question_can_be_created_via_filament_create_page(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $quiz = FitFinderQuiz::factory()->create();

        Livewire::test(CreateFitFinderQuestion::class)
            ->set('data.fit_finder_quiz_id', $quiz->id)
            ->set('data.question_text', 'How does it fit?')
            ->set('data.question_type', 'single_choice')
            ->set('data.display_order', 0)
            ->set('data.is_required', true)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseCount((new FitFinderQuestion())->getTable(), 1);
    }

    public function test_fit_finder_answer_can_be_created_via_answers_relation_manager_header_action(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $question = FitFinderQuestion::factory()->create();

        Livewire::test(AnswersRelationManager::class, [
            'ownerRecord' => $question,
            'pageClass' => EditFitFinderQuestion::class,
        ])->callTableAction('create', null, [
            'answer_text' => 'Perfect',
            'answer_value' => 'perfect',
            'display_order' => 0,
        ])->assertHasNoErrors();

        $this->assertDatabaseHas((new FitFinderAnswer())->getTable(), [
            'fit_finder_question_id' => $question->id,
            'answer_value' => 'perfect',
        ]);
    }
}


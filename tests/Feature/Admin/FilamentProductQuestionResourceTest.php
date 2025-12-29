<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\ProductQuestionResource;
use App\Filament\Resources\ProductQuestionResource\Pages\EditProductQuestion;
use App\Filament\Resources\ProductQuestionResource\Pages\ListProductQuestions;
use App\Filament\Resources\ProductQuestionResource\RelationManagers\AnswersRelationManager;
use App\Models\ProductAnswer;
use App\Models\ProductQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentProductQuestionResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_question_index_view_and_edit_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $question = ProductQuestion::factory()->pending()->create();

        $slug = ProductQuestionResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();

        $this->get(route("filament.admin.resources.{$slug}.view", [
            'record' => $question->getKey(),
        ]))->assertOk();

        $this->get(route("filament.admin.resources.{$slug}.edit", [
            'record' => $question->getKey(),
        ]))->assertOk();
    }

    public function test_product_question_can_be_approved_and_answered_via_table_actions(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $question = ProductQuestion::factory()->pending()->create([
            'is_public' => false,
        ]);

        Livewire::test(ListProductQuestions::class)
            ->callTableAction('approve', $question);

        $this->assertDatabaseHas((new ProductQuestion())->getTable(), [
            'id' => $question->id,
            'status' => 'approved',
            'is_public' => 1,
        ]);

        Livewire::test(ListProductQuestions::class)
            ->callTableAction('answer', $question, [
                'answer' => 'This is an official answer from the store team.',
                'is_official' => true,
            ]);

        $this->assertDatabaseHas((new ProductAnswer())->getTable(), [
            'question_id' => $question->id,
            'status' => 'approved',
            'is_approved' => 1,
        ]);

        $this->assertDatabaseHas((new ProductQuestion())->getTable(), [
            'id' => $question->id,
            'is_answered' => 1,
        ]);
    }

    public function test_product_answer_can_be_approved_via_answers_relation_manager_table_action(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $question = ProductQuestion::factory()->approved()->create();

        $answer = ProductAnswer::factory()->pending()->create([
            'question_id' => $question->id,
            'status' => 'pending',
            'is_approved' => false,
        ]);

        Livewire::test(AnswersRelationManager::class, [
            'ownerRecord' => $question,
            'pageClass' => EditProductQuestion::class,
        ])->callTableAction('approve', $answer);

        $this->assertDatabaseHas((new ProductAnswer())->getTable(), [
            'id' => $answer->id,
            'status' => 'approved',
            'is_approved' => 1,
        ]);
    }
}


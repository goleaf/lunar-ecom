<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\FitFinderQuizResource;
use App\Filament\Resources\FitFinderQuizResource\Pages\CreateFitFinderQuiz;
use App\Models\FitFinderQuiz;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentFitFinderQuizResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_fit_finder_quiz_index_create_and_edit_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $quiz = FitFinderQuiz::factory()->create();

        $slug = FitFinderQuizResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.create"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.edit", ['record' => $quiz->getKey()]))->assertOk();
    }

    public function test_fit_finder_quiz_can_be_created_via_filament_create_page(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        Livewire::test(CreateFitFinderQuiz::class)
            ->set('data.name', 'Fit Quiz')
            ->set('data.category_type', 'clothing')
            ->set('data.is_active', true)
            ->set('data.display_order', 0)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseCount((new FitFinderQuiz())->getTable(), 1);
    }
}


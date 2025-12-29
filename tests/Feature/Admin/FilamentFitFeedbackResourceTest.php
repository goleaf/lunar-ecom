<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\FitFeedbackResource;
use App\Filament\Resources\FitFeedbackResource\Pages\CreateFitFeedback;
use App\Models\FitFeedback;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentFitFeedbackResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_fit_feedback_index_create_and_edit_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $feedback = FitFeedback::factory()->create();

        $slug = FitFeedbackResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.create"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.edit", ['record' => $feedback->getKey()]))->assertOk();
    }

    public function test_fit_feedback_can_be_created_via_filament_create_page(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $product = Product::factory()->create();

        Livewire::test(CreateFitFeedback::class)
            ->set('data.product_id', $product->id)
            ->set('data.purchased_size', 'M')
            ->set('data.recommended_size', 'M')
            ->set('data.actual_fit', 'perfect')
            ->set('data.fit_rating', 5)
            ->set('data.would_return', false)
            ->set('data.would_exchange', false)
            ->set('data.is_helpful', false)
            ->set('data.is_public', false)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseCount((new FitFeedback())->getTable(), 1);
    }
}


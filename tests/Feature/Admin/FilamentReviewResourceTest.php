<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\ReviewResource\Pages\ListReviews;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentReviewResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_index_and_edit_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $review = Review::factory()->pending()->create();

        $this->get(route('filament.admin.resources.reviews.index'))->assertOk();

        $this->get(route('filament.admin.resources.reviews.edit', [
            'record' => $review->getKey(),
        ]))->assertOk();
    }

    public function test_review_table_actions_work(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $review = Review::factory()->pending()->create();

        Livewire::test(ListReviews::class)
            ->callTableAction('approve', $review);

        $review = $review->fresh();
        $this->assertTrue($review->is_approved);

        Livewire::test(ListReviews::class)
            ->callTableAction('respond', $review, data: [
                'response' => 'Thanks for the feedback — we appreciate it.',
            ]);

        $review = $review->fresh();
        $this->assertNotNull($review->admin_response);

        Livewire::test(ListReviews::class)
            ->callTableAction('unapprove', $review);

        $review = $review->fresh();
        $this->assertFalse($review->is_approved);
    }
}


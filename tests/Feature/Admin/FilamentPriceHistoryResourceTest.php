<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\PriceHistoryResource;
use App\Models\PriceHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Admin\Models\Staff;
use Tests\TestCase;

class FilamentPriceHistoryResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_history_index_and_view_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $history = PriceHistory::factory()->create();

        $slug = PriceHistoryResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.view", [
            'record' => $history->getKey(),
        ]))->assertOk();
    }
}


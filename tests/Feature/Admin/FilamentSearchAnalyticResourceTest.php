<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\SearchAnalyticResource;
use App\Filament\Resources\SearchAnalyticResource\Pages\ListSearchAnalytics;
use App\Models\SearchAnalytic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentSearchAnalyticResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_analytic_index_and_view_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $analytic = SearchAnalytic::factory()->create();

        $slug = SearchAnalyticResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();

        $this->get(route("filament.admin.resources.{$slug}.view", [
            'record' => $analytic->getKey(),
        ]))->assertOk();
    }

    public function test_search_analytic_can_be_deleted_via_table_action(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $analytic = SearchAnalytic::factory()->create();

        Livewire::test(ListSearchAnalytics::class)
            ->callTableAction('delete', $analytic);

        $this->assertDatabaseMissing($analytic->getTable(), [
            'id' => $analytic->id,
        ]);
    }
}


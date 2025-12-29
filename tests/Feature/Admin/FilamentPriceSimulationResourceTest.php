<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\PriceSimulationResource;
use App\Models\PriceSimulation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Admin\Models\Staff;
use Tests\TestCase;

class FilamentPriceSimulationResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_simulation_index_and_view_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $simulation = PriceSimulation::factory()->create();

        $slug = PriceSimulationResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.view", [
            'record' => $simulation->getKey(),
        ]))->assertOk();
    }
}


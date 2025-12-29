<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\MarginAlertResource;
use App\Filament\Resources\MarginAlertResource\Pages\ListMarginAlerts;
use App\Models\MarginAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentMarginAlertResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_margin_alert_index_and_view_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $alert = MarginAlert::factory()->create();

        $slug = MarginAlertResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.view", [
            'record' => $alert->getKey(),
        ]))->assertOk();
    }

    public function test_margin_alert_can_be_resolved_via_table_action(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $alert = MarginAlert::factory()->create([
            'is_resolved' => false,
            'resolved_at' => null,
        ]);

        Livewire::test(ListMarginAlerts::class)
            ->callTableAction('resolve', $alert);

        $this->assertDatabaseHas((new MarginAlert())->getTable(), [
            'id' => $alert->id,
            'is_resolved' => true,
        ]);
    }
}


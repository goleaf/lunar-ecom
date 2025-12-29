<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\AvailabilityRuleResource;
use App\Filament\Resources\AvailabilityRuleResource\Pages\CreateAvailabilityRule;
use App\Models\AvailabilityRule;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentAvailabilityRuleResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_availability_rule_index_create_view_and_edit_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $rule = AvailabilityRule::factory()->create();

        $slug = AvailabilityRuleResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.create"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.view", ['record' => $rule->getKey()]))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.edit", ['record' => $rule->getKey()]))->assertOk();
    }

    public function test_availability_rule_can_be_created_via_filament_create_page(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $product = Product::factory()->create();

        Livewire::test(CreateAvailabilityRule::class)
            ->set('data.product_id', $product->id)
            ->set('data.rule_type', 'lead_time')
            ->set('data.rule_config', json_encode(['lead_time_hours' => 24]))
            ->set('data.priority', 0)
            ->set('data.is_active', true)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseCount((new AvailabilityRule())->getTable(), 1);
    }
}


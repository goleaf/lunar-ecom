<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\SmartCollectionRuleResource;
use App\Filament\Resources\SmartCollectionRuleResource\Pages\CreateSmartCollectionRule;
use App\Models\Collection;
use App\Models\SmartCollectionRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentSmartCollectionRuleResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_smart_collection_rule_index_create_view_and_edit_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $rule = SmartCollectionRule::factory()->create();

        $slug = SmartCollectionRuleResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.create"))->assertOk();

        $this->get(route("filament.admin.resources.{$slug}.view", [
            'record' => $rule->getKey(),
        ]))->assertOk();

        $this->get(route("filament.admin.resources.{$slug}.edit", [
            'record' => $rule->getKey(),
        ]))->assertOk();
    }

    public function test_smart_collection_rule_can_be_created_via_filament_create_page(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $collection = Collection::factory()->create();

        Livewire::test(CreateSmartCollectionRule::class)
            ->set('data.collection_id', $collection->id)
            ->set('data.field', 'tag')
            ->set('data.operator', 'equals')
            ->set('data.value', 'summer')
            ->set('data.group_operator', 'and')
            ->set('data.priority', 0)
            ->set('data.is_active', true)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseCount((new SmartCollectionRule())->getTable(), 1);
    }
}


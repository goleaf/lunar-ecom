<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\CollectionResource;
use App\Filament\Resources\CollectionResource\Pages\CreateCollection;
use App\Models\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Admin\Models\Staff;
use Lunar\Models\CollectionGroup;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentCollectionResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_collection_index_create_view_and_edit_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $collection = Collection::factory()->create();

        $slug = CollectionResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.create"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.view", ['record' => $collection->getKey()]))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.edit", ['record' => $collection->getKey()]))->assertOk();
    }

    public function test_collection_can_be_created_via_filament_create_page(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $group = CollectionGroup::factory()->create();
        $beforeCount = Collection::count();

        Livewire::test(CreateCollection::class)
            ->set('data.collection_group_id', $group->id)
            ->set('data.type', 'static')
            ->set('data.sort', 'position:asc')
            ->set('data.collection_type', 'manual')
            ->set('data.attribute_data.collection_name', 'Test Collection')
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseCount((new Collection())->getTable(), $beforeCount + 1);
    }
}


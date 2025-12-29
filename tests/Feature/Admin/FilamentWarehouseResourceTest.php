<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\WarehouseResource;
use App\Filament\Resources\WarehouseResource\Pages\CreateWarehouse;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentWarehouseResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_warehouse_index_create_and_edit_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $slug = WarehouseResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.create"))->assertOk();

        $warehouse = Warehouse::create([
            'name' => 'Warehouse A',
            'code' => 'WHA-' . Str::upper(Str::random(6)),
            'is_active' => true,
            'priority' => 0,
        ]);

        $this->get(route("filament.admin.resources.{$slug}.edit", [
            'record' => $warehouse->getKey(),
        ]))->assertOk();
    }

    public function test_warehouse_can_be_created_via_filament_create_page(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $code = 'WH-' . Str::upper(Str::random(8));

        Livewire::test(CreateWarehouse::class)
            ->set('data.name', 'Test Warehouse')
            ->set('data.code', $code)
            ->set('data.is_active', true)
            ->set('data.priority', 5)
            ->call('create');

        $this->assertDatabaseHas((new Warehouse())->getTable(), [
            'code' => $code,
            'name' => 'Test Warehouse',
        ]);
    }
}


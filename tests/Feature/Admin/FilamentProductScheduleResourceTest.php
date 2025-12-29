<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\ProductScheduleResource;
use App\Filament\Resources\ProductScheduleResource\Pages\CreateProductSchedule;
use App\Models\Product;
use App\Models\ProductSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentProductScheduleResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_schedule_index_create_view_and_edit_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $schedule = ProductSchedule::factory()->create();

        $slug = ProductScheduleResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.create"))->assertOk();

        $this->get(route("filament.admin.resources.{$slug}.view", [
            'record' => $schedule->getKey(),
        ]))->assertOk();

        $this->get(route("filament.admin.resources.{$slug}.edit", [
            'record' => $schedule->getKey(),
        ]))->assertOk();
    }

    public function test_product_schedule_can_be_created_via_filament_create_page(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $product = Product::factory()->published()->create();

        Livewire::test(CreateProductSchedule::class)
            ->set('data.product_id', $product->id)
            ->set('data.type', 'publish')
            ->set('data.schedule_type', 'one_time')
            ->set('data.scheduled_at', now()->addHour()->toDateTimeString())
            ->set('data.is_active', true)
            ->call('create');

        $this->assertDatabaseCount((new ProductSchedule())->getTable(), 1);
    }
}


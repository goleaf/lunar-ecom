<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\ProductBadgeResource;
use App\Filament\Resources\ProductBadgeResource\Pages\CreateProductBadge;
use App\Models\ProductBadge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentProductBadgeResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_badge_index_create_and_edit_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $badge = ProductBadge::create([
            'name' => 'Sale Badge',
            'handle' => 'sale-badge-' . Str::lower(Str::random(6)),
            'type' => 'sale',
            'is_active' => true,
            'priority' => 10,
        ]);

        $slug = ProductBadgeResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.create"))->assertOk();

        $this->get(route("filament.admin.resources.{$slug}.edit", [
            'record' => $badge->getKey(),
        ]))->assertOk();
    }

    public function test_product_badge_can_be_created_via_filament_create_page(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $name = 'Badge ' . Str::title(Str::random(8));

        Livewire::test(CreateProductBadge::class)
            ->set('data.name', $name)
            ->set('data.type', 'custom')
            ->set('data.color', '#000000')
            ->set('data.background_color', '#FFFFFF')
            ->set('data.position', 'top-left')
            ->set('data.style', 'rounded')
            ->call('create');

        $this->assertDatabaseHas((new ProductBadge())->getTable(), [
            'name' => $name,
        ]);
    }
}


<?php

namespace Tests\Feature\Admin;

use App\Models\SizeGuide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Admin\Models\Staff;
use Tests\TestCase;

class FilamentSizeGuideResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_size_guide_edit_page_renders_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $sizeGuide = SizeGuide::create([
            'name' => 'Test Size Guide',
            'measurement_unit' => 'cm',
            'size_system' => 'us',
            'is_active' => true,
            'display_order' => 0,
        ]);

        $this->get(route('filament.admin.resources.size-guides.edit', [
            'record' => $sizeGuide->getKey(),
        ]))->assertOk();
    }
}


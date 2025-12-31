<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\CustomizationTemplateResource;
use App\Filament\Resources\CustomizationTemplateResource\Pages\CreateCustomizationTemplate;
use App\Models\CustomizationTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentCustomizationTemplateResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_customization_template_index_create_view_and_edit_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $template = CustomizationTemplate::create([
            'name' => 'Template A',
            'category' => 'engraving',
            'template_data' => [
                'font' => 'Arial',
                'font_size' => 24,
                'color' => '#000000',
            ],
            'is_active' => true,
        ]);

        $slug = CustomizationTemplateResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.create"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.view", ['record' => $template->getKey()]))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.edit", ['record' => $template->getKey()]))->assertOk();
    }

    public function test_customization_template_can_be_created_via_filament_create_page(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $beforeCount = CustomizationTemplate::count();

        Livewire::test(CreateCustomizationTemplate::class)
            ->set('data.name', 'Template B')
            ->set('data.category', 'monogram')
            ->set('data.is_active', true)
            ->set('data.template_data', json_encode(['text' => 'Sample', 'font' => 'Arial']))
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseCount((new CustomizationTemplate())->getTable(), $beforeCount + 1);
    }
}


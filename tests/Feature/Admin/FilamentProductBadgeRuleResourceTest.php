<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\ProductBadgeRuleResource;
use App\Filament\Resources\ProductBadgeRuleResource\Pages\CreateProductBadgeRule;
use App\Models\ProductBadge;
use App\Models\ProductBadgeRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentProductBadgeRuleResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_badge_rule_index_create_and_edit_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $badge = ProductBadge::create([
            'name' => 'New Badge',
            'handle' => 'new-badge-' . Str::lower(Str::random(6)),
            'type' => 'new',
            'is_active' => true,
            'priority' => 10,
        ]);

        $rule = ProductBadgeRule::create([
            'badge_id' => $badge->id,
            'condition_type' => 'automatic',
            'name' => 'Is new (30d)',
            'conditions' => [
                'is_new' => [
                    'enabled' => true,
                    'days' => 30,
                ],
            ],
            'priority' => 10,
            'is_active' => true,
        ]);

        $slug = ProductBadgeRuleResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.create"))->assertOk();

        $this->get(route("filament.admin.resources.{$slug}.edit", [
            'record' => $rule->getKey(),
        ]))->assertOk();
    }

    public function test_badge_rule_can_be_created_via_filament_create_page(): void
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

        $conditionsJson = json_encode([
            'low_stock' => [
                'enabled' => true,
                'threshold' => 5,
            ],
        ], JSON_UNESCAPED_SLASHES);

        Livewire::test(CreateProductBadgeRule::class)
            ->set('data.badge_id', $badge->id)
            ->set('data.condition_type', 'automatic')
            ->set('data.name', 'Low stock')
            ->set('data.conditions', $conditionsJson)
            ->set('data.priority', 20)
            ->set('data.is_active', true)
            ->call('create');

        $this->assertDatabaseHas((new ProductBadgeRule())->getTable(), [
            'badge_id' => $badge->id,
            'condition_type' => 'automatic',
        ]);
    }
}


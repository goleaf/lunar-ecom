<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\RecommendationRuleResource;
use App\Filament\Resources\RecommendationRuleResource\Pages\CreateRecommendationRule;
use App\Filament\Resources\RecommendationRuleResource\Pages\ListRecommendationRules;
use App\Models\Product;
use App\Models\RecommendationRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentRecommendationRuleResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_recommendation_rule_index_create_view_and_edit_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $rule = RecommendationRule::factory()->create();

        $slug = RecommendationRuleResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();
        $this->get(route("filament.admin.resources.{$slug}.create"))->assertOk();

        $this->get(route("filament.admin.resources.{$slug}.view", [
            'record' => $rule->getKey(),
        ]))->assertOk();

        $this->get(route("filament.admin.resources.{$slug}.edit", [
            'record' => $rule->getKey(),
        ]))->assertOk();
    }

    public function test_recommendation_rule_can_be_created_via_filament_create_page(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $source = Product::factory()->published()->create();
        $recommended = Product::factory()->published()->create();

        Livewire::test(CreateRecommendationRule::class)
            ->set('data.source_product_id', $source->id)
            ->set('data.recommended_product_id', $recommended->id)
            ->set('data.rule_type', 'manual')
            ->set('data.priority', 10)
            ->set('data.is_active', true)
            ->call('create');

        $this->assertDatabaseCount((new RecommendationRule())->getTable(), 1);
    }

    public function test_recommendation_rule_metrics_can_be_reset_via_table_action(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $rule = RecommendationRule::factory()->create([
            'display_count' => 10,
            'click_count' => 5,
            'conversion_rate' => 0.5,
        ]);

        Livewire::test(ListRecommendationRules::class)
            ->callTableAction('resetMetrics', $rule);

        $this->assertDatabaseHas((new RecommendationRule())->getTable(), [
            'id' => $rule->id,
            'display_count' => 0,
            'click_count' => 0,
            'conversion_rate' => 0,
        ]);
    }
}


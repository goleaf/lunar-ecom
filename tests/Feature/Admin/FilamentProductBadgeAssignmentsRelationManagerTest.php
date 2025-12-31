<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProductResource\RelationManagers\BadgeAssignmentsRelationManager;
use App\Models\Product;
use App\Models\ProductBadge;
use App\Models\ProductBadgeAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentProductBadgeAssignmentsRelationManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_badge_assignment_can_be_created_via_relation_manager(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $product = Product::factory()->create();
        $badge = ProductBadge::create([
            'name' => 'Badge ' . Str::upper(Str::random(6)),
        ]);

        Livewire::test(BadgeAssignmentsRelationManager::class, [
            'ownerRecord' => $product,
            'pageClass' => EditProduct::class,
        ])->callTableAction('create', null, [
            'badge_id' => $badge->getKey(),
            'assignment_type' => 'manual',
            'is_active' => true,
        ])->assertHasNoErrors();

        $this->assertDatabaseHas((new ProductBadgeAssignment())->getTable(), [
            'product_id' => $product->getKey(),
            'badge_id' => $badge->getKey(),
            'assignment_type' => 'manual',
            'is_active' => 1,
        ]);
    }
}


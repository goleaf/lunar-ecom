<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\CollectionResource\Pages\EditCollection;
use App\Filament\Resources\CollectionResource\RelationManagers\ProductMetadataRelationManager;
use App\Filament\Resources\CollectionResource\RelationManagers\SmartRulesRelationManager;
use App\Models\Collection;
use App\Models\CollectionProductMetadata;
use App\Models\Product;
use App\Models\SmartCollectionRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentCollectionRelationManagersTest extends TestCase
{
    use RefreshDatabase;

    public function test_collection_product_metadata_can_be_created_via_relation_manager(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $collection = Collection::factory()->create();
        $product = Product::factory()->create();

        Livewire::test(ProductMetadataRelationManager::class, [
            'ownerRecord' => $collection,
            'pageClass' => EditCollection::class,
        ])->callTableAction('create', null, [
            'product_id' => $product->getKey(),
            'is_auto_assigned' => false,
            'position' => 0,
            'assigned_at' => now()->toDateTimeString(),
        ])->assertHasNoErrors();

        $this->assertDatabaseHas((new CollectionProductMetadata())->getTable(), [
            'collection_id' => $collection->getKey(),
            'product_id' => $product->getKey(),
            'is_auto_assigned' => 0,
            'position' => 0,
        ]);
    }

    public function test_smart_collection_rule_can_be_created_via_relation_manager(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $collection = Collection::factory()->create();

        Livewire::test(SmartRulesRelationManager::class, [
            'ownerRecord' => $collection,
            'pageClass' => EditCollection::class,
        ])->callTableAction('create', null, [
            'field' => 'price',
            'operator' => 'between',
            'value' => json_encode(['min' => 10, 'max' => 50]),
            'group_operator' => 'and',
            'priority' => 0,
            'is_active' => true,
        ])->assertHasNoErrors();

        $this->assertDatabaseHas((new SmartCollectionRule())->getTable(), [
            'collection_id' => $collection->getKey(),
            'field' => 'price',
            'operator' => 'between',
            'is_active' => 1,
        ]);
    }
}


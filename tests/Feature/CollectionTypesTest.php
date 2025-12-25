<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Enums\CollectionType;
use App\Services\CollectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionTypesTest extends TestCase
{
    use RefreshDatabase;

    protected CollectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CollectionService::class);
    }

    public function test_collection_has_default_type(): void
    {
        $collection = Collection::factory()->create();

        $this->assertEquals(CollectionType::STANDARD, $collection->collection_type);
    }

    public function test_collection_can_have_cross_sell_type(): void
    {
        $collection = Collection::factory()->create([
            'collection_type' => CollectionType::CROSS_SELL->value,
        ]);

        $this->assertEquals(CollectionType::CROSS_SELL, $collection->collection_type);
    }

    public function test_collection_cross_sell_scope_works(): void
    {
        $crossSell1 = Collection::factory()->create(['collection_type' => CollectionType::CROSS_SELL->value]);
        $crossSell2 = Collection::factory()->create(['collection_type' => CollectionType::CROSS_SELL->value]);
        $standard = Collection::factory()->create(['collection_type' => CollectionType::STANDARD->value]);

        $crossSellCollections = Collection::crossSell()->get();

        $this->assertTrue($crossSellCollections->contains($crossSell1));
        $this->assertTrue($crossSellCollections->contains($crossSell2));
        $this->assertFalse($crossSellCollections->contains($standard));
    }

    public function test_collection_up_sell_scope_works(): void
    {
        $upSell = Collection::factory()->create(['collection_type' => CollectionType::UP_SELL->value]);
        $standard = Collection::factory()->create(['collection_type' => CollectionType::STANDARD->value]);

        $upSellCollections = Collection::upSell()->get();

        $this->assertTrue($upSellCollections->contains($upSell));
        $this->assertFalse($upSellCollections->contains($standard));
    }

    public function test_collection_service_can_filter_by_type(): void
    {
        Collection::factory()->create(['collection_type' => CollectionType::CROSS_SELL->value]);
        Collection::factory()->create(['collection_type' => CollectionType::UP_SELL->value]);
        Collection::factory()->create(['collection_type' => CollectionType::STANDARD->value]);

        $crossSell = $this->service->searchCollections(['collection_type' => CollectionType::CROSS_SELL->value]);

        $this->assertCount(1, $crossSell);
        $this->assertEquals(CollectionType::CROSS_SELL, $crossSell->first()->collection_type);
    }

    public function test_collection_type_enum_has_labels(): void
    {
        $this->assertEquals('Cross-Sell', CollectionType::CROSS_SELL->label());
        $this->assertEquals('Up-Sell', CollectionType::UP_SELL->label());
        $this->assertEquals('Standard', CollectionType::STANDARD->label());
    }

    public function test_collection_type_enum_has_descriptions(): void
    {
        $this->assertNotEmpty(CollectionType::CROSS_SELL->description());
        $this->assertNotEmpty(CollectionType::UP_SELL->description());
    }
}


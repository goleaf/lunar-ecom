<?php

namespace Tests\Feature\Frontend;

use App\Models\Collection;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CollectionFilterOptionsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Lunar\Models\Brand;
use Tests\TestCase;

class CollectionFilterOptionsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_brand_counts_are_calculated_independently(): void
    {
        $brandA = Brand::factory()->create(['name' => 'Brand A']);
        $brandB = Brand::factory()->create(['name' => 'Brand B']);

        $productA = Product::factory()->published()->withBrand($brandA)->create();
        ProductVariant::factory()->create(['product_id' => $productA->id]);

        $productB = Product::factory()->published()->withBrand($brandB)->create();
        ProductVariant::factory()->create(['product_id' => $productB->id]);

        $collection = Collection::factory()->create();
        $collection->products()->attach([
            $productA->id => ['position' => 1],
            $productB->id => ['position' => 2],
        ]);

        $request = Request::create('/collections/' . $collection->id, 'GET');
        $options = app(CollectionFilterOptionsService::class)->getFilterOptions($collection, $request);

        $this->assertCount(2, $options['brands']);

        $this->assertEqualsCanonicalizing([
            [
                'id' => $brandA->id,
                'name' => 'Brand A',
                'count' => 1,
            ],
            [
                'id' => $brandB->id,
                'name' => 'Brand B',
                'count' => 1,
            ],
        ], $options['brands']->toArray());
    }
}


<?php

namespace Tests\Feature\Frontend;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Models\Brand;
use Tests\TestCase;

class BrandApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_brands_api_returns_brands_with_product_counts(): void
    {
        $brandA = Brand::factory()->create(['name' => 'Alpha Brand']);
        $brandB = Brand::factory()->create(['name' => 'Beta Brand']);

        Product::factory()->published()->withBrand($brandA)->create();

        $response = $this->getJson(route('frontend.brands.api'))
            ->assertOk()
            ->assertJsonStructure([
                'brands' => [
                    ['id', 'name', 'logo_url', 'product_count'],
                ],
            ]);

        $brands = collect($response->json('brands'));

        $this->assertSame(2, $brands->count());

        $alpha = $brands->firstWhere('id', $brandA->getKey());
        $beta = $brands->firstWhere('id', $brandB->getKey());

        $this->assertNotNull($alpha);
        $this->assertNotNull($beta);

        $this->assertSame('Alpha Brand', $alpha['name']);
        $this->assertSame(1, $alpha['product_count']);

        $this->assertSame('Beta Brand', $beta['name']);
        $this->assertSame(0, $beta['product_count']);
    }
}


<?php

namespace Tests\Feature\Frontend;

use App\Models\Bundle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BundleAvailableProductsEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_bundle_available_products_endpoint_returns_products_key(): void
    {
        $bundle = Bundle::factory()->create([
            // no category_id means "no available products" is a valid outcome.
            'category_id' => null,
        ]);

        $this->getJson(route('frontend.bundles.available-products', [
            'bundle' => $bundle->getKey(),
        ]))
            ->assertOk()
            ->assertJsonStructure([
                'products',
            ]);
    }
}


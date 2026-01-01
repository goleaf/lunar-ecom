<?php

namespace Tests\Feature\Frontend;

use App\Models\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionFilterEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_collection_filter_endpoint_returns_json_payload_for_ajax_requests(): void
    {
        $collection = Collection::factory()->create();

        $this->getJson(route('frontend.collections.filter', [
            'collection' => $collection->getKey(),
        ]))
            ->assertOk()
            ->assertJsonStructure([
                'products',
                'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
                'filter_options' => ['price_range', 'brands', 'categories', 'attributes', 'availability'],
                'html',
            ]);
    }
}


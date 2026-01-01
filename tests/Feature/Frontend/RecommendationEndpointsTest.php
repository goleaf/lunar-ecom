<?php

namespace Tests\Feature\Frontend;

use App\Models\Product;
use App\Models\ProductView;
use App\Models\RecommendationClick;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_recommendations_index_returns_expected_payload(): void
    {
        $product = Product::factory()->published()->create();
        Product::factory()->published()->create();

        $this->getJson(route('frontend.recommendations.index', ['product' => $product->getKey()]) . '?algorithm=related&location=product_page&limit=5')
            ->assertOk()
            ->assertJson([
                'algorithm' => 'related',
                'location' => 'product_page',
            ])
            ->assertJsonStructure([
                'recommendations',
                'algorithm',
                'location',
            ]);
    }

    public function test_recommendations_track_view_creates_product_view_record(): void
    {
        $product = Product::factory()->published()->create();

        $this->postJson(route('frontend.recommendations.track-view', ['product' => $product->getKey()]))
            ->assertOk()
            ->assertJson([
                'message' => 'View tracked',
            ]);

        $this->assertDatabaseCount((new ProductView())->getTable(), 1);

        $this->assertDatabaseHas((new ProductView())->getTable(), [
            'product_id' => $product->getKey(),
        ]);
    }

    public function test_recommendations_track_click_creates_recommendation_click_record(): void
    {
        $source = Product::factory()->published()->create();
        $recommended = Product::factory()->published()->create();

        $this->postJson(route('frontend.recommendations.track-click'), [
            'source_product_id' => $source->getKey(),
            'recommended_product_id' => $recommended->getKey(),
            'recommendation_type' => 'related',
            'display_location' => 'product_page',
            'recommendation_algorithm' => 'related',
        ])
            ->assertOk()
            ->assertJson([
                'message' => 'Click tracked',
            ]);

        $this->assertDatabaseCount((new RecommendationClick())->getTable(), 1);

        $this->assertDatabaseHas((new RecommendationClick())->getTable(), [
            'source_product_id' => $source->getKey(),
            'recommended_product_id' => $recommended->getKey(),
            'recommendation_type' => 'related',
            'display_location' => 'product_page',
            'recommendation_algorithm' => 'related',
        ]);
    }

    public function test_frequently_bought_together_endpoint_returns_products_key(): void
    {
        $product = Product::factory()->published()->create();

        $this->getJson(route('frontend.recommendations.frequently-bought-together', ['product' => $product->getKey()]))
            ->assertOk()
            ->assertJsonStructure([
                'products',
            ]);
    }

    public function test_personalized_endpoint_returns_products_key(): void
    {
        $this->getJson(route('frontend.recommendations.personalized'))
            ->assertOk()
            ->assertJsonStructure([
                'products',
            ]);
    }
}


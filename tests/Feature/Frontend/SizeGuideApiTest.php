<?php

namespace Tests\Feature\Frontend;

use App\Models\FitReview;
use App\Models\Product;
use App\Models\SizeChart;
use App\Models\SizeGuide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SizeGuideApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_size_guide_show_returns_404_when_no_guide_is_available(): void
    {
        $product = Product::factory()->published()->create();

        $this->getJson(route('frontend.products.size-guide.show', [
            'product' => $product->getKey(),
        ]))
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_size_guide_show_returns_payload_when_guide_is_attached_to_product(): void
    {
        $product = Product::factory()->published()->create();

        $guide = SizeGuide::create([
            'name' => 'Test Size Guide',
            'measurement_unit' => 'cm',
            'size_system' => 'us',
            'is_active' => true,
            'display_order' => 0,
        ]);

        $product->sizeGuides()->attach($guide->id, [
            'region' => null,
            'priority' => 10,
        ]);

        $this->getJson(route('frontend.products.size-guide.show', [
            'product' => $product->getKey(),
        ]))
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'size_guide' => ['id'],
                'fit_statistics' => ['total_reviews'],
            ]);
    }

    public function test_size_guide_recommend_returns_ranked_recommendations(): void
    {
        $product = Product::factory()->published()->create();

        $guide = SizeGuide::create([
            'name' => 'Test Size Guide',
            'measurement_unit' => 'cm',
            'size_system' => 'us',
            'is_active' => true,
            'display_order' => 0,
        ]);

        $product->sizeGuides()->attach($guide->id, [
            'region' => null,
            'priority' => 10,
        ]);

        SizeChart::create([
            'size_guide_id' => $guide->id,
            'size_name' => 'M',
            'size_code' => 'M',
            'size_order' => 1,
            'measurements' => [
                'chest' => 38,
                'waist' => 32,
            ],
            'is_active' => true,
        ]);

        SizeChart::create([
            'size_guide_id' => $guide->id,
            'size_name' => 'L',
            'size_code' => 'L',
            'size_order' => 2,
            'measurements' => [
                'chest' => 41,
                'waist' => 35,
            ],
            'is_active' => true,
        ]);

        $this->postJson(route('frontend.products.size-guide.recommend', [
            'product' => $product->getKey(),
        ]), [
            'measurements' => [
                'chest' => 38,
                'waist' => 32,
            ],
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('top_recommendation.size', 'M');
    }

    public function test_size_guide_fit_statistics_returns_empty_stats_when_no_reviews(): void
    {
        $product = Product::factory()->published()->create();

        $this->getJson(route('frontend.products.size-guide.fit-statistics', [
            'product' => $product->getKey(),
        ]))
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('statistics.total_reviews', 0);
    }

    public function test_size_guide_fit_review_creates_review_record(): void
    {
        $product = Product::factory()->published()->create();

        $this->postJson(route('frontend.products.size-guide.fit-review', [
            'product' => $product->getKey(),
        ]), [
            'purchased_size' => 'M',
            'fit_rating' => 'perfect',
            'would_recommend_size' => true,
            'fit_notes' => 'Fits great.',
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas((new FitReview())->getTable(), [
            'product_id' => $product->id,
            'purchased_size' => 'M',
            'fit_rating' => 'perfect',
        ]);
    }
}


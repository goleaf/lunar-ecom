<?php

namespace Tests\Feature\Frontend;

use App\Lunar\Customers\CustomerHelper;
use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewHelpfulVote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_store_requires_authentication(): void
    {
        $product = Product::factory()->published()->create();

        $this->postJson(route('frontend.reviews.store', [
            'product' => $product->getKey(),
        ]), [
            'rating' => 5,
            'title' => 'Excellent product!',
            'content' => 'This product is amazing and I would buy it again.',
        ])->assertUnauthorized();
    }

    public function test_review_store_creates_pending_review_for_authenticated_user(): void
    {
        $product = Product::factory()->published()->create();
        $user = User::factory()->create();
        $customer = CustomerHelper::getOrCreateCustomerForUser($user);

        $this->actingAs($user)
            ->postJson(route('frontend.reviews.store', [
                'product' => $product->getKey(),
            ]), [
                'rating' => 5,
                'title' => 'Excellent product!',
                'content' => 'This product is amazing and I would buy it again.',
                'recommended' => true,
            ])
            ->assertStatus(201)
            ->assertJson([
                'review' => [
                    'product_id' => $product->getKey(),
                    'customer_id' => $customer->getKey(),
                    'rating' => 5,
                    'is_approved' => false,
                ],
            ]);

        $this->assertDatabaseHas((new Review())->getTable(), [
            'product_id' => $product->getKey(),
            'customer_id' => $customer->getKey(),
            'rating' => 5,
            'is_approved' => 0,
        ]);
    }

    public function test_review_helpful_endpoint_creates_vote_and_updates_counts(): void
    {
        $review = Review::factory()->approved()->create([
            'helpful_count' => 0,
            'not_helpful_count' => 0,
        ]);

        $this->postJson(route('frontend.reviews.helpful', [
            'review' => $review->getKey(),
        ]), [
            'is_helpful' => true,
        ])
            ->assertOk()
            ->assertJson([
                'review' => [
                    'id' => $review->getKey(),
                ],
            ]);

        $review->refresh();
        $this->assertSame(1, $review->helpful_count);
        $this->assertSame(0, $review->not_helpful_count);

        $this->assertDatabaseCount((new ReviewHelpfulVote())->getTable(), 1);
        $this->assertDatabaseHas((new ReviewHelpfulVote())->getTable(), [
            'review_id' => $review->getKey(),
            'is_helpful' => 1,
        ]);
    }

    public function test_review_report_endpoint_increments_report_count_and_marks_reported(): void
    {
        $review = Review::factory()->approved()->create([
            'report_count' => 0,
            'is_reported' => false,
        ]);

        $this->postJson(route('frontend.reviews.report', [
            'review' => $review->getKey(),
        ]), [
            'reason' => 'Spam',
        ])
            ->assertOk()
            ->assertJsonStructure([
                'message',
            ]);

        $review->refresh();
        $this->assertSame(1, $review->report_count);
        $this->assertTrue((bool) $review->is_reported);
    }
}


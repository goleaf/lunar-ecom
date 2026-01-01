<?php

namespace Tests\Feature\Frontend;

use App\Models\Product;
use App\Models\ProductAnswer;
use App\Models\ProductQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductQuestionActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_question_view_increments_views_count(): void
    {
        $product = Product::factory()->published()->create();
        $question = ProductQuestion::factory()->approved()->create([
            'product_id' => $product->getKey(),
            'views_count' => 0,
        ]);

        $this->postJson(route('frontend.products.questions.view', [
            'product' => $product->getKey(),
            'question' => $question->getKey(),
        ]))
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('views_count', 1);

        $question->refresh();
        $this->assertSame(1, $question->views_count);
    }

    public function test_product_question_helpful_increments_helpful_count(): void
    {
        $product = Product::factory()->published()->create();
        $question = ProductQuestion::factory()->approved()->create([
            'product_id' => $product->getKey(),
            'helpful_count' => 0,
        ]);

        $this->postJson(route('frontend.products.questions.helpful', [
            'product' => $product->getKey(),
            'question' => $question->getKey(),
        ]))
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('helpful_count', 1);

        $question->refresh();
        $this->assertSame(1, $question->helpful_count);
    }

    public function test_product_question_answer_returns_403_when_question_is_not_approved(): void
    {
        $product = Product::factory()->published()->create();
        $question = ProductQuestion::factory()->pending()->create([
            'product_id' => $product->getKey(),
            'is_public' => true,
        ]);

        $this->postJson(route('frontend.products.questions.answer', [
            'product' => $product->getKey(),
            'question' => $question->getKey(),
        ]), [
            'answer' => 'This is a valid answer with enough length.',
        ])
            ->assertForbidden()
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_product_question_answer_creates_pending_answer_for_approved_question(): void
    {
        $product = Product::factory()->published()->create();
        $question = ProductQuestion::factory()->approved()->create([
            'product_id' => $product->getKey(),
        ]);

        $this->postJson(route('frontend.products.questions.answer', [
            'product' => $product->getKey(),
            'question' => $question->getKey(),
        ]), [
            'answer' => 'This is a valid customer answer with enough length.',
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas((new ProductAnswer())->getTable(), [
            'question_id' => $question->getKey(),
            'status' => 'pending',
            'is_approved' => 0,
        ]);
    }
}


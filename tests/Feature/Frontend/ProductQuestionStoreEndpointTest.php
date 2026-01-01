<?php

namespace Tests\Feature\Frontend;

use App\Models\Product;
use App\Models\ProductQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductQuestionStoreEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_question_store_creates_pending_question(): void
    {
        $product = Product::factory()->published()->create();

        $questionText = 'How do I wash this jacket without damaging it?';

        $this->postJson(route('frontend.products.questions.store', [
            'product' => $product->getKey(),
        ]), [
            'question' => $questionText,
            'customer_name' => 'Guest User',
            'email' => 'guest@example.com',
            'is_public' => true,
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('question.product_id', $product->getKey())
            ->assertJsonPath('question.question', $questionText)
            ->assertJsonPath('question.status', 'pending');

        $this->assertDatabaseHas((new ProductQuestion())->getTable(), [
            'product_id' => $product->getKey(),
            'question' => $questionText,
            'status' => 'pending',
        ]);
    }

    public function test_product_question_store_returns_422_when_similar_question_found_and_not_forced(): void
    {
        $product = Product::factory()->published()->create();

        $existing = ProductQuestion::factory()->approved()->create([
            'product_id' => $product->getKey(),
            'question' => 'How do I wash this jacket without damaging it?',
        ]);

        $this->postJson(route('frontend.products.questions.store', [
            'product' => $product->getKey(),
        ]), [
            'question' => 'How do I wash this jacket without damaging it? (duplicate)',
            'customer_name' => 'Guest User',
            'email' => 'guest@example.com',
        ])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
            ])
            ->assertJsonStructure([
                'success',
                'similar_questions',
                'message',
            ])
            ->assertJsonPath('similar_questions.0.id', $existing->getKey());
    }

    public function test_product_question_store_allows_force_submit_even_when_similar_question_found(): void
    {
        $product = Product::factory()->published()->create();

        ProductQuestion::factory()->approved()->create([
            'product_id' => $product->getKey(),
            'question' => 'How do I wash this jacket without damaging it?',
        ]);

        $questionText = 'How do I wash this jacket without damaging it? (duplicate)';

        $this->postJson(route('frontend.products.questions.store', [
            'product' => $product->getKey(),
        ]), [
            'question' => $questionText,
            'customer_name' => 'Guest User',
            'email' => 'guest@example.com',
            'force_submit' => true,
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonPath('question.question', $questionText);
    }
}


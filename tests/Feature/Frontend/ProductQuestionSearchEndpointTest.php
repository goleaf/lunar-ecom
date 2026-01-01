<?php

namespace Tests\Feature\Frontend;

use App\Models\Product;
use App\Models\ProductQuestion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductQuestionSearchEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_question_search_only_returns_approved_public_questions(): void
    {
        $product = Product::factory()->published()->create();

        $approved = ProductQuestion::factory()->approved()->create([
            'product_id' => $product->getKey(),
            'question' => 'How do I wash this jacket?',
        ]);

        ProductQuestion::factory()->pending()->create([
            'product_id' => $product->getKey(),
            'question' => 'How do I wash this jacket? (pending)',
            'is_public' => true,
        ]);

        $this->getJson(route('frontend.products.questions.search', [
            'product' => $product->getKey(),
            'q' => 'wash',
        ]))
            ->assertOk()
            ->assertJsonCount(1, 'questions')
            ->assertJsonPath('questions.0.id', $approved->getKey());
    }
}


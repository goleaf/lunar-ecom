<?php

namespace Tests\Feature\Frontend;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCustomizationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_customizations_index_returns_success_payload(): void
    {
        $product = Product::factory()->published()->create();

        $this->getJson(route('frontend.products.customizations.index', [
            'product' => $product->getKey(),
        ]))
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'customizations',
                'examples',
                'templates',
            ]);
    }

    public function test_product_customizations_validate_accepts_empty_payload_and_returns_valid(): void
    {
        $product = Product::factory()->published()->create();

        $this->postJson(route('frontend.products.customizations.validate', [
            'product' => $product->getKey(),
        ]), [
            'customizations' => [
                // The endpoint requires at least one key; unknown keys are ignored by the service.
                'dummy' => 'value',
            ],
        ])
            ->assertOk()
            ->assertJson([
                'valid' => true,
                'errors' => [],
                'price' => 0,
            ])
            ->assertJsonStructure([
                'valid',
                'errors',
                'price',
                'data',
            ]);
    }

    public function test_product_customizations_templates_endpoint_returns_success_payload(): void
    {
        $product = Product::factory()->published()->create();

        $this->getJson(route('frontend.products.customizations.templates', [
            'product' => $product->getKey(),
        ]))
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'templates',
            ]);
    }

    public function test_product_customizations_examples_endpoint_returns_success_payload(): void
    {
        $product = Product::factory()->published()->create();

        $this->getJson(route('frontend.products.customizations.examples', [
            'product' => $product->getKey(),
        ]))
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'examples',
            ]);
    }
}


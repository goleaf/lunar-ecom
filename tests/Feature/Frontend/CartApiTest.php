<?php

namespace Tests\Feature\Frontend;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Facades\CartSession;
use Lunar\Models\Cart;
use Tests\TestCase;

class CartApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_summary_returns_empty_breakdown_when_no_cart(): void
    {
        $this->getJson(route('frontend.cart.summary'))
            ->assertOk()
            ->assertJsonPath('cart.item_count', 0)
            ->assertJsonPath('cart.has_items', false)
            ->assertJsonStructure([
                'cart' => [
                    'subtotal_pre_discount' => ['value', 'formatted', 'decimal'],
                    'total_discounts' => ['value', 'formatted', 'decimal'],
                    'tax_total' => ['value', 'formatted', 'decimal'],
                    'shipping_total' => ['value', 'formatted', 'decimal'],
                    'grand_total' => ['value', 'formatted', 'decimal'],
                ],
            ]);
    }

    public function test_cart_pricing_returns_404_when_no_cart(): void
    {
        $this->getJson(route('frontend.cart.pricing'))
            ->assertStatus(404)
            ->assertJson([
                'error' => 'No active cart found',
            ]);
    }

    public function test_cart_pricing_returns_pricing_payload_when_cart_exists(): void
    {
        $cart = Cart::factory()->create([
            'coupon_code' => null,
        ]);

        CartSession::use($cart);

        $this->getJson(route('frontend.cart.pricing'))
            ->assertOk()
            ->assertJsonStructure([
                'pricing' => [
                    'subtotal',
                    'total_discounts',
                    'tax_total',
                    'shipping_total',
                    'grand_total',
                    'audit_trail',
                    'line_items',
                ],
            ]);
    }
}


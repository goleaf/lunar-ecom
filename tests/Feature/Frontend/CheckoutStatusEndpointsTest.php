<?php

namespace Tests\Feature\Frontend;

use Tests\TestCase;

class CheckoutStatusEndpointsTest extends TestCase
{
    public function test_checkout_status_returns_no_cart_payload_when_no_cart_exists(): void
    {
        $this->getJson(route('frontend.checkout.status'))
            ->assertOk()
            ->assertJson([
                'locked' => false,
                'can_checkout' => false,
                'message' => 'No cart found',
            ]);
    }

    public function test_checkout_cancel_returns_404_when_no_cart_exists(): void
    {
        $this->postJson(route('frontend.checkout.cancel'))
            ->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'No cart found',
            ]);
    }
}


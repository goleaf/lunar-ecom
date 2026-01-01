<?php

namespace Tests\Feature\Frontend;

use App\Models\ComingSoonNotification;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComingSoonSubscribeEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_coming_soon_subscribe_returns_422_for_non_coming_soon_product(): void
    {
        $product = Product::factory()->published()->create([
            'is_coming_soon' => false,
            'expected_available_at' => null,
        ]);

        $this->postJson(route('frontend.coming-soon.subscribe', ['product' => $product->getKey()]), [
            'email' => 'test@example.com',
        ])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_coming_soon_subscribe_creates_notification_and_is_idempotent(): void
    {
        $product = Product::factory()->published()->create([
            'is_coming_soon' => true,
            'expected_available_at' => now()->addDays(7),
        ]);

        $this->postJson(route('frontend.coming-soon.subscribe', ['product' => $product->getKey()]), [
            'email' => 'test@example.com',
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas((new ComingSoonNotification())->getTable(), [
            'product_id' => $product->getKey(),
            'email' => 'test@example.com',
        ]);

        $this->assertDatabaseCount((new ComingSoonNotification())->getTable(), 1);

        // Second call should not create a duplicate.
        $this->postJson(route('frontend.coming-soon.subscribe', ['product' => $product->getKey()]), [
            'email' => 'test@example.com',
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseCount((new ComingSoonNotification())->getTable(), 1);
    }
}


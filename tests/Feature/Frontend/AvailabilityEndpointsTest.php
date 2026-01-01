<?php

namespace Tests\Feature\Frontend;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_availability_dates_endpoint_returns_dates_array(): void
    {
        $product = Product::factory()->published()->create();

        $this->getJson(route('frontend.products.availability.dates', [
            'product' => $product->getKey(),
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
        ]))
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'dates' => [
                    ['date', 'available', 'available_quantity', 'status', 'reason'],
                ],
            ]);
    }

    public function test_availability_check_endpoint_returns_availability_payload(): void
    {
        $product = Product::factory()->published()->create();

        $this->postJson(route('frontend.products.availability.check', [
            'product' => $product->getKey(),
        ]), [
            'date' => now()->toDateString(),
            'quantity' => 1,
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'availability' => ['available', 'reason', 'available_quantity'],
            ]);
    }
}


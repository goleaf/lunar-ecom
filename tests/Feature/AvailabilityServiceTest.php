<?php

namespace Tests\Feature;

use App\Models\AvailabilityBooking;
use App\Models\Product;
use App\Models\ProductAvailability;
use App\Models\ProductVariant;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_date_availability_returns_available_for_always_available_rule(): void
    {
        $this->seed();

        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        ProductAvailability::factory()
            ->forVariant($variant)
            ->create([
                'availability_type' => 'always_available',
                'is_active' => true,
                'priority' => 10,
            ]);

        $service = app(AvailabilityService::class);

        $result = $service->checkDateAvailability($product, now(), 1, $variant);

        $this->assertTrue($result['available']);
    }

    public function test_check_date_availability_respects_unavailable_dates_blackout(): void
    {
        $this->seed();

        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        $date = now()->startOfDay();

        ProductAvailability::factory()
            ->forVariant($variant)
            ->create([
                'availability_type' => 'always_available',
                'unavailable_dates' => [$date->toDateString()],
                'is_active' => true,
            ]);

        $service = app(AvailabilityService::class);

        $result = $service->checkDateAvailability($product, $date, 1, $variant);

        $this->assertFalse($result['available']);
    }

    public function test_check_date_availability_respects_max_quantity_per_date_limit(): void
    {
        $this->seed();

        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        $date = now()->startOfDay();

        ProductAvailability::factory()
            ->forVariant($variant)
            ->create([
                'availability_type' => 'always_available',
                'max_quantity_per_date' => 1,
                'is_active' => true,
            ]);

        AvailabilityBooking::factory()
            ->forVariant($variant)
            ->create([
                'start_date' => $date->toDateString(),
                'quantity' => 1,
                'status' => 'confirmed',
            ]);

        $service = app(AvailabilityService::class);

        $result = $service->checkDateAvailability($product, $date, 1, $variant);

        $this->assertFalse($result['available']);
    }
}


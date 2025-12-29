<?php

namespace Tests\Feature\Frontend;

use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Models\Currency;
use Lunar\Models\Price;
use Tests\TestCase;

class PricingApiTest extends TestCase
{
    use RefreshDatabase;

    protected function createVariantWithGuestPrice(): ProductVariant
    {
        $variant = ProductVariant::factory()->withoutPrices()->create();

        $currency = Currency::getDefault();

        Price::create([
            'price' => 1999,
            'compare_price' => null,
            'currency_id' => $currency->id,
            'customer_group_id' => null,
            'priceable_type' => ProductVariant::morphName(),
            'priceable_id' => $variant->id,
        ]);

        return $variant;
    }

    public function test_pricing_variant_endpoint_returns_success(): void
    {
        $variant = $this->createVariantWithGuestPrice();

        $this->getJson(route('frontend.pricing.variant', $variant))
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'pricing' => [
                    'price',
                    'base_price',
                    'matrix_id',
                    'savings',
                    'savings_percentage',
                    'tier',
                ],
            ]);
    }

    public function test_pricing_tiers_endpoint_returns_success(): void
    {
        $variant = $this->createVariantWithGuestPrice();

        $this->getJson(route('frontend.pricing.tiers', $variant))
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'tiers',
            ]);
    }

    public function test_pricing_volume_discounts_endpoint_returns_success(): void
    {
        $variant = $this->createVariantWithGuestPrice();

        $this->getJson(route('frontend.pricing.volume-discounts', $variant->product))
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'volume_discounts',
            ]);
    }
}


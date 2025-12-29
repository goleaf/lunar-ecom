<?php

namespace Tests\Feature;

use App\Models\PriceHistory;
use App\Models\ProductVariant;
use App\Services\AdvancedPricingService;
use App\Services\MatrixPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lunar\Models\Currency;
use Tests\TestCase;

class PriceHistoryTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_matrix_pricing_service_records_legacy_price_history_row(): void
    {
        $this->seed();

        $variant = ProductVariant::factory()->create();

        $service = app(MatrixPricingService::class);

        $history = $service->recordPriceChange(
            $variant,
            oldPrice: 10.00,
            newPrice: 12.50,
            context: ['region' => 'EU'],
            changeType: 'matrix',
            matrixId: null,
        );

        $this->assertDatabaseHas((new PriceHistory())->getTable(), [
            'id' => $history->id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'change_type' => 'matrix',
        ]);

        $history->refresh();
        $this->assertSame('12.50', (string) $history->new_price);
    }

    public function test_advanced_pricing_service_tracks_price_change_and_closes_previous_entry(): void
    {
        $this->seed();

        $currency = Currency::query()->firstOrFail();
        $variant = ProductVariant::factory()->create();

        /** @var AdvancedPricingService $service */
        $service = app(AdvancedPricingService::class);

        $first = $service->trackPriceChange($variant, [
            'currency' => $currency,
            'price' => 1000,
            'change_type' => 'manual',
            'change_reason' => 'manual',
        ]);

        $second = $service->trackPriceChange($variant, [
            'currency' => $currency,
            'price' => 1200,
            'change_type' => 'manual',
            'change_reason' => 'manual',
        ]);

        $first->refresh();
        $second->refresh();

        $this->assertNotNull($first->effective_to);
        $this->assertNull($second->effective_to);

        // Ensure required legacy columns are present as well (compat with older schema)
        $this->assertSame($variant->product_id, $second->product_id);
        $this->assertSame('12.00', (string) $second->new_price);
        $this->assertSame($currency->code, $second->currency_code);
    }
}


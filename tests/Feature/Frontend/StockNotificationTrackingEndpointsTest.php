<?php

namespace Tests\Feature\Frontend;

use App\Models\ProductVariant;
use App\Models\StockNotification;
use App\Models\StockNotificationMetric;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockNotificationTrackingEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_notification_check_endpoint_returns_false_when_not_subscribed(): void
    {
        $variant = ProductVariant::factory()->create();

        $this->getJson(route('frontend.stock-notifications.check', [
            'variant' => $variant->getKey(),
            'email' => 'test@example.com',
        ]))
            ->assertOk()
            ->assertJson([
                'subscribed' => false,
            ]);
    }

    public function test_stock_notification_check_endpoint_returns_true_when_pending_subscription_exists(): void
    {
        $variant = ProductVariant::factory()->create();

        StockNotification::create([
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->getKey(),
            'customer_id' => null,
            'email' => 'test@example.com',
            'status' => 'pending',
            'notify_on_backorder' => false,
            'min_quantity' => null,
            'token' => 'check-token',
        ]);

        $this->getJson(route('frontend.stock-notifications.check', [
            'variant' => $variant->getKey(),
            'email' => 'test@example.com',
        ]))
            ->assertOk()
            ->assertJson([
                'subscribed' => true,
            ]);
    }

    public function test_track_open_returns_gif_and_increments_open_count(): void
    {
        $variant = ProductVariant::factory()->create();

        $notification = StockNotification::create([
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->getKey(),
            'customer_id' => null,
            'email' => 'open@example.com',
            'status' => 'pending',
            'notify_on_backorder' => false,
            'min_quantity' => null,
            'token' => 'open-token',
        ]);

        $metric = StockNotificationMetric::create([
            'stock_notification_id' => $notification->getKey(),
            'product_variant_id' => $variant->getKey(),
            'email_opened' => false,
            'email_open_count' => 0,
            'link_clicked' => false,
            'link_click_count' => 0,
        ]);

        $this->get(route('frontend.stock-notifications.track-open', ['metricId' => $metric->getKey()]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/gif');

        $metric->refresh();
        $this->assertTrue($metric->email_opened);
        $this->assertSame(1, $metric->email_open_count);
    }

    public function test_track_click_redirects_home_when_metric_is_missing(): void
    {
        $this->get(route('frontend.stock-notifications.track-click', [
            'metricId' => 999999,
            'linkType' => 'product_page',
        ]))
            ->assertRedirect(url('/'));
    }

    public function test_track_click_unsubscribe_redirects_to_unsubscribe_page_and_increments_click_count(): void
    {
        $variant = ProductVariant::factory()->create();

        $notification = StockNotification::create([
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->getKey(),
            'customer_id' => null,
            'email' => 'click@example.com',
            'status' => 'pending',
            'notify_on_backorder' => false,
            'min_quantity' => null,
            'token' => 'unsubscribe-token',
        ]);

        $metric = StockNotificationMetric::create([
            'stock_notification_id' => $notification->getKey(),
            'product_variant_id' => $variant->getKey(),
            'link_clicked' => false,
            'link_click_count' => 0,
        ]);

        $this->get(route('frontend.stock-notifications.track-click', [
            'metricId' => $metric->getKey(),
            'linkType' => 'unsubscribe',
        ]))
            ->assertRedirect(url('/stock-notifications/unsubscribe/' . $notification->token));

        $metric->refresh();
        $this->assertTrue($metric->link_clicked);
        $this->assertSame(1, $metric->link_click_count);
        $this->assertSame('unsubscribe', $metric->clicked_link_type);
    }
}


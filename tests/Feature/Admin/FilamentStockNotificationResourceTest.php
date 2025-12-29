<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\StockNotificationResource;
use App\Filament\Resources\StockNotificationResource\Pages\ListStockNotifications;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Lunar\Admin\Models\Staff;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentStockNotificationResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_notification_index_and_edit_pages_render_for_staff(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $product = Product::factory()->published()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
        ]);

        $notification = StockNotification::create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'email' => 'buyer@example.com',
            'name' => 'Buyer',
            'status' => 'pending',
            'notify_on_backorder' => false,
            'min_quantity' => null,
            'token' => Str::random(32),
        ]);

        $slug = StockNotificationResource::getSlug();

        $this->get(route("filament.admin.resources.{$slug}.index"))->assertOk();

        $this->get(route("filament.admin.resources.{$slug}.edit", [
            'record' => $notification->getKey(),
        ]))->assertOk();
    }

    public function test_stock_notification_can_be_cancelled_via_table_action(): void
    {
        $this->seed();

        $staff = Staff::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($staff, 'staff');

        $product = Product::factory()->published()->create();

        $notification = StockNotification::create([
            'product_id' => $product->id,
            'email' => 'buyer@example.com',
            'name' => 'Buyer',
            'status' => 'pending',
            'notify_on_backorder' => false,
            'min_quantity' => null,
            'token' => Str::random(32),
        ]);

        Livewire::test(ListStockNotifications::class)
            ->callTableAction('cancel', $notification);

        $this->assertSame('cancelled', $notification->fresh()->status);
    }
}


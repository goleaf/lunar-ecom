<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductSchedule;
use App\Models\ProductScheduleHistory;
use App\Services\ProductSchedulingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSchedulingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_due_schedules_publishes_product_and_records_history(): void
    {
        $this->seed();

        $product = Product::factory()->draft()->create();

        $schedule = ProductSchedule::factory()
            ->due()
            ->create([
                'product_id' => $product->id,
                'type' => 'publish',
                'target_status' => Product::STATUS_ACTIVE,
                'schedule_type' => 'one_time',
            ]);

        $service = app(ProductSchedulingService::class);

        $executedCount = $service->executeDueSchedules();

        $this->assertSame(1, $executedCount);

        $this->assertDatabaseHas($product->getTable(), [
            'id' => $product->id,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseHas($schedule->getTable(), [
            'id' => $schedule->id,
        ]);

        $this->assertDatabaseHas((new ProductScheduleHistory())->getTable(), [
            'product_schedule_id' => $schedule->id,
            'product_id' => $product->id,
            'action' => 'publish',
        ]);
    }

    public function test_handle_expired_schedules_unpublishes_time_limited_and_marks_executed(): void
    {
        $this->seed();

        $product = Product::factory()->published()->create();

        $schedule = ProductSchedule::factory()
            ->expired()
            ->create([
                'product_id' => $product->id,
                'type' => 'time_limited',
                'target_status' => Product::STATUS_DRAFT,
            ]);

        $service = app(ProductSchedulingService::class);

        $handled = $service->handleExpiredSchedules();

        $this->assertSame(1, $handled);

        $this->assertDatabaseHas($product->getTable(), [
            'id' => $product->id,
            'status' => Product::STATUS_DRAFT,
        ]);

        $this->assertDatabaseHas($schedule->getTable(), [
            'id' => $schedule->id,
        ]);
    }
}


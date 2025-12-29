<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductSchedule>
 */
class ProductScheduleFactory extends Factory
{
    protected $model = ProductSchedule::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['publish', 'unpublish', 'flash_sale', 'seasonal', 'time_limited']);

        $scheduledAt = now()->addHours(fake()->numberBetween(1, 72));

        $data = [
            'product_id' => Product::factory(),
            'type' => $type,
            'schedule_type' => 'one_time',
            'scheduled_at' => $scheduledAt,
            'expires_at' => null,
            'target_status' => null,
            'is_active' => true,

            'sale_price' => null,
            'sale_percentage' => null,
            'restore_original_price' => true,

            'is_recurring' => false,
            'recurrence_pattern' => null,
            'recurrence_config' => null,

            'send_notification' => false,
            'notification_sent_at' => null,
            'notification_hours_before' => null,
            'notification_scheduled_at' => null,

            'executed_at' => null,
            'execution_success' => false,
            'execution_error' => null,

            'start_date' => null,
            'end_date' => null,
            'days_of_week' => null,
            'time_start' => null,
            'time_end' => null,
            'timezone' => 'UTC',
            'season_tag' => null,
            'auto_unpublish_after_season' => false,
            'is_coming_soon' => false,
            'coming_soon_message' => null,
            'bulk_schedule_id' => null,
            'applied_to' => null,
        ];

        if ($type === 'publish') {
            $data['target_status'] = Product::STATUS_ACTIVE;
        }

        if ($type === 'unpublish') {
            $data['target_status'] = Product::STATUS_DRAFT;
        }

        if ($type === 'flash_sale') {
            $data['sale_percentage'] = fake()->randomElement([10, 15, 20, 25, 30]);
            $data['expires_at'] = $scheduledAt->copy()->addHours(fake()->randomElement([6, 12, 24]));
        }

        if ($type === 'time_limited') {
            $data['expires_at'] = $scheduledAt->copy()->addDays(1);
        }

        return $data;
    }

    public function due(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_at' => now()->subMinute(),
            'expires_at' => null,
            'is_active' => true,
            'schedule_type' => 'one_time',
            'executed_at' => null,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_at' => now()->subDays(2),
            'expires_at' => now()->subDay(),
            'is_active' => true,
            'executed_at' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}


<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductSchedule;
use App\Models\ProductScheduleHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductScheduleHistory>
 */
class ProductScheduleHistoryFactory extends Factory
{
    protected $model = ProductScheduleHistory::class;

    public function definition(): array
    {
        return [
            'product_schedule_id' => ProductSchedule::factory(),
            'product_id' => Product::factory(),
            'action' => fake()->randomElement(['publish', 'unpublish', 'flash_sale', 'seasonal', 'time_limited']),
            'previous_status' => fake()->randomElement([Product::STATUS_DRAFT, Product::STATUS_ACTIVE, null]),
            'new_status' => fake()->randomElement([Product::STATUS_DRAFT, Product::STATUS_ACTIVE, null]),
            'metadata' => fake()->optional(0.4)->randomElement([
                ['note' => 'system'],
                ['timezone' => 'UTC'],
            ]),
            'executed_by' => null,
            'executed_at' => now()->subHours(fake()->numberBetween(1, 72)),
            'timezone' => 'UTC',
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }
}


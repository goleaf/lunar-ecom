<?php

namespace Database\Factories;

use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Lunar\Models\Order;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderStatusHistory>
 */
class OrderStatusHistoryFactory extends Factory
{
    protected $model = OrderStatusHistory::class;

    public function definition(): array
    {
        $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
        $previousStatus = fake()->optional(0.7)->randomElement($statuses);
        $currentStatus = fake()->randomElement($statuses);

        return [
            'order_id' => Order::factory(),
            'status' => $currentStatus,
            'previous_status' => $previousStatus,
            'notes' => fake()->optional(0.5)->sentence(),
            'changed_by' => fake()->optional(0.6)->randomElement([User::factory()->create()->id, null]),
            'meta' => fake()->optional(0.3)->randomElements([
                ['reason' => 'Customer request'],
                ['shipping_method' => 'express'],
                ['tracking_number' => fake()->bothify('TRACK-####-???')],
            ], fake()->numberBetween(1, 2)),
        ];
    }

    public function forOrder(Order $order): static
    {
        return $this->state(fn (array $attributes) => [
            'order_id' => $order->id,
        ]);
    }

    public function withStatus(string $status, ?string $previousStatus = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
            'previous_status' => $previousStatus,
        ]);
    }

    public function changedByUser(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'changed_by' => $user ? $user->id : User::factory()->create()->id,
        ]);
    }

    public function withNotes(string $notes): static
    {
        return $this->state(fn (array $attributes) => [
            'notes' => $notes,
        ]);
    }
}


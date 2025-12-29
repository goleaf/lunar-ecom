<?php

namespace Database\Factories;

use App\Models\AvailabilityBooking;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AvailabilityBooking>
 */
class AvailabilityBookingFactory extends Factory
{
    protected $model = AvailabilityBooking::class;

    public function definition(): array
    {
        $start = now()->addDays(fake()->numberBetween(0, 20))->startOfDay();

        return [
            'product_id' => Product::factory(),
            'product_variant_id' => null,
            'order_id' => null,
            'order_line_id' => null,
            'customer_id' => null,
            'start_date' => $start->toDateString(),
            'end_date' => null,
            'start_time' => null,
            'end_time' => null,
            'quantity' => fake()->numberBetween(1, 3),
            'status' => fake()->randomElement(['pending', 'confirmed', 'cancelled', 'completed', 'no_show']),
            'total_price' => fake()->optional(0.6)->randomFloat(2, 10, 250),
            'currency_code' => fake()->randomElement(['EUR', 'USD']),
            'duration_days' => null,
            'pricing_type' => fake()->randomElement(['daily', 'weekly', 'monthly', 'fixed']),
            'customer_name' => fake()->optional(0.5)->name(),
            'customer_email' => fake()->optional(0.5)->safeEmail(),
            'customer_phone' => fake()->optional(0.3)->phoneNumber(),
            'notes' => null,
            'admin_notes' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'timezone' => 'UTC',
        ];
    }

    public function forVariant(ProductVariant $variant): static
    {
        return $this->state(fn () => [
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['status' => 'confirmed']);
    }
}


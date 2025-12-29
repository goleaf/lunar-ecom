<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductAvailability;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductAvailability>
 */
class ProductAvailabilityFactory extends Factory
{
    protected $model = ProductAvailability::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'product_variant_id' => null,
            'availability_type' => 'always_available',
            'start_date' => null,
            'end_date' => null,
            'available_dates' => null,
            'unavailable_dates' => null,
            'is_recurring' => false,
            'recurrence_pattern' => null,
            'max_quantity_per_date' => null,
            'total_quantity' => null,
            'available_from' => null,
            'available_until' => null,
            'slot_duration_minutes' => null,
            'is_active' => true,
            'timezone' => 'UTC',
            'priority' => 0,
        ];
    }

    public function forVariant(ProductVariant $variant): static
    {
        return $this->state(fn () => [
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
        ]);
    }

    public function specificDates(array $dates): static
    {
        return $this->state(fn () => [
            'availability_type' => 'specific_dates',
            'available_dates' => $dates,
        ]);
    }

    public function dateRange(string $start, ?string $end = null): static
    {
        return $this->state(fn () => [
            'availability_type' => 'date_range',
            'start_date' => $start,
            'end_date' => $end,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}


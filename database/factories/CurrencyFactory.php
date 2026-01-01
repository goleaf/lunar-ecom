<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Lunar\Models\Currency;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Lunar\Models\Currency>
 */
class CurrencyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Currency::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        do {
            $code = strtoupper(fake()->unique()->lexify('???'));
        } while (Currency::where('code', $code)->exists());

        return [
            'code' => $code,
            'name' => $code . ' Currency',
            'exchange_rate' => fake()->randomFloat(4, 0.5, 1.5),
            'decimal_places' => 2,
            'enabled' => true,
            'default' => false,
        ];
    }

    /**
     * Add formatting fields when the schema supports them.
     */
    public function withFormatting(
        string $format = '{symbol}{value}',
        string $decimalPoint = '.',
        string $thousandPoint = ','
    ): static {
        return $this->state(fn () => [
            'format' => $format,
            'decimal_point' => $decimalPoint,
            'thousand_point' => $thousandPoint,
        ]);
    }

    /**
     * Mark the currency as default.
     */
    public function defaultCurrency(): static
    {
        return $this->state(fn () => [
            'default' => true,
        ]);
    }
}

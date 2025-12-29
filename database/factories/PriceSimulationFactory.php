<?php

namespace Database\Factories;

use App\Models\PriceSimulation;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Lunar\Models\Currency;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PriceSimulation>
 */
class PriceSimulationFactory extends Factory
{
    protected $model = PriceSimulation::class;

    public function definition(): array
    {
        $base = fake()->numberBetween(200, 5000);
        $final = max(0, $base - fake()->numberBetween(0, 500));

        return [
            'product_variant_id' => ProductVariant::factory(),
            'currency_id' => function () {
                $currencyId = Currency::query()->where('default', true)->value('id')
                    ?? Currency::query()->value('id');

                if ($currencyId) {
                    return $currencyId;
                }

                return Currency::query()->create([
                    'code' => 'EUR',
                    'name' => 'Euro',
                    'exchange_rate' => 1,
                    'decimal_places' => 2,
                    'enabled' => true,
                    'default' => true,
                ])->id;
            },
            'quantity' => fake()->numberBetween(1, 10),
            'channel_id' => null,
            'customer_group_id' => null,
            'customer_id' => null,
            'base_price' => $base,
            'final_price' => $final,
            'applied_rules' => [],
            'pricing_breakdown' => [
                'base_price' => $base,
                'final_price' => $final,
            ],
            'simulation_context' => null,
        ];
    }
}


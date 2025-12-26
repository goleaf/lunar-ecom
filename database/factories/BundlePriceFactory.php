<?php

namespace Database\Factories;

use App\Models\Bundle;
use App\Models\BundlePrice;
use Illuminate\Database\Eloquent\Factories\Factory;
use Lunar\Models\Currency;
use Lunar\Models\CustomerGroup;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BundlePrice>
 */
class BundlePriceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = BundlePrice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $currencyId = Currency::query()->where('default', true)->value('id')
            ?? CurrencyFactory::new()->defaultCurrency()->create([
                'code' => 'USD',
                'name' => 'US Dollar',
                'exchange_rate' => 1.0,
                'decimal_places' => 2,
                'enabled' => true,
            ])->id;

        $customerGroupId = CustomerGroup::query()->where('default', true)->value('id');

        return [
            'bundle_id' => Bundle::factory(),
            'currency_id' => $currencyId,
            'customer_group_id' => $customerGroupId,
            'price' => fake()->numberBetween(2000, 20000),
            'compare_at_price' => fake()->optional(0.3)->numberBetween(2100, 25000),
            'min_quantity' => 1,
            'max_quantity' => fake()->optional(0.3)->numberBetween(2, 10),
        ];
    }
}

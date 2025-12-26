<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Lunar\Models\Currency;
use Lunar\Models\CustomerGroup;
use Lunar\Models\Price;
use App\Models\ProductVariant;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Lunar\Models\Price>
 */
class PriceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Price::class;

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

        $customerGroupId = CustomerGroup::query()->where('default', true)->value('id')
            ?? CustomerGroupFactory::new()->defaultGroup()->create([
                'name' => 'Default',
                'handle' => 'default',
            ])->id;

        return [
            'price' => fake()->numberBetween(1000, 100000),
            'compare_price' => fake()->optional(0.3)->numberBetween(1100, 120000),
            'currency_id' => $currencyId,
            'customer_group_id' => $customerGroupId,
            'priceable_type' => ProductVariant::class,
            'priceable_id' => ProductVariant::factory(),
            // Lunar renamed `tier` -> `min_quantity` (2024_01_31_100000_update_tier_to_min_quantity_on_prices_table).
            'min_quantity' => 1,
        ];
    }

    /**
     * Attach the price to a variant.
     */
    public function forVariant(ProductVariant $variant): static
    {
        return $this->state(fn () => [
            'priceable_type' => ProductVariant::class,
            'priceable_id' => $variant->id,
        ]);
    }
}

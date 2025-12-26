<?php

namespace Database\Factories;

use App\Models\Bundle;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Lunar\Models\Currency;
use Lunar\Models\CustomerGroup;
use Lunar\Models\TaxClass;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bundle>
 */
class BundleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Bundle::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->words(3, true));
        $pricingType = fake()->randomElement(['fixed', 'percentage', 'dynamic']);
        $isDynamic = $pricingType === 'dynamic';

        $bundlePrice = $pricingType === 'fixed'
            ? fake()->numberBetween(5000, 25000)
            : null;

        $discountAmount = $pricingType === 'percentage'
            ? fake()->numberBetween(5, 25)
            : ($pricingType === 'fixed' ? fake()->numberBetween(500, 2500) : null);

        return [
            'product_id' => Product::factory()->published()->bundle(),
            'name' => $name,
            'description' => fake()->optional()->paragraph(),
            'slug' => Str::slug($name) . '-' . fake()->unique()->numerify('###'),
            'sku' => 'BND-' . fake()->unique()->numerify('#####'),
            'pricing_type' => $pricingType,
            'discount_amount' => $discountAmount,
            'bundle_price' => $bundlePrice,
            'inventory_type' => fake()->randomElement(['component', 'independent', 'unlimited']),
            'stock' => fake()->numberBetween(0, 150),
            'min_quantity' => 1,
            'max_quantity' => fake()->optional(0.4)->numberBetween(2, 6),
            'is_active' => true,
            'is_featured' => fake()->boolean(25),
            'display_order' => fake()->numberBetween(0, 20),
            'image' => null,
            'allow_customization' => $isDynamic || fake()->boolean(30),
            'show_individual_prices' => true,
            'show_savings' => true,
            'meta_title' => $name,
            'meta_description' => fake()->sentence(),
        ];
    }

    /**
     * Indicate that the bundle uses fixed pricing.
     */
    public function fixedPricing(): static
    {
        return $this->state(fn () => [
            'pricing_type' => 'fixed',
            'bundle_price' => fake()->numberBetween(5000, 25000),
            'discount_amount' => null,
        ]);
    }

    /**
     * Indicate that the bundle uses percentage pricing.
     */
    public function percentagePricing(int $percent = 15): static
    {
        return $this->state(fn () => [
            'pricing_type' => 'percentage',
            'bundle_price' => null,
            'discount_amount' => $percent,
        ]);
    }

    /**
     * Indicate that the bundle is dynamic.
     */
    public function dynamicPricing(): static
    {
        return $this->state(fn () => [
            'pricing_type' => 'dynamic',
            'bundle_price' => null,
            'discount_amount' => null,
            'allow_customization' => true,
        ]);
    }

    /**
     * Ensure bundle products have a variant and base price.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Bundle $bundle) {
            $product = $bundle->product;
            if (!$product) {
                return;
            }

            if (!$product->is_bundle) {
                $product->forceFill(['is_bundle' => true])->save();
            }

            $variant = $product->variants()->first();
            if (!$variant) {
                $taxClassId = TaxClass::query()->where('default', true)->value('id')
                    ?? TaxClassFactory::new()->defaultClass()->create(['name' => 'Standard Tax'])->id;

                $variant = ProductVariant::factory()->create([
                    'product_id' => $product->id,
                    'tax_class_id' => $taxClassId,
                ]);
            }

            $currencyId = Currency::query()->where('default', true)->value('id');
            $customerGroupId = CustomerGroup::query()->where('default', true)->value('id');

            if ($currencyId && $customerGroupId) {
                $existing = $variant->prices()
                    ->where('currency_id', $currencyId)
                    ->where('customer_group_id', $customerGroupId)
                    ->exists();

                if (!$existing) {
                    PriceFactory::new()
                        ->forVariant($variant)
                        ->create([
                            'currency_id' => $currencyId,
                            'customer_group_id' => $customerGroupId,
                        ]);
                }
            }
        });
    }
}

<?php

namespace Database\Seeders;

use App\Models\Bundle;
use App\Models\Product;
use App\Models\ProductVariant;
use Database\Factories\BundleAnalyticFactory;
use Database\Factories\BundleFactory;
use Database\Factories\BundleItemFactory;
use Database\Factories\BundlePriceFactory;
use Database\Factories\CurrencyFactory;
use Database\Factories\CustomerGroupFactory;
use Database\Factories\PriceFactory;
use Database\Factories\ProductFactory;
use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Lunar\Models\Currency;
use Lunar\Models\CustomerGroup;

class BundleSeeder extends Seeder
{
    public int $minBundles = 6;
    public int $minBaseProducts = 12;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command?->info('Seeding bundles...');

        $existingCount = Bundle::query()->count();
        if ($existingCount >= $this->minBundles) {
            $this->command?->info("Bundles already exist ({$existingCount}).");
            return;
        }

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

        $baseProducts = $this->ensureBaseProducts($currencyId, $customerGroupId);
        if ($baseProducts->isEmpty()) {
            $this->command?->warn('No base products found. Skipping bundle creation.');
            return;
        }

        $toCreate = $this->minBundles - $existingCount;
        $pricingModes = ['fixed', 'percentage', 'dynamic'];

        $created = 0;
        for ($i = 0; $i < $toCreate; $i++) {
            $pricingType = $pricingModes[$i % count($pricingModes)];
            $bundleName = Str::title(fake()->words(3, true));

            $bundle = BundleFactory::new()
                ->state([
                    'name' => $bundleName,
                    'slug' => Str::slug($bundleName) . '-bundle-' . ($i + 1),
                    'pricing_type' => $pricingType,
                    'is_featured' => $i < 2,
                    'allow_customization' => $pricingType === 'dynamic',
                ])
                ->create();

            $items = $baseProducts->random(min($baseProducts->count(), fake()->numberBetween(3, 6)));
            $requiredCount = $pricingType === 'dynamic' ? max(1, (int) floor($items->count() / 2)) : $items->count();

            foreach ($items->values() as $index => $product) {
                $variant = $this->ensureVariantAndPrice($product, $currencyId, $customerGroupId);

                $isRequired = $index < $requiredCount;
                $quantity = fake()->numberBetween(1, 2);

                BundleItemFactory::new()->create([
                    'bundle_id' => $bundle->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'quantity' => $quantity,
                    'min_quantity' => $isRequired ? 1 : 0,
                    'max_quantity' => $isRequired ? null : 3,
                    'is_required' => $isRequired,
                    'is_default' => $isRequired,
                    'display_order' => $index,
                    'price_override' => null,
                    'discount_amount' => null,
                ]);
            }

            if ($bundle->pricing_type === 'fixed') {
                BundlePriceFactory::new()->create([
                    'bundle_id' => $bundle->id,
                    'currency_id' => $currencyId,
                    'customer_group_id' => $customerGroupId,
                    'price' => $bundle->bundle_price ?? fake()->numberBetween(5000, 20000),
                    'min_quantity' => 1,
                    'max_quantity' => null,
                ]);
            }

            BundleAnalyticFactory::new()
                ->count(fake()->numberBetween(3, 8))
                ->create([
                    'bundle_id' => $bundle->id,
                ]);

            $created++;
        }

        $this->command?->info("Created {$created} bundles.");
    }

    /**
     * Ensure a baseline set of products for bundling.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\Product>
     */
    protected function ensureBaseProducts(int $currencyId, int $customerGroupId)
    {
        $products = Product::query()
            ->where('is_bundle', false)
            ->where('status', 'published')
            ->get();

        if ($products->count() < $this->minBaseProducts) {
            $toCreate = $this->minBaseProducts - $products->count();
            $newProducts = ProductFactory::new()
                ->count($toCreate)
                ->published()
                ->create();
            $products = $products->merge($newProducts);
        }

        foreach ($products as $product) {
            $this->ensureVariantAndPrice($product, $currencyId, $customerGroupId);
        }

        return $products->values();
    }

    protected function ensureVariantAndPrice(Product $product, int $currencyId, int $customerGroupId): ?ProductVariant
    {
        $variant = $product->variants()->first();

        if (!$variant) {
            $variant = ProductVariantFactory::new()->create([
                'product_id' => $product->id,
            ]);
        }

        $hasPrice = $variant->prices()
            ->where('currency_id', $currencyId)
            ->where('customer_group_id', $customerGroupId)
            ->exists();

        if (!$hasPrice) {
            PriceFactory::new()->forVariant($variant)->create([
                'currency_id' => $currencyId,
                'customer_group_id' => $customerGroupId,
            ]);
        }

        return $variant;
    }
}

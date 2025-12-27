<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Database\Factories\PriceFactory;
use Database\Factories\ProductVariantFactory;
use Lunar\Models\Currency;
use Lunar\Models\CustomerGroup;
use Lunar\Models\Price;
use Lunar\Models\TaxClass;

/**
 * Ensures every existing product has at least one variant (and a base price).
 *
 * Intended for:
 * - products imported via admin/API without variants
 * - older databases where variants were not enforced
 *
 * Safe to run multiple times (idempotent for products that already have variants).
 */
class ExistingProductVariantsSeeder extends Seeder
{
    /**
     * Minimum variants each product should have after this seeder.
     * (We only *add* variants up to this minimum; we never delete.)
     */
    public int $minVariantsPerProduct = 1;

    /**
     * Maximum variants to create for products that currently have 0 variants.
     * (Only used on first fill; reruns will not add more once >= minVariantsPerProduct.)
     */
    public int $initialMaxVariantsWhenEmpty = 3;

    public function run(): void
    {
        $this->command?->info('Ensuring every product has variants...');

        // Dependencies used for new variants and base pricing.
        CustomerGroupSeeder::seed();

        $currency = Currency::where('default', true)->first()
            ?? Currency::firstOrCreate(['code' => 'USD'], [
                'name' => 'US Dollar',
                'exchange_rate' => 1.00,
                'decimal_places' => 2,
                'default' => true,
                'enabled' => true,
            ]);

        $customerGroup = CustomerGroup::where('default', true)->first()
            ?? CustomerGroup::where('handle', CustomerGroupSeeder::DEFAULT_HANDLE)->first();

        $taxClass = TaxClass::where('default', true)->first()
            ?? TaxClass::firstOrCreate(['name' => 'Standard Tax'], [
                'name' => 'Standard Tax',
                'default' => true,
            ]);

        $createdVariants = 0;
        $ensuredPrices = 0;

        Product::query()
            ->select(['id', 'sku'])
            ->orderBy('id')
            ->chunkById(200, function ($products) use ($taxClass, $currency, $customerGroup, &$createdVariants, &$ensuredPrices) {
                /** @var \App\Models\Product $product */
                foreach ($products as $product) {
                    $existingCount = ProductVariant::where('product_id', $product->id)->count();

                    if ($existingCount >= $this->minVariantsPerProduct) {
                        // Still ensure base prices exist for all variants.
                        $ensuredPrices += $this->ensureVariantBasePrices($product->id, $currency?->id, $customerGroup?->id);
                        continue;
                    }

                    $target = $existingCount === 0
                        ? random_int($this->minVariantsPerProduct, $this->initialMaxVariantsWhenEmpty)
                        : $this->minVariantsPerProduct;

                    $toCreate = max(0, $target - $existingCount);

                    if ($toCreate === 0) {
                        $ensuredPrices += $this->ensureVariantBasePrices($product->id, $currency?->id, $customerGroup?->id);
                        continue;
                    }

                    $baseSku = $this->baseSkuForProduct($product);

                    for ($i = 1; $i <= $toCreate; $i++) {
                        $sku = $this->uniqueVariantSku($baseSku, $product->id, $existingCount + $i);

                        // Use the factory but skip price creation to avoid cache side-effects.
                        $variant = ProductVariantFactory::new()
                            ->withoutPrices()
                            ->create([
                                'product_id' => $product->id,
                                'tax_class_id' => $taxClass?->id,
                                'sku' => $sku,
                                'title' => 'Default',
                                'variant_name' => null,
                                'attribute_data' => null,
                                // Ensure the variant is eligible for "shared/cross variants" logic.
                                // In this project, cross-variant generation filters by `status = active`.
                                'status' => 'active',
                                'visibility' => 'public',
                                'enabled' => true,
                                'stock' => random_int(0, 1000),
                                'backorder' => 0,
                                'purchasable' => 'always',
                                'shippable' => true,
                                'unit_quantity' => 1,
                            ]);

                        $createdVariants++;

                        // Ensure a base price exists for the created variant.
                        if ($currency?->id && $customerGroup?->id) {
                            $ensuredPrices += $this->ensureSingleVariantBasePrice($variant->id, $currency->id, $customerGroup->id);
                        }
                    }

                    // Also ensure prices for any pre-existing variants on this product.
                    $ensuredPrices += $this->ensureVariantBasePrices($product->id, $currency?->id, $customerGroup?->id);
                }
            });

        $this->command?->info("✅ Created {$createdVariants} variants.");
        $this->command?->info("✅ Ensured {$ensuredPrices} base prices (default currency + default customer group).");
    }

    protected function baseSkuForProduct(Product $product): string
    {
        $raw = $product->sku ?: "PROD-{$product->id}";
        $raw = strtoupper($raw);
        $raw = preg_replace('/[^A-Z0-9\\-]/', '-', $raw) ?: "PROD-{$product->id}";
        $raw = trim($raw, '-');

        return Str::limit($raw, 40, '');
    }

    protected function uniqueVariantSku(string $baseSku, int $productId, int $index): string
    {
        // Keep SKU stable and unique across products.
        // Example: PROD-123-V1
        $candidate = "{$baseSku}-V{$index}";
        $candidate = Str::limit($candidate, 60, '');

        // If collision, append product id suffix.
        if (ProductVariant::where('sku', $candidate)->exists()) {
            $candidate = Str::limit("{$baseSku}-P{$productId}-V{$index}", 60, '');
        }

        // Final fallback: add a short random suffix (only if needed).
        if (ProductVariant::where('sku', $candidate)->exists()) {
            $candidate = Str::limit("{$baseSku}-P{$productId}-V{$index}-" . strtoupper(Str::random(4)), 60, '');
        }

        return $candidate;
    }

    protected function ensureVariantBasePrices(int $productId, ?int $currencyId, ?int $customerGroupId): int
    {
        if (!$currencyId || !$customerGroupId) {
            return 0;
        }

        $count = 0;

        $variantIds = ProductVariant::where('product_id', $productId)->pluck('id');

        foreach ($variantIds as $variantId) {
            $count += $this->ensureSingleVariantBasePrice($variantId, $currencyId, $customerGroupId);
        }

        return $count;
    }

    protected function ensureSingleVariantBasePrice(int $variantId, int $currencyId, int $customerGroupId): int
    {
        $exists = Price::query()
            ->where('currency_id', $currencyId)
            ->where('customer_group_id', $customerGroupId)
            ->where('priceable_type', ProductVariant::morphName())
            ->where('priceable_id', $variantId)
            ->exists();

        if ($exists) {
            return 0;
        }

        // A reasonable default base price (in cents) for dev/demo.
        // Avoid triggering app-level cache invalidation that may rely on Redis.
        Price::withoutEvents(function () use ($variantId, $currencyId, $customerGroupId) {
            $variant = ProductVariant::find($variantId);
            if (!$variant) {
                return;
            }

            PriceFactory::new()
                ->forVariant($variant)
                ->create([
                    'price' => random_int(1000, 100000),
                    'compare_price' => random_int(0, 1) ? random_int(1100, 120000) : null,
                    'currency_id' => $currencyId,
                    'customer_group_id' => $customerGroupId,
                ]);
        });

        return 1;
    }
}


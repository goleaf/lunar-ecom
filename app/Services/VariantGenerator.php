<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Lunar\Models\ProductOption;
use Lunar\Models\ProductOptionValue;
use Lunar\Models\Price;
use Lunar\Models\Currency;
use Illuminate\Support\Facades\DB;

/**
 * Service for automatically generating product variants from option combinations.
 * 
 * This service generates all possible variant combinations from selected
 * product options (size, color, material, style, pattern, etc.).
 */
class VariantGenerator
{
    /**
     * Generate all possible variant combinations for a product.
     *
     * Supports multiple attributes: size, color, material, style, etc.
     * 
     * @param  Product  $product
     * @param  array  $options  Array of option IDs to use for generation (empty = use all product options)
     * @param  array  $defaults  Default values for new variants:
     *                           - stock: int
     *                           - backorder: int
     *                           - purchasable: 'always'|'in_stock'|'never'
     *                           - shippable: bool
     *                           - enabled: bool
     *                           - weight: int (grams)
     *                           - price: int (smallest currency unit)
     *                           - currency_id: int
     *                           - compare_price: int
     *                           - sku_prefix: string
     * @return Collection Collection of created ProductVariant instances
     */
    public function generateVariants(
        Product $product,
        array $options = [],
        array $defaults = []
    ): Collection {
        // Get product options if not provided
        if (empty($options)) {
            $options = $product->productOptions()->pluck('id')->toArray();
        }

        if (empty($options)) {
            throw new \InvalidArgumentException('No product options provided for variant generation.');
        }

        // Get all option values grouped by option
        $optionValues = ProductOptionValue::whereHas('option', function ($query) use ($options) {
            $query->whereIn('id', $options);
        })->with('option')->get()->groupBy('option_id');

        if ($optionValues->isEmpty()) {
            throw new \InvalidArgumentException('No option values found for the provided options.');
        }

        // Generate all combinations (cartesian product)
        $combinations = $this->generateCombinations($optionValues->values()->toArray());

        $createdVariants = collect();

        DB::transaction(function () use ($product, $combinations, $defaults, &$createdVariants) {
            foreach ($combinations as $combination) {
                $optionValueIds = collect($combination)->pluck('id')->toArray();

                // Check if variant with this combination already exists
                $existing = $product->variants()
                    ->whereHas('variantOptions', function ($query) use ($optionValueIds) {
                        $query->whereIn('product_option_values.id', $optionValueIds);
                    })
                    ->withCount(['variantOptions' => function ($query) use ($optionValueIds) {
                        $query->whereIn('product_option_values.id', $optionValueIds);
                    }])
                    ->having('variant_options_count', '=', count($optionValueIds))
                    ->first();

                if ($existing) {
                    continue; // Skip if variant already exists
                }

                // Generate SKU
                $sku = $this->generateSku($product, $combination, $defaults['sku_prefix'] ?? null);

                // Create variant
                $variant = ProductVariant::create(array_merge([
                    'product_id' => $product->id,
                    'sku' => $sku,
                    'stock' => $defaults['stock'] ?? 0,
                    'backorder' => $defaults['backorder'] ?? 0,
                    'purchasable' => $defaults['purchasable'] ?? 'always',
                    'shippable' => $defaults['shippable'] ?? true,
                    'enabled' => $defaults['enabled'] ?? true,
                    'weight' => $defaults['weight'] ?? null,
                    'barcode' => $defaults['barcode'] ?? null,
                    'price_override' => $defaults['price_override'] ?? null,
                    'cost_price' => $defaults['cost_price'] ?? null,
                    'compare_at_price' => $defaults['compare_at_price'] ?? null,
                ], $defaults));

                // Attach option values
                $variant->variantOptions()->attach($optionValueIds);

                // Set pricing if provided
                if (isset($defaults['price']) && isset($defaults['currency_id'])) {
                    $this->setVariantPrice($variant, $defaults['price'], $defaults['currency_id'], $defaults['compare_price'] ?? null);
                }

                $createdVariants->push($variant->fresh(['variantOptions']));
            }
        });

        return $createdVariants;
    }

    /**
     * Generate all combinations from option values.
     *
     * @param  array  $optionValueGroups  Array of option value arrays
     * @return array
     */
    protected function generateCombinations(array $optionValueGroups): array
    {
        if (empty($optionValueGroups)) {
            return [];
        }

        // Cartesian product
        $result = [[]];

        foreach ($optionValueGroups as $group) {
            $newResult = [];
            foreach ($result as $existing) {
                foreach ($group as $value) {
                    $newResult[] = array_merge($existing, [$value]);
                }
            }
            $result = $newResult;
        }

        return $result;
    }

    /**
     * Generate variants with dependency validation.
     *
     * @param  Product  $product
     * @param  array  $options
     * @param  array  $defaults
     * @return Collection
     */
    public function generateVariantsWithDependencies(
        Product $product,
        array $options = [],
        array $defaults = []
    ): Collection {
        $combinationService = app(VariantAttributeCombinationService::class);
        $dependencyService = app(VariantDependencyService::class);

        // Get all combinations
        $allCombinations = $combinationService->generateAllCombinations($product);

        // Filter by dependencies
        $validCombinations = [];
        foreach ($allCombinations as $combination) {
            $validation = $combinationService->validateCombination($product, $combination);
            if ($validation['valid']) {
                $validCombinations[] = $combination;
            }
        }

        // Generate variants for valid combinations
        $createdVariants = collect();

        foreach ($validCombinations as $combination) {
            try {
                $variant = $combinationService->createVariantFromCombination($product, $combination, [
                    'status' => $defaults['status'] ?? 'active',
                    'variant_data' => $defaults,
                ]);
                $createdVariants->push($variant);
            } catch (\Exception $e) {
                \Log::warning("Failed to create variant", [
                    'product_id' => $product->id,
                    'combination' => $combination,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $createdVariants;
    }

    /**
     * Generate SKU for a variant.
     *
     * @param  Product  $product
     * @param  array  $optionValues  Array of ProductOptionValue instances
     * @param  string|null  $prefix
     * @return string
     */
    protected function generateSku(Product $product, array $optionValues, ?string $prefix = null): string
    {
        $baseSku = $prefix ?? $product->sku ?? 'PROD-' . $product->id;

        // Get option value identifiers (handles or first 3 chars of name)
        $identifiers = collect($optionValues)->map(function ($value) {
            return $value->handle ?? Str::upper(Str::substr($value->name, 0, 3));
        })->join('-');

        return $baseSku . '-' . $identifiers;
    }

    /**
     * Set price for a variant.
     *
     * @param  ProductVariant  $variant
     * @param  int  $price  Price in smallest currency unit
     * @param  int  $currencyId
     * @param  int|null  $comparePrice
     * @return void
     */
    protected function setVariantPrice(ProductVariant $variant, int $price, int $currencyId, ?int $comparePrice = null): void
    {
        Price::updateOrCreate(
            [
                'priceable_type' => ProductVariant::morphName(),
                'priceable_id' => $variant->id,
                'currency_id' => $currencyId,
                'min_quantity' => 1,
            ],
            [
                'price' => $price,
                'compare_price' => $comparePrice,
            ]
        );
    }

    /**
     * Bulk update variants with the same values.
     *
     * @param  Collection  $variants
     * @param  array  $attributes
     * @return int Number of updated variants
     */
    public function bulkUpdateVariants(Collection $variants, array $attributes): int
    {
        $allowedAttributes = [
            'stock',
            'backorder',
            'purchasable',
            'shippable',
            'enabled',
            'weight',
            'price_override',
            'cost_price',
            'compare_at_price',
        ];

        $updateData = array_intersect_key($attributes, array_flip($allowedAttributes));

        if (empty($updateData)) {
            return 0;
        }

        return ProductVariant::whereIn('id', $variants->pluck('id'))
            ->update($updateData);
    }

    /**
     * Delete variants by option combination.
     *
     * @param  Product  $product
     * @param  array  $optionValueIds
     * @return int Number of deleted variants
     */
    public function deleteVariantsByCombination(Product $product, array $optionValueIds): int
    {
        $variants = $product->variants()
            ->whereHas('variantOptions', function ($query) use ($optionValueIds) {
                $query->whereIn('product_option_values.id', $optionValueIds);
            })
            ->withCount(['variantOptions' => function ($query) use ($optionValueIds) {
                $query->whereIn('product_option_values.id', $optionValueIds);
            }])
            ->having('variant_options_count', '=', count($optionValueIds))
            ->get();

        $count = $variants->count();
        $variants->each->delete();

        return $count;
    }

    /**
     * Get variant by option combination.
     *
     * @param  Product  $product
     * @param  array  $optionValueIds
     * @return ProductVariant|null
     */
    public function getVariantByCombination(Product $product, array $optionValueIds): ?ProductVariant
    {
        return $product->variants()
            ->whereHas('variantOptions', function ($query) use ($optionValueIds) {
                $query->whereIn('product_option_values.id', $optionValueIds);
            })
            ->withCount(['variantOptions' => function ($query) use ($optionValueIds) {
                $query->whereIn('product_option_values.id', $optionValueIds);
            }])
            ->having('variant_options_count', '=', count($optionValueIds))
            ->first();
    }
}


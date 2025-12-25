<?php

namespace App\Lunar\Products;

use Illuminate\Support\Collection;
use Lunar\Models\Product;
use Lunar\Models\ProductOption;
use Lunar\Models\ProductOptionValue;

/**
 * Helper class for working with Product Options and generating variants.
 * 
 * Provides convenience methods for managing product options and variant generation.
 * See: https://docs.lunarphp.com/1.x/reference/products#product-options
 */
class ProductOptionHelper
{
    /**
     * Create a product option with values.
     * 
     * Example:
     * $color = ProductOptionHelper::createOption('Colour', 'Colour', ['Red', 'Blue', 'Green']);
     * 
     * @param string $name
     * @param string|null $label
     * @param array $values Array of value names ['Red', 'Blue', 'Green']
     * @return ProductOption
     */
    public static function createOption(string $name, ?string $label = null, array $values = []): ProductOption
    {
        $option = ProductOption::create([
            'name' => [
                'en' => $name,
            ],
            'label' => [
                'en' => $label ?? $name,
            ],
        ]);

        if (!empty($values)) {
            static::createValues($option, $values);
        }

        return $option->fresh();
    }

    /**
     * Create multiple option values for an option.
     * 
     * @param ProductOption $option
     * @param array $values Array of value names
     * @return Collection
     */
    public static function createValues(ProductOption $option, array $values): Collection
    {
        $optionValues = collect($values)->map(function ($value) {
            return [
                'name' => [
                    'en' => $value,
                ],
            ];
        })->toArray();

        $option->values()->createMany($optionValues);

        return $option->values()->get();
    }

    /**
     * Generate variants for a product using option values.
     * 
     * This dispatches the GenerateVariants job as shown in the documentation.
     * 
     * @param Product $product
     * @param Collection|array $optionValueIds Array or collection of ProductOptionValue IDs
     * @return void
     */
    public static function generateVariants(Product $product, Collection|array $optionValueIds): void
    {
        if (is_array($optionValueIds)) {
            $optionValueIds = collect($optionValueIds);
        }

        // Dispatch the GenerateVariants job as per Lunar documentation
        // Note: This requires the Lunar Hub package for the job
        // For non-hub installations, you may need to create variants manually
        if (class_exists(\Lunar\Hub\Jobs\Products\GenerateVariants::class)) {
            \Lunar\Hub\Jobs\Products\GenerateVariants::dispatch($product, $optionValueIds->toArray());
        } else {
            // Fallback: Create variants manually if Hub is not installed
            // This is a simplified version - the actual GenerateVariants job does more
            static::createVariantsManually($product, $optionValueIds);
        }
    }

    /**
     * Create variants manually (fallback if GenerateVariants job is not available).
     * 
     * @param Product $product
     * @param Collection $optionValueIds
     * @return void
     */
    protected static function createVariantsManually(Product $product, Collection $optionValueIds): void
    {
        // Group option values by their option
        $options = ProductOptionValue::whereIn('id', $optionValueIds)
            ->with('option')
            ->get()
            ->groupBy('option_id');

        // Generate cartesian product of all option values
        $combinations = static::cartesianProduct($options->values()->toArray());

        $baseSku = $product->variants()->first()?->sku ?? 'PROD';
        $count = 0;

        foreach ($combinations as $combination) {
            $count++;
            $sku = "{$baseSku}-{$count}";

            $variant = \Lunar\Models\ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $sku,
                'purchasable' => 'always',
                'shippable' => true,
            ]);

            // Attach option values to variant
            $variant->values()->attach(collect($combination)->pluck('id'));
        }
    }

    /**
     * Calculate cartesian product of arrays.
     * 
     * @param array $arrays
     * @return array
     */
    protected static function cartesianProduct(array $arrays): array
    {
        if (empty($arrays)) {
            return [[]];
        }

        $result = [];
        $first = array_shift($arrays);
        $rest = static::cartesianProduct($arrays);

        foreach ($first as $item) {
            foreach ($rest as $combination) {
                $result[] = array_merge([$item], $combination);
            }
        }

        return $result;
    }

    /**
     * Get product options for a product.
     * 
     * @param Product $product
     * @return Collection
     */
    public static function getProductOptions(Product $product): Collection
    {
        return $product->productOptions()->with('values')->get();
    }

    /**
     * Get option values for a variant.
     * 
     * @param \Lunar\Models\ProductVariant $variant
     * @return Collection
     */
    public static function getVariantValues(\Lunar\Models\ProductVariant $variant): Collection
    {
        return $variant->values()->with('option')->get();
    }
}



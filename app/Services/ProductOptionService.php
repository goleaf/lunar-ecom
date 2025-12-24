<?php

namespace App\Services;

use App\Models\Product;
use Lunar\Models\ProductOption;
use Lunar\Models\ProductOptionValue;
use Illuminate\Database\Eloquent\Collection;

class ProductOptionService
{
    /**
     * Create a product option (e.g., Color, Size)
     */
    public function createOption(array $data): ProductOption
    {
        return ProductOption::create([
            'name' => $data['name'],
            'handle' => $data['handle'],
            'label' => $data['label'] ?? $data['name'],
            'shared' => $data['shared'] ?? false,
        ]);
    }

    /**
     * Create option values for an option (e.g., Red, Blue for Color)
     */
    public function createOptionValue(ProductOption $option, array $data): ProductOptionValue
    {
        return $option->values()->create([
            'name' => $data['name'],
            'handle' => $data['handle'],
            'position' => $data['position'] ?? 0,
        ]);
    }

    /**
     * Associate option with product
     */
    public function associateOptionWithProduct(Product $product, ProductOption $option): void
    {
        $product->productOptions()->syncWithoutDetaching([$option->id]);
    }

    /**
     * Get all options for a product
     */
    public function getProductOptions(Product $product): Collection
    {
        return $product->productOptions()->with('values')->get();
    }

    /**
     * Create multiple option values at once
     */
    public function createMultipleOptionValues(ProductOption $option, array $values): Collection
    {
        $createdValues = collect();

        foreach ($values as $index => $valueData) {
            $value = $this->createOptionValue($option, [
                'name' => $valueData['name'],
                'handle' => $valueData['handle'],
                'position' => $valueData['position'] ?? $index,
            ]);
            $createdValues->push($value);
        }

        return $createdValues;
    }

    /**
     * Update option value
     */
    public function updateOptionValue(ProductOptionValue $value, array $data): ProductOptionValue
    {
        $value->update([
            'name' => $data['name'] ?? $value->name,
            'handle' => $data['handle'] ?? $value->handle,
            'position' => $data['position'] ?? $value->position,
        ]);

        return $value->fresh();
    }

    /**
     * Delete option value
     */
    public function deleteOptionValue(ProductOptionValue $value): bool
    {
        return $value->delete();
    }

    /**
     * Get option by handle
     */
    public function getOptionByHandle(string $handle): ?ProductOption
    {
        return ProductOption::where('handle', $handle)->first();
    }

    /**
     * Get option value by handle
     */
    public function getOptionValueByHandle(ProductOption $option, string $handle): ?ProductOptionValue
    {
        return $option->values()->where('handle', $handle)->first();
    }
}
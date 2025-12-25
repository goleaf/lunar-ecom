<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\VariantAttributeCombination;
use App\Models\VariantAttributeDependency;
use App\Models\VariantAttributeNormalization;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Service for managing variant attribute combinations.
 */
class VariantAttributeCombinationService
{
    /**
     * Create variant from attribute combination.
     *
     * @param  Product  $product
     * @param  array  $combination  Array of option_id => value_id
     * @param  array  $options  Additional options
     * @return ProductVariant
     */
    public function createVariantFromCombination(
        Product $product,
        array $combination,
        array $options = []
    ): ProductVariant {
        return DB::transaction(function () use ($product, $combination, $options) {
            // Normalize combination
            $combination = $this->normalizeCombination($combination);

            // Validate combination
            $validation = $this->validateCombination($product, $combination);
            if (!$validation['valid']) {
                throw new \InvalidArgumentException($validation['message']);
            }

            // Check for existing variant
            $hash = $this->generateHash($combination);
            $existing = VariantAttributeCombination::where('product_id', $product->id)
                ->where('combination_hash', $hash)
                ->where('status', '!=', 'draft')
                ->first();

            if ($existing && $existing->variant_id) {
                throw new \InvalidArgumentException('Variant with this combination already exists.');
            }

            // Separate defining and informational attributes
            $definingAttributes = $options['defining_attributes'] ?? $this->getDefiningAttributes($product);
            $informationalAttributes = array_diff_key($combination, array_flip($definingAttributes));

            // Check if partial variant
            $isPartial = $this->isPartialCombination($product, $combination, $definingAttributes);

            // Create variant
            $variant = ProductVariant::create(array_merge([
                'product_id' => $product->id,
                'status' => $isPartial ? 'draft' : ($options['status'] ?? 'active'),
            ], $options['variant_data'] ?? []));

            // Attach option values
            $variant->variantOptions()->attach(array_values($combination));

            // Create combination record
            VariantAttributeCombination::create([
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'combination' => $combination,
                'combination_hash' => $hash,
                'defining_attributes' => array_keys(array_intersect_key($combination, array_flip($definingAttributes))),
                'informational_attributes' => array_keys($informationalAttributes),
                'status' => $isPartial ? 'draft' : 'active',
                'is_partial' => $isPartial,
                'template_id' => $options['template_id'] ?? null,
            ]);

            return $variant->fresh(['variantOptions']);
        });
    }

    /**
     * Normalize attribute combination values.
     *
     * @param  array  $combination
     * @return array
     */
    public function normalizeCombination(array $combination): array
    {
        $normalized = [];

        foreach ($combination as $optionId => $value) {
            // If value is string, try to normalize it
            if (is_string($value)) {
                $normalizedValueId = VariantAttributeNormalization::normalize($optionId, $value);
                if ($normalizedValueId) {
                    $normalized[$optionId] = $normalizedValueId;
                } else {
                    // Try to find by name
                    $optionValue = \Lunar\Models\ProductOptionValue::where('product_option_id', $optionId)
                        ->where(function ($q) use ($value) {
                            $q->where('name', $value)
                              ->orWhereRaw('LOWER(name) = LOWER(?)', [$value]);
                        })
                        ->first();
                    
                    $normalized[$optionId] = $optionValue?->id ?? $value;
                }
            } else {
                $normalized[$optionId] = $value;
            }
        }

        return $normalized;
    }

    /**
     * Validate combination against dependencies.
     *
     * @param  Product  $product
     * @param  array  $combination
     * @return array  ['valid' => bool, 'message' => string]
     */
    public function validateCombination(Product $product, array $combination): array
    {
        // Get all dependencies for this product
        $dependencies = VariantAttributeDependency::where(function ($q) use ($product) {
            $q->where('product_id', $product->id)
              ->orWhereNull('product_id');
        })
        ->where('is_active', true)
        ->orderByDesc('priority')
        ->get();

        foreach ($dependencies as $dependency) {
            $validation = $dependency->validateCombination($combination);
            if (!$validation['valid']) {
                return $validation;
            }
        }

        return ['valid' => true, 'message' => null];
    }

    /**
     * Check if combination is valid.
     *
     * @param  Product  $product
     * @param  array  $combination
     * @return array  ['valid' => bool, 'message' => string, 'allowed_values' => array]
     */
    public function checkCombinationValidity(Product $product, array $combination): array
    {
        $validation = $this->validateCombination($product, $combination);
        
        if (!$validation['valid']) {
            return $validation;
        }

        // Get allowed values for each option based on dependencies
        $allowedValues = $this->getAllowedValues($product, $combination);

        return [
            'valid' => true,
            'message' => null,
            'allowed_values' => $allowedValues,
        ];
    }

    /**
     * Get allowed values for options based on current combination.
     *
     * @param  Product  $product
     * @param  array  $currentCombination
     * @return array
     */
    public function getAllowedValues(Product $product, array $currentCombination = []): array
    {
        $allowedValues = [];

        // Get all product options
        $options = $product->productOptions()->with('values')->get();

        foreach ($options as $option) {
            $optionId = $option->id;
            $allValues = $option->values->pluck('id')->toArray();

            // If option already has a value in combination, return that
            if (isset($currentCombination[$optionId])) {
                $allowedValues[$optionId] = [$currentCombination[$optionId]];
                continue;
            }

            // Get dependencies affecting this option
            $dependencies = VariantAttributeDependency::where(function ($q) use ($product) {
                $q->where('product_id', $product->id)
                  ->orWhereNull('product_id');
            })
            ->where('target_option_id', $optionId)
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->get();

            $filteredValues = $allValues;

            foreach ($dependencies as $dependency) {
                if ($dependency->appliesTo($currentCombination)) {
                    $targetValueIds = $dependency->target_value_ids ?? [];

                    match($dependency->type) {
                        'allows_only' => $filteredValues = array_intersect($filteredValues, $targetValueIds),
                        'excludes' => $filteredValues = array_diff($filteredValues, $targetValueIds),
                        'requires' => $filteredValues = array_intersect($filteredValues, $targetValueIds),
                        default => null,
                    };
                }
            }

            $allowedValues[$optionId] = array_values($filteredValues);
        }

        return $allowedValues;
    }

    /**
     * Get defining attributes for product.
     *
     * @param  Product  $product
     * @return array  Array of option IDs
     */
    public function getDefiningAttributes(Product $product): array
    {
        // Get from product configuration or default to all options
        $config = $product->custom_meta['variant_defining_attributes'] ?? null;
        
        if ($config) {
            return $config;
        }

        // Default: all product options are defining
        return $product->productOptions()->pluck('id')->toArray();
    }

    /**
     * Check if combination is partial (missing some defining attributes).
     *
     * @param  Product  $product
     * @param  array  $combination
     * @param  array  $definingAttributes
     * @return bool
     */
    public function isPartialCombination(Product $product, array $combination, array $definingAttributes): bool
    {
        foreach ($definingAttributes as $optionId) {
            if (!isset($combination[$optionId])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate hash for combination.
     *
     * @param  array  $combination
     * @return string
     */
    public function generateHash(array $combination): string
    {
        ksort($combination);
        return hash('sha256', json_encode($combination));
    }

    /**
     * Check if combination is unique.
     *
     * @param  Product  $product
     * @param  array  $combination
     * @param  int|null  $excludeVariantId
     * @return bool
     */
    public function isUniqueCombination(Product $product, array $combination, ?int $excludeVariantId = null): bool
    {
        $hash = $this->generateHash($combination);

        $query = VariantAttributeCombination::where('product_id', $product->id)
            ->where('combination_hash', $hash)
            ->where('status', '!=', 'draft');

        if ($excludeVariantId) {
            $query->where('variant_id', '!=', $excludeVariantId);
        }

        return !$query->exists();
    }

    /**
     * Get variant by combination.
     *
     * @param  Product  $product
     * @param  array  $combination
     * @return ProductVariant|null
     */
    public function getVariantByCombination(Product $product, array $combination): ?ProductVariant
    {
        $hash = $this->generateHash($this->normalizeCombination($combination));

        $combinationRecord = VariantAttributeCombination::where('product_id', $product->id)
            ->where('combination_hash', $hash)
            ->whereNotNull('variant_id')
            ->first();

        return $combinationRecord?->variant;
    }

    /**
     * Get all combinations for product.
     *
     * @param  Product  $product
     * @param  bool  $includePartial
     * @return Collection
     */
    public function getCombinations(Product $product, bool $includePartial = false): Collection
    {
        $query = VariantAttributeCombination::where('product_id', $product->id);

        if (!$includePartial) {
            $query->where('is_partial', false);
        }

        return $query->with(['variant', 'template'])->get();
    }

    /**
     * Get invalid combinations (disabled by dependencies).
     *
     * @param  Product  $product
     * @return Collection
     */
    public function getInvalidCombinations(Product $product): Collection
    {
        // Get all possible combinations
        $allCombinations = $this->generateAllCombinations($product);

        $invalid = collect();

        foreach ($allCombinations as $combination) {
            $validation = $this->validateCombination($product, $combination);
            if (!$validation['valid']) {
                $invalid->push([
                    'combination' => $combination,
                    'message' => $validation['message'],
                ]);
            }
        }

        return $invalid;
    }

    /**
     * Generate all possible combinations from product options.
     *
     * @param  Product  $product
     * @return array
     */
    public function generateAllCombinations(Product $product): array
    {
        $options = $product->productOptions()->with('values')->get();

        $optionValueGroups = $options->map(function ($option) {
            return $option->values->map(function ($value) use ($option) {
                return [$option->id => $value->id];
            })->toArray();
        })->toArray();

        return $this->cartesianProduct($optionValueGroups);
    }

    /**
     * Generate cartesian product of arrays.
     *
     * @param  array  $arrays
     * @return array
     */
    protected function cartesianProduct(array $arrays): array
    {
        if (empty($arrays)) {
            return [[]];
        }

        $result = [[]];

        foreach ($arrays as $array) {
            $newResult = [];
            foreach ($result as $existing) {
                foreach ($array as $item) {
                    $newResult[] = array_merge($existing, $item);
                }
            }
            $result = $newResult;
        }

        return $result;
    }
}


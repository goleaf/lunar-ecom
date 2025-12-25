<?php

namespace App\Services;

use App\Models\Product;
use App\Models\VariantAttributeDependency;
use Illuminate\Support\Collection;

/**
 * Service for managing variant attribute dependencies.
 */
class VariantDependencyService
{
    /**
     * Create dependency rule.
     *
     * @param  array  $data
     * @return VariantAttributeDependency
     */
    public function createDependency(array $data): VariantAttributeDependency
    {
        return VariantAttributeDependency::create([
            'product_id' => $data['product_id'] ?? null,
            'type' => $data['type'],
            'source_option_id' => $data['source_option_id'],
            'source_value_id' => $data['source_value_id'] ?? null,
            'target_option_id' => $data['target_option_id'],
            'target_value_ids' => $data['target_value_ids'] ?? [],
            'config' => $data['config'] ?? [],
            'priority' => $data['priority'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Create "requires" dependency.
     * Example: "If Color = Red, then Size is required"
     *
     * @param  int  $sourceOptionId
     * @param  int|null  $sourceValueId
     * @param  int  $targetOptionId
     * @param  array  $requiredValueIds
     * @param  int|null  $productId
     * @return VariantAttributeDependency
     */
    public function createRequiresDependency(
        int $sourceOptionId,
        ?int $sourceValueId,
        int $targetOptionId,
        array $requiredValueIds,
        ?int $productId = null
    ): VariantAttributeDependency {
        return $this->createDependency([
            'product_id' => $productId,
            'type' => 'requires',
            'source_option_id' => $sourceOptionId,
            'source_value_id' => $sourceValueId,
            'target_option_id' => $targetOptionId,
            'target_value_ids' => $requiredValueIds,
        ]);
    }

    /**
     * Create "excludes" dependency.
     * Example: "If Size = XL, then Color cannot be Black"
     *
     * @param  int  $sourceOptionId
     * @param  int  $sourceValueId
     * @param  int  $targetOptionId
     * @param  array  $excludedValueIds
     * @param  int|null  $productId
     * @return VariantAttributeDependency
     */
    public function createExcludesDependency(
        int $sourceOptionId,
        int $sourceValueId,
        int $targetOptionId,
        array $excludedValueIds,
        ?int $productId = null
    ): VariantAttributeDependency {
        return $this->createDependency([
            'product_id' => $productId,
            'type' => 'excludes',
            'source_option_id' => $sourceOptionId,
            'source_value_id' => $sourceValueId,
            'target_option_id' => $targetOptionId,
            'target_value_ids' => $excludedValueIds,
            'config' => ['message' => 'This combination is not available.'],
        ]);
    }

    /**
     * Create "allows_only" dependency.
     * Example: "If Size = XL, then only Black and White colors are allowed"
     *
     * @param  int  $sourceOptionId
     * @param  int  $sourceValueId
     * @param  int  $targetOptionId
     * @param  array  $allowedValueIds
     * @param  int|null  $productId
     * @return VariantAttributeDependency
     */
    public function createAllowsOnlyDependency(
        int $sourceOptionId,
        int $sourceValueId,
        int $targetOptionId,
        array $allowedValueIds,
        ?int $productId = null
    ): VariantAttributeDependency {
        return $this->createDependency([
            'product_id' => $productId,
            'type' => 'allows_only',
            'source_option_id' => $sourceOptionId,
            'source_value_id' => $sourceValueId,
            'target_option_id' => $targetOptionId,
            'target_value_ids' => $allowedValueIds,
            'config' => ['message' => 'Only specific values are available for this option.'],
        ]);
    }

    /**
     * Get dependencies for product.
     *
     * @param  Product  $product
     * @return Collection
     */
    public function getDependencies(Product $product): Collection
    {
        return VariantAttributeDependency::where(function ($q) use ($product) {
            $q->where('product_id', $product->id)
              ->orWhereNull('product_id');
        })
        ->where('is_active', true)
        ->orderByDesc('priority')
        ->get();
    }

    /**
     * Get disabled combinations for product.
     *
     * @param  Product  $product
     * @return Collection
     */
    public function getDisabledCombinations(Product $product): Collection
    {
        $combinationService = app(VariantAttributeCombinationService::class);
        $allCombinations = $combinationService->generateAllCombinations($product);

        $disabled = collect();

        foreach ($allCombinations as $combination) {
            $validation = $combinationService->validateCombination($product, $combination);
            if (!$validation['valid']) {
                $disabled->push([
                    'combination' => $combination,
                    'message' => $validation['message'],
                ]);
            }
        }

        return $disabled;
    }

    /**
     * Validate combination against all dependencies.
     *
     * @param  Product  $product
     * @param  array  $combination
     * @return array
     */
    public function validateAgainstDependencies(Product $product, array $combination): array
    {
        $dependencies = $this->getDependencies($product);
        $errors = [];

        foreach ($dependencies as $dependency) {
            $validation = $dependency->validateCombination($combination);
            if (!$validation['valid']) {
                $errors[] = [
                    'dependency_id' => $dependency->id,
                    'type' => $dependency->type,
                    'message' => $validation['message'],
                ];
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
}


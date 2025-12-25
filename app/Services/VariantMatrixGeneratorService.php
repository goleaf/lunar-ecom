<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\VariantAttributeDependency;
use App\Models\VariantTemplate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service for auto-generating variants from attribute matrix.
 */
class VariantMatrixGeneratorService
{
    /**
     * Generate variants from attribute matrix.
     *
     * @param  Product  $product
     * @param  array  $options  Generation options
     * @return Collection  Created variants
     */
    public function generateFromMatrix(Product $product, array $options = []): Collection
    {
        $combinationService = app(VariantAttributeCombinationService::class);
        
        // Get defining attributes
        $definingAttributes = $options['defining_attributes'] ?? $combinationService->getDefiningAttributes($product);
        
        // Get all combinations
        $allCombinations = $combinationService->generateAllCombinations($product);
        
        // Filter by defining attributes if specified
        if (!empty($definingAttributes)) {
            $allCombinations = array_filter($allCombinations, function ($combination) use ($definingAttributes) {
                foreach ($definingAttributes as $optionId) {
                    if (!isset($combination[$optionId])) {
                        return false;
                    }
                }
                return true;
            });
        }

        // Filter invalid combinations
        $validCombinations = [];
        foreach ($allCombinations as $combination) {
            $validation = $combinationService->validateCombination($product, $combination);
            if ($validation['valid']) {
                $validCombinations[] = $combination;
            }
        }

        // Generate variants
        $createdVariants = collect();

        DB::transaction(function () use ($product, $validCombinations, $options, &$createdVariants, $combinationService) {
            foreach ($validCombinations as $combination) {
                try {
                    // Check if variant already exists
                    $existing = $combinationService->getVariantByCombination($product, $combination);
                    if ($existing) {
                        continue;
                    }

                    // Create variant
                    $variant = $combinationService->createVariantFromCombination($product, $combination, [
                        'status' => $options['status'] ?? 'active',
                        'variant_data' => $options['defaults'] ?? [],
                        'template_id' => $options['template_id'] ?? null,
                    ]);

                    $createdVariants->push($variant);
                } catch (\Exception $e) {
                    // Log error but continue
                    \Log::warning("Failed to create variant for combination", [
                        'product_id' => $product->id,
                        'combination' => $combination,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });

        return $createdVariants;
    }

    /**
     * Generate variants from template.
     *
     * @param  Product  $product
     * @param  VariantTemplate  $template
     * @param  array  $overrides
     * @return Collection
     */
    public function generateFromTemplate(Product $product, VariantTemplate $template, array $overrides = []): Collection
    {
        $defaultCombination = $template->default_combination ?? [];
        $defaultFields = $template->default_fields ?? [];

        // Merge with overrides
        $combination = array_merge($defaultCombination, $overrides['combination'] ?? []);
        $fields = array_merge($defaultFields, $overrides['fields'] ?? []);

        $combinationService = app(VariantAttributeCombinationService::class);

        // Generate all combinations based on template
        $allCombinations = $combinationService->generateAllCombinations($product);

        // Apply template filter if specified
        if (!empty($defaultCombination)) {
            $allCombinations = array_filter($allCombinations, function ($comb) use ($defaultCombination) {
                foreach ($defaultCombination as $optionId => $valueId) {
                    if (!isset($comb[$optionId]) || $comb[$optionId] != $valueId) {
                        return false;
                    }
                }
                return true;
            });
        }

        $createdVariants = collect();

        DB::transaction(function () use ($product, $allCombinations, $fields, $template, &$createdVariants, $combinationService) {
            foreach ($allCombinations as $combination) {
                try {
                    $variant = $combinationService->createVariantFromCombination($product, $combination, [
                        'status' => 'active',
                        'variant_data' => $fields,
                        'template_id' => $template->id,
                    ]);

                    $createdVariants->push($variant);
                } catch (\Exception $e) {
                    \Log::warning("Failed to create variant from template", [
                        'product_id' => $product->id,
                        'template_id' => $template->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Increment template usage
            $template->incrementUsage();
        });

        return $createdVariants;
    }

    /**
     * Generate variants with dependency filtering.
     *
     * @param  Product  $product
     * @param  array  $options
     * @return Collection
     */
    public function generateWithDependencies(Product $product, array $options = []): Collection
    {
        $combinationService = app(VariantAttributeCombinationService::class);
        
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

        // Generate variants
        return $this->generateFromMatrix($product, array_merge($options, [
            'combinations' => $validCombinations,
        ]));
    }
}


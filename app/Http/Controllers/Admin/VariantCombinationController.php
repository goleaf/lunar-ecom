<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\VariantAttributeCombinationService;
use App\Services\VariantMatrixGeneratorService;
use App\Services\VariantDependencyService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller for managing variant attribute combinations.
 */
class VariantCombinationController extends Controller
{
    /**
     * Generate variants from matrix.
     *
     * @param  Request  $request
     * @param  Product  $product
     * @return JsonResponse
     */
    public function generateFromMatrix(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'defining_attributes' => 'nullable|array',
            'defining_attributes.*' => 'integer|exists:' . config('lunar.database.table_prefix') . 'product_options,id',
            'status' => 'nullable|in:draft,active',
            'defaults' => 'nullable|array',
        ]);

        $service = app(VariantMatrixGeneratorService::class);
        
        $variants = $service->generateFromMatrix($product, [
            'defining_attributes' => $request->input('defining_attributes'),
            'status' => $request->input('status', 'active'),
            'defaults' => $request->input('defaults', []),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Generated {$variants->count()} variants",
            'variants' => $variants,
        ]);
    }

    /**
     * Create variant manually.
     *
     * @param  Request  $request
     * @param  Product  $product
     * @return JsonResponse
     */
    public function createManual(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'combination' => 'required|array',
            'combination.*' => 'integer|exists:' . config('lunar.database.table_prefix') . 'product_option_values,id',
            'variant_data' => 'nullable|array',
            'allow_partial' => 'nullable|boolean',
        ]);

        $service = app(VariantAttributeCombinationService::class);

        try {
            $variant = $service->createVariantFromCombination($product, $request->input('combination'), [
                'status' => $request->input('status', 'active'),
                'variant_data' => $request->input('variant_data', []),
                'allow_partial' => $request->input('allow_partial', false),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Variant created successfully',
                'variant' => $variant,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Validate combination.
     *
     * @param  Request  $request
     * @param  Product  $product
     * @return JsonResponse
     */
    public function validateCombination(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'combination' => 'required|array',
            'combination.*' => 'integer',
        ]);

        $service = app(VariantAttributeCombinationService::class);
        
        $result = $service->checkCombinationValidity($product, $request->input('combination'));

        return response()->json($result);
    }

    /**
     * Get allowed values for options.
     *
     * @param  Request  $request
     * @param  Product  $product
     * @return JsonResponse
     */
    public function getAllowedValues(Request $request, Product $product): JsonResponse
    {
        $currentCombination = $request->input('combination', []);

        $service = app(VariantAttributeCombinationService::class);
        $allowedValues = $service->getAllowedValues($product, $currentCombination);

        return response()->json([
            'allowed_values' => $allowedValues,
        ]);
    }

    /**
     * Get invalid combinations.
     *
     * @param  Product  $product
     * @return JsonResponse
     */
    public function getInvalidCombinations(Product $product): JsonResponse
    {
        $service = app(VariantAttributeCombinationService::class);
        $invalid = $service->getInvalidCombinations($product);

        return response()->json([
            'invalid_combinations' => $invalid,
        ]);
    }
}



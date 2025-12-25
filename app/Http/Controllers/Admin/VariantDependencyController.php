<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\VariantAttributeDependency;
use App\Services\VariantDependencyService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Controller for managing variant attribute dependencies.
 */
class VariantDependencyController extends Controller
{
    /**
     * Create dependency rule.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'nullable|exists:' . config('lunar.database.table_prefix') . 'products,id',
            'type' => 'required|in:requires,excludes,allows_only,requires_one_of',
            'source_option_id' => 'required|exists:' . config('lunar.database.table_prefix') . 'product_options,id',
            'source_value_id' => 'nullable|exists:' . config('lunar.database.table_prefix') . 'product_option_values,id',
            'target_option_id' => 'required|exists:' . config('lunar.database.table_prefix') . 'product_options,id',
            'target_value_ids' => 'required|array',
            'target_value_ids.*' => 'integer|exists:' . config('lunar.database.table_prefix') . 'product_option_values,id',
            'config' => 'nullable|array',
            'priority' => 'nullable|integer',
        ]);

        $service = app(VariantDependencyService::class);
        $dependency = $service->createDependency($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Dependency rule created',
            'dependency' => $dependency,
        ]);
    }

    /**
     * Get dependencies for product.
     *
     * @param  Product  $product
     * @return JsonResponse
     */
    public function index(Product $product): JsonResponse
    {
        $service = app(VariantDependencyService::class);
        $dependencies = $service->getDependencies($product);

        return response()->json([
            'dependencies' => $dependencies,
        ]);
    }

    /**
     * Get disabled combinations.
     *
     * @param  Product  $product
     * @return JsonResponse
     */
    public function getDisabledCombinations(Product $product): JsonResponse
    {
        $service = app(VariantDependencyService::class);
        $disabled = $service->getDisabledCombinations($product);

        return response()->json([
            'disabled_combinations' => $disabled,
        ]);
    }

    /**
     * Validate combination against dependencies.
     *
     * @param  Request  $request
     * @param  Product  $product
     * @return JsonResponse
     */
    public function validate(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'combination' => 'required|array',
            'combination.*' => 'integer',
        ]);

        $service = app(VariantDependencyService::class);
        $result = $service->validateAgainstDependencies($product, $request->input('combination'));

        return response()->json($result);
    }

    /**
     * Delete dependency.
     *
     * @param  VariantAttributeDependency  $dependency
     * @return JsonResponse
     */
    public function destroy(VariantAttributeDependency $dependency): JsonResponse
    {
        $dependency->delete();

        return response()->json([
            'success' => true,
            'message' => 'Dependency deleted',
        ]);
    }
}


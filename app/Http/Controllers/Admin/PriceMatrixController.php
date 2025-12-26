<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PriceMatrix;
use App\Models\PricingTier;
use App\Models\PricingRule;
use App\Services\MatrixPricingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PriceMatrixController extends Controller
{
    public function __construct(
        protected MatrixPricingService $pricingService
    ) {}

    /**
     * Display price matrices for a product.
     */
    public function index(Product $product)
    {
        $matrices = PriceMatrix::where('product_id', $product->id)
            ->with(['productVariant', 'tiers', 'pricingRules'])
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.products.pricing.matrices', compact('product', 'matrices'));
    }

    /**
     * Store a new price matrix.
     */
    public function store(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'product_variant_id' => 'nullable|exists:lunar_product_variants,id',
            'name' => 'nullable|string|max:255',
            'matrix_type' => 'required|in:quantity,customer_group,region,mixed,rule_based',
            'description' => 'nullable|string',
            'priority' => 'nullable|integer|min:0|max:100',
            'is_active' => 'boolean',
            'requires_approval' => 'boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'rules' => 'nullable|array',
            'allow_mix_match' => 'boolean',
            'mix_match_variants' => 'nullable|array',
            'mix_match_min_quantity' => 'nullable|integer|min:1',
            'min_order_quantity' => 'nullable|integer|min:1',
            'max_order_quantity' => 'nullable|integer|min:1',
        ]);

        $matrix = PriceMatrix::create(array_merge($validated, [
            'product_id' => $product->id,
            'approval_status' => $validated['requires_approval'] ?? false ? 'pending' : 'approved',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Price matrix created successfully.',
            'matrix' => $matrix,
        ]);
    }

    /**
     * Update a price matrix.
     */
    public function update(Request $request, Product $product, PriceMatrix $matrix): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'nullable|integer|min:0|max:100',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'rules' => 'nullable|array',
            'allow_mix_match' => 'boolean',
            'mix_match_variants' => 'nullable|array',
            'mix_match_min_quantity' => 'nullable|integer|min:1',
            'min_order_quantity' => 'nullable|integer|min:1',
            'max_order_quantity' => 'nullable|integer|min:1',
        ]);

        $matrix->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Price matrix updated successfully.',
            'matrix' => $matrix->fresh(),
        ]);
    }

    /**
     * Delete a price matrix.
     */
    public function destroy(Product $product, PriceMatrix $matrix): JsonResponse
    {
        $matrix->delete();

        return response()->json([
            'success' => true,
            'message' => 'Price matrix deleted successfully.',
        ]);
    }

    /**
     * Add pricing tier.
     */
    public function addTier(Request $request, Product $product, PriceMatrix $matrix): JsonResponse
    {
        $validated = $request->validate([
            'tier_name' => 'nullable|string|max:255',
            'min_quantity' => 'required|integer|min:1',
            'max_quantity' => 'nullable|integer|min:1|gt:min_quantity',
            'price' => 'nullable|numeric|min:0',
            'price_adjustment' => 'nullable|numeric',
            'percentage_discount' => 'nullable|integer|min:0|max:100',
            'pricing_type' => 'required|in:fixed,adjustment,percentage',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $tier = PricingTier::create(array_merge($validated, [
            'price_matrix_id' => $matrix->id,
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Pricing tier added successfully.',
            'tier' => $tier,
        ]);
    }

    /**
     * Update pricing tier.
     */
    public function updateTier(Request $request, Product $product, PriceMatrix $matrix, PricingTier $tier): JsonResponse
    {
        $validated = $request->validate([
            'tier_name' => 'nullable|string|max:255',
            'min_quantity' => 'required|integer|min:1',
            'max_quantity' => 'nullable|integer|min:1|gt:min_quantity',
            'price' => 'nullable|numeric|min:0',
            'price_adjustment' => 'nullable|numeric',
            'percentage_discount' => 'nullable|integer|min:0|max:100',
            'pricing_type' => 'required|in:fixed,adjustment,percentage',
            'display_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $tier->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Pricing tier updated successfully.',
            'tier' => $tier->fresh(),
        ]);
    }

    /**
     * Approve price matrix.
     */
    public function approve(Request $request, Product $product, PriceMatrix $matrix): JsonResponse
    {
        $validated = $request->validate([
            'approval_status' => 'required|in:approved,rejected',
            'approval_notes' => 'nullable|string|max:1000',
        ]);

        $matrix->update([
            'approval_status' => $validated['approval_status'],
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_notes' => $validated['approval_notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Price matrix ' . $validated['approval_status'] . ' successfully.',
            'matrix' => $matrix->fresh(),
        ]);
    }

    /**
     * Get pricing report.
     */
    public function report(Request $request): JsonResponse
    {
        $filters = $request->only([
            'product_id',
            'customer_group',
            'region',
            'start_date',
            'end_date',
        ]);

        $report = $this->pricingService->getPricingReport($filters);

        return response()->json([
            'success' => true,
            'report' => $report,
        ]);
    }
}



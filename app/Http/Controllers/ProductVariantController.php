<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductVariantService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductVariantController extends Controller
{
    public function __construct(
        protected ProductVariantService $variantService
    ) {}

    /**
     * Create a new product variant
     */
    public function store(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'sku' => 'required|string|unique:lunar_product_variants,sku',
            'gtin' => 'nullable|string',
            'mpn' => 'nullable|string',
            'ean' => 'nullable|string',
            'unit_quantity' => 'integer|min:1',
            'min_quantity' => 'integer|min:1',
            'quantity_increment' => 'integer|min:1',
            'stock' => 'integer|min:0',
            'backorder' => 'integer|min:0',
            'purchasable' => 'in:always,in_stock,never',
            'shippable' => 'boolean',
            'prices' => 'array',
            'prices.*.price' => 'required|numeric|min:0',
            'prices.*.compare_price' => 'nullable|numeric|min:0',
            'prices.*.min_quantity' => 'integer|min:1',
            'options' => 'array',
        ]);

        $variant = $this->variantService->createVariant($product, $validated);

        return response()->json([
            'data' => $variant->load(['prices', 'values.option']),
            'message' => 'Product variant created successfully'
        ], 201);
    }

    /**
     * Update variant stock
     */
    public function updateStock(Request $request, ProductVariant $variant): JsonResponse
    {
        $validated = $request->validate([
            'stock' => 'required|integer|min:0'
        ]);

        $updatedVariant = $this->variantService->updateStock($variant, $validated['stock']);

        return response()->json([
            'data' => $updatedVariant,
            'message' => 'Stock updated successfully'
        ]);
    }

    /**
     * Check variant availability
     */
    public function checkAvailability(ProductVariant $variant): JsonResponse
    {
        $isAvailable = $this->variantService->isAvailable($variant);

        return response()->json([
            'available' => $isAvailable,
            'stock' => $variant->stock,
            'purchasable' => $variant->purchasable
        ]);
    }

    /**
     * Get variant pricing
     */
    public function getPricing(ProductVariant $variant, Request $request): JsonResponse
    {
        $currencyCode = $request->get('currency', 'USD');
        $currency = \Lunar\Models\Currency::where('code', $currencyCode)->first();

        if (!$currency) {
            return response()->json(['error' => 'Currency not found'], 404);
        }

        $price = $this->variantService->getPrice($variant, $currency);

        return response()->json([
            'price' => $price ? [
                'price' => $price->price,
                'compare_price' => $price->compare_price,
                'currency' => $currency->code,
                'min_quantity' => $price->min_quantity,
            ] : null
        ]);
    }

    /**
     * Update variant details
     */
    public function update(Request $request, ProductVariant $variant): JsonResponse
    {
        $validated = $request->validate([
            'sku' => 'string|unique:lunar_product_variants,sku,' . $variant->id,
            'gtin' => 'nullable|string',
            'mpn' => 'nullable|string',
            'ean' => 'nullable|string',
            'unit_quantity' => 'integer|min:1',
            'min_quantity' => 'integer|min:1',
            'quantity_increment' => 'integer|min:1',
            'stock' => 'integer|min:0',
            'backorder' => 'integer|min:0',
            'purchasable' => 'in:always,in_stock,never',
            'shippable' => 'boolean',
        ]);

        $variant->update($validated);

        return response()->json([
            'data' => $variant->fresh(),
            'message' => 'Product variant updated successfully'
        ]);
    }

    /**
     * Delete variant
     */
    public function destroy(ProductVariant $variant): JsonResponse
    {
        $variant->delete();

        return response()->json([
            'message' => 'Product variant deleted successfully'
        ]);
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\VariantGenerator;
use App\Services\VariantPriceCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Lunar\Models\Currency;

/**
 * Controller for advanced variant management operations.
 */
class VariantManagementController extends Controller
{
    public function __construct(
        protected VariantGenerator $variantGenerator,
        protected VariantPriceCalculator $priceCalculator
    ) {}

    /**
     * Generate variants for a product.
     *
     * @param  Request  $request
     * @param  Product  $product
     * @return JsonResponse
     */
    public function generateVariants(Request $request, Product $product): JsonResponse
    {
        $this->authorize('create', ProductVariant::class);
        
        $validator = Validator::make($request->all(), [
            'options' => 'nullable|array',
            'options.*' => 'exists:product_options,id',
            'defaults' => 'nullable|array',
            'defaults.stock' => 'nullable|integer|min:0',
            'defaults.price' => 'nullable|numeric|min:0',
            'defaults.currency_id' => 'nullable|exists:currencies,id',
            'defaults.enabled' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $defaults = $request->input('defaults', []);
            
            // Convert price to cents if provided
            if (isset($defaults['price'])) {
                $defaults['price'] = (int) ($defaults['price'] * 100);
            }

            $variants = $this->variantGenerator->generateVariants(
                $product,
                $request->input('options', []),
                $defaults
            );

            return response()->json([
                'data' => $variants->load(['variantOptions.option', 'prices']),
                'message' => "Successfully generated {$variants->count()} variants"
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Bulk update variants.
     *
     * @param  Request  $request
     * @param  Product  $product
     * @return JsonResponse
     */
    public function bulkUpdate(Request $request, Product $product): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'variant_ids' => 'required|array',
            'variant_ids.*' => 'exists:product_variants,id',
            'attributes' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $variants = ProductVariant::whereIn('id', $request->input('variant_ids'))
            ->where('product_id', $product->id)
            ->get();

        if ($variants->isEmpty()) {
            return response()->json([
                'error' => 'No valid variants found'
            ], 404);
        }

        // Check authorization on first variant (if user can update one, they can update all in bulk)
        $this->authorize('update', $variants->first());

        $updated = $this->variantGenerator->bulkUpdateVariants(
            $variants,
            $request->input('attributes', [])
        );

        return response()->json([
            'message' => "Successfully updated {$updated} variants",
            'updated_count' => $updated
        ]);
    }

    /**
     * Get variant price with tiered pricing.
     *
     * @param  Request  $request
     * @param  ProductVariant  $variant
     * @return JsonResponse
     */
    public function getPrice(Request $request, ProductVariant $variant): JsonResponse
    {
        $quantity = (int) $request->input('quantity', 1);
        $currencyCode = $request->input('currency');
        
        $currency = $currencyCode 
            ? Currency::where('code', $currencyCode)->first()
            : Currency::where('default', true)->first();

        if (!$currency) {
            return response()->json([
                'error' => 'Currency not found'
            ], 404);
        }

        $priceInfo = $this->priceCalculator->calculatePrice(
            $variant,
            $quantity,
            $currency
        );

        return response()->json([
            'data' => $priceInfo
        ]);
    }

    /**
     * Get all price tiers for a variant.
     *
     * @param  Request  $request
     * @param  ProductVariant  $variant
     * @return JsonResponse
     */
    public function getPriceTiers(Request $request, ProductVariant $variant): JsonResponse
    {
        $currencyCode = $request->input('currency');
        
        $currency = $currencyCode 
            ? Currency::where('code', $currencyCode)->first()
            : Currency::where('default', true)->first();

        if (!$currency) {
            return response()->json([
                'error' => 'Currency not found'
            ], 404);
        }

        $tiers = $this->priceCalculator->getPriceTiers($variant, $currency);

        return response()->json([
            'data' => $tiers
        ]);
    }

    /**
     * Check variant availability.
     *
     * @param  ProductVariant  $variant
     * @return JsonResponse
     */
    public function checkAvailability(ProductVariant $variant): JsonResponse
    {
        return response()->json([
            'data' => [
                'available' => $variant->isAvailable(),
                'enabled' => $variant->enabled,
                'stock' => $variant->stock,
                'purchasable' => $variant->purchasable,
            ]
        ]);
    }

    /**
     * Get variant by option combination.
     *
     * @param  Request  $request
     * @param  Product  $product
     * @return JsonResponse
     */
    public function getVariantByOptions(Request $request, Product $product): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'option_values' => 'required|array',
            'option_values.*' => 'exists:product_option_values,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $variant = $this->variantGenerator->getVariantByCombination(
            $product,
            $request->input('option_values')
        );

        if (!$variant) {
            return response()->json([
                'error' => 'Variant not found for this option combination'
            ], 404);
        }

        return response()->json([
            'data' => $variant->load(['variantOptions.option', 'prices'])
        ]);
    }
}


<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\ProductVariant;
use App\Services\MatrixPricingService;
use Lunar\Models\Currency;
use Lunar\Models\CustomerGroup;

class PricingController extends Controller
{
    protected MatrixPricingService $pricingService;

    public function __construct(MatrixPricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    /**
     * Calculate price for a variant.
     */
    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'variant_id' => 'required|exists:lunar_product_variants,id',
            'quantity' => 'required|integer|min:1',
            'currency_id' => 'sometimes|exists:lunar_currencies,id',
            'customer_group' => 'sometimes|string',
            'region' => 'sometimes|string',
        ]);

        $variant = ProductVariant::findOrFail($request->variant_id);
        $currency = $request->currency_id 
            ? Currency::find($request->currency_id)
            : Currency::where('default', true)->first();

        $customerGroup = $request->customer_group 
            ? CustomerGroup::where('handle', $request->customer_group)->first()
            : null;

        $price = $this->pricingService->calculatePrice(
            $variant,
            $request->quantity,
            $currency,
            $customerGroup,
            $request->region
        );

        return response()->json($price);
    }

    /**
     * Get tiered pricing for a variant.
     */
    public function tiers(Request $request): JsonResponse
    {
        $request->validate([
            'variant_id' => 'required|exists:lunar_product_variants,id',
            'currency_id' => 'sometimes|exists:lunar_currencies,id',
            'customer_group' => 'sometimes|string',
            'region' => 'sometimes|string',
        ]);

        $variant = ProductVariant::findOrFail($request->variant_id);
        $currency = $request->currency_id 
            ? Currency::find($request->currency_id)
            : Currency::where('default', true)->first();

        $customerGroup = $request->customer_group 
            ? CustomerGroup::where('handle', $request->customer_group)->first()
            : null;

        $tiers = $this->pricingService->getTieredPricing(
            $variant,
            $currency,
            $customerGroup,
            $request->region
        );

        return response()->json([
            'tiers' => $tiers,
        ]);
    }

    /**
     * Get volume discounts for a variant.
     */
    public function volumeDiscounts(Request $request): JsonResponse
    {
        $request->validate([
            'variant_id' => 'required|exists:lunar_product_variants,id',
            'currency_id' => 'sometimes|exists:lunar_currencies,id',
            'customer_group' => 'sometimes|string',
        ]);

        $variant = ProductVariant::findOrFail($request->variant_id);
        $currency = $request->currency_id 
            ? Currency::find($request->currency_id)
            : Currency::where('default', true)->first();

        $customerGroup = $request->customer_group 
            ? CustomerGroup::where('handle', $request->customer_group)->first()
            : null;

        $discounts = $this->pricingService->getVolumeDiscounts(
            $variant,
            $currency,
            $customerGroup
        );

        return response()->json([
            'discounts' => $discounts,
        ]);
    }
}


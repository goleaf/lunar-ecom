<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\MatrixPricingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PricingController extends Controller
{
    public function __construct(
        protected MatrixPricingService $pricingService
    ) {}

    /**
     * Get price for a variant.
     */
    public function getPrice(ProductVariant $variant, Request $request): JsonResponse
    {
        $context = [
            'quantity' => $request->input('quantity', 1),
            'customer_group' => $request->input('customer_group'),
            'region' => $request->input('region'),
        ];

        // Add customer group from authenticated user if available
        if (auth()->check() && auth()->user()->customer) {
            $customerGroup = auth()->user()->customer->customerGroups->first();
            if ($customerGroup) {
                $context['customer_group'] = $customerGroup->handle ?? $customerGroup->name;
            }
        }

        $pricing = $this->pricingService->calculatePrice($variant, $context);

        return response()->json([
            'success' => true,
            'pricing' => $pricing,
        ]);
    }

    /**
     * Get tiered pricing table.
     */
    public function getTieredPricing(ProductVariant $variant, Request $request): JsonResponse
    {
        $context = [
            'customer_group' => $request->input('customer_group'),
            'region' => $request->input('region'),
        ];

        $tiers = $this->pricingService->getTieredPricing($variant, $context);

        return response()->json([
            'success' => true,
            'tiers' => $tiers,
        ]);
    }

    /**
     * Get volume discounts.
     */
    public function getVolumeDiscounts(Product $product, Request $request): JsonResponse
    {
        $variants = $product->variants;
        $context = [
            'customer_group' => $request->input('customer_group'),
            'region' => $request->input('region'),
        ];

        $discounts = [];

        foreach ($variants as $variant) {
            $tiers = $this->pricingService->getVolumeDiscounts($variant, $context);
            
            if (!empty($tiers)) {
                $discounts[] = [
                    'variant_id' => $variant->id,
                    'variant_name' => $variant->name,
                    'tiers' => $tiers,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'volume_discounts' => $discounts,
        ]);
    }
}



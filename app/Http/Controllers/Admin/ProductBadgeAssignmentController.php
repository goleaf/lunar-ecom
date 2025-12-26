<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductBadge;
use App\Services\BadgeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductBadgeAssignmentController extends Controller
{
    public function __construct(
        protected BadgeService $badgeService
    ) {}

    /**
     * Display badges for a product.
     */
    public function index(Product $product)
    {
        $badges = $this->badgeService->getProductBadges($product);
        $allBadges = ProductBadge::active()->orderBy('name')->get();
        
        return view('admin.products.badges', compact('product', 'badges', 'allBadges'));
    }

    /**
     * Assign a badge to a product.
     */
    public function assign(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'badge_id' => 'required|exists:lunar_product_badges,id',
            'priority' => 'nullable|integer|min:0|max:100',
            'display_position' => 'nullable|in:top-left,top-right,bottom-left,bottom-right,center',
            'visibility_rules' => 'nullable|array',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
        ]);

        $badge = ProductBadge::findOrFail($validated['badge_id']);
        
        $assignment = $this->badgeService->assignBadge($product, $badge, [
            'assignment_type' => 'manual',
            'priority' => $validated['priority'] ?? null,
            'display_position' => $validated['display_position'] ?? null,
            'visibility_rules' => $validated['visibility_rules'] ?? null,
            'starts_at' => $validated['starts_at'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Badge assigned successfully.',
            'assignment' => $assignment->load('badge'),
        ]);
    }

    /**
     * Remove a badge from a product.
     */
    public function remove(Product $product, ProductBadge $badge): JsonResponse
    {
        $removed = $this->badgeService->removeBadge($product, $badge, true);

        if ($removed) {
            return response()->json([
                'success' => true,
                'message' => 'Badge removed successfully.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to remove badge.',
        ], 400);
    }
}



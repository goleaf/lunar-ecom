<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductBadge;
use App\Services\ProductBadgeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Admin controller for product badges.
 */
class ProductBadgeController extends Controller
{
    public function __construct(
        protected ProductBadgeService $badgeService
    ) {}

    /**
     * Display badges index.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $badges = ProductBadge::orderBy('priority', 'desc')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.products.badges.index', compact('badges'));
    }

    /**
     * Show badge creation form.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('admin.products.badges.create');
    }

    /**
     * Store a new badge.
     *
     * @param  Request  $request
     * @return JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:lunar_product_badges,name',
            'handle' => 'nullable|string|max:255|unique:lunar_product_badges,handle',
            'type' => 'required|in:new,sale,hot,limited,exclusive,custom',
            'description' => 'nullable|string',
            'label' => 'nullable|string|max:255',
            'color' => 'required|string|max:7',
            'background_color' => 'required|string|max:7',
            'border_color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:255',
            'position' => 'required|in:top-left,top-right,bottom-left,bottom-right,center',
            'style' => 'required|in:rounded,square,pill,custom',
            'font_size' => 'required|integer|min:8|max:24',
            'padding_x' => 'required|integer|min:0|max:32',
            'padding_y' => 'required|integer|min:0|max:32',
            'border_radius' => 'required|integer|min:0|max:50',
            'show_icon' => 'boolean',
            'animated' => 'boolean',
            'animation_type' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'priority' => 'required|integer|min:0|max:100',
            'max_display_count' => 'nullable|integer|min:1',
            'auto_assign' => 'boolean',
            'assignment_rules' => 'nullable|array',
            'display_conditions' => 'nullable|array',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        try {
            $badge = ProductBadge::create($validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Badge created successfully',
                    'badge' => $badge,
                ]);
            }

            return redirect()->route('admin.products.badges.index')
                ->with('success', 'Badge created successfully');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create badge: ' . $e->getMessage(),
                ], 500);
            }

            return back()->withErrors(['error' => 'Failed to create badge: ' . $e->getMessage()]);
        }
    }

    /**
     * Show badge edit form.
     *
     * @param  ProductBadge  $badge
     * @return \Illuminate\View\View
     */
    public function edit(ProductBadge $badge)
    {
        return view('admin.products.badges.edit', compact('badge'));
    }

    /**
     * Update badge.
     *
     * @param  Request  $request
     * @param  ProductBadge  $badge
     * @return JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, ProductBadge $badge)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:lunar_product_badges,name,' . $badge->id,
            'handle' => 'nullable|string|max:255|unique:lunar_product_badges,handle,' . $badge->id,
            'type' => 'required|in:new,sale,hot,limited,exclusive,custom',
            'description' => 'nullable|string',
            'label' => 'nullable|string|max:255',
            'color' => 'required|string|max:7',
            'background_color' => 'required|string|max:7',
            'border_color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:255',
            'position' => 'required|in:top-left,top-right,bottom-left,bottom-right,center',
            'style' => 'required|in:rounded,square,pill,custom',
            'font_size' => 'required|integer|min:8|max:24',
            'padding_x' => 'required|integer|min:0|max:32',
            'padding_y' => 'required|integer|min:0|max:32',
            'border_radius' => 'required|integer|min:0|max:50',
            'show_icon' => 'boolean',
            'animated' => 'boolean',
            'animation_type' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'priority' => 'required|integer|min:0|max:100',
            'max_display_count' => 'nullable|integer|min:1',
            'auto_assign' => 'boolean',
            'assignment_rules' => 'nullable|array',
            'display_conditions' => 'nullable|array',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
        ]);

        try {
            $badge->update($validated);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Badge updated successfully',
                    'badge' => $badge->fresh(),
                ]);
            }

            return redirect()->route('admin.products.badges.index')
                ->with('success', 'Badge updated successfully');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update badge: ' . $e->getMessage(),
                ], 500);
            }

            return back()->withErrors(['error' => 'Failed to update badge: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete badge.
     *
     * @param  ProductBadge  $badge
     * @return JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroy(ProductBadge $badge)
    {
        try {
            $badge->delete();

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Badge deleted successfully',
                ]);
            }

            return redirect()->route('admin.products.badges.index')
                ->with('success', 'Badge deleted successfully');
        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete badge: ' . $e->getMessage(),
                ], 500);
            }

            return back()->withErrors(['error' => 'Failed to delete badge: ' . $e->getMessage()]);
        }
    }

    /**
     * Assign badge to product.
     *
     * @param  Request  $request
     * @param  Product  $product
     * @return JsonResponse
     */
    public function assignToProduct(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'badge_id' => 'required|exists:lunar_product_badges,id',
            'expires_at' => 'nullable|date',
            'position' => 'nullable|string',
            'priority' => 'nullable|integer',
        ]);

        $badge = ProductBadge::findOrFail($validated['badge_id']);

        $this->badgeService->assignBadge($product, $badge, [
            'expires_at' => $validated['expires_at'] ?? null,
            'position' => $validated['position'] ?? null,
            'priority' => $validated['priority'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Badge assigned successfully',
        ]);
    }

    /**
     * Remove badge from product.
     *
     * @param  Request  $request
     * @param  Product  $product
     * @return JsonResponse
     */
    public function removeFromProduct(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'badge_id' => 'required|exists:lunar_product_badges,id',
        ]);

        $badge = ProductBadge::findOrFail($validated['badge_id']);

        $this->badgeService->removeBadge($product, $badge);

        return response()->json([
            'success' => true,
            'message' => 'Badge removed successfully',
        ]);
    }

    /**
     * Process auto-assignment for all products.
     *
     * @return JsonResponse
     */
    public function processAutoAssignment(): JsonResponse
    {
        try {
            $processed = $this->badgeService->processAutoAssignment();

            return response()->json([
                'success' => true,
                'message' => "Processed {$processed} products",
                'processed' => $processed,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process auto-assignment: ' . $e->getMessage(),
            ], 500);
        }
    }
}


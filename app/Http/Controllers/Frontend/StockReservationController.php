<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Lunar\Models\ProductVariant;

/**
 * Controller for stock reservations during checkout.
 */
class StockReservationController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Reserve stock for cart/checkout.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function reserve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_variant_id' => 'required|exists:lunar_product_variants,id',
            'quantity' => 'required|integer|min:1',
            'warehouse_id' => 'nullable|exists:lunar_warehouses,id',
            'expiration_minutes' => 'integer|min:1|max:60',
        ]);

        $variant = ProductVariant::findOrFail($validated['product_variant_id']);

        try {
            $reservation = $this->inventoryService->reserveStock(
                $variant,
                $validated['quantity'],
                'Cart',
                null,
                $validated['warehouse_id'] ?? null,
                $validated['expiration_minutes'] ?? 15
            );

            return response()->json([
                'message' => 'Stock reserved successfully',
                'reservation' => $reservation,
                'expires_at' => $reservation->expires_at->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Release reserved stock.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function release(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reservation_id' => 'required|exists:lunar_stock_reservations,id',
        ]);

        $this->inventoryService->releaseReservedStock($validated['reservation_id']);

        return response()->json([
            'message' => 'Stock reservation released',
        ]);
    }
}


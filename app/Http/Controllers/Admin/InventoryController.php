<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryLevel;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Lunar\Models\ProductVariant;

/**
 * Admin controller for inventory management.
 */
class InventoryController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Display inventory levels.
     *
     * @param  Request  $request
     * @return \Illuminate\View\View|JsonResponse
     */
    public function index(Request $request)
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $query = InventoryLevel::with(['productVariant.product', 'warehouse']);

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->get('warehouse_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('low_stock')) {
            $query->lowStock();
        }

        $inventoryLevels = $query->paginate(50);

        if ($request->wantsJson()) {
            return response()->json($inventoryLevels);
        }

        $warehouses = Warehouse::active()->get();

        return view('admin.inventory.index', compact('inventoryLevels', 'warehouses'));
    }

    /**
     * Adjust inventory.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function adjust(Request $request): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'product_variant_id' => 'required|exists:product_variants,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|integer',
            'note' => 'nullable|string|max:500',
        ]);

        $variant = ProductVariant::findOrFail($validated['product_variant_id']);

        $level = $this->inventoryService->adjustInventory(
            $variant,
            $validated['warehouse_id'],
            $validated['quantity'],
            $validated['note'] ?? '',
            auth('staff')->id()
        );

        return response()->json([
            'message' => 'Inventory adjusted successfully',
            'inventory_level' => $level->load(['productVariant.product', 'warehouse']),
        ]);
    }

    /**
     * Transfer stock between warehouses.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function transfer(Request $request): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'product_variant_id' => 'required|exists:product_variants,id',
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'quantity' => 'required|integer|min:1',
            'note' => 'nullable|string|max:500',
        ]);

        $variant = ProductVariant::findOrFail($validated['product_variant_id']);

        try {
            $result = $this->inventoryService->transferStock(
                $variant,
                $validated['from_warehouse_id'],
                $validated['to_warehouse_id'],
                $validated['quantity'],
                $validated['note'] ?? '',
                auth('staff')->id()
            );

            return response()->json([
                'message' => 'Stock transferred successfully',
                'from_level' => $result['from_level']->load(['productVariant.product', 'warehouse']),
                'to_level' => $result['to_level']->load(['productVariant.product', 'warehouse']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get purchase order suggestions.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function purchaseOrderSuggestions(Request $request): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $warehouseId = $request->get('warehouse_id');

        $suggestions = $this->inventoryService->getPurchaseOrderSuggestions($warehouseId);

        return response()->json([
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * Check availability.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
            'warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        $variant = ProductVariant::findOrFail($validated['product_variant_id']);

        $availability = $this->inventoryService->checkAvailability(
            $variant,
            $validated['quantity'],
            $validated['warehouse_id'] ?? null
        );

        return response()->json($availability);
    }

    /**
     * Handle barcode scan for quick stock updates.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function barcodeScan(Request $request): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $validated = $request->validate([
            'barcode' => 'required|string',
        ]);

        // Find product variant by barcode
        $variant = ProductVariant::where('barcode', $validated['barcode'])
            ->orWhere('sku', $validated['barcode'])
            ->with('product')
            ->first();

        if (!$variant) {
            return response()->json([
                'message' => 'Product not found',
            ], 404);
        }

        // Get inventory levels
        $levels = InventoryLevel::where('product_variant_id', $variant->id)
            ->with('warehouse')
            ->get();

        return response()->json([
            'product_variant_id' => $variant->id,
            'product_name' => $variant->product->translateAttribute('name'),
            'sku' => $variant->sku,
            'barcode' => $variant->barcode,
            'inventory_levels' => $levels->map(function ($level) {
                return [
                    'warehouse_id' => $level->warehouse_id,
                    'warehouse_name' => $level->warehouse->name,
                    'quantity' => $level->quantity,
                    'available_quantity' => $level->available_quantity,
                    'reserved_quantity' => $level->reserved_quantity,
                    'status' => $level->status,
                ];
            }),
        ]);
    }
}

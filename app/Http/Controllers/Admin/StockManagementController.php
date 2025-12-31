<?php

namespace App\Http\Controllers\Admin;

use App\Filament\Resources\InventoryLevelResource;
use App\Http\Controllers\Controller;
use App\Models\InventoryLevel;
use App\Models\LowStockAlert;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Lunar\Models\ProductVariant;

/**
 * Admin controller for stock management.
 */
class StockManagementController extends Controller
{
    public function __construct(
        protected StockService $stockService
    ) {}

    /**
     * Display stock management dashboard.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function index(Request $request)
    {
        // Prefer Filament for the admin UI.
        return redirect()->route('filament.admin.resources.inventory-levels.index', $request->query());
    }

    /**
     * Show stock details for a variant.
     *
     * @param  ProductVariant  $variant
     * @return RedirectResponse
     */
    public function show(ProductVariant $variant): RedirectResponse
    {
        // Prefer Filament for the admin UI. Use table search to narrow to the SKU.
        $slug = InventoryLevelResource::getSlug();

        return redirect()->route("filament.admin.resources.{$slug}.index", [
            'tableSearch' => (string) $variant->sku,
        ]);
    }

    /**
     * Adjust stock level.
     *
     * @param  Request  $request
     * @param  ProductVariant  $variant
     * @return JsonResponse
     */
    public function adjustStock(Request $request, ProductVariant $variant): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|integer',
            'reason' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $inventoryLevel = $this->stockService->adjustStock(
            $variant,
            $validated['warehouse_id'],
            $validated['quantity'],
            $validated['reason'] ?? 'Manual adjustment',
            $validated['notes'] ?? null
        );

        return response()->json([
            'message' => 'Stock adjusted successfully',
            'inventory_level' => $inventoryLevel,
        ]);
    }

    /**
     * Transfer stock between warehouses.
     *
     * @param  Request  $request
     * @param  ProductVariant  $variant
     * @return JsonResponse
     */
    public function transferStock(Request $request, ProductVariant $variant): JsonResponse
    {
        $validated = $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ]);

        $success = $this->stockService->transferStock(
            $variant,
            $validated['from_warehouse_id'],
            $validated['to_warehouse_id'],
            $validated['quantity'],
            $validated['notes'] ?? null
        );

        if (!$success) {
            return response()->json([
                'message' => 'Insufficient stock in source warehouse',
            ], 422);
        }

        return response()->json([
            'message' => 'Stock transferred successfully',
        ]);
    }

    /**
     * Get stock movements.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function movements(Request $request): JsonResponse
    {
        $query = StockMovement::with(['productVariant.product', 'warehouse', 'creator']);

        if ($request->has('variant_id')) {
            $query->where('product_variant_id', $request->get('variant_id'));
        }

        if ($request->has('warehouse_id')) {
            $query->where('warehouse_id', $request->get('warehouse_id'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->get('type'));
        }

        $movements = $query->orderByDesc('movement_date')->paginate(50);

        return response()->json($movements);
    }

    /**
     * Resolve low stock alert.
     *
     * @param  LowStockAlert  $alert
     * @return JsonResponse
     */
    public function resolveAlert(LowStockAlert $alert): JsonResponse
    {
        $alert->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            // NOTE: `resolved_by` references the `users` table, not `staff`. If no web user is present, this stays null.
            'resolved_by' => auth('web')->id(),
        ]);

        return response()->json([
            'message' => 'Alert resolved successfully',
        ]);
    }

    /**
     * Get stock statistics.
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_warehouses' => Warehouse::active()->count(),
            'total_variants' => ProductVariant::count(),
            'low_stock_items' => InventoryLevel::lowStock()->count(),
            'out_of_stock_items' => InventoryLevel::outOfStock()->count(),
            'active_reservations' => \App\Models\StockReservation::active()->count(),
            'unresolved_alerts' => LowStockAlert::unresolved()->count(),
        ];

        return response()->json($stats);
    }
}


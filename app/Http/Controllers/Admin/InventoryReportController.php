<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryLevel;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Admin controller for inventory reports.
 */
class InventoryReportController extends Controller
{
    /**
     * Get stock valuation report.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function stockValuation(Request $request): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $warehouseId = $request->get('warehouse_id');

        $query = InventoryLevel::with(['productVariant.product', 'warehouse'])
            ->where('quantity', '>', 0);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $levels = $query->get();

        $totalValue = 0;
        $items = [];

        foreach ($levels as $level) {
            $costPrice = $level->productVariant->cost_price ?? 0;
            $value = $level->quantity * $costPrice;
            $totalValue += $value;

            $items[] = [
                'product_variant_id' => $level->product_variant_id,
                'product_name' => $level->productVariant->product->translateAttribute('name'),
                'variant_sku' => $level->productVariant->sku,
                'warehouse_name' => $level->warehouse->name,
                'quantity' => $level->quantity,
                'cost_price' => $costPrice,
                'total_value' => $value,
            ];
        }

        return response()->json([
            'total_value' => $totalValue,
            'item_count' => count($items),
            'items' => $items,
        ]);
    }

    /**
     * Get inventory turnover report.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function inventoryTurnover(Request $request): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $days = (int) $request->get('days', 30);
        $warehouseId = $request->get('warehouse_id');

        $query = InventoryTransaction::whereIn('type', ['sale', 'return'])
            ->where('created_at', '>=', now()->subDays($days));

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $transactions = $query->with(['productVariant.product', 'warehouse'])
            ->get()
            ->groupBy('product_variant_id');

        $turnover = [];

        foreach ($transactions as $variantId => $variantTransactions) {
            $sales = $variantTransactions->where('type', 'sale')->sum('quantity');
            $returns = $variantTransactions->where('type', 'return')->sum('quantity');
            $netSales = abs($sales) - abs($returns);

            $variant = $variantTransactions->first()->productVariant;
            $currentStock = InventoryLevel::where('product_variant_id', $variantId)
                ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
                ->sum('quantity');

            $turnoverRate = $currentStock > 0 ? ($netSales / $currentStock) * (365 / $days) : 0;

            $turnover[] = [
                'product_variant_id' => $variantId,
                'product_name' => $variant->product->translateAttribute('name'),
                'variant_sku' => $variant->sku,
                'current_stock' => $currentStock,
                'sales_quantity' => abs($sales),
                'returns_quantity' => abs($returns),
                'net_sales' => $netSales,
                'turnover_rate' => round($turnoverRate, 2),
                'days_to_sell' => $turnoverRate > 0 ? round(365 / $turnoverRate, 1) : null,
            ];
        }

        usort($turnover, fn($a, $b) => $b['turnover_rate'] <=> $a['turnover_rate']);

        return response()->json([
            'period_days' => $days,
            'items' => $turnover,
        ]);
    }

    /**
     * Get dead stock report (items with no sales in X days).
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function deadStock(Request $request): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $days = (int) $request->get('days', 90);
        $warehouseId = $request->get('warehouse_id');

        $query = InventoryLevel::with(['productVariant.product', 'warehouse'])
            ->where('quantity', '>', 0);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $levels = $query->get();

        $deadStock = [];

        foreach ($levels as $level) {
            $lastSale = InventoryTransaction::where('product_variant_id', $level->product_variant_id)
                ->where('warehouse_id', $level->warehouse_id)
                ->where('type', 'sale')
                ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
                ->orderByDesc('created_at')
                ->first();

            if (!$lastSale || $lastSale->created_at->lt(now()->subDays($days))) {
                $costPrice = $level->productVariant->cost_price ?? 0;
                $value = $level->quantity * $costPrice;

                $deadStock[] = [
                    'product_variant_id' => $level->product_variant_id,
                    'product_name' => $level->productVariant->product->translateAttribute('name'),
                    'variant_sku' => $level->productVariant->sku,
                    'warehouse_name' => $level->warehouse->name,
                    'quantity' => $level->quantity,
                    'last_sale_date' => $lastSale?->created_at?->format('Y-m-d'),
                    'days_since_sale' => $lastSale ? $lastSale->created_at->diffInDays(now()) : null,
                    'value' => $value,
                ];
            }
        }

        usort($deadStock, fn($a, $b) => ($b['days_since_sale'] ?? 0) <=> ($a['days_since_sale'] ?? 0));

        return response()->json([
            'dead_stock_items' => $deadStock,
            'total_value' => array_sum(array_column($deadStock, 'value')),
        ]);
    }

    /**
     * Get fast-moving items report.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function fastMovingItems(Request $request): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $days = (int) $request->get('days', 30);
        $limit = (int) $request->get('limit', 50);
        $warehouseId = $request->get('warehouse_id');

        $transactions = InventoryTransaction::where('type', 'sale')
            ->where('created_at', '>=', now()->subDays($days))
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->select('product_variant_id', DB::raw('SUM(ABS(quantity)) as total_sold'))
            ->groupBy('product_variant_id')
            ->orderByDesc('total_sold')
            ->limit($limit)
            ->with(['productVariant.product'])
            ->get();

        $items = [];

        foreach ($transactions as $transaction) {
            $items[] = [
                'product_variant_id' => $transaction->product_variant_id,
                'product_name' => $transaction->productVariant->product->translateAttribute('name'),
                'variant_sku' => $transaction->productVariant->sku,
                'total_sold' => $transaction->total_sold,
                'average_daily_sales' => round($transaction->total_sold / $days, 2),
            ];
        }

        return response()->json([
            'period_days' => $days,
            'items' => $items,
        ]);
    }
}

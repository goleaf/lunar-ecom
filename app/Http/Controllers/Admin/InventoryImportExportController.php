<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryLevel;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Lunar\Models\ProductVariant;

/**
 * Controller for inventory import/export.
 */
class InventoryImportExportController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Export inventory to CSV.
     *
     * @param  Request  $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $warehouseId = $request->get('warehouse_id');

        $query = InventoryLevel::with(['productVariant.product', 'warehouse']);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $levels = $query->get();

        $filename = 'inventory_export_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($levels) {
            $file = fopen('php://output', 'w');

            // Headers
            fputcsv($file, [
                'SKU',
                'Product Name',
                'Warehouse Code',
                'Warehouse Name',
                'Quantity',
                'Reserved Quantity',
                'Available Quantity',
                'Incoming Quantity',
                'Reorder Point',
                'Reorder Quantity',
                'Status',
            ]);

            // Data
            foreach ($levels as $level) {
                fputcsv($file, [
                    $level->productVariant->sku ?? '',
                    $level->productVariant->product->translateAttribute('name') ?? '',
                    $level->warehouse->code ?? '',
                    $level->warehouse->name ?? '',
                    $level->quantity,
                    $level->reserved_quantity,
                    $level->available_quantity,
                    $level->incoming_quantity,
                    $level->reorder_point,
                    $level->reorder_quantity,
                    $level->status,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import inventory from CSV.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function import(Request $request): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();
        $data = array_map('str_getcsv', file($path));

        // Remove header row
        array_shift($data);

        $imported = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($data as $row) {
                if (count($row) < 4) {
                    continue;
                }

                $sku = trim($row[0]);
                $warehouseCode = trim($row[2] ?? '');
                $quantity = (int) ($row[4] ?? 0);

                if (empty($sku) || empty($warehouseCode)) {
                    continue;
                }

                $variant = ProductVariant::where('sku', $sku)->first();
                if (!$variant) {
                    $errors[] = "Variant with SKU '{$sku}' not found";
                    continue;
                }

                $warehouse = \App\Models\Warehouse::where('code', $warehouseCode)->first();
                if (!$warehouse) {
                    $errors[] = "Warehouse with code '{$warehouseCode}' not found";
                    continue;
                }

                // Adjust inventory
                $this->inventoryService->adjustInventory(
                    $variant,
                    $warehouse->id,
                    $quantity,
                    'Bulk import from CSV',
                    auth('staff')->id()
                );

                $imported++;
            }

            DB::commit();

            return response()->json([
                'message' => "Imported {$imported} inventory level(s)",
                'imported' => $imported,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Import failed: ' . $e->getMessage(),
                'errors' => $errors,
            ], 422);
        }
    }
}

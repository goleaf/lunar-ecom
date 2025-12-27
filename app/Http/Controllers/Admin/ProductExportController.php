<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Exports\ProductExport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Admin controller for product export operations.
 */
class ProductExportController extends Controller
{
    /**
     * Export products.
     *
     * @param  Request  $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request)
    {
        $validated = $request->validate([
            'columns' => 'nullable|array',
            'category_id' => 'nullable|integer|exists:collections,id',
            'brand_id' => 'nullable|integer|exists:brands,id',
            'stock_status' => 'nullable|in:in_stock,out_of_stock,low_stock',
            'format' => 'nullable|in:xlsx,csv',
        ]);

        $columns = $validated['columns'] ?? [];
        $categoryId = $validated['category_id'] ?? null;
        $brandId = $validated['brand_id'] ?? null;
        $stockStatus = $validated['stock_status'] ?? null;
        $format = $validated['format'] ?? 'xlsx';

        $export = new ProductExport($columns, [], $categoryId, $brandId, $stockStatus);

        $filename = 'products_export_' . date('Y-m-d_His') . '.' . $format;

        return Excel::download($export, $filename);
    }

    /**
     * Get available export columns.
     *
     * @return JsonResponse
     */
    public function columns(): JsonResponse
    {
        return response()->json([
            'columns' => [
                ['value' => 'sku', 'label' => 'SKU'],
                ['value' => 'name', 'label' => 'Name'],
                ['value' => 'description', 'label' => 'Description'],
                ['value' => 'price', 'label' => 'Price'],
                ['value' => 'compare_at_price', 'label' => 'Compare At Price'],
                ['value' => 'category_path', 'label' => 'Category Path'],
                ['value' => 'brand', 'label' => 'Brand'],
                ['value' => 'images', 'label' => 'Images (URLs)'],
                ['value' => 'attributes', 'label' => 'Attributes (JSON)'],
                ['value' => 'stock_quantity', 'label' => 'Stock Quantity'],
                ['value' => 'weight', 'label' => 'Weight (grams)'],
                ['value' => 'length', 'label' => 'Length (cm)'],
                ['value' => 'width', 'label' => 'Width (cm)'],
                ['value' => 'height', 'label' => 'Height (cm)'],
                ['value' => 'status', 'label' => 'Status'],
                ['value' => 'created_at', 'label' => 'Created At'],
                ['value' => 'updated_at', 'label' => 'Updated At'],
            ],
        ]);
    }
}


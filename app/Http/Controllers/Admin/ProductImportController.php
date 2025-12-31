<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Filament\Resources\ProductImportResource;
use App\Imports\ProductImport;
use App\Models\ProductImport as ProductImportModel;
use App\Services\ProductImportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Admin controller for product import operations.
 */
class ProductImportController extends Controller
{
    public function __construct(
        protected ProductImportService $importService
    ) {}

    /**
     * Display import interface.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function index()
    {
        // Prefer Filament for the admin UI.
        return redirect()->route('filament.admin.resources.' . ProductImportResource::getSlug() . '.index');
    }

    /**
     * Upload and preview import file.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240', // 10MB max
        ]);

        try {
            $file = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('imports', $filename, 'local');

            // Read first 10 rows for preview
            $previewData = Excel::toArray([], $file)[0] ?? [];
            $headers = array_shift($previewData); // Remove header row
            $previewRows = array_slice($previewData, 0, 10);

            // Validate preview rows
            $validationErrors = [];
            foreach ($previewRows as $index => $row) {
                $rowData = array_combine($headers, $row);
                $errors = $this->validatePreviewRow($rowData, $index + 2); // +2 for header and 0-index
                if (!empty($errors)) {
                    $validationErrors[$index + 2] = $errors;
                }
            }

            return response()->json([
                'success' => true,
                'file_path' => $path,
                'filename' => $file->getClientOriginalName(),
                'file_type' => $file->getClientOriginalExtension(),
                'headers' => $headers,
                'preview_rows' => $previewRows,
                'validation_errors' => $validationErrors,
                'total_rows' => count($previewData),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process file: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Start import process.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function import(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file_path' => 'required|string',
            'filename' => 'required|string',
            'file_type' => 'required|in:csv,xlsx,xls',
            'field_mapping' => 'nullable|array',
            'import_options' => 'nullable|array',
        ]);

        try {
            // Process field mapping - convert from UI format to import format
            $fieldMapping = [];
            if (!empty($validated['field_mapping'])) {
                // UI format: {'Product SKU': 'sku', 'Product Name': 'name'}
                // Convert to: {'sku': 'Product SKU', 'name': 'Product Name'}
                foreach ($validated['field_mapping'] as $sourceColumn => $targetField) {
                    if (!empty($targetField)) {
                        $fieldMapping[$targetField] = $sourceColumn;
                    }
                }
            }

            // Create import record
            $import = ProductImportModel::create([
                'original_filename' => $validated['filename'],
                'file_name' => basename($validated['file_path']),
                'file_path' => $validated['file_path'],
                'file_type' => $validated['file_type'],
                'status' => 'pending',
                'field_mapping' => $fieldMapping,
                'options' => $validated['import_options'] ?? ['action' => 'create'],
                'user_id' => auth('web')->id(),
            ]);

            // Get total row count
            $filePath = Storage::disk('local')->path($validated['file_path']);
            $totalRows = $this->countRows($filePath, $validated['file_type']);
            $fileSize = Storage::disk('local')->exists($validated['file_path'])
                ? Storage::disk('local')->size($validated['file_path'])
                : null;

            $import->update([
                'total_rows' => $totalRows,
                'file_size' => $fileSize,
            ]);

            // Dispatch import job (queue it for background processing)
            Excel::queueImport(
                new ProductImport($import, $fieldMapping, $validated['import_options'] ?? []),
                $validated['file_path'],
                'local'
            );

            return response()->json([
                'success' => true,
                'import_id' => $import->id,
                'message' => 'Import started successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start import: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get import status and progress.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function status(int $id): JsonResponse
    {
        $import = ProductImportModel::findOrFail($id);

        return response()->json([
            'id' => $import->id,
            'status' => $import->status,
            'total_rows' => $import->total_rows,
            'processed_rows' => $import->processed_rows,
            'successful_rows' => $import->successful_rows,
            'failed_rows' => $import->failed_rows,
            'skipped_rows' => $import->skipped_rows,
            'progress_percentage' => $import->getProgressPercentage(),
            'started_at' => $import->started_at?->toIso8601String(),
            'completed_at' => $import->completed_at?->toIso8601String(),
            'error_message' => $import->error_message,
        ]);
    }

    /**
     * Get import report.
     *
     * @param  int  $id
     * @return JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function report(int $id)
    {
        $import = ProductImportModel::with('rows')->findOrFail($id);

        $failedRows = $import->rows()
            ->where('status', 'failed')
            ->orderBy('row_number')
            ->get()
            ->map(function ($row) {
                return [
                    'row_number' => $row->row_number,
                    'sku' => $row->sku,
                    'errors' => $row->validation_errors,
                    'error_message' => $row->error_message,
                ];
            });

        if (request()->wantsJson()) {
            return response()->json([
                'import' => $import,
                'report' => $import->import_report,
                'failed_rows' => $failedRows,
            ]);
        }

        // Prefer Filament for the admin UI.
        return redirect()->route('filament.admin.resources.' . ProductImportResource::getSlug() . '.view', [
            'record' => $import->getKey(),
        ]);
    }

    /**
     * Rollback an import.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function rollback(Request $request, int $id): JsonResponse
    {
        $import = ProductImportModel::findOrFail($id);

        // NOTE: `rolled_back_by` references the `users` table, not `staff`.
        $result = $this->importService->rollback($import, auth('web')->id());

        return response()->json($result);
    }

    /**
     * Download import template.
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadTemplate()
    {
        $templatePath = resource_path('templates/product_import_template.xlsx');
        
        if (!file_exists($templatePath)) {
            // Generate template on the fly
            $headers = [
                'SKU',
                'Name',
                'Description',
                'Price',
                'Compare At Price',
                'Category Path',
                'Brand',
                'Images (URLs)',
                'Attributes (JSON)',
                'Stock Quantity',
                'Weight (grams)',
                'Length (cm)',
                'Width (cm)',
                'Height (cm)',
            ];

            $data = []; // Empty data for template
            Excel::store(new \App\Exports\ProductTemplateExport($headers, $data), 'templates/product_import_template.xlsx', 'local');
            $templatePath = Storage::disk('local')->path('templates/product_import_template.xlsx');
        }

        return response()->download($templatePath, 'product_import_template.xlsx');
    }

    /**
     * Validate preview row.
     */
    protected function validatePreviewRow(array $row, int $rowNumber): array
    {
        $errors = [];

        if (empty($row['sku'] ?? '')) {
            $errors['sku'] = 'SKU is required';
        }

        if (isset($row['price']) && !empty($row['price']) && !is_numeric($row['price'])) {
            $errors['price'] = 'Price must be numeric';
        }

        return $errors;
    }

    /**
     * Count rows in file.
     */
    protected function countRows(string $filePath, string $fileType): int
    {
        try {
            $data = Excel::toArray([], $filePath);
            return count($data[0] ?? []) - 1; // Subtract header row
        } catch (\Exception $e) {
            return 0;
        }
    }
}

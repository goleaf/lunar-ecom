<?php

namespace App\Imports;

use App\Models\ProductImport as ProductImportModel;
use App\Models\ProductImportRow;
use App\Services\ProductImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithProgressBar;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Events\ImportFailed;

/**
 * Product Import class for processing product data from Excel/CSV files.
 */
class ProductImport implements 
    ToCollection, 
    WithChunkReading, 
    WithBatchInserts, 
    WithHeadingRow, 
    WithValidation,
    WithProgressBar,
    ShouldQueue,
    SkipsOnError,
    SkipsOnFailure,
    WithEvents
{
    use Importable, SkipsErrors, SkipsFailures, RegistersEventListeners;

    public ProductImportModel $import;
    public array $fieldMapping;
    public array $importOptions;

    /**
     * Create a new import instance.
     */
    public function __construct(ProductImportModel $import, array $fieldMapping = [], array $importOptions = [])
    {
        $this->import = $import;
        $this->fieldMapping = $fieldMapping;
        $this->importOptions = $importOptions;
    }

    /**
     * Process the collection of rows.
     */
    public function collection(Collection $rows)
    {
        $service = app(ProductImportService::class);
        
        // Refresh import model to get latest data
        $this->import->refresh();
        
        foreach ($rows as $index => $row) {
            try {
                // Calculate actual row number (accounting for header and chunk offset)
                // This is approximate - for exact row numbers, we'd need to track chunk start
                $rowNumber = $this->import->processed_rows + $index + 2; // +2 for header and 1-indexed
                
                // Convert row to array (handle both array and object)
                $rowArray = $row instanceof Collection ? $row->toArray() : (array) $row;
                
                // Map fields according to field mapping
                $mappedData = $this->mapFields($rowArray);
                
                // Validate row data
                $validationErrors = $this->validateRow($mappedData, $rowNumber);
                
                // Create import row record
                $importRow = ProductImportRow::create([
                    'product_import_id' => $this->import->id,
                    'row_number' => $rowNumber,
                    'status' => 'pending',
                    'raw_data' => $rowArray,
                    'mapped_data' => $mappedData,
                    'validation_errors' => $validationErrors,
                    'sku' => $mappedData['sku'] ?? null,
                ]);

                // If validation passed, process the row
                if (empty($validationErrors)) {
                    $result = $service->processRow($mappedData, $this->importOptions);
                    
                    if ($result['success']) {
                        // Update mapped_data with action for rollback tracking
                        $mappedDataWithAction = array_merge($mappedData, ['action' => $result['action'] ?? 'created']);
                        
                        $importRow->update([
                            'status' => 'success',
                            'product_id' => $result['product_id'],
                            'success_message' => $result['message'],
                            'mapped_data' => $mappedDataWithAction,
                        ]);
                        
                        $this->import->increment('successful_rows');
                    } else {
                        $importRow->update([
                            'status' => 'failed',
                            'error_message' => $result['message'],
                        ]);
                        
                        $this->import->increment('failed_rows');
                    }
                } else {
                    $importRow->update([
                        'status' => 'failed',
                        'error_message' => 'Validation failed: ' . json_encode($validationErrors),
                    ]);
                    
                    $this->import->increment('failed_rows');
                }

                // Update processed count
                $this->import->increment('processed_rows');
                
            } catch (\Exception $e) {
                Log::error("Product import error at row {$rowNumber}: " . $e->getMessage());
                $this->import->increment('failed_rows');
                $this->import->increment('processed_rows');
            }
        }
    }

    /**
     * Map fields according to field mapping configuration.
     * Field mapping format: ['target_field' => 'source_column_name']
     */
    protected function mapFields(array $row): array
    {
        $mapped = [];
        
        // If field mapping is provided, use it
        if (!empty($this->fieldMapping)) {
            // Field mapping format: ['sku' => 'Product SKU', 'name' => 'Product Name']
            foreach ($this->fieldMapping as $targetField => $sourceColumn) {
                // Handle case-insensitive matching
                $sourceColumnLower = strtolower($sourceColumn);
                foreach ($row as $key => $value) {
                    if (strtolower($key) === $sourceColumnLower) {
                        $mapped[$targetField] = $value;
                        break;
                    }
                }
            }
        } else {
            // Default: assume column names match field names (case-insensitive)
            $defaultFields = [
                'sku', 'name', 'description', 'price', 'compare_at_price',
                'category_path', 'brand', 'images', 'attributes',
                'stock_quantity', 'weight', 'length', 'width', 'height'
            ];
            
            foreach ($defaultFields as $field) {
                $fieldLower = strtolower($field);
                foreach ($row as $key => $value) {
                    if (strtolower($key) === $fieldLower) {
                        $mapped[$field] = $value;
                        break;
                    }
                }
            }
        }

        return $mapped;
    }

    /**
     * Validate row data.
     */
    protected function validateRow(array $data, int $rowNumber): array
    {
        $errors = [];

        // Required fields
        if (empty($data['sku'])) {
            $errors['sku'] = 'SKU is required';
        }

        // Unique SKU check (if creating new)
        if (!empty($data['sku']) && ($this->importOptions['action'] ?? 'create') === 'create') {
            $exists = \Lunar\Models\Product::whereHas('variants', function ($q) use ($data) {
                $q->where('sku', $data['sku']);
            })->exists();
            
            if ($exists) {
                $errors['sku'] = 'SKU already exists';
            }
        }

        // Price validation
        if (isset($data['price']) && !is_numeric($data['price']) && $data['price'] !== '') {
            $errors['price'] = 'Price must be numeric';
        }

        // Images validation (URLs)
        if (isset($data['images']) && !empty($data['images'])) {
            $images = is_array($data['images']) ? $data['images'] : explode(',', $data['images']);
            foreach ($images as $image) {
                $image = trim($image);
                if (!empty($image) && !filter_var($image, FILTER_VALIDATE_URL) && !base64_decode($image, true)) {
                    $errors['images'] = 'Invalid image URL or base64 format';
                    break;
                }
            }
        }

        return $errors;
    }

    /**
     * Get validation rules.
     */
    public function rules(): array
    {
        return [
            'sku' => 'required',
            'name' => 'nullable|string|max:255',
            'price' => 'nullable|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Chunk size for processing.
     */
    public function chunkSize(): int
    {
        return 1000;
    }

    /**
     * Batch size for inserts.
     */
    public function batchSize(): int
    {
        return 1000;
    }

    /**
     * Handle before import event.
     */
    public static function beforeImport(BeforeImport $event)
    {
        $import = $event->getConcernable()->import;
        $import->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    /**
     * Handle after import event.
     */
    public static function afterImport(AfterImport $event)
    {
        $import = $event->getConcernable()->import;
        $import->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Generate import report
        $service = app(\App\Services\ProductImportService::class);
        $service->generateReport($import);
    }

    /**
     * Handle import failure event.
     */
    public static function importFailed(ImportFailed $event)
    {
        $import = $event->getConcernable()->import;
        $import->update([
            'status' => 'failed',
            'error_message' => $event->getException()->getMessage(),
            'completed_at' => now(),
        ]);
    }
}


<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ProductImport;
use App\Models\ProductImportError;
use App\Services\ProductImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Job to process product import.
 */
class ProcessProductImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     *
     * @param  ProductImport  $import
     */
    public function __construct(
        public ProductImport $import
    ) {}

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ProductImportService $service): void
    {
        // Mark as processing
        $this->import->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        try {
            $this->processImport();
        } catch (\Exception $e) {
            Log::error('Product import failed', [
                'import_id' => $this->import->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->import->update([
                'status' => 'failed',
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * Process the import file.
     *
     * @return void
     */
    protected function processImport(): void
    {
        $filePath = Storage::disk('local')->path($this->import->file_path);
        
        if (!file_exists($filePath)) {
            throw new \Exception('Import file not found.');
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \Exception('Could not open import file.');
        }

        // Read header row
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            throw new \Exception('Invalid CSV file: no header row found.');
        }

        // Normalize headers (trim, lowercase)
        $headers = array_map(function ($header) {
            return trim(strtolower($header));
        }, $headers);

        $fieldMapping = $this->import->field_mapping ?? [];
        $options = $this->import->options ?? [];
        $updateExisting = $options['update_existing'] ?? false;
        $skipErrors = $options['skip_errors'] ?? false;

        $rowNumber = 1; // Start at 1 (header is row 0)
        $successful = 0;
        $failed = 0;
        $errors = [];

        // Process each row
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            try {
                $this->processRow($row, $headers, $fieldMapping, $updateExisting);
                $successful++;
            } catch (\Exception $e) {
                $failed++;
                
                // Record error
                ProductImportError::create([
                    'import_id' => $this->import->id,
                    'row_number' => $rowNumber,
                    'field' => null,
                    'error_message' => $e->getMessage(),
                    'error_type' => $this->getErrorType($e),
                    'row_data' => array_combine($headers, $row),
                ]);

                $errors[] = [
                    'row' => $rowNumber,
                    'message' => $e->getMessage(),
                ];

                if (!$skipErrors) {
                    // Stop on first error if skip_errors is false
                    break;
                }
            }

            // Update progress every 10 rows
            if ($rowNumber % 10 === 0) {
                $this->import->update([
                    'processed_rows' => $rowNumber - 1,
                    'successful_rows' => $successful,
                    'failed_rows' => $failed,
                ]);
            }
        }

        fclose($handle);

        // Final update
        $this->import->update([
            'status' => 'completed',
            'processed_rows' => $rowNumber - 1,
            'successful_rows' => $successful,
            'failed_rows' => $failed,
            'completed_at' => now(),
            'error_summary' => $this->generateErrorSummary($errors),
        ]);
    }

    /**
     * Process a single row.
     *
     * @param  array  $row
     * @param  array  $headers
     * @param  array  $fieldMapping
     * @param  bool  $updateExisting
     * @return void
     */
    protected function processRow(array $row, array $headers, array $fieldMapping, bool $updateExisting): void
    {
        // Map CSV columns to product fields
        $data = [];
        foreach ($fieldMapping as $csvColumn => $productField) {
            $columnIndex = array_search(strtolower(trim($csvColumn)), $headers);
            if ($columnIndex !== false && isset($row[$columnIndex])) {
                $data[$productField] = trim($row[$columnIndex]);
            }
        }

        if (empty($data)) {
            throw new \Exception('No valid data found in row.');
        }

        DB::transaction(function () use ($data, $updateExisting) {
            // Find or create product
            $product = null;
            if (isset($data['sku']) && $updateExisting) {
                $product = Product::where('sku', $data['sku'])->first();
            }

            if (!$product) {
                // Create new product
                $product = $this->createProduct($data);
            } else {
                // Update existing product
                $this->updateProduct($product, $data);
            }

            // Handle categories
            if (isset($data['categories'])) {
                $this->attachCategories($product, $data['categories']);
            }

            // Handle collections
            if (isset($data['collections'])) {
                $this->attachCollections($product, $data['collections']);
            }

            // Handle variant
            if (isset($data['variant_sku']) || isset($data['variant_price']) || isset($data['variant_stock'])) {
                $this->createOrUpdateVariant($product, $data);
            }
        });
    }

    /**
     * Create a new product.
     *
     * @param  array  $data
     * @return Product
     */
    protected function createProduct(array $data): Product
    {
        // Required fields
        if (empty($data['name'])) {
            throw new \Exception('Product name is required.');
        }

        // Get or create product type
        $productTypeId = $data['product_type_id'] ?? $this->getDefaultProductTypeId();

        $product = Product::create([
            'product_type_id' => $productTypeId,
            'status' => $data['status'] ?? 'draft',
            'sku' => $data['sku'] ?? null,
            'barcode' => $data['barcode'] ?? null,
            'weight' => isset($data['weight']) ? (int)($data['weight'] * 1000) : null, // Convert kg to grams
            'length' => $data['length'] ?? null,
            'width' => $data['width'] ?? null,
            'height' => $data['height'] ?? null,
            'manufacturer_name' => $data['manufacturer_name'] ?? null,
            'warranty_period' => isset($data['warranty_period']) ? (int)$data['warranty_period'] : null,
            'condition' => $data['condition'] ?? null,
            'origin_country' => $data['origin_country'] ?? null,
        ]);

        // Set translatable fields using Lunar's attribute_data
        $defaultLanguage = \Lunar\Facades\Language::getDefault();
        $attributeData = $product->attribute_data ?? [];
        
        // Set name
        $nameAttribute = \Lunar\Models\Attribute::where('handle', 'name')->first();
        if ($nameAttribute) {
            $attributeData[$nameAttribute->handle] = [
                $defaultLanguage->code => $data['name'],
            ];
        }
        
        // Set description if provided
        if (isset($data['description'])) {
            $descriptionAttribute = \Lunar\Models\Attribute::where('handle', 'description')->first();
            if ($descriptionAttribute) {
                $attributeData[$descriptionAttribute->handle] = [
                    $defaultLanguage->code => $data['description'],
                ];
            }
        }
        
        $product->attribute_data = $attributeData;
        if (isset($data['meta_title'])) {
            $product->meta_title = $data['meta_title'];
        }
        if (isset($data['meta_description'])) {
            $product->meta_description = $data['meta_description'];
        }

        $product->save();

        // Set brand if provided
        if (isset($data['brand_id'])) {
            $product->brand_id = $data['brand_id'];
            $product->save();
        }

        return $product;
    }

    /**
     * Update existing product.
     *
     * @param  Product  $product
     * @param  array  $data
     * @return void
     */
    protected function updateProduct(Product $product, array $data): void
    {
        $updates = [];

        // Update translatable fields
        $defaultLanguage = \Lunar\Facades\Language::getDefault();
        $attributeData = $product->attribute_data ?? [];
        
        if (isset($data['name'])) {
            $nameAttribute = \Lunar\Models\Attribute::where('handle', 'name')->first();
            if ($nameAttribute) {
                $attributeData[$nameAttribute->handle] = [
                    $defaultLanguage->code => $data['name'],
                ];
            }
        }
        
        if (isset($data['description'])) {
            $descriptionAttribute = \Lunar\Models\Attribute::where('handle', 'description')->first();
            if ($descriptionAttribute) {
                $attributeData[$descriptionAttribute->handle] = [
                    $defaultLanguage->code => $data['description'],
                ];
            }
        }
        
        if (!empty($attributeData)) {
            $product->attribute_data = $attributeData;
        }

        if (isset($data['status'])) {
            $updates['status'] = $data['status'];
        }

        if (isset($data['barcode'])) {
            $updates['barcode'] = $data['barcode'];
        }

        if (isset($data['weight'])) {
            $updates['weight'] = (int)($data['weight'] * 1000);
        }

        if (isset($data['length'])) {
            $updates['length'] = $data['length'];
        }

        if (isset($data['width'])) {
            $updates['width'] = $data['width'];
        }

        if (isset($data['height'])) {
            $updates['height'] = $data['height'];
        }

        if (isset($data['manufacturer_name'])) {
            $updates['manufacturer_name'] = $data['manufacturer_name'];
        }

        if (isset($data['warranty_period'])) {
            $updates['warranty_period'] = (int)$data['warranty_period'];
        }

        if (isset($data['condition'])) {
            $updates['condition'] = $data['condition'];
        }

        if (isset($data['origin_country'])) {
            $updates['origin_country'] = $data['origin_country'];
        }

        if (isset($data['meta_title'])) {
            $updates['meta_title'] = $data['meta_title'];
        }

        if (isset($data['meta_description'])) {
            $updates['meta_description'] = $data['meta_description'];
        }

        if (isset($data['brand_id'])) {
            $updates['brand_id'] = $data['brand_id'];
        }

        if (!empty($updates)) {
            $product->update($updates);
            $product->save();
        }
    }

    /**
     * Create or update product variant.
     *
     * @param  Product  $product
     * @param  array  $data
     * @return void
     */
    protected function createOrUpdateVariant(Product $product, array $data): void
    {
        $variant = $product->variants()->where('sku', $data['variant_sku'] ?? null)->first();

        if (!$variant) {
            $variant = $product->variants()->create([
                'sku' => $data['variant_sku'] ?? null,
                'stock' => isset($data['variant_stock']) ? (int)$data['variant_stock'] : 0,
                'barcode' => $data['variant_barcode'] ?? null,
            ]);
        } else {
            $variant->update([
                'stock' => isset($data['variant_stock']) ? (int)$data['variant_stock'] : $variant->stock,
                'barcode' => $data['variant_barcode'] ?? $variant->barcode,
            ]);
        }

        // Set price if provided
        if (isset($data['variant_price'])) {
            $currency = \Lunar\Facades\Currency::getDefault();
            $price = (int)($data['variant_price'] * 100); // Convert to cents

            $variant->prices()->updateOrCreate(
                [
                    'currency_id' => $currency->id,
                    'price_type' => 'default',
                ],
                [
                    'price' => $price,
                ]
            );
        }
    }

    /**
     * Attach categories to product.
     *
     * @param  Product  $product
     * @param  string  $categoriesString
     * @return void
     */
    protected function attachCategories(Product $product, string $categoriesString): void
    {
        $categoryNames = array_map('trim', explode(',', $categoriesString));
        $categoryIds = [];

        foreach ($categoryNames as $categoryName) {
            $category = \App\Models\Category::whereRaw("JSON_EXTRACT(name, '$.en') = ?", [$categoryName])
                ->orWhere('slug', Str::slug($categoryName))
                ->first();

            if ($category) {
                $categoryIds[] = $category->id;
            }
        }

        if (!empty($categoryIds)) {
            $product->categories()->sync($categoryIds);
        }
    }

    /**
     * Attach collections to product.
     *
     * @param  Product  $product
     * @param  string  $collectionsString
     * @return void
     */
    protected function attachCollections(Product $product, string $collectionsString): void
    {
        $collectionNames = array_map('trim', explode(',', $collectionsString));
        $collectionIds = [];

        foreach ($collectionNames as $collectionName) {
            $collection = \Lunar\Models\Collection::where('name', $collectionName)
                ->orWhere('handle', Str::slug($collectionName))
                ->first();

            if ($collection) {
                $collectionIds[] = $collection->id;
            }
        }

        if (!empty($collectionIds)) {
            $product->collections()->sync($collectionIds);
        }
    }

    /**
     * Get default product type ID.
     *
     * @return int
     */
    protected function getDefaultProductTypeId(): int
    {
        $productType = \Lunar\Models\ProductType::first();
        
        if (!$productType) {
            throw new \Exception('No product type found. Please create a product type first.');
        }

        return $productType->id;
    }

    /**
     * Get error type from exception.
     *
     * @param  \Exception  $e
     * @return string
     */
    protected function getErrorType(\Exception $e): string
    {
        if (str_contains($e->getMessage(), 'validation')) {
            return 'validation';
        }

        if (str_contains($e->getMessage(), 'duplicate') || str_contains($e->getMessage(), 'unique')) {
            return 'duplicate';
        }

        if (str_contains($e->getMessage(), 'required') || str_contains($e->getMessage(), 'missing')) {
            return 'missing';
        }

        return 'other';
    }

    /**
     * Generate error summary.
     *
     * @param  array  $errors
     * @return array
     */
    protected function generateErrorSummary(array $errors): array
    {
        $summary = [
            'total' => count($errors),
            'by_type' => [],
        ];

        foreach ($errors as $error) {
            $type = $this->getErrorType(new \Exception($error['message']));
            $summary['by_type'][$type] = ($summary['by_type'][$type] ?? 0) + 1;
        }

        return $summary;
    }
}


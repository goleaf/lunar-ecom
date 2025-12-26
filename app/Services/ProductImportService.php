<?php

namespace App\Services;

use App\Models\ProductImport;
use App\Models\ProductImportRow;
use App\Models\ProductImportRollback;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Product;
use App\Models\ProductVariant;
use Lunar\Models\Collection;
use Lunar\Models\Brand;
use Lunar\FieldTypes\Text;
use Lunar\FieldTypes\TranslatedText;

/**
 * Service for handling product import operations.
 */
class ProductImportService
{
    /**
     * Process a single import row.
     *
     * @param  array  $data
     * @param  array  $options
     * @return array
     */
    public function processRow(array $data, array $options = []): array
    {
        try {
            $action = $options['action'] ?? 'create'; // create, update, create_or_update
            $product = null;

            // Find existing product by SKU if updating
            if (in_array($action, ['update', 'create_or_update']) && !empty($data['sku'])) {
                $variant = ProductVariant::where('sku', $data['sku'])->first();
                if ($variant) {
                    $product = $variant->product;
                }
            }

            // Create or update product
            $wasCreated = false;
            if (!$product && in_array($action, ['create', 'create_or_update'])) {
                $product = $this->createProduct($data);
                $wasCreated = true;
            } elseif ($product && in_array($action, ['update', 'create_or_update'])) {
                $this->updateProduct($product, $data);
                $wasCreated = false;
            } else {
                return [
                    'success' => false,
                    'message' => 'Product not found and create action not allowed',
                ];
            }

            // Handle images
            if (!empty($data['images'])) {
                $this->processImages($product, $data['images']);
            }

            // Handle categories
            if (!empty($data['category_path'])) {
                $this->processCategories($product, $data['category_path']);
            }

            // Handle brand
            if (!empty($data['brand'])) {
                $this->processBrand($product, $data['brand']);
            }

            // Handle attributes
            if (!empty($data['attributes'])) {
                $this->processAttributes($product, $data['attributes']);
            }

            return [
                'success' => true,
                'product_id' => $product->id,
                'message' => $wasCreated ? 'Product created' : 'Product updated',
                'action' => $wasCreated ? 'created' : 'updated',
            ];

        } catch (\Exception $e) {
            Log::error('Product import error: ' . $e->getMessage(), [
                'data' => $data,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a new product.
     */
    protected function createProduct(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            // Get or create product type
            $productType = \Lunar\Models\ProductType::first();
            if (!$productType) {
                throw new \Exception('No product type found. Please create a product type first.');
            }

            // Build attribute data using Lunar FieldTypes
            $defaultLanguage = \Lunar\Facades\Language::getDefault();
            $attributeData = collect();
            
            // Set name (TranslatedText)
            $nameValue = $data['name'] ?? 'Untitled Product';
            $attributeData['name'] = new \Lunar\FieldTypes\TranslatedText(collect([
                $defaultLanguage->code => new \Lunar\FieldTypes\Text($nameValue),
            ]));
            
            // Set description (Text)
            if (!empty($data['description'])) {
                $attributeData['description'] = new \Lunar\FieldTypes\Text($data['description']);
            }

            // Create product with attribute data
            $product = Product::create([
                'product_type_id' => $productType->id,
                'status' => $data['status'] ?? Product::STATUS_ACTIVE,
                'attribute_data' => $attributeData,
            ]);

            // Create variant
            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $data['sku'],
            ]);

            // Set price
            if (!empty($data['price'])) {
                $price = (int) ($data['price'] * 100); // Convert to cents
                $variant->prices()->create([
                    'price' => $price,
                    'currency_id' => \Lunar\Facades\Currency::getDefault()->id,
                ]);
            }

            // Set compare at price
            if (!empty($data['compare_at_price'])) {
                $variant->compare_price = (int) ($data['compare_at_price'] * 100);
                $variant->save();
            }

            // Set stock
            if (isset($data['stock_quantity'])) {
                $variant->stock = (int) $data['stock_quantity'];
                $variant->save();
            }

            // Set custom attributes
            if (isset($data['weight'])) {
                $product->weight = (int) $data['weight'];
            }
            if (isset($data['length'])) {
                $product->length = (float) $data['length'];
            }
            if (isset($data['width'])) {
                $product->width = (float) $data['width'];
            }
            if (isset($data['height'])) {
                $product->height = (float) $data['height'];
            }

            $product->save();

            return $product;
        });
    }

    /**
     * Update an existing product.
     */
    protected function updateProduct(Product $product, array $data): void
    {
        DB::transaction(function () use ($product, $data) {
            // Update attribute data
            $defaultLanguage = \Lunar\Facades\Language::getDefault();
            $attributeData = $product->attribute_data ?? collect();
            
            // Update name (TranslatedText)
            if (!empty($data['name'])) {
                $attributeData['name'] = new \Lunar\FieldTypes\TranslatedText(collect([
                    $defaultLanguage->code => new \Lunar\FieldTypes\Text($data['name']),
                ]));
            }
            
            // Update description (Text)
            if (!empty($data['description'])) {
                $attributeData['description'] = new \Lunar\FieldTypes\Text($data['description']);
            }
            
            if ($attributeData->isNotEmpty()) {
                $product->attribute_data = $attributeData;
            }

            // Update variant
            $variant = $product->variants->first();
            if ($variant) {
                if (!empty($data['sku'])) {
                    $variant->sku = $data['sku'];
                }

                // Update price
                if (isset($data['price'])) {
                    $price = (int) ($data['price'] * 100);
                    $existingPrice = $variant->prices()
                        ->where('currency_id', \Lunar\Facades\Currency::getDefault()->id)
                        ->first();

                    if ($existingPrice) {
                        $existingPrice->update(['price' => $price]);
                    } else {
                        $variant->prices()->create([
                            'price' => $price,
                            'currency_id' => \Lunar\Facades\Currency::getDefault()->id,
                        ]);
                    }
                }

                // Update compare at price
                if (isset($data['compare_at_price'])) {
                    $variant->compare_price = (int) ($data['compare_at_price'] * 100);
                }

                // Update stock
                if (isset($data['stock_quantity'])) {
                    $variant->stock = (int) $data['stock_quantity'];
                }

                $variant->save();
            }

            // Update custom attributes
            if (isset($data['weight'])) {
                $product->weight = (int) $data['weight'];
            }
            if (isset($data['length'])) {
                $product->length = (float) $data['length'];
            }
            if (isset($data['width'])) {
                $product->width = (float) $data['width'];
            }
            if (isset($data['height'])) {
                $product->height = (float) $data['height'];
            }

            $product->save();
        });
    }

    /**
     * Process and attach images to product.
     */
    protected function processImages(Product $product, string|array $images): void
    {
        $imageUrls = is_array($images) ? $images : explode(',', $images);
        $imageUrls = array_map('trim', $imageUrls);
        $imageUrls = array_filter($imageUrls);

        foreach ($imageUrls as $imageUrl) {
            try {
                $this->downloadAndAttachImage($product, $imageUrl);
            } catch (\Exception $e) {
                Log::warning("Failed to download image: {$imageUrl} - " . $e->getMessage());
            }
        }
    }

    /**
     * Download image from URL and attach to product.
     */
    protected function downloadAndAttachImage(Product $product, string $imageUrl): void
    {
        // Check if base64
        if (base64_decode($imageUrl, true)) {
            $this->attachBase64Image($product, $imageUrl);
            return;
        }

        try {
            // Use Media Library's addMediaFromUrl method
            $product->addMediaFromUrl($imageUrl)
                ->usingName($product->translateAttribute('name') . ' - Image')
                ->toMediaCollection('images');
        } catch (\Exception $e) {
            // Fallback to manual download if addMediaFromUrl fails
            $imageData = @file_get_contents($imageUrl);
            if ($imageData === false) {
                throw new \Exception("Failed to download image from URL: {$imageUrl}");
            }

            // Create temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'product_import_');
            file_put_contents($tempFile, $imageData);

            // Attach to product
            $productName = $product->translateAttribute('name') ?? 'Product';
            $product->addMedia($tempFile)
                ->usingName($productName . ' - Image')
                ->toMediaCollection('images');

            // Clean up
            @unlink($tempFile);
        }
    }

    /**
     * Attach base64 image to product.
     */
    protected function attachBase64Image(Product $product, string $base64Data): void
    {
        // Decode base64
        $imageData = base64_decode($base64Data);
        if ($imageData === false) {
            throw new \Exception('Invalid base64 image data');
        }

        // Create temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'product_import_');
        file_put_contents($tempFile, $imageData);

        // Attach to product
        $productName = $product->translateAttribute('name') ?? 'Product';
        $product->addMedia($tempFile)
            ->usingName($productName . ' - Image')
            ->toMediaCollection('images');

        // Clean up
        @unlink($tempFile);
    }

    /**
     * Process categories from category path.
     */
    protected function processCategories(Product $product, string $categoryPath): void
    {
        $categories = explode('>', $categoryPath);
        $categories = array_map('trim', $categories);
        $categories = array_filter($categories);

        $collectionIds = [];

        foreach ($categories as $categoryName) {
            $collection = Collection::where('name', $categoryName)->first();
            if (!$collection) {
                // Create collection if it doesn't exist
                $collection = Collection::create([
                    'name' => $categoryName,
                ]);
            }
            $collectionIds[] = $collection->id;
        }

        if (!empty($collectionIds)) {
            $product->collections()->sync($collectionIds);
        }
    }

    /**
     * Process brand.
     */
    protected function processBrand(Product $product, string $brandName): void
    {
        $brand = Brand::where('name', $brandName)->first();
        if (!$brand) {
            // Create brand if it doesn't exist
            $brand = Brand::create([
                'name' => $brandName,
            ]);
        }

        $product->brand_id = $brand->id;
        $product->save();
    }

    /**
     * Process attributes.
     */
    protected function processAttributes(Product $product, string|array $attributes): void
    {
        if (is_string($attributes)) {
            $attributes = json_decode($attributes, true);
        }

        if (!is_array($attributes)) {
            return;
        }

        // Process each attribute
        foreach ($attributes as $handle => $value) {
            $attribute = \Lunar\Models\Attribute::where('handle', $handle)->first();
            if ($attribute) {
                $product->attributeValues()->updateOrCreate(
                    [
                        'attribute_id' => $attribute->id,
                    ],
                    [
                        'value' => $value,
                    ]
                );
            }
        }
    }

    /**
     * Generate import report.
     */
    public function generateReport(ProductImport $import): void
    {
        $rows = ProductImportRow::where('product_import_id', $import->id)->get();

        $report = [
            'total_rows' => $import->total_rows,
            'processed_rows' => $import->processed_rows,
            'successful_rows' => $import->successful_rows,
            'failed_rows' => $import->failed_rows,
            'skipped_rows' => $import->skipped_rows,
            'success_rate' => $import->total_rows > 0 
                ? round(($import->successful_rows / $import->total_rows) * 100, 2) 
                : 0,
            'errors' => $rows->where('status', 'failed')
                ->map(function ($row) {
                    return [
                        'row_number' => $row->row_number,
                        'sku' => $row->sku,
                        'errors' => $row->validation_errors,
                        'message' => $row->error_message,
                    ];
                })->values()->toArray(),
        ];

        $import->update(['import_report' => $report]);
    }

    /**
     * Rollback an import.
     */
    public function rollback(ProductImport $import, ?int $userId = null): array
    {
        if (!$import->canRollback()) {
            return [
                'success' => false,
                'message' => 'Import cannot be rolled back',
            ];
        }

        $rolledBack = 0;
        $errors = [];

        DB::transaction(function () use ($import, $userId, &$rolledBack, &$errors) {
            $rows = ProductImportRow::where('product_import_id', $import->id)
                ->where('status', 'success')
                ->whereNotNull('product_id')
                ->get();

            foreach ($rows as $row) {
                try {
                    $product = Product::find($row->product_id);
                    if (!$product) {
                        continue;
                    }

                    // Create rollback record
                    ProductImportRollback::create([
                        'product_import_id' => $import->id,
                        'product_id' => $product->id,
                        'original_data' => $this->getProductSnapshot($product),
                        'action' => $row->mapped_data['action'] ?? 'created',
                        'rolled_back_by' => $userId,
                        'rolled_back_at' => now(),
                    ]);

                    // Delete product if it was created, restore if it was updated
                    if (($row->mapped_data['action'] ?? 'created') === 'created') {
                        $product->delete();
                    } else {
                        // Restore from snapshot would require more complex logic
                        // For now, just mark as rolled back
                    }

                    $rolledBack++;
                } catch (\Exception $e) {
                    $errors[] = "Failed to rollback product {$row->product_id}: " . $e->getMessage();
                }
            }
        });

        return [
            'success' => true,
            'rolled_back' => $rolledBack,
            'errors' => $errors,
        ];
    }

    /**
     * Get product snapshot for rollback.
     */
    protected function getProductSnapshot(Product $product): array
    {
        return [
            'name' => $product->translateAttribute('name'),
            'description' => $product->translateAttribute('description'),
            'status' => $product->status,
            'weight' => $product->weight,
            'length' => $product->length,
            'width' => $product->width,
            'height' => $product->height,
            'variant_data' => $product->variants->map(function ($variant) {
                return [
                    'sku' => $variant->sku,
                    'stock' => $variant->stock,
                    'prices' => $variant->prices->map(function ($price) {
                        return [
                            'price' => $price->price,
                            'currency_id' => $price->currency_id,
                        ];
                    })->toArray(),
                ];
            })->toArray(),
        ];
    }
}

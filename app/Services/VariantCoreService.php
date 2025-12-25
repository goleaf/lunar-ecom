<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Service for managing variant core operations at SKU level.
 */
class VariantCoreService
{
    /**
     * Create a new variant with core fields.
     *
     * @param  Product  $product
     * @param  array  $data
     * @return ProductVariant
     */
    public function createVariant(Product $product, array $data): ProductVariant
    {
        return DB::transaction(function () use ($product, $data) {
            // Generate UUID if not provided
            if (empty($data['uuid'])) {
                $data['uuid'] = (string) Str::uuid();
            }

            // Generate SKU if not provided
            if (empty($data['sku'])) {
                $data['sku'] = $this->generateSKU($product, $data);
            }

            // Generate title if not provided
            if (empty($data['title']) && empty($data['variant_name'])) {
                $data['title'] = $this->generateTitle($data);
            }

            // Set defaults
            $data['product_id'] = $product->id;
            $data['status'] = $data['status'] ?? 'active';
            $data['visibility'] = $data['visibility'] ?? 'public';
            $data['enabled'] = $data['enabled'] ?? true;
            $data['position'] = $data['position'] ?? $this->getNextPosition($product);

            // Create variant
            $variant = ProductVariant::create($data);

            // Attach option values if provided
            if (isset($data['option_values'])) {
                $variant->variantOptions()->sync($data['option_values']);
            }

            return $variant->fresh();
        });
    }

    /**
     * Update variant core fields.
     *
     * @param  ProductVariant  $variant
     * @param  array  $data
     * @return ProductVariant
     */
    public function updateVariant(ProductVariant $variant, array $data): ProductVariant
    {
        return DB::transaction(function () use ($variant, $data) {
            // Regenerate SKU if format changed
            if (isset($data['sku_format']) && $data['sku_format'] !== $variant->sku_format) {
                $data['sku'] = $this->generateSKU($variant->product, array_merge($variant->toArray(), $data));
            }

            // Regenerate title if option values changed
            if (isset($data['option_values']) && empty($data['title']) && empty($data['variant_name'])) {
                $data['title'] = $this->generateTitle(array_merge($variant->toArray(), $data));
            }

            $variant->update($data);

            // Update option values if provided
            if (isset($data['option_values'])) {
                $variant->variantOptions()->sync($data['option_values']);
            }

            return $variant->fresh();
        });
    }

    /**
     * Generate SKU based on format.
     *
     * @param  Product  $product
     * @param  array  $data
     * @return string
     */
    public function generateSKU(Product $product, array $data = []): string
    {
        $format = $data['sku_format'] ?? config('lunar.variants.default_sku_format', '{PRODUCT-SKU}-{OPTIONS}');
        
        $productSku = $product->sku ?? 'PROD-' . $product->id;
        
        // Replace placeholders
        $sku = str_replace('{PRODUCT-SKU}', $productSku, $format);
        $sku = str_replace('{PRODUCT-ID}', (string) $product->id, $sku);
        
        // Get option values if provided
        $optionValues = '';
        if (isset($data['option_values'])) {
            $optionValues = \Lunar\Models\ProductOptionValue::whereIn('id', $data['option_values'])
                ->get()
                ->map(function ($value) {
                    return strtoupper(substr($value->translateAttribute('name'), 0, 3));
                })
                ->join('-');
        }
        
        $sku = str_replace('{OPTIONS}', $optionValues ?: 'DEFAULT', $sku);
        $sku = str_replace('{UUID}', substr($data['uuid'] ?? Str::uuid(), 0, 8), $sku);
        $sku = str_replace('{TIMESTAMP}', time(), $sku);
        
        // Clean up SKU
        $sku = preg_replace('/[^A-Z0-9\-]/', '', strtoupper($sku));
        
        // Ensure uniqueness
        $baseSku = $sku;
        $counter = 1;
        while (ProductVariant::where('sku', $sku)->exists()) {
            $sku = $baseSku . '-' . $counter;
            $counter++;
        }
        
        return $sku;
    }

    /**
     * Generate variant title from option values.
     *
     * @param  array  $data
     * @return string
     */
    public function generateTitle(array $data): string
    {
        if (isset($data['option_values'])) {
            $values = \Lunar\Models\ProductOptionValue::whereIn('id', $data['option_values'])
                ->with('option')
                ->orderBy('product_options.position')
                ->get()
                ->map(function ($value) {
                    return $value->translateAttribute('name');
                })
                ->join(' / ');
            
            if ($values) {
                return $values;
            }
        }
        
        return 'Variant';
    }

    /**
     * Get next position for variant.
     *
     * @param  Product  $product
     * @return int
     */
    public function getNextPosition(Product $product): int
    {
        $maxPosition = ProductVariant::where('product_id', $product->id)
            ->max('position');
        
        return ($maxPosition ?? 0) + 1;
    }

    /**
     * Bulk update variant status.
     *
     * @param  array  $variantIds
     * @param  string  $status
     * @return int
     */
    public function bulkUpdateStatus(array $variantIds, string $status): int
    {
        return ProductVariant::whereIn('id', $variantIds)
            ->update(['status' => $status]);
    }

    /**
     * Bulk update variant visibility.
     *
     * @param  array  $variantIds
     * @param  string  $visibility
     * @param  array|null  $channelIds
     * @return int
     */
    public function bulkUpdateVisibility(array $variantIds, string $visibility, ?array $channelIds = null): int
    {
        $data = ['visibility' => $visibility];
        
        if ($visibility === 'channel_specific' && $channelIds) {
            $data['channel_visibility'] = $channelIds;
        }
        
        return ProductVariant::whereIn('id', $variantIds)
            ->update($data);
    }

    /**
     * Archive variants.
     *
     * @param  array  $variantIds
     * @return int
     */
    public function archiveVariants(array $variantIds): int
    {
        return $this->bulkUpdateStatus($variantIds, 'archived');
    }

    /**
     * Activate variants.
     *
     * @param  array  $variantIds
     * @return int
     */
    public function activateVariants(array $variantIds): int
    {
        return $this->bulkUpdateStatus($variantIds, 'active');
    }

    /**
     * Get variant by SKU.
     *
     * @param  string  $sku
     * @return ProductVariant|null
     */
    public function findBySKU(string $sku): ?ProductVariant
    {
        return ProductVariant::where('sku', $sku)->first();
    }

    /**
     * Get variant by UUID.
     *
     * @param  string  $uuid
     * @return ProductVariant|null
     */
    public function findByUUID(string $uuid): ?ProductVariant
    {
        return ProductVariant::where('uuid', $uuid)->first();
    }

    /**
     * Get variant by GTIN/EAN/UPC/ISBN.
     *
     * @param  string  $code
     * @return ProductVariant|null
     */
    public function findByBarcode(string $code): ?ProductVariant
    {
        return ProductVariant::where('gtin', $code)
            ->orWhere('ean', $code)
            ->orWhere('upc', $code)
            ->orWhere('isbn', $code)
            ->orWhere('barcode', $code)
            ->first();
    }

    /**
     * Get variant by internal reference.
     *
     * @param  string  $reference
     * @return ProductVariant|null
     */
    public function findByInternalReference(string $reference): ?ProductVariant
    {
        return ProductVariant::where('internal_reference', $reference)->first();
    }
}


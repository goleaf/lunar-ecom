<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Lunar\Models\Price;
use Lunar\Models\Currency;

class ProductVariantService
{
    /**
     * Create a product variant with SKU, pricing, and stock
     */
    public function createVariant(Product $product, array $data): ProductVariant
    {
        $taxClass = \Lunar\Models\TaxClass::where('default', true)->first();
        
        $variant = new ProductVariant([
            'product_id' => $product->id,
            'sku' => $data['sku'],
            'gtin' => $data['gtin'] ?? null,
            'mpn' => $data['mpn'] ?? null,
            'ean' => $data['ean'] ?? null,
            'unit_quantity' => $data['unit_quantity'] ?? 1,
            'min_quantity' => $data['min_quantity'] ?? 1,
            'quantity_increment' => $data['quantity_increment'] ?? 1,
            'stock' => $data['stock'] ?? 0,
            'backorder' => $data['backorder'] ?? 0,
            'purchasable' => $data['purchasable'] ?? 'always',
            'shippable' => $data['shippable'] ?? true,
            'tax_class_id' => $taxClass?->id,
        ]);
        $variant->save();

        // Set pricing if provided
        if (isset($data['prices'])) {
            $this->setPricing($variant, $data['prices']);
        }

        // Set variant options if provided
        if (isset($data['options'])) {
            $this->setVariantOptions($variant, $data['options']);
        }

        return $variant;
    }

    /**
     * Update variant stock
     */
    public function updateStock(ProductVariant $variant, int $quantity): ProductVariant
    {
        $variant->update(['stock' => $quantity]);
        $variant->refresh();
        return $variant;
    }

    /**
     * Check if variant is available
     */
    public function isAvailable(ProductVariant $variant): bool
    {
        return $variant->purchasable === 'always' || 
               ($variant->purchasable === 'in_stock' && $variant->stock > 0);
    }

    /**
     * Get variant price for currency
     */
    public function getPrice(ProductVariant $variant, Currency $currency): ?Price
    {
        return $variant->prices()
            ->where('currency_id', $currency->id)
            ->first();
    }

    /**
     * Set pricing for variant
     */
    protected function setPricing(ProductVariant $variant, array $prices): void
    {
        foreach ($prices as $currencyCode => $priceData) {
            $currency = Currency::where('code', $currencyCode)->first();
            if ($currency) {
                $variant->prices()->updateOrCreate(
                    [
                        'currency_id' => $currency->id,
                        'min_quantity' => $priceData['min_quantity'] ?? 1,
                    ],
                    [
                        'price' => $priceData['price'],
                        'compare_price' => $priceData['compare_price'] ?? null,
                    ]
                );
            }
        }
    }

    /**
     * Set variant options (color, size, etc.)
     */
    protected function setVariantOptions(ProductVariant $variant, array $options): void
    {
        $optionValueIds = [];
        
        foreach ($options as $optionHandle => $valueHandle) {
            // Find the product option value
            $optionValue = \Lunar\Models\ProductOptionValue::whereHas('option', function ($q) use ($optionHandle) {
                $q->where('handle', $optionHandle);
            })->where('handle', $valueHandle)->first();
            
            if ($optionValue) {
                $optionValueIds[] = $optionValue->id;
            }
        }

        if (!empty($optionValueIds)) {
            $variant->values()->sync($optionValueIds);
        }
    }

    /**
     * Generate SKU based on product and options
     */
    public function generateSku(Product $product, array $options = []): string
    {
        $baseSku = strtoupper(substr($product->productType->name ?? 'PROD', 0, 4));
        $productId = str_pad($product->id, 4, '0', STR_PAD_LEFT);
        
        $optionSuffix = '';
        if (!empty($options)) {
            $optionParts = [];
            foreach ($options as $optionHandle => $valueHandle) {
                $optionParts[] = strtoupper(substr($valueHandle, 0, 2));
            }
            $optionSuffix = '-' . implode('', $optionParts);
        }
        
        return $baseSku . $productId . $optionSuffix;
    }

    /**
     * Bulk update variant stock
     */
    public function bulkUpdateStock(array $variantStockData): array
    {
        $results = [];
        
        foreach ($variantStockData as $variantId => $stock) {
            $variant = ProductVariant::find($variantId);
            if ($variant) {
                $results[$variantId] = $this->updateStock($variant, $stock);
            }
        }
        
        return $results;
    }

    /**
     * Get low stock variants
     */
    public function getLowStockVariants(int $threshold = 10): \Illuminate\Database\Eloquent\Collection
    {
        return ProductVariant::where('stock', '<=', $threshold)
            ->where('purchasable', '!=', 'never')
            ->with(['product', 'prices'])
            ->get();
    }

    /**
     * Check if variant has sufficient stock for quantity
     */
    public function hasSufficientStock(ProductVariant $variant, int $requestedQuantity): bool
    {
        if ($variant->purchasable === 'never') {
            return false;
        }
        
        if ($variant->purchasable === 'always') {
            return true;
        }
        
        // For 'in_stock' purchasable type
        return $variant->stock >= $requestedQuantity;
    }
}
<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;

/**
 * Service for exporting products (CSV, XML, API).
 */
class ProductExportService
{
    /**
     * Export products to CSV.
     *
     * @param  Collection|array  $products
     * @param  array  $fields
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportToCSV($products, array $fields = [])
    {
        $products = $products instanceof Collection ? $products : collect($products);
        
        // Default fields if none specified
        if (empty($fields)) {
            $fields = [
                'id',
                'sku',
                'name',
                'description',
                'status',
                'price',
                'stock',
                'barcode',
                'weight',
                'brand',
                'categories',
                'collections',
            ];
        }
        
        $filename = 'products_export_' . now()->format('Y-m-d_His') . '.csv';
        
        return Response::streamDownload(function () use ($products, $fields) {
            $handle = fopen('php://output', 'w');
            
            // Write BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Write header
            fputcsv($handle, $fields);
            
            // Write data rows
            foreach ($products as $product) {
                $row = [];
                foreach ($fields as $field) {
                    $row[] = $this->getFieldValue($product, $field);
                }
                fputcsv($handle, $row);
            }
            
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export products to XML.
     *
     * @param  Collection|array  $products
     * @param  array  $fields
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function exportToXML($products, array $fields = [])
    {
        $products = $products instanceof Collection ? $products : collect($products);
        
        if (empty($fields)) {
            $fields = [
                'id',
                'sku',
                'name',
                'description',
                'status',
                'price',
                'stock',
                'barcode',
                'weight',
                'brand',
                'categories',
                'collections',
            ];
        }
        
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><products></products>');
        
        foreach ($products as $product) {
            $productNode = $xml->addChild('product');
            
            foreach ($fields as $field) {
                $value = $this->getFieldValue($product, $field);
                $productNode->addChild($field, htmlspecialchars($value, ENT_XML1, 'UTF-8'));
            }
        }
        
        $filename = 'products_export_' . now()->format('Y-m-d_His') . '.xml';
        
        return Response::make($xml->asXML(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Export products to JSON (API format).
     *
     * @param  Collection|array  $products
     * @param  array  $fields
     * @return \Illuminate\Http\JsonResponse
     */
    public function exportToJSON($products, array $fields = [])
    {
        $products = $products instanceof Collection ? $products : collect($products);
        
        $data = $products->map(function ($product) use ($fields) {
            if (empty($fields)) {
                return $this->getProductData($product);
            }
            
            $result = [];
            foreach ($fields as $field) {
                $result[$field] = $this->getFieldValue($product, $field);
            }
            return $result;
        });
        
        return response()->json([
            'meta' => [
                'exported_at' => now()->toIso8601String(),
                'total' => $data->count(),
            ],
            'data' => $data,
        ]);
    }

    /**
     * Get field value from product.
     *
     * @param  Product  $product
     * @param  string  $field
     * @return mixed
     */
    protected function getFieldValue(Product $product, string $field)
    {
        return match($field) {
            'id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->translateAttribute('name'),
            'description' => $product->translateAttribute('description'),
            'status' => $product->status,
            'price' => $this->getPrice($product),
            'stock' => $this->getStock($product),
            'barcode' => $product->barcode,
            'weight' => $product->weight,
            'brand' => $product->brand?->name,
            'categories' => $product->categories->pluck('name')->join(', '),
            'collections' => $product->collections->pluck('name')->join(', '),
            'created_at' => $product->created_at?->toIso8601String(),
            'updated_at' => $product->updated_at?->toIso8601String(),
            default => $product->getAttribute($field) ?? '',
        };
    }

    /**
     * Get product price.
     *
     * @param  Product  $product
     * @return string
     */
    protected function getPrice(Product $product): string
    {
        $variant = $product->variants->first();
        if (!$variant) {
            return '';
        }
        
        $price = $variant->prices()->first();
        return $price ? number_format($price->price->decimal, 2) : '';
    }

    /**
     * Get product stock.
     *
     * @param  Product  $product
     * @return int
     */
    protected function getStock(Product $product): int
    {
        return $product->variants->sum('stock');
    }

    /**
     * Get full product data.
     *
     * @param  Product  $product
     * @return array
     */
    protected function getProductData(Product $product): array
    {
        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->translateAttribute('name'),
            'description' => $product->translateAttribute('description'),
            'status' => $product->status,
            'price' => $this->getPrice($product),
            'stock' => $this->getStock($product),
            'barcode' => $product->barcode,
            'weight' => $product->weight,
            'brand' => $product->brand?->name,
            'categories' => $product->categories->pluck('name')->toArray(),
            'collections' => $product->collections->pluck('name')->toArray(),
            'created_at' => $product->created_at?->toIso8601String(),
            'updated_at' => $product->updated_at?->toIso8601String(),
        ];
    }
}

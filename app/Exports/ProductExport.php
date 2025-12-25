<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Lunar\Models\Product;

/**
 * Product Export class for exporting products to Excel/CSV.
 */
class ProductExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle
{
    protected array $columns;
    protected array $filters;
    protected ?int $categoryId;
    protected ?int $brandId;
    protected ?string $stockStatus;

    /**
     * Create a new export instance.
     */
    public function __construct(
        array $columns = [],
        array $filters = [],
        ?int $categoryId = null,
        ?int $brandId = null,
        ?string $stockStatus = null
    ) {
        $this->columns = !empty($columns) ? $columns : $this->getDefaultColumns();
        $this->filters = $filters;
        $this->categoryId = $categoryId;
        $this->brandId = $brandId;
        $this->stockStatus = $stockStatus;
    }

    /**
     * Get the collection of products to export.
     */
    public function collection()
    {
        $query = Product::with([
            'variants.prices',
            'collections',
            'brand',
            'attributeValues.attribute',
        ]);

        // Apply filters
        if ($this->categoryId) {
            $query->whereHas('collections', function ($q) {
                $q->where('id', $this->categoryId);
            });
        }

        if ($this->brandId) {
            $query->where('brand_id', $this->brandId);
        }

        if ($this->stockStatus) {
            $query->whereHas('variants', function ($q) {
                switch ($this->stockStatus) {
                    case 'in_stock':
                        $q->where('stock', '>', 0);
                        break;
                    case 'out_of_stock':
                        $q->where('stock', '<=', 0);
                        break;
                    case 'low_stock':
                        $q->where('stock', '>', 0)->where('stock', '<', 10);
                        break;
                }
            });
        }

        return $query->get();
    }

    /**
     * Get headings for the export.
     */
    public function headings(): array
    {
        $headings = [];

        foreach ($this->columns as $column) {
            $headings[] = $this->getColumnHeading($column);
        }

        return $headings;
    }

    /**
     * Map each product to a row.
     */
    public function map($product): array
    {
        $row = [];

        foreach ($this->columns as $column) {
            $row[] = $this->getColumnValue($product, $column);
        }

        return $row;
    }

    /**
     * Get column value for a product.
     */
    protected function getColumnValue(Product $product, string $column): mixed
    {
        return match ($column) {
            'sku' => $product->variants->first()?->sku ?? '',
            'name' => $product->translateAttribute('name'),
            'description' => $product->translateAttribute('description'),
            'price' => $this->getPrice($product),
            'compare_at_price' => $this->getCompareAtPrice($product),
            'category_path' => $this->getCategoryPath($product),
            'brand' => $product->brand?->name ?? '',
            'images' => $this->getImageUrls($product),
            'attributes' => $this->getAttributes($product),
            'stock_quantity' => $product->variants->sum('stock'),
            'weight' => $product->weight ?? '',
            'length' => $product->length ?? '',
            'width' => $product->width ?? '',
            'height' => $product->height ?? '',
            'status' => $product->status,
            'created_at' => $product->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $product->updated_at?->format('Y-m-d H:i:s'),
            default => '',
        };
    }

    /**
     * Get column heading.
     */
    protected function getColumnHeading(string $column): string
    {
        $headings = [
            'sku' => 'SKU',
            'name' => 'Name',
            'description' => 'Description',
            'price' => 'Price',
            'compare_at_price' => 'Compare At Price',
            'category_path' => 'Category Path',
            'brand' => 'Brand',
            'images' => 'Images (URLs)',
            'attributes' => 'Attributes (JSON)',
            'stock_quantity' => 'Stock Quantity',
            'weight' => 'Weight (grams)',
            'length' => 'Length (cm)',
            'width' => 'Width (cm)',
            'height' => 'Height (cm)',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];

        return $headings[$column] ?? ucfirst(str_replace('_', ' ', $column));
    }

    /**
     * Get default columns.
     */
    protected function getDefaultColumns(): array
    {
        return [
            'sku',
            'name',
            'description',
            'price',
            'compare_at_price',
            'category_path',
            'brand',
            'images',
            'stock_quantity',
            'weight',
        ];
    }

    /**
     * Get product price.
     */
    protected function getPrice(Product $product): string
    {
        $variant = $product->variants->first();
        if (!$variant) {
            return '';
        }

        $pricing = \Lunar\Facades\Pricing::for($variant)->get();
        $price = $pricing->matched?->price;

        return $price ? ($price->value / 100) : '';
    }

    /**
     * Get compare at price.
     */
    protected function getCompareAtPrice(Product $product): string
    {
        $variant = $product->variants->first();
        if (!$variant) {
            return '';
        }

        // Lunar uses compare_price field
        return $variant->compare_price ? ($variant->compare_price / 100) : '';
    }

    /**
     * Get category path.
     */
    protected function getCategoryPath(Product $product): string
    {
        $categories = $product->collections->pluck('name')->toArray();
        return implode(' > ', $categories);
    }

    /**
     * Get image URLs.
     */
    protected function getImageUrls(Product $product): string
    {
        $images = $product->getMedia('images');
        $urls = $images->map(function ($image) {
            return $image->getUrl();
        })->toArray();

        return implode(', ', $urls);
    }

    /**
     * Get attributes as JSON.
     */
    protected function getAttributes(Product $product): string
    {
        $attributes = [];
        foreach ($product->attributeValues as $attributeValue) {
            $attribute = $attributeValue->attribute;
            $attributes[$attribute->handle] = $attributeValue->translate('value');
        }

        return json_encode($attributes);
    }

    /**
     * Apply styles to the worksheet.
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']]],
        ];
    }

    /**
     * Set column widths.
     */
    public function columnWidths(): array
    {
        return [
            'A' => 15, // SKU
            'B' => 30, // Name
            'C' => 50, // Description
            'D' => 12, // Price
            'E' => 15, // Compare At Price
            'F' => 30, // Category Path
            'G' => 20, // Brand
            'H' => 50, // Images
        ];
    }

    /**
     * Get sheet title.
     */
    public function title(): string
    {
        return 'Products';
    }
}


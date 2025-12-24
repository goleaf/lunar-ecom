<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductType;
use App\Models\Collection;
use App\Models\Attribute;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Lunar\Models\Currency;
use Lunar\Models\TaxClass;

class ProductService
{
    /**
     * Create a new product with attributes and media
     */
    public function createProduct(array $data): Product
    {
        $product = new Product([
            'product_type_id' => $data['product_type_id'],
            'status' => $data['status'] ?? 'published',
            'brand' => $data['brand'] ?? null,
            'attribute_data' => $data['attribute_data'] ?? collect(),
        ]);
        $product->save();

        // Set product attributes if provided (legacy format)
        if (isset($data['attributes'])) {
            $this->setProductAttributes($product, $data['attributes']);
        }

        // Associate media if provided
        if (isset($data['media'])) {
            $this->associateMedia($product, $data['media']);
        }

        // Add to collections if provided
        if (isset($data['collections'])) {
            $product->collections()->sync($data['collections']);
        }

        return $product->fresh();
    }

    /**
     * Find product with variants loaded
     */
    public function findWithVariants(int $id): ?Product
    {
        return Product::with(['variants', 'productType', 'attributes', 'collections'])
            ->find($id);
    }

    /**
     * Search products by attributes
     */
    public function searchByAttributes(array $filters): EloquentCollection
    {
        $query = Product::query()->with(['variants', 'productType', 'attributes']);

        // Filter by product type
        if (isset($filters['product_type_id'])) {
            $query->where('product_type_id', $filters['product_type_id']);
        }

        // Filter by status
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by brand
        if (isset($filters['brand'])) {
            $query->where('brand', 'like', '%' . $filters['brand'] . '%');
        }

        // Filter by collections
        if (isset($filters['collections'])) {
            $query->whereHas('collections', function ($q) use ($filters) {
                $q->whereIn('id', (array) $filters['collections']);
            });
        }

        // Filter by attributes
        if (isset($filters['attributes'])) {
            foreach ($filters['attributes'] as $attributeHandle => $value) {
                $query->whereHas('attributes', function ($q) use ($attributeHandle, $value) {
                    $q->where('handle', $attributeHandle)
                      ->where('pivot.value', 'like', '%' . $value . '%');
                });
            }
        }

        return $query->get();
    }

    /**
     * Set product attributes
     */
    protected function setProductAttributes(Product $product, array $attributes): void
    {
        foreach ($attributes as $handle => $value) {
            $attribute = Attribute::where('handle', $handle)->first();
            if ($attribute) {
                $product->attributes()->syncWithoutDetaching([
                    $attribute->id => ['value' => $value]
                ]);
            }
        }
    }

    /**
     * Associate media with product
     */
    protected function associateMedia(Product $product, array $mediaIds): void
    {
        // This would typically handle media association
        // For now, we'll just sync the media IDs
        if (!empty($mediaIds)) {
            $product->media()->sync($mediaIds);
        }
    }
}
<?php

namespace App\Services;

use App\Models\Attribute;
use Lunar\Models\ProductType;
use Illuminate\Support\Collection;

/**
 * Service for managing attribute sets per product type.
 * 
 * Product types can have different sets of attributes assigned to them.
 * This service provides methods to manage these attribute sets.
 */
class AttributeSetService
{
    /**
     * Assign attributes to a product type.
     *
     * @param  ProductType  $productType
     * @param  array  $attributeIds
     * @return void
     */
    public function assignAttributesToProductType(ProductType $productType, array $attributeIds): void
    {
        $productType->mappedAttributes()->sync($attributeIds);
    }

    /**
     * Add attributes to a product type (without removing existing ones).
     *
     * @param  ProductType  $productType
     * @param  array  $attributeIds
     * @return void
     */
    public function addAttributesToProductType(ProductType $productType, array $attributeIds): void
    {
        $productType->mappedAttributes()->syncWithoutDetaching($attributeIds);
    }

    /**
     * Remove attributes from a product type.
     *
     * @param  ProductType  $productType
     * @param  array  $attributeIds
     * @return void
     */
    public function removeAttributesFromProductType(ProductType $productType, array $attributeIds): void
    {
        $productType->mappedAttributes()->detach($attributeIds);
    }

    /**
     * Get all attributes for a product type.
     *
     * @param  ProductType  $productType
     * @return Collection
     */
    public function getAttributesForProductType(ProductType $productType): Collection
    {
        return $productType->mappedAttributes;
    }

    /**
     * Get required attributes for a product type.
     *
     * @param  ProductType  $productType
     * @return Collection
     */
    public function getRequiredAttributesForProductType(ProductType $productType): Collection
    {
        return $productType->mappedAttributes()
            ->where('required', true)
            ->get();
    }

    /**
     * Get filterable attributes for a product type.
     *
     * @param  ProductType  $productType
     * @return Collection
     */
    public function getFilterableAttributesForProductType(ProductType $productType): Collection
    {
        return $productType->mappedAttributes()
            ->where('filterable', true)
            ->get();
    }

    /**
     * Get sortable attributes for a product type.
     *
     * @param  ProductType  $productType
     * @return Collection
     */
    public function getSortableAttributesForProductType(ProductType $productType): Collection
    {
        return $productType->mappedAttributes()
            ->where('sortable', true)
            ->get();
    }

    /**
     * Get searchable attributes for a product type.
     *
     * @param  ProductType  $productType
     * @return Collection
     */
    public function getSearchableAttributesForProductType(ProductType $productType): Collection
    {
        return $productType->mappedAttributes()
            ->where('searchable', true)
            ->get();
    }

    /**
     * Check if a product type has a specific attribute.
     *
     * @param  ProductType  $productType
     * @param  string  $attributeHandle
     * @return bool
     */
    public function productTypeHasAttribute(ProductType $productType, string $attributeHandle): bool
    {
        return $productType->mappedAttributes()
            ->where('handle', $attributeHandle)
            ->exists();
    }

    /**
     * Validate that all required attributes are present for a product type.
     *
     * @param  ProductType  $productType
     * @param  array  $attributeData
     * @return array Array of missing required attribute handles
     */
    public function validateRequiredAttributes(ProductType $productType, array $attributeData): array
    {
        $requiredAttributes = $this->getRequiredAttributesForProductType($productType);
        $missing = [];

        foreach ($requiredAttributes as $attribute) {
            if (!isset($attributeData[$attribute->handle])) {
                $missing[] = $attribute->handle;
            }
        }

        return $missing;
    }
}


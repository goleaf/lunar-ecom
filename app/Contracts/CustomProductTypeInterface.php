<?php

namespace App\Contracts;

use App\Models\Product;

/**
 * Interface for custom product types.
 * 
 * Custom product types can extend product behavior with:
 * - Custom validation rules
 * - Custom pricing logic
 * - Custom display logic
 * - Custom workflow rules
 */
interface CustomProductTypeInterface
{
    /**
     * Get the product type identifier.
     *
     * @return string
     */
    public function getTypeIdentifier(): string;

    /**
     * Get the product type name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get custom validation rules for this product type.
     *
     * @param  Product  $product
     * @return array
     */
    public function getValidationRules(Product $product): array;

    /**
     * Get custom form fields for this product type.
     *
     * @return array
     */
    public function getFormFields(): array;

    /**
     * Calculate custom price for this product type.
     *
     * @param  Product  $product
     * @param  array  $context
     * @return int|null  Price in cents, or null to use default
     */
    public function calculatePrice(Product $product, array $context = []): ?int;

    /**
     * Handle product creation for this type.
     *
     * @param  Product  $product
     * @param  array  $data
     * @return void
     */
    public function onCreate(Product $product, array $data): void;

    /**
     * Handle product update for this type.
     *
     * @param  Product  $product
     * @param  array  $data
     * @return void
     */
    public function onUpdate(Product $product, array $data): void;

    /**
     * Handle product deletion for this type.
     *
     * @param  Product  $product
     * @return void
     */
    public function onDelete(Product $product): void;

    /**
     * Get custom display data for storefront.
     *
     * @param  Product  $product
     * @return array
     */
    public function getStorefrontData(Product $product): array;
}


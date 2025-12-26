<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\VariantValidationRule;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

/**
 * Service for variant validation and rules engine.
 * 
 * Handles:
 * - SKU uniqueness rules
 * - Attribute combination validation
 * - Stock constraints
 * - Price sanity checks
 * - Shipping eligibility rules
 * - Channel availability rules
 * - Country restrictions
 * - Customer-group restrictions
 */
class VariantValidationService
{
    /**
     * Validate variant data.
     *
     * @param  ProductVariant  $variant
     * @param  array  $data
     * @return array Array of validation errors (empty if valid)
     */
    public function validate(ProductVariant $variant, array $data = []): array
    {
        $errors = [];

        // SKU uniqueness
        $skuErrors = $this->validateSkuUniqueness($variant, $data['sku'] ?? $variant->sku);
        $errors = array_merge($errors, $skuErrors);

        // Attribute combination
        $attributeErrors = $this->validateAttributeCombination($variant, $data);
        $errors = array_merge($errors, $attributeErrors);

        // Stock constraints
        $stockErrors = $this->validateStockConstraints($variant, $data);
        $errors = array_merge($errors, $stockErrors);

        // Price sanity checks
        $priceErrors = $this->validatePriceSanity($variant, $data);
        $errors = array_merge($errors, $priceErrors);

        return $errors;
    }

    /**
     * Validate SKU uniqueness.
     *
     * @param  ProductVariant  $variant
     * @param  string|null  $sku
     * @return array
     */
    public function validateSkuUniqueness(ProductVariant $variant, ?string $sku): array
    {
        $errors = [];

        if (empty($sku)) {
            return $errors; // SKU is optional
        }

        $query = ProductVariant::where('sku', $sku);

        // Exclude current variant if updating
        if ($variant->exists) {
            $query->where('id', '!=', $variant->id);
        }

        if ($query->exists()) {
            $errors[] = "SKU '{$sku}' is already in use by another variant.";
        }

        return $errors;
    }

    /**
     * Validate attribute combination uniqueness.
     *
     * @param  ProductVariant  $variant
     * @param  array  $data
     * @return array
     */
    public function validateAttributeCombination(ProductVariant $variant, array $data): array
    {
        $errors = [];

        // Get option value IDs from data or existing variant
        $optionValueIds = $data['option_values'] ?? $data['option_value_ids'] ?? null;

        if ($optionValueIds === null && $variant->exists) {
            $optionValueIds = $variant->variantOptions->pluck('id')->toArray();
        }

        if (empty($optionValueIds)) {
            return $errors; // No attributes to validate
        }

        // Check for duplicate combination within same product
        $productId = $data['product_id'] ?? $variant->product_id;

        if (!$productId) {
            return $errors;
        }

        $existing = ProductVariant::where('product_id', $productId)
            ->where('id', '!=', $variant->id ?? 0)
            ->whereHas('variantOptions', function ($query) use ($optionValueIds) {
                $query->whereIn('product_option_values.id', $optionValueIds);
            }, '=', count($optionValueIds))
            ->exists();

        if ($existing) {
            $errors[] = 'A variant with this attribute combination already exists for this product.';
        }

        return $errors;
    }

    /**
     * Validate stock constraints.
     *
     * @param  ProductVariant  $variant
     * @param  array  $data
     * @return array
     */
    public function validateStockConstraints(ProductVariant $variant, array $data): array
    {
        $errors = [];

        // Min/max quantity validation
        $minQuantity = $data['min_order_quantity'] ?? $variant->min_order_quantity ?? 1;
        $maxQuantity = $data['max_order_quantity'] ?? $variant->max_order_quantity ?? null;

        if ($maxQuantity !== null && $minQuantity > $maxQuantity) {
            $errors[] = 'Minimum order quantity cannot be greater than maximum order quantity.';
        }

        // Stock validation
        $stock = $data['stock'] ?? $variant->stock ?? 0;
        $backorder = $data['backorder'] ?? $variant->backorder ?? 0;
        $backorderLimit = $data['backorder_limit'] ?? $variant->backorder_limit ?? null;

        if ($backorderLimit !== null && $backorder > $backorderLimit) {
            $errors[] = "Backorder quantity ({$backorder}) exceeds limit ({$backorderLimit}).";
        }

        // Low stock threshold validation
        $lowStockThreshold = $data['low_stock_threshold'] ?? $variant->low_stock_threshold ?? null;
        if ($lowStockThreshold !== null && $lowStockThreshold < 0) {
            $errors[] = 'Low stock threshold cannot be negative.';
        }

        return $errors;
    }

    /**
     * Validate price sanity.
     *
     * @param  ProductVariant  $variant
     * @param  array  $data
     * @return array
     */
    public function validatePriceSanity(ProductVariant $variant, array $data): array
    {
        $errors = [];

        $price = $data['price'] ?? null;
        $compareAtPrice = $data['compare_at_price'] ?? $variant->compare_at_price ?? null;
        $costPrice = $data['cost_price'] ?? $variant->cost_price ?? null;
        $mapPrice = $data['map_price'] ?? $variant->map_price ?? null;

        // Compare-at price should be higher than regular price
        if ($compareAtPrice !== null && $price !== null && $compareAtPrice <= $price) {
            $errors[] = 'Compare-at price must be higher than regular price.';
        }

        // MAP price validation
        if ($mapPrice !== null && $price !== null && $price < $mapPrice && config('lunar.pricing.enforce_map_pricing', true)) {
            $errors[] = "Price ({$price}) cannot be below MAP price ({$mapPrice}).";
        }

        // Cost price sanity check (warn if selling below cost)
        if ($costPrice !== null && $price !== null && $price < $costPrice) {
            // This is a warning, not an error - allow but log
            \Log::warning("Variant {$variant->id} price ({$price}) is below cost price ({$costPrice})");
        }

        // Price should be positive
        if ($price !== null && $price < 0) {
            $errors[] = 'Price cannot be negative.';
        }

        return $errors;
    }

    /**
     * Validate shipping eligibility.
     *
     * @param  ProductVariant  $variant
     * @param  array  $shippingContext
     * @return array
     */
    public function validateShippingEligibility(ProductVariant $variant, array $shippingContext = []): array
    {
        $errors = [];

        $rules = VariantValidationRule::where('product_variant_id', $variant->id)
            ->where('rule_type', 'shipping_eligibility')
            ->active()
            ->orderedByPriority()
            ->get();

        foreach ($rules as $rule) {
            $conditions = $rule->conditions ?? [];

            // Check weight limits
            if (isset($conditions['max_weight']) && $variant->weight > $conditions['max_weight']) {
                $errors[] = "Variant weight ({$variant->weight}g) exceeds maximum shipping weight ({$conditions['max_weight']}g).";
            }

            // Check dimension limits
            if (isset($conditions['max_dimensions'])) {
                $dimensions = $variant->dimensions ?? [];
                $maxDimensions = $conditions['max_dimensions'];
                
                if (isset($dimensions['length']) && $dimensions['length'] > $maxDimensions['length']) {
                    $errors[] = "Variant length exceeds maximum shipping length.";
                }
                if (isset($dimensions['width']) && $dimensions['width'] > $maxDimensions['width']) {
                    $errors[] = "Variant width exceeds maximum shipping width.";
                }
                if (isset($dimensions['height']) && $dimensions['height'] > $maxDimensions['height']) {
                    $errors[] = "Variant height exceeds maximum shipping height.";
                }
            }

            // Check fragile/hazardous restrictions
            if (isset($conditions['require_special_handling']) && $conditions['require_special_handling']) {
                if (!$variant->is_fragile && !$variant->is_hazardous) {
                    // This might be a warning, not an error
                }
            }
        }

        return $errors;
    }

    /**
     * Validate channel availability.
     *
     * @param  ProductVariant  $variant
     * @param  int|null  $channelId
     * @return array
     */
    public function validateChannelAvailability(ProductVariant $variant, ?int $channelId = null): array
    {
        $errors = [];

        if ($channelId === null) {
            return $errors;
        }

        $rules = VariantValidationRule::where('product_variant_id', $variant->id)
            ->where('rule_type', 'channel_availability')
            ->active()
            ->orderedByPriority()
            ->get();

        foreach ($rules as $rule) {
            $restrictions = $rule->restrictions ?? [];
            $allowedValues = $rule->allowed_values ?? [];

            // Check blocked channels
            if (isset($restrictions['blocked_channels']) && in_array($channelId, $restrictions['blocked_channels'])) {
                $errors[] = "Variant is not available in this channel.";
                break;
            }

            // Check allowed channels (if specified, only these are allowed)
            if (!empty($allowedValues['allowed_channels']) && !in_array($channelId, $allowedValues['allowed_channels'])) {
                $errors[] = "Variant is not available in this channel.";
                break;
            }
        }

        // Check variant visibility
        if ($variant->visibility === 'channel_specific') {
            $channelVisibility = $variant->channel_visibility ?? [];
            if (!in_array($channelId, $channelVisibility)) {
                $errors[] = "Variant is not visible in this channel.";
            }
        }

        return $errors;
    }

    /**
     * Validate country restrictions.
     *
     * @param  ProductVariant  $variant
     * @param  string  $countryCode
     * @return array
     */
    public function validateCountryRestrictions(ProductVariant $variant, string $countryCode): array
    {
        $errors = [];

        $rules = VariantValidationRule::where('product_variant_id', $variant->id)
            ->where('rule_type', 'country_restriction')
            ->active()
            ->orderedByPriority()
            ->get();

        foreach ($rules as $rule) {
            $restrictions = $rule->restrictions ?? [];
            $allowedValues = $rule->allowed_values ?? [];

            // Check blocked countries
            if (isset($restrictions['blocked_countries']) && in_array($countryCode, $restrictions['blocked_countries'])) {
                $errors[] = "Variant is not available in {$countryCode}.";
                break;
            }

            // Check allowed countries (if specified, only these are allowed)
            if (!empty($allowedValues['allowed_countries']) && !in_array($countryCode, $allowedValues['allowed_countries'])) {
                $errors[] = "Variant is not available in {$countryCode}.";
                break;
            }
        }

        // Check product-level country restrictions
        $product = $variant->product;
        if ($product && method_exists($product, 'isAvailableInCountry')) {
            if (!$product->isAvailableInCountry($countryCode)) {
                $errors[] = "Product is not available in {$countryCode}.";
            }
        }

        return $errors;
    }

    /**
     * Validate customer-group restrictions.
     *
     * @param  ProductVariant  $variant
     * @param  int|null  $customerGroupId
     * @return array
     */
    public function validateCustomerGroupRestrictions(ProductVariant $variant, ?int $customerGroupId = null): array
    {
        $errors = [];

        if ($customerGroupId === null) {
            return $errors; // No customer group restriction
        }

        $rules = VariantValidationRule::where('product_variant_id', $variant->id)
            ->where('rule_type', 'customer_group_restriction')
            ->active()
            ->orderedByPriority()
            ->get();

        foreach ($rules as $rule) {
            $restrictions = $rule->restrictions ?? [];
            $allowedValues = $rule->allowed_values ?? [];

            // Check blocked customer groups
            if (isset($restrictions['blocked_customer_groups']) && in_array($customerGroupId, $restrictions['blocked_customer_groups'])) {
                $errors[] = "Variant is not available for this customer group.";
                break;
            }

            // Check allowed customer groups (if specified, only these are allowed)
            if (!empty($allowedValues['allowed_customer_groups']) && !in_array($customerGroupId, $allowedValues['allowed_customer_groups'])) {
                $errors[] = "Variant is not available for this customer group.";
                break;
            }
        }

        return $errors;
    }

    /**
     * Validate variant for context (all validations).
     *
     * @param  ProductVariant  $variant
     * @param  array  $context
     * @return array
     */
    public function validateForContext(ProductVariant $variant, array $context = []): array
    {
        $errors = [];

        // Basic validation
        $basicErrors = $this->validate($variant, $context);
        $errors = array_merge($errors, $basicErrors);

        // Shipping eligibility
        if (isset($context['shipping'])) {
            $shippingErrors = $this->validateShippingEligibility($variant, $context['shipping']);
            $errors = array_merge($errors, $shippingErrors);
        }

        // Channel availability
        if (isset($context['channel_id'])) {
            $channelErrors = $this->validateChannelAvailability($variant, $context['channel_id']);
            $errors = array_merge($errors, $channelErrors);
        }

        // Country restrictions
        if (isset($context['country_code'])) {
            $countryErrors = $this->validateCountryRestrictions($variant, $context['country_code']);
            $errors = array_merge($errors, $countryErrors);
        }

        // Customer-group restrictions
        if (isset($context['customer_group_id'])) {
            $customerGroupErrors = $this->validateCustomerGroupRestrictions($variant, $context['customer_group_id']);
            $errors = array_merge($errors, $customerGroupErrors);
        }

        return $errors;
    }

    /**
     * Check if variant is valid for context.
     *
     * @param  ProductVariant  $variant
     * @param  array  $context
     * @return bool
     */
    public function isValidForContext(ProductVariant $variant, array $context = []): bool
    {
        $errors = $this->validateForContext($variant, $context);
        return empty($errors);
    }

    /**
     * Throw validation exception if invalid.
     *
     * @param  ProductVariant  $variant
     * @param  array  $context
     * @return void
     * @throws ValidationException
     */
    public function validateOrFail(ProductVariant $variant, array $context = []): void
    {
        $errors = $this->validateForContext($variant, $context);

        if (!empty($errors)) {
            throw ValidationException::withMessages([
                'variant' => $errors,
            ]);
        }
    }
}



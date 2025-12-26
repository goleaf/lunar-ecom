<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\VariantValidationRule;
use Illuminate\Support\Collection;

/**
 * Rules engine for variant validation rules.
 * 
 * Manages complex validation rules:
 * - Shipping eligibility rules
 * - Channel availability rules
 * - Country restrictions
 * - Customer-group restrictions
 */
class VariantRulesEngine
{
    /**
     * Create a validation rule.
     *
     * @param  ProductVariant  $variant
     * @param  string  $ruleType
     * @param  array  $config
     * @return VariantValidationRule
     */
    public function createRule(ProductVariant $variant, string $ruleType, array $config): VariantValidationRule
    {
        $validTypes = [
            'shipping_eligibility',
            'channel_availability',
            'country_restriction',
            'customer_group_restriction',
        ];

        if (!in_array($ruleType, $validTypes)) {
            throw new \InvalidArgumentException("Invalid rule type: {$ruleType}");
        }

        return VariantValidationRule::create([
            'product_variant_id' => $variant->id,
            'rule_type' => $ruleType,
            'rule_name' => $config['name'] ?? $ruleType,
            'rule_description' => $config['description'] ?? null,
            'conditions' => $config['conditions'] ?? null,
            'restrictions' => $config['restrictions'] ?? null,
            'allowed_values' => $config['allowed_values'] ?? null,
            'is_active' => $config['is_active'] ?? true,
            'priority' => $config['priority'] ?? 0,
        ]);
    }

    /**
     * Get rules for variant.
     *
     * @param  ProductVariant  $variant
     * @param  string|null  $ruleType
     * @return Collection
     */
    public function getRules(ProductVariant $variant, ?string $ruleType = null): Collection
    {
        $query = VariantValidationRule::where('product_variant_id', $variant->id)
            ->active()
            ->orderedByPriority();

        if ($ruleType) {
            $query->ofType($ruleType);
        }

        return $query->get();
    }

    /**
     * Delete rule.
     *
     * @param  int  $ruleId
     * @return bool
     */
    public function deleteRule(int $ruleId): bool
    {
        return VariantValidationRule::where('id', $ruleId)->delete() > 0;
    }

    /**
     * Create shipping eligibility rule.
     *
     * @param  ProductVariant  $variant
     * @param  array  $conditions
     * @return VariantValidationRule
     */
    public function createShippingEligibilityRule(ProductVariant $variant, array $conditions): VariantValidationRule
    {
        return $this->createRule($variant, 'shipping_eligibility', [
            'name' => 'Shipping Eligibility',
            'conditions' => $conditions,
        ]);
    }

    /**
     * Create channel availability rule.
     *
     * @param  ProductVariant  $variant
     * @param  array  $allowedChannels
     * @param  array  $blockedChannels
     * @return VariantValidationRule
     */
    public function createChannelAvailabilityRule(
        ProductVariant $variant,
        array $allowedChannels = [],
        array $blockedChannels = []
    ): VariantValidationRule {
        return $this->createRule($variant, 'channel_availability', [
            'name' => 'Channel Availability',
            'allowed_values' => ['allowed_channels' => $allowedChannels],
            'restrictions' => ['blocked_channels' => $blockedChannels],
        ]);
    }

    /**
     * Create country restriction rule.
     *
     * @param  ProductVariant  $variant
     * @param  array  $allowedCountries
     * @param  array  $blockedCountries
     * @return VariantValidationRule
     */
    public function createCountryRestrictionRule(
        ProductVariant $variant,
        array $allowedCountries = [],
        array $blockedCountries = []
    ): VariantValidationRule {
        return $this->createRule($variant, 'country_restriction', [
            'name' => 'Country Restriction',
            'allowed_values' => ['allowed_countries' => $allowedCountries],
            'restrictions' => ['blocked_countries' => $blockedCountries],
        ]);
    }

    /**
     * Create customer-group restriction rule.
     *
     * @param  ProductVariant  $variant
     * @param  array  $allowedGroups
     * @param  array  $blockedGroups
     * @return VariantValidationRule
     */
    public function createCustomerGroupRestrictionRule(
        ProductVariant $variant,
        array $allowedGroups = [],
        array $blockedGroups = []
    ): VariantValidationRule {
        return $this->createRule($variant, 'customer_group_restriction', [
            'name' => 'Customer Group Restriction',
            'allowed_values' => ['allowed_customer_groups' => $allowedGroups],
            'restrictions' => ['blocked_customer_groups' => $blockedGroups],
        ]);
    }
}



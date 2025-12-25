<?php

namespace App\Contracts;

use App\Models\Product;
use App\Models\ProductVariant;

/**
 * Interface for custom pricing rules.
 * 
 * Custom pricing rules can:
 * - Override default pricing
 * - Apply discounts
 * - Calculate dynamic pricing
 * - Apply conditional pricing
 */
interface CustomPricingRuleInterface
{
    /**
     * Get the pricing rule identifier.
     *
     * @return string
     */
    public function getRuleIdentifier(): string;

    /**
     * Get the pricing rule name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the priority (higher = applied first).
     *
     * @return int
     */
    public function getPriority(): int;

    /**
     * Check if this rule applies to the given context.
     *
     * @param  ProductVariant  $variant
     * @param  array  $context
     * @return bool
     */
    public function appliesTo(ProductVariant $variant, array $context = []): bool;

    /**
     * Calculate price using this rule.
     *
     * @param  ProductVariant  $variant
     * @param  int  $basePrice  Base price in cents
     * @param  array  $context
     * @return int  Calculated price in cents
     */
    public function calculatePrice(ProductVariant $variant, int $basePrice, array $context = []): int;

    /**
     * Get rule configuration.
     *
     * @return array
     */
    public function getConfiguration(): array;

    /**
     * Set rule configuration.
     *
     * @param  array  $config
     * @return void
     */
    public function setConfiguration(array $config): void;
}


<?php

namespace App\Services;

use App\Models\PriceMatrix;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PriceHistory;
use Lunar\Models\Currency;
use Lunar\Models\CustomerGroup;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * MatrixPricingService - Advanced pricing calculation service.
 * 
 * Handles:
 * - Quantity-based tiered pricing
 * - Customer group pricing
 * - Regional pricing
 * - Mixed pricing rules
 * - Promotional pricing
 * - Mix-and-match pricing across variants
 * - Minimum order quantities
 */
class MatrixPricingService
{
    /**
     * Calculate price for a variant based on context.
     *
     * @param ProductVariant $variant
     * @param int $quantity
     * @param Currency|null $currency
     * @param CustomerGroup|string|null $customerGroup
     * @param string|null $region
     * @param array $variantQuantities For mix-and-match pricing
     * @return array
     */
    public function calculatePrice(
        ProductVariant $variant,
        int $quantity = 1,
        ?Currency $currency = null,
        $customerGroup = null,
        ?string $region = null,
        array $variantQuantities = []
    ): array {
        // Get currency
        if (!$currency) {
            $currency = Currency::where('default', true)->first();
        }

        if (!$currency) {
            throw new \RuntimeException('No currency available for price calculation.');
        }

        // Normalize customer group
        $customerGroupHandle = $this->normalizeCustomerGroup($customerGroup);

        // Get product
        $product = $variant->product;

        // Get active price matrices for this product, ordered by priority
        $matrices = PriceMatrix::forProduct($product->id)
            ->active()
            ->orderBy('priority', 'desc')
            ->get();

        // Try mix-and-match pricing first if variant quantities provided
        if (!empty($variantQuantities)) {
            $mixMatchPrice = $this->calculateMixAndMatchPrice(
                $product,
                $variantQuantities,
                $currency,
                $customerGroupHandle,
                $region
            );

            if ($mixMatchPrice !== null) {
                return $this->formatPriceResponse(
                    $mixMatchPrice,
                    null,
                    $currency,
                    $quantity
                );
            }
        }

        // Calculate price using matrices
        $bestPrice = null;
        $matchedMatrix = null;

        foreach ($matrices as $matrix) {
            $rule = $matrix->getRulesForContext($quantity, $customerGroupHandle, $region);

            if ($rule && isset($rule['price'])) {
                $price = $rule['price'];

                // Check minimum order quantity if specified
                if (isset($rule['min_quantity']) && $quantity < $rule['min_quantity']) {
                    continue;
                }

                // Use this price if it's better (lower) or first match
                if ($bestPrice === null || $price < $bestPrice) {
                    $bestPrice = $price;
                    $matchedMatrix = $matrix;
                }
            }
        }

        // Fallback to variant's base price or Lunar pricing
        if ($bestPrice === null) {
            $bestPrice = $variant->getEffectivePrice($quantity, $customerGroup);
        }

        return $this->formatPriceResponse(
            $bestPrice,
            $variant->compare_at_price,
            $currency,
            $quantity,
            $matchedMatrix
        );
    }

    /**
     * Get tiered pricing information for a variant.
     *
     * @param ProductVariant $variant
     * @param Currency|null $currency
     * @param CustomerGroup|string|null $customerGroup
     * @param string|null $region
     * @return Collection
     */
    public function getTieredPricing(
        ProductVariant $variant,
        ?Currency $currency = null,
        $customerGroup = null,
        ?string $region = null
    ): Collection {
        if (!$currency) {
            $currency = Currency::where('default', true)->first();
        }

        $product = $variant->product;
        $customerGroupHandle = $this->normalizeCustomerGroup($customerGroup);

        // Get base price
        $basePrice = $variant->getEffectivePrice(1, $customerGroup) ?? 0;

        // Get all quantity-based matrices
        $matrices = PriceMatrix::forProduct($product->id)
            ->active()
            ->byType(PriceMatrix::TYPE_QUANTITY)
            ->orderBy('priority', 'desc')
            ->get();

        $tiers = collect();

        foreach ($matrices as $matrix) {
            $matrixTiers = $matrix->getTierPricing();

            foreach ($matrixTiers as $tier) {
                $tierPrice = $tier['price'] ?? $basePrice;
                $minQty = $tier['min_quantity'] ?? 1;
                $maxQty = $tier['max_quantity'] ?? null;

                // Check if this tier applies to the context
                if ($customerGroupHandle && !$this->tierAppliesToContext($matrix, $customerGroupHandle, $region)) {
                    continue;
                }

                $savings = $basePrice - $tierPrice;
                $savingsPercent = $basePrice > 0 ? round(($savings / $basePrice) * 100, 2) : 0;

                $tiers->push([
                    'min_quantity' => $minQty,
                    'max_quantity' => $maxQty,
                    'price' => $tierPrice,
                    'price_decimal' => $tierPrice / 100,
                    'formatted_price' => $this->formatCurrency($tierPrice / 100, $currency),
                    'savings' => $savings,
                    'savings_percent' => $savingsPercent,
                    'matrix_id' => $matrix->id,
                ]);
            }
        }

        // If no tiers found, add base price as single tier
        if ($tiers->isEmpty()) {
            $tiers->push([
                'min_quantity' => 1,
                'max_quantity' => null,
                'price' => $basePrice,
                'price_decimal' => $basePrice / 100,
                'formatted_price' => $this->formatCurrency($basePrice / 100, $currency),
                'savings' => 0,
                'savings_percent' => 0,
                'matrix_id' => null,
            ]);
        }

        return $tiers->sortBy('min_quantity')->values();
    }

    /**
     * Get volume discounts for a variant.
     *
     * @param ProductVariant $variant
     * @param Currency|null $currency
     * @param CustomerGroup|string|null $customerGroup
     * @return array
     */
    public function getVolumeDiscounts(
        ProductVariant $variant,
        ?Currency $currency = null,
        $customerGroup = null
    ): array {
        if (!$currency) {
            $currency = Currency::where('default', true)->first();
        }

        $basePrice = $variant->getEffectivePrice(1, $customerGroup) ?? 0;

        $tiers = $this->getTieredPricing($variant, $currency, $customerGroup);

        return $tiers->map(function ($tier) use ($basePrice) {
            return [
                'quantity_range' => $this->formatQuantityRange($tier['min_quantity'], $tier['max_quantity']),
                'min_quantity' => $tier['min_quantity'],
                'max_quantity' => $tier['max_quantity'],
                'price' => $tier['price'],
                'formatted_price' => $tier['formatted_price'],
                'savings' => $tier['savings'],
                'savings_percent' => $tier['savings_percent'],
                'you_save' => $tier['savings_percent'] > 0 
                    ? "Save {$tier['savings_percent']}%" 
                    : null,
            ];
        })->toArray();
    }

    /**
     * Calculate mix-and-match pricing across multiple variants.
     *
     * @param Product $product
     * @param array $variantQuantities ['variant_id' => quantity]
     * @param Currency $currency
     * @param string|null $customerGroupHandle
     * @param string|null $region
     * @return int|null Price in cents, or null if no mix-and-match rule applies
     */
    protected function calculateMixAndMatchPrice(
        Product $product,
        array $variantQuantities,
        Currency $currency,
        ?string $customerGroupHandle,
        ?string $region
    ): ?int {
        $totalQuantity = array_sum($variantQuantities);

        // Get mix-and-match matrices
        $matrices = PriceMatrix::forProduct($product->id)
            ->active()
            ->byType(PriceMatrix::TYPE_QUANTITY)
            ->orderBy('priority', 'desc')
            ->get();

        foreach ($matrices as $matrix) {
            $rules = $matrix->rules;

            // Check if this matrix supports mix-and-match
            if (!isset($rules['mix_and_match']) || !$rules['mix_and_match']) {
                continue;
            }

            // Get tier for total quantity
            $tier = $matrix->getRulesForContext($totalQuantity, $customerGroupHandle, $region);

            if ($tier && isset($tier['price'])) {
                return $tier['price'];
            }
        }

        return null;
    }

    /**
     * Track price change in history.
     *
     * @param ProductVariant $variant
     * @param int $oldPrice
     * @param int $newPrice
     * @param string $changeType
     * @param PriceMatrix|null $matrix
     * @param int|null $userId
     * @param string|null $reason
     * @return PriceHistory
     */
    public function trackPriceChange(
        ProductVariant $variant,
        int $oldPrice,
        int $newPrice,
        string $changeType = PriceHistory::TYPE_UPDATED,
        ?PriceMatrix $matrix = null,
        ?int $userId = null,
        ?string $reason = null
    ): PriceHistory {
        $currency = Currency::where('default', true)->first();

        return PriceHistory::create([
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'price_matrix_id' => $matrix?->id,
            'currency_id' => $currency->id,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'change_type' => $changeType,
            'change_reason' => $reason,
            'changed_by' => $userId ?? auth()->id(),
        ]);
    }

    /**
     * Normalize customer group to handle.
     */
    protected function normalizeCustomerGroup($customerGroup): ?string
    {
        if ($customerGroup instanceof CustomerGroup) {
            return $customerGroup->handle;
        }

        if (is_string($customerGroup)) {
            return $customerGroup;
        }

        return null;
    }

    /**
     * Check if tier applies to context.
     */
    protected function tierAppliesToContext(
        PriceMatrix $matrix,
        ?string $customerGroupHandle,
        ?string $region
    ): bool {
        // For quantity-based matrices, check if they have customer group or region filters
        $rules = $matrix->rules;

        if ($customerGroupHandle && isset($rules['customer_group_filter'])) {
            if (!in_array($customerGroupHandle, $rules['customer_group_filter'])) {
                return false;
            }
        }

        if ($region && isset($rules['region_filter'])) {
            if (!in_array($region, $rules['region_filter'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Format price response.
     */
    protected function formatPriceResponse(
        int $price,
        ?int $comparePrice,
        Currency $currency,
        int $quantity,
        ?PriceMatrix $matrix = null
    ): array {
        $priceDecimal = $price / 100;
        $comparePriceDecimal = $comparePrice ? $comparePrice / 100 : null;

        $savings = null;
        $savingsPercentage = null;

        if ($comparePrice && $comparePrice > $price) {
            $savings = ($comparePrice - $price) / 100;
            $savingsPercentage = round(($savings / ($comparePrice / 100)) * 100, 2);
        }

        return [
            'price' => $price,
            'price_decimal' => $priceDecimal,
            'compare_price' => $comparePrice,
            'compare_price_decimal' => $comparePriceDecimal,
            'formatted_price' => $this->formatCurrency($priceDecimal, $currency),
            'formatted_compare_price' => $comparePriceDecimal ? $this->formatCurrency($comparePriceDecimal, $currency) : null,
            'savings' => $savings,
            'savings_percentage' => $savingsPercentage,
            'quantity' => $quantity,
            'currency' => $currency->code,
            'matrix_id' => $matrix?->id,
            'you_save' => $savingsPercentage ? "Save {$savingsPercentage}%" : null,
        ];
    }

    /**
     * Format currency.
     */
    protected function formatCurrency(float $amount, Currency $currency): string
    {
        $formatter = new \NumberFormatter(
            app()->getLocale(),
            \NumberFormatter::CURRENCY
        );

        return $formatter->formatCurrency($amount, $currency->code);
    }

    /**
     * Format quantity range.
     */
    protected function formatQuantityRange(int $min, ?int $max): string
    {
        if ($max === null) {
            return "{$min}+";
        }

        if ($min === $max) {
            return (string) $min;
        }

        return "{$min}-{$max}";
    }
}


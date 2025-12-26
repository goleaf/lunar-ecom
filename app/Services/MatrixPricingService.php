<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PriceMatrix;
use App\Models\PricingTier;
use App\Models\PricingRule;
use App\Models\PriceHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lunar\Facades\Currency;
use Lunar\Facades\Pricing;

class MatrixPricingService
{
    /**
     * Calculate price based on context.
     *
     * @param  ProductVariant  $variant
     * @param  array  $context  ['quantity' => 10, 'customer_group' => 'wholesale', 'region' => 'US']
     * @return array  ['price' => decimal, 'matrix_id' => int, 'savings' => decimal, 'savings_percentage' => float]
     */
    public function calculatePrice(ProductVariant $variant, array $context = []): array
    {
        // Get base price
        $currency = Currency::getDefault();
        $basePricing = Pricing::for($variant)->currency($currency)->get();
        $basePrice = $basePricing->matched?->price?->value ?? 0;

        // Get applicable price matrices
        $matrices = $this->getApplicableMatrices($variant, $context);

        if ($matrices->isEmpty()) {
            return [
                'price' => $basePrice,
                'base_price' => $basePrice,
                'matrix_id' => null,
                'savings' => 0,
                'savings_percentage' => 0,
                'tier' => null,
            ];
        }

        // Evaluate matrices (highest priority first)
        foreach ($matrices as $matrix) {
            $calculatedPrice = $this->calculateMatrixPrice($variant, $matrix, $context, $basePrice);
            
            if ($calculatedPrice !== null) {
                $savings = $basePrice - $calculatedPrice;
                $savingsPercentage = $basePrice > 0 ? ($savings / $basePrice) * 100 : 0;

                return [
                    'price' => $calculatedPrice,
                    'base_price' => $basePrice,
                    'matrix_id' => $matrix->id,
                    'matrix_name' => $matrix->name,
                    'savings' => $savings,
                    'savings_percentage' => round($savingsPercentage, 2),
                    'tier' => $this->getTierForContext($matrix, $context),
                ];
            }
        }

        // Fallback to base price
        return [
            'price' => $basePrice,
            'base_price' => $basePrice,
            'matrix_id' => null,
            'savings' => 0,
            'savings_percentage' => 0,
            'tier' => null,
        ];
    }

    /**
     * Get applicable price matrices for variant and context.
     *
     * @param  ProductVariant  $variant
     * @param  array  $context
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getApplicableMatrices(ProductVariant $variant, array $context): \Illuminate\Database\Eloquent\Collection
    {
        return PriceMatrix::where(function ($query) use ($variant) {
                $query->where('product_id', $variant->product_id)
                      ->whereNull('product_variant_id')
                      ->orWhere('product_variant_id', $variant->id);
            })
            ->active()
            ->orderBy('priority', 'desc')
            ->get()
            ->filter(function ($matrix) use ($context) {
                return $this->matrixMatchesContext($matrix, $context);
            });
    }

    /**
     * Check if matrix matches context.
     *
     * @param  PriceMatrix  $matrix
     * @param  array  $context
     * @return bool
     */
    protected function matrixMatchesContext(PriceMatrix $matrix, array $context): bool
    {
        // Check date range
        if ($matrix->starts_at && $matrix->starts_at->isFuture()) {
            return false;
        }

        if ($matrix->expires_at && $matrix->expires_at->isPast()) {
            return false;
        }

        // Check minimum/maximum order quantity
        $quantity = $context['quantity'] ?? 1;
        
        if ($matrix->min_order_quantity && $quantity < $matrix->min_order_quantity) {
            return false;
        }

        if ($matrix->max_order_quantity && $quantity > $matrix->max_order_quantity) {
            return false;
        }

        // Check rules if matrix is rule-based
        if ($matrix->matrix_type === 'rule_based' && $matrix->rules) {
            return $this->evaluateRules($matrix->rules, $context);
        }

        return true;
    }

    /**
     * Evaluate rules against context.
     *
     * @param  array  $rules
     * @param  array  $context
     * @return bool
     */
    protected function evaluateRules(array $rules, array $context): bool
    {
        $conditions = $rules['conditions'] ?? [];

        foreach ($conditions as $condition) {
            $key = $condition['type'] ?? $condition['key'] ?? null;
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? null;

            if (!$key) {
                continue;
            }

            $contextValue = $context[$key] ?? null;

            if ($contextValue === null) {
                return false;
            }

            $matches = match ($operator) {
                '=' => $contextValue == $value,
                '!=' => $contextValue != $value,
                '>' => is_numeric($contextValue) && is_numeric($value) && $contextValue > $value,
                '>=' => is_numeric($contextValue) && is_numeric($value) && $contextValue >= $value,
                '<' => is_numeric($contextValue) && is_numeric($value) && $contextValue < $value,
                '<=' => is_numeric($contextValue) && is_numeric($value) && $contextValue <= $value,
                'in' => in_array($contextValue, (array) $value),
                'not_in' => !in_array($contextValue, (array) $value),
                'between' => is_numeric($contextValue) && is_array($value) && count($value) === 2 && $contextValue >= $value[0] && $contextValue <= $value[1],
                default => false,
            };

            if (!$matches) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate price from a matrix.
     *
     * @param  ProductVariant  $variant
     * @param  PriceMatrix  $matrix
     * @param  array  $context
     * @param  float  $basePrice
     * @return float|null
     */
    protected function calculateMatrixPrice(ProductVariant $variant, PriceMatrix $matrix, array $context, float $basePrice): ?float
    {
        switch ($matrix->matrix_type) {
            case 'quantity':
                return $this->calculateQuantityPrice($matrix, $context, $basePrice);

            case 'customer_group':
                return $this->calculateCustomerGroupPrice($matrix, $context, $basePrice);

            case 'region':
                return $this->calculateRegionalPrice($matrix, $context, $basePrice);

            case 'rule_based':
                return $this->calculateRuleBasedPrice($matrix, $context, $basePrice);

            case 'mixed':
                return $this->calculateMixedPrice($matrix, $context, $basePrice);

            default:
                return null;
        }
    }

    /**
     * Calculate quantity-based price.
     */
    protected function calculateQuantityPrice(PriceMatrix $matrix, array $context, float $basePrice): ?float
    {
        $quantity = $context['quantity'] ?? 1;

        // Check tiers first
        $tier = $matrix->tiers()->where('min_quantity', '<=', $quantity)
            ->where(function ($q) use ($quantity) {
                $q->whereNull('max_quantity')
                  ->orWhere('max_quantity', '>=', $quantity);
            })
            ->orderBy('min_quantity', 'desc')
            ->first();

        if ($tier) {
            return $this->applyTierPricing($tier, $basePrice);
        }

        // Check rules
        $rules = $matrix->pricingRules()->where('rule_type', 'quantity')->get();
        
        foreach ($rules as $rule) {
            if ($rule->matches(['quantity' => $quantity])) {
                return $this->applyRulePricing($rule, $basePrice);
            }
        }

        return null;
    }

    /**
     * Calculate customer group price.
     */
    protected function calculateCustomerGroupPrice(PriceMatrix $matrix, array $context, float $basePrice): ?float
    {
        $customerGroup = $context['customer_group'] ?? $context['customer_group_id'] ?? null;

        if (!$customerGroup) {
            return null;
        }

        $rules = $matrix->pricingRules()->where('rule_type', 'customer_group')->get();

        foreach ($rules as $rule) {
            if ($rule->matches(['customer_group' => $customerGroup])) {
                return $this->applyRulePricing($rule, $basePrice);
            }
        }

        return null;
    }

    /**
     * Calculate regional price.
     */
    protected function calculateRegionalPrice(PriceMatrix $matrix, array $context, float $basePrice): ?float
    {
        $region = $context['region'] ?? $context['country'] ?? $context['country_code'] ?? null;

        if (!$region) {
            return null;
        }

        $rules = $matrix->pricingRules()->where('rule_type', 'region')->get();

        foreach ($rules as $rule) {
            if ($rule->matches(['region' => $region])) {
                return $this->applyRulePricing($rule, $basePrice);
            }
        }

        return null;
    }

    /**
     * Calculate rule-based price.
     */
    protected function calculateRuleBasedPrice(PriceMatrix $matrix, array $context, float $basePrice): ?float
    {
        $rules = $matrix->pricingRules()->orderBy('priority', 'desc')->get();

        foreach ($rules as $rule) {
            if ($rule->matches($context)) {
                return $this->applyRulePricing($rule, $basePrice);
            }
        }

        return null;
    }

    /**
     * Calculate mixed price (multiple conditions).
     */
    protected function calculateMixedPrice(PriceMatrix $matrix, array $context, float $basePrice): ?float
    {
        // For mixed matrices, all rules must match
        $rules = $matrix->pricingRules()->orderBy('priority', 'desc')->get();

        $allMatch = true;
        $matchedRule = null;

        foreach ($rules as $rule) {
            if (!$rule->matches($context)) {
                $allMatch = false;
                break;
            }
            $matchedRule = $rule;
        }

        if ($allMatch && $matchedRule) {
            return $this->applyRulePricing($matchedRule, $basePrice);
        }

        return null;
    }

    /**
     * Apply tier pricing.
     */
    protected function applyTierPricing(PricingTier $tier, float $basePrice): float
    {
        return match ($tier->pricing_type) {
            'fixed' => $tier->price ?? $basePrice,
            'adjustment' => $basePrice + ($tier->price_adjustment ?? 0),
            'percentage' => $basePrice * (1 - ($tier->percentage_discount ?? 0) / 100),
            default => $basePrice,
        };
    }

    /**
     * Apply rule pricing.
     */
    protected function applyRulePricing(PricingRule $rule, float $basePrice): float
    {
        return match ($rule->adjustment_type) {
            'fixed', 'override' => $rule->price ?? $basePrice,
            'add' => $basePrice + ($rule->price_adjustment ?? 0),
            'subtract' => $basePrice - ($rule->price_adjustment ?? 0),
            'percentage' => $basePrice * (1 - ($rule->percentage_discount ?? 0) / 100),
            default => $basePrice,
        };
    }

    /**
     * Get tiered pricing for a variant.
     *
     * @param  ProductVariant  $variant
     * @param  array  $context
     * @return array
     */
    public function getTieredPricing(ProductVariant $variant, array $context = []): array
    {
        $currency = Currency::getDefault();
        $basePricing = Pricing::for($variant)->currency($currency)->get();
        $basePrice = $basePricing->matched?->price?->value ?? 0;

        $matrices = PriceMatrix::where('product_id', $variant->product_id)
            ->where('matrix_type', 'quantity')
            ->active()
            ->orderBy('priority', 'desc')
            ->get();

        $tiers = [];

        foreach ($matrices as $matrix) {
            foreach ($matrix->tiers as $tier) {
                $price = $this->applyTierPricing($tier, $basePrice);
                $savings = $basePrice - $price;
                $savingsPercentage = $basePrice > 0 ? ($savings / $basePrice) * 100 : 0;

                $tiers[] = [
                    'tier_id' => $tier->id,
                    'matrix_id' => $matrix->id,
                    'tier_name' => $tier->tier_name,
                    'min_quantity' => $tier->min_quantity,
                    'max_quantity' => $tier->max_quantity,
                    'price' => $price,
                    'base_price' => $basePrice,
                    'savings' => $savings,
                    'savings_percentage' => round($savingsPercentage, 2),
                ];
            }
        }

        // Sort by min_quantity
        usort($tiers, function ($a, $b) {
            return $a['min_quantity'] <=> $b['min_quantity'];
        });

        return $tiers;
    }

    /**
     * Get volume discounts.
     *
     * @param  ProductVariant  $variant
     * @param  array  $context
     * @return array
     */
    public function getVolumeDiscounts(ProductVariant $variant, array $context = []): array
    {
        return $this->getTieredPricing($variant, $context);
    }

    /**
     * Get tier for context.
     */
    protected function getTierForContext(PriceMatrix $matrix, array $context): ?array
    {
        if ($matrix->matrix_type !== 'quantity') {
            return null;
        }

        $quantity = $context['quantity'] ?? 1;

        $tier = $matrix->tiers()->where('min_quantity', '<=', $quantity)
            ->where(function ($q) use ($quantity) {
                $q->whereNull('max_quantity')
                  ->orWhere('max_quantity', '>=', $quantity);
            })
            ->orderBy('min_quantity', 'desc')
            ->first();

        if ($tier) {
            return [
                'id' => $tier->id,
                'name' => $tier->tier_name,
                'min_quantity' => $tier->min_quantity,
                'max_quantity' => $tier->max_quantity,
            ];
        }

        return null;
    }

    /**
     * Record price change in history.
     *
     * @param  ProductVariant  $variant
     * @param  float  $oldPrice
     * @param  float  $newPrice
     * @param  array  $context
     * @param  string  $changeType
     * @param  int|null  $matrixId
     * @return PriceHistory
     */
    public function recordPriceChange(
        ProductVariant $variant,
        float $oldPrice,
        float $newPrice,
        array $context = [],
        string $changeType = 'manual',
        ?int $matrixId = null
    ): PriceHistory {
        return PriceHistory::create([
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'price_matrix_id' => $matrixId,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'currency_code' => Currency::getDefault()->code ?? 'USD',
            'change_type' => $changeType,
            'context' => $context,
            'changed_by' => auth()->id(),
            'changed_at' => now(),
        ]);
    }

    /**
     * Get pricing report.
     *
     * @param  array  $filters
     * @return array
     */
    public function getPricingReport(array $filters = []): array
    {
        $query = PriceHistory::query();

        if (isset($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (isset($filters['customer_group'])) {
            $query->whereJsonContains('context->customer_group', $filters['customer_group']);
        }

        if (isset($filters['region'])) {
            $query->whereJsonContains('context->region', $filters['region']);
        }

        if (isset($filters['start_date'])) {
            $query->where('changed_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('changed_at', '<=', $filters['end_date']);
        }

        $history = $query->with(['product', 'productVariant', 'priceMatrix'])
            ->orderBy('changed_at', 'desc')
            ->get();

        return [
            'total_changes' => $history->count(),
            'changes' => $history,
            'summary' => $this->generateReportSummary($history),
        ];
    }

    /**
     * Generate report summary.
     */
    protected function generateReportSummary($history): array
    {
        $summary = [
            'by_product' => [],
            'by_customer_group' => [],
            'by_region' => [],
        ];

        foreach ($history as $change) {
            // By product
            $productId = $change->product_id;
            if (!isset($summary['by_product'][$productId])) {
                $summary['by_product'][$productId] = [
                    'product_id' => $productId,
                    'product_name' => $change->product->translateAttribute('name') ?? 'Unknown',
                    'change_count' => 0,
                ];
            }
            $summary['by_product'][$productId]['change_count']++;

            // By customer group
            $customerGroup = $change->context['customer_group'] ?? 'default';
            if (!isset($summary['by_customer_group'][$customerGroup])) {
                $summary['by_customer_group'][$customerGroup] = [
                    'customer_group' => $customerGroup,
                    'change_count' => 0,
                ];
            }
            $summary['by_customer_group'][$customerGroup]['change_count']++;

            // By region
            $region = $change->context['region'] ?? 'default';
            if (!isset($summary['by_region'][$region])) {
                $summary['by_region'][$region] = [
                    'region' => $region,
                    'change_count' => 0,
                ];
            }
            $summary['by_region'][$region]['change_count']++;
        }

        return $summary;
    }
}



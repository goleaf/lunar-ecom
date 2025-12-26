<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\PricingRule;
use Lunar\Models\Currency;
use Lunar\Models\Channel;
use Lunar\Models\CustomerGroup;
use Lunar\Models\Customer;
use Illuminate\Support\Collection;

/**
 * Pricing Rule Engine.
 * 
 * Applies pricing rules to variants with comprehensive condition support.
 */
class PricingRuleEngine
{
    /**
     * Apply pricing rules to variant.
     */
    public function applyRules(
        ProductVariant $variant,
        int $basePrice,
        int $quantity = 1,
        ?Currency $currency = null,
        ?Channel $channel = null,
        ?CustomerGroup $customerGroup = null,
        ?Customer $customer = null,
        array $context = []
    ): array {
        $context = array_merge($context, [
            'quantity' => $quantity,
            'currency_id' => $currency?->id,
            'channel_id' => $channel?->id,
            'customer_group_id' => $customerGroup?->id,
            'customer_id' => $customer?->id,
            'variant' => $variant,
            'product' => $variant->product,
            'currency' => $currency,
            'channel' => $channel,
            'customer_group' => $customerGroup,
            'customer' => $customer,
        ]);

        $rules = $this->getApplicableRules($variant, $context);
        $nonStackableRules = $rules->where('is_stackable', false)->sortByDesc('priority');
        $stackableRules = $rules->where('is_stackable', true)->sortByDesc('priority');

        $currentPrice = $basePrice;
        $appliedRules = collect();
        $stackDepth = 0;
        $maxStackDepth = $stackableRules->max('max_stack_depth') ?? PHP_INT_MAX;

        foreach ($nonStackableRules as $rule) {
            $result = $this->applyRule($rule, $variant, $currentPrice, $context);
            if ($result['applied']) {
                $currentPrice = $result['price'];
                $appliedRules->push([
                    'rule' => $rule,
                    'type' => $rule->rule_type,
                    'price_before' => $basePrice,
                    'price_after' => $result['price'],
                    'adjustment' => $result['adjustment'],
                ]);
                break;
            }
        }

        foreach ($stackableRules as $rule) {
            if ($stackDepth >= $maxStackDepth) {
                break;
            }
            $result = $this->applyRule($rule, $variant, $currentPrice, $context);
            if ($result['applied']) {
                $currentPrice = $result['price'];
                $appliedRules->push([
                    'rule' => $rule,
                    'type' => $rule->rule_type,
                    'price_before' => $appliedRules->isNotEmpty() ? $appliedRules->last()['price_after'] : $basePrice,
                    'price_after' => $result['price'],
                    'adjustment' => $result['adjustment'],
                ]);
                $stackDepth++;
            }
        }

        return [
            'original_price' => $basePrice,
            'final_price' => $currentPrice,
            'applied_rules' => $appliedRules,
            'total_discount' => $basePrice - $currentPrice,
        ];
    }

    protected function getApplicableRules(ProductVariant $variant, array $context): Collection
    {
        return PricingRule::active()
            ->orderedByPriority()
            ->get()
            ->filter(fn($rule) => $rule->appliesTo($variant, $context));
    }

    protected function applyRule(PricingRule $rule, ProductVariant $variant, int $currentPrice, array $context): array
    {
        $config = $rule->rule_config ?? [];

        return match ($rule->rule_type) {
            'fixed_price' => $this->applyFixedPrice($rule, $currentPrice, $config),
            'percentage_discount' => $this->applyPercentageDiscount($rule, $currentPrice, $config),
            'absolute_discount' => $this->applyAbsoluteDiscount($rule, $currentPrice, $config),
            'cost_plus' => $this->applyCostPlus($rule, $variant, $currentPrice, $config),
            'margin_protected' => $this->applyMarginProtected($rule, $variant, $currentPrice, $config),
            'map_enforcement' => $this->applyMAPEnforcement($rule, $variant, $currentPrice, $config),
            'rounding' => $this->applyRounding($rule, $currentPrice, $config),
            'currency_override' => $this->applyCurrencyOverride($rule, $currentPrice, $config, $context),
            default => ['applied' => false, 'price' => $currentPrice, 'adjustment' => 0],
        };
    }

    protected function applyFixedPrice(PricingRule $rule, int $currentPrice, array $config): array
    {
        $fixedPrice = $config['price'] ?? null;
        if ($fixedPrice === null) {
            return ['applied' => false, 'price' => $currentPrice, 'adjustment' => 0];
        }
        return [
            'applied' => true,
            'price' => (int)$fixedPrice,
            'adjustment' => $currentPrice - (int)$fixedPrice,
        ];
    }

    protected function applyPercentageDiscount(PricingRule $rule, int $currentPrice, array $config): array
    {
        $percentage = $config['percentage'] ?? null;
        if ($percentage === null || $percentage <= 0 || $percentage > 100) {
            return ['applied' => false, 'price' => $currentPrice, 'adjustment' => 0];
        }
        $discount = (int)round($currentPrice * ($percentage / 100));
        $newPrice = $currentPrice - $discount;
        if (isset($config['min_price']) && $newPrice < $config['min_price']) {
            $newPrice = $config['min_price'];
            $discount = $currentPrice - $newPrice;
        }
        if (isset($config['max_price']) && $newPrice > $config['max_price']) {
            return ['applied' => false, 'price' => $currentPrice, 'adjustment' => 0];
        }
        return [
            'applied' => true,
            'price' => max(0, $newPrice),
            'adjustment' => $discount,
        ];
    }

    protected function applyAbsoluteDiscount(PricingRule $rule, int $currentPrice, array $config): array
    {
        $discountAmount = $config['discount_amount'] ?? null;
        if ($discountAmount === null || $discountAmount <= 0) {
            return ['applied' => false, 'price' => $currentPrice, 'adjustment' => 0];
        }
        $newPrice = $currentPrice - (int)$discountAmount;
        if (isset($config['min_price']) && $newPrice < $config['min_price']) {
            $newPrice = $config['min_price'];
            $discountAmount = $currentPrice - $newPrice;
        }
        return [
            'applied' => true,
            'price' => max(0, $newPrice),
            'adjustment' => $discountAmount,
        ];
    }

    protected function applyCostPlus(PricingRule $rule, ProductVariant $variant, int $currentPrice, array $config): array
    {
        $costPrice = $variant->cost_price ?? $config['cost_price'] ?? null;
        if ($costPrice === null) {
            return ['applied' => false, 'price' => $currentPrice, 'adjustment' => 0];
        }
        $marginPercentage = $config['margin_percentage'] ?? null;
        $marginAmount = $config['margin_amount'] ?? null;
        if ($marginPercentage !== null) {
            $newPrice = (int)round($costPrice * (1 + ($marginPercentage / 100)));
        } elseif ($marginAmount !== null) {
            $newPrice = $costPrice + (int)$marginAmount;
        } else {
            return ['applied' => false, 'price' => $currentPrice, 'adjustment' => 0];
        }
        return [
            'applied' => true,
            'price' => $newPrice,
            'adjustment' => $currentPrice - $newPrice,
        ];
    }

    protected function applyMarginProtected(PricingRule $rule, ProductVariant $variant, int $currentPrice, array $config): array
    {
        $costPrice = $variant->cost_price ?? $config['cost_price'] ?? null;
        $minMarginPercentage = $config['min_margin_percentage'] ?? null;
        if ($costPrice === null || $minMarginPercentage === null) {
            return ['applied' => false, 'price' => $currentPrice, 'adjustment' => 0];
        }
        $minPrice = (int)round($costPrice * (1 + ($minMarginPercentage / 100)));
        if ($currentPrice < $minPrice) {
            return [
                'applied' => true,
                'price' => $minPrice,
                'adjustment' => $minPrice - $currentPrice,
            ];
        }
        return ['applied' => false, 'price' => $currentPrice, 'adjustment' => 0];
    }

    protected function applyMAPEnforcement(PricingRule $rule, ProductVariant $variant, int $currentPrice, array $config): array
    {
        $mapPrice = $variant->map_price ?? $config['map_price'] ?? null;
        if ($mapPrice === null) {
            return ['applied' => false, 'price' => $currentPrice, 'adjustment' => 0];
        }
        if ($currentPrice < $mapPrice) {
            return [
                'applied' => true,
                'price' => $mapPrice,
                'adjustment' => $mapPrice - $currentPrice,
            ];
        }
        return ['applied' => false, 'price' => $currentPrice, 'adjustment' => 0];
    }

    protected function applyRounding(PricingRule $rule, int $currentPrice, array $config): array
    {
        $roundingMethod = $config['method'] ?? 'round';
        $roundTo = $config['round_to'] ?? 100;
        $newPrice = match ($roundingMethod) {
            'round_up' => (int)ceil($currentPrice / $roundTo) * $roundTo,
            'round_down' => (int)floor($currentPrice / $roundTo) * $roundTo,
            'nearest' => (int)round($currentPrice / $roundTo) * $roundTo,
            'round' => (int)round($currentPrice / $roundTo) * $roundTo,
            default => $currentPrice,
        };
        if ($newPrice === $currentPrice) {
            return ['applied' => false, 'price' => $currentPrice, 'adjustment' => 0];
        }
        return [
            'applied' => true,
            'price' => $newPrice,
            'adjustment' => $newPrice - $currentPrice,
        ];
    }

    protected function applyCurrencyOverride(PricingRule $rule, int $currentPrice, array $config, array $context): array
    {
        if (!$rule->currency_id || !isset($context['currency_id']) || $rule->currency_id !== $context['currency_id']) {
            return ['applied' => false, 'price' => $currentPrice, 'adjustment' => 0];
        }
        $overridePrice = $config['price'] ?? null;
        $exchangeRate = $config['exchange_rate'] ?? null;
        if ($overridePrice !== null) {
            return [
                'applied' => true,
                'price' => (int)$overridePrice,
                'adjustment' => $currentPrice - (int)$overridePrice,
            ];
        }
        if ($exchangeRate !== null) {
            $newPrice = (int)round($currentPrice * $exchangeRate);
            return [
                'applied' => true,
                'price' => $newPrice,
                'adjustment' => $currentPrice - $newPrice,
            ];
        }
        return ['applied' => false, 'price' => $currentPrice, 'adjustment' => 0];
    }
}



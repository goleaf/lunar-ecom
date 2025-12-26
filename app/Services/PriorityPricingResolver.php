<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\VariantPrice;
use App\Services\PricingRuleEngine;
use Lunar\Models\Currency;
use Lunar\Models\Channel;
use Lunar\Models\CustomerGroup;
use Lunar\Models\Customer;
use Carbon\Carbon;

/**
 * Priority-Based Pricing Resolver.
 * 
 * Resolves prices in strict priority order:
 * 1. Manual override price
 * 2. Contract price (B2B / customer-specific)
 * 3. Customer group price
 * 4. Channel price
 * 5. Time-based promotional price
 * 6. Tiered (quantity) price
 * 7. Base variant price
 * 
 * The first valid rule wins (configurable).
 */
class PriorityPricingResolver
{
    /**
     * Default priority order (highest to lowest).
     */
    protected array $priorityOrder = [
        'manual_override' => 1000,
        'contract' => 900,
        'customer_group' => 800,
        'channel' => 700,
        'promotional' => 600,
        'tiered' => 500,
        'base' => 100,
    ];

    /**
     * Resolve price for variant.
     *
     * @param  ProductVariant  $variant
     * @param  int  $quantity
     * @param  Currency|null  $currency
     * @param  Channel|null  $channel
     * @param  CustomerGroup|null  $customerGroup
     * @param  Customer|null  $customer
     * @param  array  $customPriorityOrder
     * @return array|null Returns price data or null if no price found
     */
    public function resolvePrice(
        ProductVariant $variant,
        int $quantity = 1,
        ?Currency $currency = null,
        ?Channel $channel = null,
        ?CustomerGroup $customerGroup = null,
        ?Customer $customer = null,
        array $customPriorityOrder = []
    ): ?array {
        // Use custom priority order if provided
        $priorityOrder = !empty($customPriorityOrder) ? $customPriorityOrder : $this->priorityOrder;
        
        // Sort by priority (highest first)
        arsort($priorityOrder);

        // Get currency
        if (!$currency) {
            $currency = Currency::where('default', true)->first();
        }

        if (!$currency) {
            return null;
        }

        // Try each pricing layer in priority order
        foreach ($priorityOrder as $layer => $priority) {
            $price = $this->resolveLayerPrice(
                $variant,
                $layer,
                $quantity,
                $currency,
                $channel,
                $customerGroup,
                $customer
            );

            if ($price !== null) {
                $resolvedPrice = $price['price'];
                
                // Apply pricing rules to the resolved price
                $ruleEngine = app(PricingRuleEngine::class);
                $ruleResult = $ruleEngine->applyRules(
                    $variant,
                    $resolvedPrice,
                    $quantity,
                    $currency,
                    $channel,
                    $customerGroup,
                    $customer,
                    $customPriorityOrder
                );

                return [
                    'price' => $ruleResult['final_price'],
                    'original_price' => $price['price'], // Original price before rules
                    'compare_at_price' => $price['compare_at_price'] ?? null,
                    'layer' => $layer,
                    'source' => $price['source'] ?? null,
                    'currency' => $currency,
                    'tax_inclusive' => $price['tax_inclusive'] ?? false,
                    'applied_rules' => $ruleResult['applied_rules'],
                ];
            }
        }

        // Fallback to variant's base price_override or Lunar pricing
        $basePrice = $this->getBasePrice($variant, $currency, $quantity);
        
        if ($basePrice) {
            // Apply pricing rules to base price
            $ruleEngine = app(PricingRuleEngine::class);
            $ruleResult = $ruleEngine->applyRules(
                $variant,
                $basePrice['price'],
                $quantity,
                $currency,
                $channel,
                $customerGroup,
                $customer,
                []
            );

            return [
                'price' => $ruleResult['final_price'],
                'original_price' => $basePrice['price'],
                'compare_at_price' => $basePrice['compare_at_price'] ?? null,
                'layer' => 'base',
                'source' => $basePrice['source'] ?? 'lunar_base',
                'currency' => $currency,
                'tax_inclusive' => $basePrice['tax_inclusive'] ?? false,
                'applied_rules' => $ruleResult['applied_rules'],
            ];
        }

        return null;
    }

    /**
     * Resolve price for a specific layer.
     *
     * @param  ProductVariant  $variant
     * @param  string  $layer
     * @param  int  $quantity
     * @param  Currency  $currency
     * @param  Channel|null  $channel
     * @param  CustomerGroup|null  $customerGroup
     * @param  Customer|null  $customer
     * @return array|null
     */
    protected function resolveLayerPrice(
        ProductVariant $variant,
        string $layer,
        int $quantity,
        Currency $currency,
        ?Channel $channel,
        ?CustomerGroup $customerGroup,
        ?Customer $customer
    ): ?array {
        return match ($layer) {
            'manual_override' => $this->resolveManualOverride($variant, $currency, $quantity),
            'contract' => $this->resolveContractPrice($variant, $currency, $quantity, $customer),
            'customer_group' => $this->resolveCustomerGroupPrice($variant, $currency, $quantity, $customerGroup),
            'channel' => $this->resolveChannelPrice($variant, $currency, $quantity, $channel),
            'promotional' => $this->resolvePromotionalPrice($variant, $currency, $quantity, $channel, $customerGroup),
            'tiered' => $this->resolveTieredPrice($variant, $currency, $quantity, $channel, $customerGroup),
            'base' => $this->resolveBasePrice($variant, $currency, $quantity),
            default => null,
        };
    }

    /**
     * Resolve manual override price.
     *
     * @param  ProductVariant  $variant
     * @param  Currency  $currency
     * @param  int  $quantity
     * @return array|null
     */
    protected function resolveManualOverride(
        ProductVariant $variant,
        Currency $currency,
        int $quantity
    ): ?array {
        $price = VariantPrice::where('variant_id', $variant->id)
            ->where('currency_id', $currency->id)
            ->where('pricing_layer', 'manual_override')
            ->where('is_manual_override', true)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderByDesc('priority')
            ->first();

        if ($price && $price->appliesToQuantity($quantity)) {
            return [
                'price' => $price->price,
                'compare_at_price' => $price->compare_at_price,
                'tax_inclusive' => $price->tax_inclusive,
                'source' => 'manual_override',
            ];
        }

        // Check variant's price_override field
        if ($variant->price_override !== null) {
            return [
                'price' => $variant->price_override,
                'compare_at_price' => $variant->compare_at_price,
                'tax_inclusive' => $variant->tax_type === 'inclusive',
                'source' => 'variant_override',
            ];
        }

        return null;
    }

    /**
     * Resolve contract price (B2B / customer-specific).
     *
     * @param  ProductVariant  $variant
     * @param  Currency  $currency
     * @param  int  $quantity
     * @param  Customer|null  $customer
     * @return array|null
     */
    protected function resolveContractPrice(
        ProductVariant $variant,
        Currency $currency,
        int $quantity,
        ?Customer $customer
    ): ?array {
        if (!$customer) {
            return null;
        }

        $price = VariantPrice::where('variant_id', $variant->id)
            ->where('currency_id', $currency->id)
            ->where('pricing_layer', 'contract')
            ->where(function ($q) use ($customer) {
                $q->where('customer_id', $customer->id)
                  ->orWhereHas('contract', function ($contractQuery) use ($customer) {
                      $contractQuery->where('customer_id', $customer->id)
                                   ->where('is_active', true)
                                   ->where(function ($dateQuery) {
                                       $dateQuery->whereNull('starts_at')
                                                 ->orWhere('starts_at', '<=', now());
                                   })
                                   ->where(function ($dateQuery) {
                                       $dateQuery->whereNull('ends_at')
                                                 ->orWhere('ends_at', '>=', now());
                                   });
                  });
            })
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->where(function ($q) use ($quantity) {
                $q->where('min_quantity', '<=', $quantity)
                  ->where(function ($subQ) use ($quantity) {
                      $subQ->whereNull('max_quantity')
                           ->orWhere('max_quantity', '>=', $quantity);
                  });
            })
            ->orderByDesc('priority')
            ->orderByDesc('min_quantity') // Best tier for quantity
            ->first();

        if ($price) {
            return [
                'price' => $price->price,
                'compare_at_price' => $price->compare_at_price,
                'tax_inclusive' => $price->tax_inclusive,
                'source' => 'contract',
                'contract_id' => $price->contract_id,
            ];
        }

        return null;
    }

    /**
     * Resolve customer group price.
     *
     * @param  ProductVariant  $variant
     * @param  Currency  $currency
     * @param  int  $quantity
     * @param  CustomerGroup|null  $customerGroup
     * @return array|null
     */
    protected function resolveCustomerGroupPrice(
        ProductVariant $variant,
        Currency $currency,
        int $quantity,
        ?CustomerGroup $customerGroup
    ): ?array {
        if (!$customerGroup) {
            return null;
        }

        $price = VariantPrice::where('variant_id', $variant->id)
            ->where('currency_id', $currency->id)
            ->where('pricing_layer', 'customer_group')
            ->where('customer_group_id', $customerGroup->id)
            ->whereNull('customer_id') // Not customer-specific
            ->whereNull('contract_id') // Not contract-specific
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->where(function ($q) use ($quantity) {
                $q->where('min_quantity', '<=', $quantity)
                  ->where(function ($subQ) use ($quantity) {
                      $subQ->whereNull('max_quantity')
                           ->orWhere('max_quantity', '>=', $quantity);
                  });
            })
            ->orderByDesc('priority')
            ->orderByDesc('min_quantity')
            ->first();

        if ($price) {
            return [
                'price' => $price->price,
                'compare_at_price' => $price->compare_at_price,
                'tax_inclusive' => $price->tax_inclusive,
                'source' => 'customer_group',
                'customer_group_id' => $customerGroup->id,
            ];
        }

        return null;
    }

    /**
     * Resolve channel price.
     *
     * @param  ProductVariant  $variant
     * @param  Currency  $currency
     * @param  int  $quantity
     * @param  Channel|null  $channel
     * @return array|null
     */
    protected function resolveChannelPrice(
        ProductVariant $variant,
        Currency $currency,
        int $quantity,
        ?Channel $channel
    ): ?array {
        if (!$channel) {
            return null;
        }

        $price = VariantPrice::where('variant_id', $variant->id)
            ->where('currency_id', $currency->id)
            ->where('pricing_layer', 'channel')
            ->where('channel_id', $channel->id)
            ->whereNull('customer_id')
            ->whereNull('customer_group_id')
            ->whereNull('contract_id')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->where(function ($q) use ($quantity) {
                $q->where('min_quantity', '<=', $quantity)
                  ->where(function ($subQ) use ($quantity) {
                      $subQ->whereNull('max_quantity')
                           ->orWhere('max_quantity', '>=', $quantity);
                  });
            })
            ->orderByDesc('priority')
            ->orderByDesc('min_quantity')
            ->first();

        if ($price) {
            return [
                'price' => $price->price,
                'compare_at_price' => $price->compare_at_price,
                'tax_inclusive' => $price->tax_inclusive,
                'source' => 'channel',
                'channel_id' => $channel->id,
            ];
        }

        return null;
    }

    /**
     * Resolve time-based promotional price.
     *
     * @param  ProductVariant  $variant
     * @param  Currency  $currency
     * @param  int  $quantity
     * @param  Channel|null  $channel
     * @param  CustomerGroup|null  $customerGroup
     * @return array|null
     */
    protected function resolvePromotionalPrice(
        ProductVariant $variant,
        Currency $currency,
        int $quantity,
        ?Channel $channel,
        ?CustomerGroup $customerGroup
    ): ?array {
        $now = Carbon::now();

        $query = VariantPrice::where('variant_id', $variant->id)
            ->where('currency_id', $currency->id)
            ->where('pricing_layer', 'promotional')
            ->where('is_active', true)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now)
            ->where(function ($q) use ($quantity) {
                $q->where('min_quantity', '<=', $quantity)
                  ->where(function ($subQ) use ($quantity) {
                      $subQ->whereNull('max_quantity')
                           ->orWhere('max_quantity', '>=', $quantity);
                  });
            })
            ->whereNull('customer_id')
            ->whereNull('contract_id');

        // Optional channel filter
        if ($channel) {
            $query->where(function ($q) use ($channel) {
                $q->whereNull('channel_id')->orWhere('channel_id', $channel->id);
            });
        } else {
            $query->whereNull('channel_id');
        }

        // Optional customer group filter
        if ($customerGroup) {
            $query->where(function ($q) use ($customerGroup) {
                $q->whereNull('customer_group_id')->orWhere('customer_group_id', $customerGroup->id);
            });
        } else {
            $query->whereNull('customer_group_id');
        }

        $price = $query->orderByDesc('priority')
            ->orderByDesc('min_quantity')
            ->first();

        if ($price) {
            return [
                'price' => $price->price,
                'compare_at_price' => $price->compare_at_price,
                'tax_inclusive' => $price->tax_inclusive,
                'source' => 'promotional',
                'starts_at' => $price->starts_at,
                'ends_at' => $price->ends_at,
            ];
        }

        return null;
    }

    /**
     * Resolve tiered (quantity) price.
     *
     * @param  ProductVariant  $variant
     * @param  Currency  $currency
     * @param  int  $quantity
     * @param  Channel|null  $channel
     * @param  CustomerGroup|null  $customerGroup
     * @return array|null
     */
    protected function resolveTieredPrice(
        ProductVariant $variant,
        Currency $currency,
        int $quantity,
        ?Channel $channel,
        ?CustomerGroup $customerGroup
    ): ?array {
        $query = VariantPrice::where('variant_id', $variant->id)
            ->where('currency_id', $currency->id)
            ->where('pricing_layer', 'tiered')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->where('min_quantity', '<=', $quantity)
            ->where(function ($q) use ($quantity) {
                $q->whereNull('max_quantity')->orWhere('max_quantity', '>=', $quantity);
            })
            ->whereNull('customer_id')
            ->whereNull('contract_id');

        // Optional channel filter
        if ($channel) {
            $query->where(function ($q) use ($channel) {
                $q->whereNull('channel_id')->orWhere('channel_id', $channel->id);
            });
        } else {
            $query->whereNull('channel_id');
        }

        // Optional customer group filter
        if ($customerGroup) {
            $query->where(function ($q) use ($customerGroup) {
                $q->whereNull('customer_group_id')->orWhere('customer_group_id', $customerGroup->id);
            });
        } else {
            $query->whereNull('customer_group_id');
        }

        $price = $query->orderByDesc('min_quantity') // Best tier for quantity
            ->orderByDesc('priority')
            ->first();

        if ($price) {
            return [
                'price' => $price->price,
                'compare_at_price' => $price->compare_at_price,
                'tax_inclusive' => $price->tax_inclusive,
                'source' => 'tiered',
                'min_quantity' => $price->min_quantity,
                'max_quantity' => $price->max_quantity,
            ];
        }

        return null;
    }

    /**
     * Resolve base price.
     *
     * @param  ProductVariant  $variant
     * @param  Currency  $currency
     * @param  int  $quantity
     * @return array|null
     */
    protected function resolveBasePrice(
        ProductVariant $variant,
        Currency $currency,
        int $quantity
    ): ?array {
        $price = VariantPrice::where('variant_id', $variant->id)
            ->where('currency_id', $currency->id)
            ->where('pricing_layer', 'base')
            ->where('is_active', true)
            ->whereNull('channel_id')
            ->whereNull('customer_group_id')
            ->whereNull('customer_id')
            ->whereNull('contract_id')
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->where(function ($q) use ($quantity) {
                $q->where('min_quantity', '<=', $quantity)
                  ->where(function ($subQ) use ($quantity) {
                      $subQ->whereNull('max_quantity')
                           ->orWhere('max_quantity', '>=', $quantity);
                  });
            })
            ->orderByDesc('priority')
            ->orderByDesc('min_quantity')
            ->first();

        if ($price) {
            return [
                'price' => $price->price,
                'compare_at_price' => $price->compare_at_price,
                'tax_inclusive' => $price->tax_inclusive,
                'source' => 'base',
            ];
        }

        return null;
    }

    /**
     * Get base price fallback.
     *
     * @param  ProductVariant  $variant
     * @param  Currency  $currency
     * @param  int  $quantity
     * @return array|null
     */
    protected function getBasePrice(
        ProductVariant $variant,
        Currency $currency,
        int $quantity
    ): ?array {
        // Try variant's price_override
        if ($variant->price_override !== null) {
            return [
                'price' => $variant->price_override,
                'compare_at_price' => $variant->compare_at_price,
                'tax_inclusive' => $variant->tax_type === 'inclusive',
                'source' => 'variant_override',
            ];
        }

        // Use Lunar's pricing system as final fallback
        $pricing = \Lunar\Facades\Pricing::qty($quantity)->for($variant);
        $response = $pricing->get();
        
        if ($response->matched?->price) {
            return [
                'price' => $response->matched->price->value,
                'compare_at_price' => null,
                'tax_inclusive' => false,
                'source' => 'lunar_base',
            ];
        }

        return null;
    }

    /**
     * Get priority order configuration.
     *
     * @return array
     */
    public function getPriorityOrder(): array
    {
        return $this->priorityOrder;
    }

    /**
     * Set custom priority order.
     *
     * @param  array  $priorityOrder
     * @return void
     */
    public function setPriorityOrder(array $priorityOrder): void
    {
        $this->priorityOrder = $priorityOrder;
    }
}


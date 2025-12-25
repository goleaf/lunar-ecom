<?php

namespace App\Services;

use App\Services\CartPricing\DTOs\CartPricingResult;
use App\Services\CartPricing\DTOs\DiscountBreakdown;
use App\Services\CartPricing\DTOs\LineItemPricing;
use App\Services\CartPricing\DTOs\ShippingCost;
use App\Services\CartPricing\DTOs\TaxBreakdown;
use App\Services\CartPricing\Pipeline\ApplyB2BContractStep;
use App\Services\CartPricing\Pipeline\ApplyCartDiscountsStep;
use App\Services\CartPricing\Pipeline\ApplyItemDiscountsStep;
use App\Services\CartPricing\Pipeline\ApplyQuantityTierStep;
use App\Services\CartPricing\Pipeline\ApplyRoundingStep;
use App\Services\CartPricing\Pipeline\CalculateShippingStep;
use App\Services\CartPricing\Pipeline\CalculateTaxStep;
use App\Services\CartPricing\Pipeline\ResolveBasePriceStep;
use App\Services\CartPricing\PriceIntegrityService;
use Illuminate\Support\Collection;
use Lunar\Models\Cart;
use Lunar\Models\CartLine;

/**
 * Cart Pricing Engine - Main service orchestrating the pricing pipeline.
 * 
 * Recomputes prices deterministically on every change, stores detailed
 * pricing metadata, and provides real-time repricing with full audit trails.
 */
class CartPricingEngine
{
    public function __construct(
        protected ResolveBasePriceStep $resolveBasePriceStep,
        protected ApplyB2BContractStep $applyB2BContractStep,
        protected ApplyQuantityTierStep $applyQuantityTierStep,
        protected ApplyItemDiscountsStep $applyItemDiscountsStep,
        protected ApplyCartDiscountsStep $applyCartDiscountsStep,
        protected CalculateShippingStep $calculateShippingStep,
        protected CalculateTaxStep $calculateTaxStep,
        protected ApplyRoundingStep $applyRoundingStep,
        protected PriceIntegrityService $integrityService
    ) {}

    /**
     * Calculate prices for entire cart.
     */
    public function calculateCartPrices(Cart $cart): CartPricingResult
    {
        // Ensure cart is hydrated
        $cart->load(['lines.purchasable', 'currency', 'channel', 'customer.customerGroups']);
        
        // Run Lunar's cart calculation first to get shipping/tax totals
        $cart->calculate();
        
        // Calculate pricing for each line item
        $lineItemPricings = collect();
        $lineItemsData = [];
        $allAppliedRules = [];
        $itemDiscounts = collect();
        
        foreach ($cart->lines as $line) {
            $linePricing = $this->calculateLineItemPrice($line, $cart);
            $lineItemPricings->push($linePricing);
            
            // Store line data for cart-level discount distribution
            $lineItemsData[$line->id] = [
                'line' => $line,
                'quantity' => $line->quantity,
                'current_price' => $linePricing->finalUnitPrice,
                'original_price' => $linePricing->originalUnitPrice,
            ];
            
            // Collect item discounts
            $itemDiscounts = $itemDiscounts->merge($linePricing->discountBreakdown->itemDiscounts);
            
            // Collect applied rules
            $allAppliedRules = array_merge($allAppliedRules, $linePricing->appliedRules);
        }
        
        // Calculate cart subtotal (pre-discount)
        $cartSubtotal = $lineItemPricings->sum(function($pricing) {
            return $pricing->originalUnitPrice * $pricing->quantity;
        });
        
        // Run cart-level discount step
        $cartDiscountData = [
            'line_items' => $lineItemsData,
            'cart_subtotal' => $cartSubtotal,
            'applied_rules' => $allAppliedRules,
        ];
        
        $cartDiscountResult = $this->runPipelineStep(
            $this->applyCartDiscountsStep,
            $cartDiscountData,
            $cart
        );
        
        // Update line items with cart discounts
        foreach ($cartDiscountResult['line_items'] as $lineId => $lineData) {
            $linePricing = $lineItemPricings->firstWhere('cartLineId', $lineId);
            if ($linePricing) {
                $cartDiscount = $lineData['cart_discount'] ?? 0;
                $finalPrice = max(0, $linePricing->finalUnitPrice - $cartDiscount);
                
                // Create updated line pricing with cart discount
                $lineItemPricings = $lineItemPricings->reject(fn($p) => $p->cartLineId === $lineId);
                $lineItemPricings->push(new LineItemPricing(
                    cartLineId: $linePricing->cartLineId,
                    originalUnitPrice: $linePricing->originalUnitPrice,
                    finalUnitPrice: $finalPrice,
                    quantity: $linePricing->quantity,
                    lineTotal: $finalPrice * $linePricing->quantity,
                    discountBreakdown: $linePricing->discountBreakdown,
                    taxBase: $linePricing->taxBase,
                    taxAmount: $linePricing->taxAmount,
                    appliedRules: array_merge($linePricing->appliedRules, $cartDiscountResult['applied_rules'] ?? []),
                    priceSource: $linePricing->priceSource,
                    tierPrice: $linePricing->tierPrice,
                    tierName: $linePricing->tierName,
                ));
            }
        }
        
        // Run shipping step
        $shippingData = ['cart' => $cart];
        $shippingResult = $this->runPipelineStep(
            $this->calculateShippingStep,
            $shippingData,
            $cart
        );
        $shippingCost = $shippingResult['shipping_cost'] ?? new ShippingCost(amount: 0);
        
        // Run tax step
        $taxData = [
            'line_items' => [],
        ];
        foreach ($lineItemPricings as $pricing) {
            $line = CartLine::find($pricing->cartLineId);
            if ($line) {
                $taxData['line_items'][$pricing->cartLineId] = [
                    'line' => $line,
                    'quantity' => $pricing->quantity,
                    'current_price' => $pricing->finalUnitPrice,
                ];
            }
        }
        $taxResult = $this->runPipelineStep(
            $this->calculateTaxStep,
            $taxData,
            $cart
        );
        $taxBreakdown = $taxResult['tax_breakdown'] ?? new TaxBreakdown(
            totalAmount: 0,
            lineItemTaxes: collect(),
            taxRates: collect(),
        );
        
        // Update line item pricings with tax amounts
        if ($taxBreakdown->lineItemTaxes->isNotEmpty()) {
            $updatedLineItems = collect();
            foreach ($lineItemPricings as $linePricing) {
                $lineTax = $taxBreakdown->lineItemTaxes->firstWhere('cartLineId', $linePricing->cartLineId);
                if ($lineTax) {
                    // Create updated line pricing with tax
                    $updatedLineItems->push(new LineItemPricing(
                        cartLineId: $linePricing->cartLineId,
                        originalUnitPrice: $linePricing->originalUnitPrice,
                        finalUnitPrice: $linePricing->finalUnitPrice,
                        quantity: $linePricing->quantity,
                        lineTotal: $linePricing->lineTotal,
                        discountBreakdown: $linePricing->discountBreakdown,
                        taxBase: $lineTax->taxBase,
                        taxAmount: $lineTax->taxAmount,
                        appliedRules: $linePricing->appliedRules,
                        priceSource: $linePricing->priceSource,
                        tierPrice: $linePricing->tierPrice,
                        tierName: $linePricing->tierName,
                    ));
                } else {
                    $updatedLineItems->push($linePricing);
                }
            }
            $lineItemPricings = $updatedLineItems;
        }
        
        // Run rounding step
        $roundingData = [
            'line_items' => [],
        ];
        foreach ($lineItemPricings as $pricing) {
            $line = CartLine::find($pricing->cartLineId);
            if ($line) {
                $roundingData['line_items'][$pricing->cartLineId] = [
                    'line' => $line,
                    'quantity' => $pricing->quantity,
                    'current_price' => $pricing->finalUnitPrice,
                ];
            }
        }
        $roundingData['cart_subtotal'] = $cartSubtotal;
        $roundingData['cart_discount_total'] = $cartDiscountResult['cart_discount_total'] ?? 0;
        $roundingData['item_discount_total'] = $itemDiscounts->sum('amount');
        $roundingData['tax_total'] = $taxBreakdown->totalAmount;
        $roundingData['shipping_total'] = $shippingCost->amount;
        $roundingResult = $this->runPipelineStep(
            $this->applyRoundingStep,
            $roundingData,
            $cart
        );
        
        // Update line item pricings with rounded prices
        if (isset($roundingResult['line_items'])) {
            $updatedLineItems = collect();
            foreach ($lineItemPricings as $linePricing) {
                $roundedLineData = $roundingResult['line_items'][$linePricing->cartLineId] ?? null;
                if ($roundedLineData && isset($roundedLineData['final_unit_price'])) {
                    // Create updated line pricing with rounded price
                    $updatedLineItems->push(new LineItemPricing(
                        cartLineId: $linePricing->cartLineId,
                        originalUnitPrice: $linePricing->originalUnitPrice,
                        finalUnitPrice: $roundedLineData['final_unit_price'],
                        quantity: $linePricing->quantity,
                        lineTotal: $roundedLineData['final_unit_price'] * $linePricing->quantity,
                        discountBreakdown: $linePricing->discountBreakdown,
                        taxBase: $linePricing->taxBase,
                        taxAmount: $linePricing->taxAmount,
                        appliedRules: $linePricing->appliedRules,
                        priceSource: $linePricing->priceSource,
                        tierPrice: $linePricing->tierPrice,
                        tierName: $linePricing->tierName,
                    ));
                } else {
                    $updatedLineItems->push($linePricing);
                }
            }
            $lineItemPricings = $updatedLineItems;
        }
        
        // Calculate totals
        $totalDiscounts = ($cartDiscountResult['cart_discount_total'] ?? 0) + $itemDiscounts->sum('amount');
        $grandTotal = $roundingResult['grand_total'] ?? (
            $roundingResult['cart_subtotal'] - $roundingResult['cart_discount_total'] 
            + $roundingResult['tax_total'] + $roundingResult['shipping_total']
        );
        
        // Create discount breakdown
        $discountBreakdown = new DiscountBreakdown(
            totalAmount: $totalDiscounts,
            itemDiscounts: $itemDiscounts,
            cartDiscounts: $cartDiscountResult['cart_discounts'] ?? collect(),
        );
        
        // Generate price hash for tamper detection
        $pricingDataForHash = [
            'cart_subtotal' => $roundingResult['cart_subtotal'] ?? $cartSubtotal,
            'cart_discount_total' => $roundingResult['cart_discount_total'] ?? $totalDiscounts,
            'tax_total' => $roundingResult['tax_total'] ?? $taxBreakdown->totalAmount,
            'shipping_total' => $roundingResult['shipping_total'] ?? $shippingCost->amount,
            'grand_total' => $grandTotal,
        ];
        $priceHash = $this->integrityService->generatePriceHash($cart, $pricingDataForHash);
        
        // Create result
        $result = new CartPricingResult(
            subtotal: $roundingResult['cart_subtotal'] ?? $cartSubtotal,
            totalDiscounts: $roundingResult['cart_discount_total'] ?? $totalDiscounts,
            taxTotal: $roundingResult['tax_total'] ?? $taxBreakdown->totalAmount,
            shippingTotal: $roundingResult['shipping_total'] ?? $shippingCost->amount,
            grandTotal: $grandTotal,
            lineItems: $lineItemPricings,
            discountBreakdown: $discountBreakdown,
            taxBreakdown: $taxBreakdown,
            shippingCost: $shippingCost,
            appliedRules: array_merge($allAppliedRules, $cartDiscountResult['applied_rules'] ?? []),
            priceHash: $priceHash,
            calculatedAt: now(),
            pricingVersion: ($cart->pricing_version ?? 0) + 1,
        );
        
        // Store pricing data in cart
        $this->storePricingData($cart, $result);
        
        // Store snapshot if enabled
        if (config('lunar.cart.pricing.store_snapshots', false)) {
            $this->storePricingSnapshot($cart, $result, 'calculation');
        }
        
        // Validate price integrity
        $this->integrityService->validateCartPrices($cart);
        
        return $result;
    }

    /**
     * Calculate price for a single line item.
     */
    public function calculateLineItemPrice(CartLine $line, Cart $cart): LineItemPricing
    {
        // Run pipeline steps for line item
        $data = $this->runLineItemPipeline($line, $cart);
        
        $finalPrice = $data['current_price'] ?? 0;
        $originalPrice = $data['base_price'] ?? $finalPrice;
        
        // Get tax base (price before tax)
        $taxBase = $finalPrice;
        
        // Create discount breakdown
        $itemDiscounts = $data['item_discounts'] ?? collect();
        $discountBreakdown = new DiscountBreakdown(
            totalAmount: $data['item_discount_total'] ?? 0,
            itemDiscounts: $itemDiscounts,
            cartDiscounts: collect(),
        );
        
        // Create line item pricing
        return new LineItemPricing(
            cartLineId: $line->id,
            originalUnitPrice: $originalPrice,
            finalUnitPrice: $finalPrice,
            quantity: $line->quantity,
            lineTotal: $finalPrice * $line->quantity,
            discountBreakdown: $discountBreakdown,
            taxBase: $taxBase * $line->quantity,
            taxAmount: 0, // Will be calculated in tax step
            appliedRules: $data['applied_rules'] ?? [],
            priceSource: $data['price_source'] ?? 'base',
            tierPrice: $data['tier_price'] ?? null,
            tierName: $data['tier_name'] ?? null,
        );
    }

    /**
     * Reprice entire cart.
     */
    public function repriceCart(Cart $cart, ?string $trigger = null): Cart
    {
        $result = $this->calculateCartPrices($cart);
        
        // Update cart metadata
        $cart->update([
            'last_reprice_at' => now(),
            'pricing_version' => $result->pricingVersion,
            'requires_reprice' => false,
            'pricing_snapshot' => $result->toArray(),
        ]);
        
        // Store snapshot if enabled and trigger provided
        if (config('lunar.cart.pricing.store_snapshots', false) && $trigger) {
            $this->storePricingSnapshot($cart, $result, 'calculation', $trigger);
        }
        
        return $cart;
    }

    /**
     * Run a pipeline step.
     */
    protected function runPipelineStep($step, array $data, Cart $cart): array
    {
        return $step->handle($data, $cart, function($result) {
            return $result;
        });
    }

    /**
     * Run line item pricing pipeline.
     */
    protected function runLineItemPipeline(CartLine $line, Cart $cart): array
    {
        $data = [
            'line' => $line,
            'quantity' => $line->quantity,
            'applied_rules' => [],
        ];
        
        // Step 1: Resolve base price
        $data = $this->resolveBasePriceStep->handle($data, $cart, fn($d) => $d);
        
        // Step 2: Apply B2B contract
        $data = $this->applyB2BContractStep->handle($data, $cart, fn($d) => $d);
        
        // Step 3: Apply quantity tier
        $data = $this->applyQuantityTierStep->handle($data, $cart, fn($d) => $d);
        
        // Step 4: Apply item discounts
        $data = $this->applyItemDiscountsStep->handle($data, $cart, fn($d) => $d);
        
        return $data;
    }

    /**
     * Store pricing data in cart and cart lines.
     */
    protected function storePricingData(Cart $cart, CartPricingResult $result): void
    {
        // Update cart lines with pricing data
        foreach ($result->lineItems as $linePricing) {
            $line = CartLine::find($linePricing->cartLineId);
            if ($line) {
                $line->update([
                    'original_unit_price' => $linePricing->originalUnitPrice,
                    'final_unit_price' => $linePricing->finalUnitPrice,
                    'discount_breakdown' => $linePricing->discountBreakdown->toArray(),
                    'tax_base' => $linePricing->taxBase,
                    'applied_rules' => $linePricing->appliedRules,
                    'price_source' => $linePricing->priceSource,
                    'price_calculated_at' => now(),
                    'price_hash' => substr(md5(json_encode($linePricing->appliedRules)), 0, 16),
                ]);
            }
        }
        
        // Update cart with pricing snapshot
        $cart->update([
            'pricing_snapshot' => $result->toArray(),
            'last_reprice_at' => now(),
            'pricing_version' => $result->pricingVersion,
            'requires_reprice' => false,
        ]);
    }

    /**
     * Store pricing snapshot for audit trail.
     */
    protected function storePricingSnapshot(Cart $cart, CartPricingResult $result, string $snapshotType = 'calculation', ?string $trigger = null): void
    {
        \App\Models\CartPricingSnapshot::create([
            'cart_id' => $cart->id,
            'snapshot_type' => $snapshotType,
            'pricing_data' => $result->toArray(),
            'trigger' => $trigger,
            'pricing_version' => (string) $result->pricingVersion,
        ]);
    }
}


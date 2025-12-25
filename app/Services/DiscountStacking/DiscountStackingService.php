<?php

namespace App\Services\DiscountStacking;

use App\Enums\DiscountStackingMode;
use App\Enums\DiscountStackingStrategy;
use App\Enums\DiscountType;
use App\Services\DiscountStacking\DTOs\DiscountApplication;
use App\Services\DiscountStacking\DTOs\DiscountStackingResult;
use Illuminate\Support\Collection;
use Lunar\Models\Cart;
use Lunar\Models\Discount;

/**
 * Discount Stacking Service
 * 
 * Handles the complex logic of applying multiple discounts according to
 * stacking rules, priorities, and conflict resolution strategies.
 */
class DiscountStackingService
{
    /**
     * Apply discounts with stacking rules
     */
    public function applyDiscounts(
        Collection $discounts,
        Cart $cart,
        int $baseAmount,
        string $scope = 'cart'
    ): DiscountStackingResult {
        // Group discounts by type and scope
        $groupedDiscounts = $this->groupDiscounts($discounts, $scope);
        
        // Apply conflict resolution
        $resolvedDiscounts = $this->resolveConflicts($groupedDiscounts, $cart);
        
        // Apply stacking strategy
        $applications = $this->applyStackingStrategy($resolvedDiscounts, $baseAmount, $cart);
        
        // Calculate totals
        $totalDiscount = $applications->sum('amount');
        $remainingAmount = max(0, $baseAmount - $totalDiscount);
        
        return new DiscountStackingResult(
            applications: $applications,
            totalDiscount: $totalDiscount,
            remainingAmount: $remainingAmount,
            baseAmount: $baseAmount,
            appliedRules: $this->buildAppliedRules($applications),
        );
    }

    /**
     * Group discounts by type and scope
     */
    protected function groupDiscounts(Collection $discounts, string $scope): Collection
    {
        return $discounts->groupBy(function($discount) use ($scope) {
            $discountType = $this->getDiscountType($discount);
            $discountScope = $discountType->scope();
            
            // Filter by scope
            if ($discountScope !== $scope) {
                return null;
            }
            
            return $discountType->value;
        })->filter();
    }

    /**
     * Resolve conflicts between discounts
     */
    protected function resolveConflicts(Collection $groupedDiscounts, Cart $cart): Collection
    {
        $resolved = collect();
        
        foreach ($groupedDiscounts as $type => $discounts) {
            // Sort by priority (higher first)
            $sorted = $discounts->sortByDesc(function($discount) {
                return $this->getPriority($discount);
            });
            
            // Apply conflict resolution rules
            $filtered = $this->applyConflictRules($sorted, $cart);
            
            $resolved->put($type, $filtered);
        }
        
        return $resolved;
    }

    /**
     * Apply conflict resolution rules
     */
    protected function applyConflictRules(Collection $discounts, Cart $cart): Collection
    {
        $applicable = collect();
        $exclusiveApplied = false;
        
        foreach ($discounts as $discount) {
            $stackingMode = $this->getStackingMode($discount);
            
            // Rule: Exclusive discounts override all others
            if ($stackingMode->isExclusive()) {
                if ($exclusiveApplied) {
                    continue; // Skip if exclusive already applied
                }
                $applicable->push($discount);
                $exclusiveApplied = true;
                continue;
            }
            
            // Rule: Manual coupons override auto promos (if configured)
            if ($this->isManualCoupon($discount) && $this->shouldOverrideAutoPromos($cart)) {
                // Remove any automatic promotions already added
                $applicable = $applicable->reject(function($d) {
                    return $this->isAutomaticPromotion($d);
                });
                $applicable->push($discount);
                continue;
            }
            
            // Rule: B2B contracts override promotions (default)
            if ($this->isB2BContract($discount)) {
                // Remove any promotions already added
                $applicable = $applicable->reject(function($d) {
                    return !$this->isB2BContract($d);
                });
                $applicable->push($discount);
                continue;
            }
            
            // Rule: MAP-protected variants block discounts
            if ($this->isMapProtected($discount, $cart)) {
                continue; // Skip MAP-protected discounts
            }
            
            // Rule: Non-stackable discounts replace previous non-stackable
            if ($stackingMode === DiscountStackingMode::NON_STACKABLE) {
                // Remove previous non-stackable discounts of same type
                $applicable = $applicable->reject(function($d) use ($discount) {
                    return $this->getDiscountType($d) === $this->getDiscountType($discount)
                        && $this->getStackingMode($d) === DiscountStackingMode::NON_STACKABLE;
                });
            }
            
            $applicable->push($discount);
        }
        
        return $applicable;
    }

    /**
     * Apply stacking strategy
     */
    protected function applyStackingStrategy(
        Collection $groupedDiscounts,
        int $baseAmount,
        Cart $cart
    ): Collection {
        $applications = collect();
        $remainingAmount = $baseAmount;
        
        foreach ($groupedDiscounts as $type => $discounts) {
            $strategy = $this->getStackingStrategy($discounts->first());
            
            $typeApplications = match($strategy) {
                DiscountStackingStrategy::BEST_OF => $this->applyBestOfStrategy($discounts, $remainingAmount, $cart),
                DiscountStackingStrategy::PRIORITY_FIRST => $this->applyPriorityFirstStrategy($discounts, $remainingAmount, $cart),
                DiscountStackingStrategy::CUMULATIVE => $this->applyCumulativeStrategy($discounts, $remainingAmount, $cart),
                DiscountStackingStrategy::EXCLUSIVE_OVERRIDE => $this->applyExclusiveOverrideStrategy($discounts, $remainingAmount, $cart),
            };
            
            $applications = $applications->merge($typeApplications);
            
            // Update remaining amount
            $totalApplied = $typeApplications->sum('amount');
            $remainingAmount = max(0, $remainingAmount - $totalApplied);
        }
        
        return $applications;
    }

    /**
     * Apply best-of strategy (choose max discount)
     */
    protected function applyBestOfStrategy(Collection $discounts, int $baseAmount, Cart $cart): Collection
    {
        $bestDiscount = null;
        $bestAmount = 0;
        
        foreach ($discounts as $discount) {
            $amount = $this->calculateDiscountAmount($discount, $baseAmount, $cart);
            
            if ($amount > $bestAmount) {
                $bestAmount = $amount;
                $bestDiscount = $discount;
            }
        }
        
        if ($bestDiscount && $bestAmount > 0) {
            return collect([
                new DiscountApplication(
                    discount: $bestDiscount,
                    amount: $bestAmount,
                    type: $this->getDiscountType($bestDiscount),
                    reason: 'Best-of strategy: highest discount selected',
                ),
            ]);
        }
        
        return collect();
    }

    /**
     * Apply priority-first strategy
     */
    protected function applyPriorityFirstStrategy(Collection $discounts, int $baseAmount, Cart $cart): Collection
    {
        $applications = collect();
        $remainingAmount = $baseAmount;
        
        // Already sorted by priority
        foreach ($discounts as $discount) {
            $stackingMode = $this->getStackingMode($discount);
            
            // If exclusive, stop after applying
            if ($stackingMode->isExclusive()) {
                $amount = $this->calculateDiscountAmount($discount, $remainingAmount, $cart);
                if ($amount > 0) {
                    $applications->push(new DiscountApplication(
                        discount: $discount,
                        amount: $amount,
                        type: $this->getDiscountType($discount),
                        reason: 'Priority-first strategy: exclusive discount applied',
                    ));
                }
                break;
            }
            
            // Apply if stackable
            if ($stackingMode->allowsStacking()) {
                $amount = $this->calculateDiscountAmount($discount, $remainingAmount, $cart);
                if ($amount > 0) {
                    $applications->push(new DiscountApplication(
                        discount: $discount,
                        amount: $amount,
                        type: $this->getDiscountType($discount),
                        reason: 'Priority-first strategy: stackable discount applied',
                    ));
                    $remainingAmount = max(0, $remainingAmount - $amount);
                }
            }
        }
        
        return $applications;
    }

    /**
     * Apply cumulative strategy
     */
    protected function applyCumulativeStrategy(Collection $discounts, int $baseAmount, Cart $cart): Collection
    {
        $applications = collect();
        $remainingAmount = $baseAmount;
        
        foreach ($discounts as $discount) {
            $stackingMode = $this->getStackingMode($discount);
            
            // Only apply stackable discounts
            if (!$stackingMode->allowsStacking()) {
                continue;
            }
            
            $amount = $this->calculateDiscountAmount($discount, $remainingAmount, $cart);
            
            if ($amount > 0) {
                $applications->push(new DiscountApplication(
                    discount: $discount,
                    amount: $amount,
                    type: $this->getDiscountType($discount),
                    reason: 'Cumulative strategy: discount stacked',
                ));
                
                $remainingAmount = max(0, $remainingAmount - $amount);
            }
        }
        
        return $applications;
    }

    /**
     * Apply exclusive override strategy
     */
    protected function applyExclusiveOverrideStrategy(Collection $discounts, int $baseAmount, Cart $cart): Collection
    {
        // Find exclusive discounts first
        $exclusive = $discounts->first(function($discount) {
            return $this->getStackingMode($discount)->isExclusive();
        });
        
        if ($exclusive) {
            $amount = $this->calculateDiscountAmount($exclusive, $baseAmount, $cart);
            if ($amount > 0) {
                return collect([
                    new DiscountApplication(
                        discount: $exclusive,
                        amount: $amount,
                        type: $this->getDiscountType($exclusive),
                        reason: 'Exclusive override strategy: exclusive discount applied',
                    ),
                ]);
            }
        }
        
        // Otherwise apply cumulative
        return $this->applyCumulativeStrategy($discounts, $baseAmount, $cart);
    }

    /**
     * Calculate discount amount
     */
    protected function calculateDiscountAmount(Discount $discount, int $baseAmount, Cart $cart): int
    {
        $data = $discount->data ?? [];
        
        // Percentage discount
        if (isset($data['percentage'])) {
            $amount = (int) round($baseAmount * ($data['percentage'] / 100));
            
            // Check max discount cap
            $maxCap = $this->getMaxDiscountCap($discount);
            if ($maxCap && $amount > $maxCap) {
                $amount = $maxCap;
            }
            
            return min($amount, $baseAmount);
        }
        
        // Fixed amount discount
        if (isset($data['fixed_amount'])) {
            return min($data['fixed_amount'], $baseAmount);
        }
        
        return 0;
    }

    /**
     * Get discount type
     */
    protected function getDiscountType(Discount $discount): DiscountType
    {
        $data = $discount->data ?? [];
        
        // Check if coupon-based
        if ($discount->coupon) {
            return DiscountType::COUPON_BASED;
        }
        
        // Check data for explicit type
        if (isset($data['discount_type'])) {
            return DiscountType::tryFrom($data['discount_type']) ?? DiscountType::CART_LEVEL;
        }
        
        // Infer from discount structure
        if (isset($data['shipping_discount'])) {
            return DiscountType::SHIPPING;
        }
        
        if (isset($data['payment_method_discount'])) {
            return DiscountType::PAYMENT_METHOD;
        }
        
        if (isset($data['loyalty_discount'])) {
            return DiscountType::CUSTOMER_LOYALTY;
        }
        
        // Check if has purchasables (item-level)
        if ($discount->purchasables()->exists()) {
            return DiscountType::ITEM_LEVEL;
        }
        
        // Default to cart-level
        return DiscountType::CART_LEVEL;
    }

    /**
     * Get stacking mode
     */
    protected function getStackingMode(Discount $discount): DiscountStackingMode
    {
        $data = $discount->data ?? [];
        
        if (isset($data['stacking_mode'])) {
            return DiscountStackingMode::tryFrom($data['stacking_mode']) ?? DiscountStackingMode::NON_STACKABLE;
        }
        
        // Default: non-stackable
        return DiscountStackingMode::NON_STACKABLE;
    }

    /**
     * Get stacking strategy
     */
    protected function getStackingStrategy(Discount $discount): DiscountStackingStrategy
    {
        $data = $discount->data ?? [];
        
        if (isset($data['stacking_strategy'])) {
            return DiscountStackingStrategy::tryFrom($data['stacking_strategy']) ?? DiscountStackingStrategy::PRIORITY_FIRST;
        }
        
        // Default: priority-first
        return DiscountStackingStrategy::PRIORITY_FIRST;
    }

    /**
     * Get priority
     */
    protected function getPriority(Discount $discount): int
    {
        return $discount->priority ?? 1;
    }

    /**
     * Get max discount cap
     */
    protected function getMaxDiscountCap(Discount $discount): ?int
    {
        $data = $discount->data ?? [];
        return $data['max_discount_amount'] ?? $data['max_discount_cap'] ?? null;
    }

    /**
     * Check if discount is manual coupon
     */
    protected function isManualCoupon(Discount $discount): bool
    {
        return (bool) $discount->coupon;
    }

    /**
     * Check if discount is automatic promotion
     */
    protected function isAutomaticPromotion(Discount $discount): bool
    {
        return $this->getDiscountType($discount) === DiscountType::AUTOMATIC_PROMOTION;
    }

    /**
     * Check if discount is B2B contract
     */
    protected function isB2BContract(Discount $discount): bool
    {
        $data = $discount->data ?? [];
        return isset($data['b2b_contract']) && $data['b2b_contract'] === true;
    }

    /**
     * Check if discount is MAP-protected
     */
    protected function isMapProtected(Discount $discount, Cart $cart): bool
    {
        // Check if any cart items are MAP-protected
        foreach ($cart->lines as $line) {
            $purchasable = $line->purchasable;
            if ($purchasable && isset($purchasable->data['map_protected']) && $purchasable->data['map_protected']) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if manual coupons should override auto promos
     */
    protected function shouldOverrideAutoPromos(Cart $cart): bool
    {
        // Check configuration or cart metadata
        return config('lunar.discounts.manual_coupons_override_auto', true);
    }

    /**
     * Build applied rules array
     */
    protected function buildAppliedRules(Collection $applications): array
    {
        return $applications->map(function($application) {
            return [
                'type' => $application->type->value,
                'discount_id' => $application->discount->id,
                'discount_name' => $application->discount->name,
                'amount' => $application->amount,
                'reason' => $application->reason,
                'stacking_mode' => $this->getStackingMode($application->discount)->value,
                'priority' => $this->getPriority($application->discount),
            ];
        })->toArray();
    }
}


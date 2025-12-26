<?php

namespace App\Services;

use App\Models\ReferralRule;
use App\Models\ReferralProgram;
use App\Models\User;
use Lunar\Models\Order;
use Lunar\Models\Cart;
use Lunar\Models\Discount;
use Illuminate\Support\Collection;

/**
 * Referral Discount Stacking Service
 * 
 * Handles discount stacking logic with policies:
 * - Exclusive: referral discount cannot stack with other promos
 * - Best-of: choose the largest discount automatically
 * - Stackable: allow stacking with caps (e.g. max 20% total)
 */
class ReferralDiscountStackingService
{
    /**
     * Apply referral discount to cart/order based on stacking policy.
     * 
     * @param Cart|Order $cartOrOrder
     * @param ReferralRule $rule
     * @param User $user
     * @return array Returns applied discounts and stacking info
     */
    public function applyReferralDiscount($cartOrOrder, ReferralRule $rule, User $user): array
    {
        $program = $rule->program;
        $stackingMode = $rule->stacking_mode ?? ($program ? $program->default_stacking_mode : null) ?? ReferralRule::STACKING_EXCLUSIVE;

        // Get all applicable discounts
        $applicableDiscounts = $this->getApplicableDiscounts($cartOrOrder, $user);

        // Get referral discount
        $referralDiscount = $this->getReferralDiscount($rule, $user, $cartOrOrder);

        if (!$referralDiscount) {
            return [
                'applied' => false,
                'reason' => 'No referral discount available',
            ];
        }

        // Apply stacking logic
        return match ($stackingMode) {
            ReferralRule::STACKING_EXCLUSIVE => $this->applyExclusive($cartOrOrder, $referralDiscount, $applicableDiscounts, $rule),
            ReferralRule::STACKING_BEST_OF => $this->applyBestOf($cartOrOrder, $referralDiscount, $applicableDiscounts, $rule),
            ReferralRule::STACKING_STACKABLE => $this->applyStackable($cartOrOrder, $referralDiscount, $applicableDiscounts, $rule),
            default => $this->applyExclusive($cartOrOrder, $referralDiscount, $applicableDiscounts, $rule),
        };
    }

    /**
     * Exclusive mode: Remove other discounts, apply only referral discount.
     */
    protected function applyExclusive($cartOrOrder, Discount $referralDiscount, Collection $otherDiscounts, ReferralRule $rule): array
    {
        // Remove all other discounts
        foreach ($otherDiscounts as $discount) {
            $this->removeDiscount($cartOrOrder, $discount);
        }

        // Apply referral discount
        $this->applyDiscount($cartOrOrder, $referralDiscount);

        return [
            'applied' => true,
            'mode' => 'exclusive',
            'referral_discount' => $referralDiscount,
            'removed_discounts' => $otherDiscounts->pluck('id')->toArray(),
        ];
    }

    /**
     * Best-of mode: Compare discounts, apply the largest one.
     */
    protected function applyBestOf($cartOrOrder, Discount $referralDiscount, Collection $otherDiscounts, ReferralRule $rule): array
    {
        // Calculate discount values
        $referralValue = $this->calculateDiscountValue($cartOrOrder, $referralDiscount);
        $bestDiscount = $referralDiscount;
        $bestValue = $referralValue;

        foreach ($otherDiscounts as $discount) {
            $value = $this->calculateDiscountValue($cartOrOrder, $discount);
            if ($value > $bestValue) {
                $bestValue = $value;
                $bestDiscount = $discount;
            }
        }

        // Remove all discounts
        foreach ($otherDiscounts as $discount) {
            $this->removeDiscount($cartOrOrder, $discount);
        }
        $this->removeDiscount($cartOrOrder, $referralDiscount);

        // Apply best discount
        if ($bestDiscount->id === $referralDiscount->id) {
            $this->applyDiscount($cartOrOrder, $referralDiscount);
        } else {
            $this->applyDiscount($cartOrOrder, $bestDiscount);
        }

        return [
            'applied' => $bestDiscount->id === $referralDiscount->id,
            'mode' => 'best_of',
            'applied_discount' => $bestDiscount,
            'referral_value' => $referralValue,
            'best_value' => $bestValue,
        ];
    }

    /**
     * Stackable mode: Apply referral discount with other discounts, respecting caps.
     */
    protected function applyStackable($cartOrOrder, Discount $referralDiscount, Collection $otherDiscounts, ReferralRule $rule): array
    {
        // Calculate current total discount percentage
        $currentTotalPercent = $this->calculateTotalDiscountPercent($cartOrOrder, $otherDiscounts);
        $referralPercent = $this->getDiscountPercent($referralDiscount);

        // Check max total discount cap
        $program = $rule->program;
        $maxTotalPercent = $rule->max_total_discount_percent ?? ($program ? $program->max_total_discount_percent : null) ?? null;
        
        if ($maxTotalPercent) {
            $newTotalPercent = $currentTotalPercent + $referralPercent;
            
            if ($newTotalPercent > $maxTotalPercent) {
                // Adjust referral discount to fit within cap
                $allowedReferralPercent = max(0, $maxTotalPercent - $currentTotalPercent);
                
                if ($allowedReferralPercent <= 0) {
                    return [
                        'applied' => false,
                        'reason' => 'Max discount cap reached',
                        'current_percent' => $currentTotalPercent,
                        'max_percent' => $maxTotalPercent,
                    ];
                }

                // Create adjusted discount if needed
                if ($allowedReferralPercent < $referralPercent) {
                    $referralDiscount = $this->createAdjustedDiscount($referralDiscount, $allowedReferralPercent);
                }
            }
        }

        // Check max total discount amount
        $maxTotalAmount = $rule->max_total_discount_amount ?? ($program ? $program->max_total_discount_amount : null) ?? null;
        
        if ($maxTotalAmount) {
            $currentTotalAmount = $this->calculateTotalDiscountAmount($cartOrOrder, $otherDiscounts);
            $referralAmount = $this->calculateDiscountValue($cartOrOrder, $referralDiscount);
            $newTotalAmount = $currentTotalAmount + $referralAmount;

            if ($newTotalAmount > $maxTotalAmount) {
                $allowedReferralAmount = max(0, $maxTotalAmount - $currentTotalAmount);
                
                if ($allowedReferralAmount <= 0) {
                    return [
                        'applied' => false,
                        'reason' => 'Max discount amount cap reached',
                        'current_amount' => $currentTotalAmount,
                        'max_amount' => $maxTotalAmount,
                    ];
                }

                if ($allowedReferralAmount < $referralAmount) {
                    $referralDiscount = $this->createAdjustedDiscount($referralDiscount, $allowedReferralAmount, 'amount');
                }
            }
        }

        // Apply referral discount
        $this->applyDiscount($cartOrOrder, $referralDiscount);

        return [
            'applied' => true,
            'mode' => 'stackable',
            'referral_discount' => $referralDiscount,
            'total_percent' => $currentTotalPercent + $this->getDiscountPercent($referralDiscount),
            'total_amount' => $this->calculateTotalDiscountAmount($cartOrOrder, $otherDiscounts->push($referralDiscount)),
        ];
    }

    /**
     * Get all applicable discounts for cart/order.
     */
    protected function getApplicableDiscounts($cartOrOrder, User $user): Collection
    {
        // Get all active discounts from Lunar
        $discounts = Discount::where('starts_at', '<=', now())
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->get();

        // Filter discounts that apply to this cart/order
        return $discounts->filter(function ($discount) use ($cartOrOrder, $user) {
            return $this->discountAppliesTo($discount, $cartOrOrder, $user);
        });
    }

    /**
     * Get referral discount for user.
     */
    protected function getReferralDiscount(ReferralRule $rule, User $user, $cartOrOrder): ?Discount
    {
        // Get coupon assigned to user from this rule
        $coupon = \App\Models\Coupon::where('created_by_rule_id', $rule->id)
            ->where('assigned_to_user_id', $user->id)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('start_at')
                    ->orWhere('start_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('end_at')
                    ->orWhere('end_at', '>=', now());
            })
            ->first();

        if (!$coupon) {
            return null;
        }

        // Convert coupon to Lunar discount
        return $this->convertCouponToDiscount($coupon, $rule);
    }

    /**
     * Convert coupon to Lunar discount.
     */
    protected function convertCouponToDiscount(\App\Models\Coupon $coupon, ReferralRule $rule): Discount
    {
        // Check if discount already exists
        $discount = Discount::where('handle', 'referral-coupon-' . $coupon->id)->first();

        if (!$discount) {
            $discount = Discount::create([
                'name' => "Referral Coupon - {$coupon->code}",
                'handle' => 'referral-coupon-' . $coupon->id,
                'type' => $coupon->type === \App\Models\Coupon::TYPE_PERCENTAGE ? 'percentage' : 'fixed',
                'starts_at' => $coupon->start_at,
                'ends_at' => $coupon->end_at,
            ]);

            $discount->data = [
                'value' => $coupon->value,
                'min_basket' => $rule->min_order_total ?? 0,
            ];

            $discount->save();
        }

        return $discount;
    }

    /**
     * Calculate discount value.
     */
    protected function calculateDiscountValue($cartOrOrder, Discount $discount): float
    {
        $subtotal = $cartOrOrder->subTotal->value ?? $cartOrOrder->sub_total->value ?? 0;

        if ($discount->type === 'percentage') {
            $percent = $discount->data['value'] ?? 0;
            return ($subtotal * $percent) / 100;
        }

        return $discount->data['value'] ?? 0;
    }

    /**
     * Get discount percentage.
     */
    protected function getDiscountPercent(Discount $discount): float
    {
        if ($discount->type === 'percentage') {
            return $discount->data['value'] ?? 0;
        }

        return 0; // Fixed discounts don't have percentage
    }

    /**
     * Calculate total discount percentage.
     */
    protected function calculateTotalDiscountPercent($cartOrOrder, Collection $discounts): float
    {
        return $discounts->sum(function ($discount) {
            return $this->getDiscountPercent($discount);
        });
    }

    /**
     * Calculate total discount amount.
     */
    protected function calculateTotalDiscountAmount($cartOrOrder, Collection $discounts): float
    {
        return $discounts->sum(function ($discount) use ($cartOrOrder) {
            return $this->calculateDiscountValue($cartOrOrder, $discount);
        });
    }

    /**
     * Check if discount applies to cart/order.
     */
    protected function discountAppliesTo(Discount $discount, $cartOrOrder, User $user): bool
    {
        // Basic checks
        if ($discount->starts_at && $discount->starts_at->isFuture()) {
            return false;
        }

        if ($discount->ends_at && $discount->ends_at->isPast()) {
            return false;
        }

        // Check min basket
        $subtotal = $cartOrOrder->subTotal->value ?? $cartOrOrder->sub_total->value ?? 0;
        $minBasket = $discount->data['min_basket'] ?? 0;

        if ($subtotal < $minBasket) {
            return false;
        }

        return true;
    }

    /**
     * Apply discount to cart/order.
     */
    protected function applyDiscount($cartOrOrder, Discount $discount): void
    {
        // Lunar handles discount application automatically
        // This method can be used to track discount application
        if (method_exists($cartOrOrder, 'discounts')) {
            $cartOrOrder->discounts()->syncWithoutDetaching([$discount->id]);
        }
    }

    /**
     * Remove discount from cart/order.
     */
    protected function removeDiscount($cartOrOrder, Discount $discount): void
    {
        if (method_exists($cartOrOrder, 'discounts')) {
            $cartOrOrder->discounts()->detach($discount->id);
        }
    }

    /**
     * Create adjusted discount with capped value.
     */
    protected function createAdjustedDiscount(Discount $originalDiscount, float $cappedValue, string $type = 'percent'): Discount
    {
        $handle = $originalDiscount->handle . '-adjusted-' . uniqid();

        $discount = Discount::create([
            'name' => $originalDiscount->name . ' (Adjusted)',
            'handle' => $handle,
            'type' => $type === 'percent' ? 'percentage' : 'fixed',
            'starts_at' => $originalDiscount->starts_at,
            'ends_at' => $originalDiscount->ends_at,
        ]);

        $discount->data = array_merge($originalDiscount->data ?? [], [
            'value' => $cappedValue,
        ]);

        $discount->save();

        return $discount;
    }

    /**
     * Check if referral discount should apply before tax.
     */
    public function appliesBeforeTax(ReferralRule $rule): bool
    {
        return $rule->apply_before_tax ?? $rule->program->apply_before_tax ?? true;
    }

    /**
     * Check if shipping discount stacks.
     */
    public function shippingDiscountStacks(ReferralRule $rule): bool
    {
        return $rule->shipping_discount_stacks ?? $rule->program->shipping_discount_stacks ?? false;
    }
}


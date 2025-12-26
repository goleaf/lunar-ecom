<?php

namespace App\Services;

use App\Models\ReferralAttribution;
use App\Models\ReferralRule;
use App\Models\ReferralProgram;
use App\Models\ReferralUserOverride;
use App\Models\ReferralGroupOverride;
use App\Models\User;
use App\Models\UserGroup;
use App\Services\ReferralDiscountStackingService;
use Lunar\Models\Cart;
use Lunar\Models\Order;
use Lunar\Models\Discount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Referral Checkout Service
 * 
 * Handles referral discount application at checkout, including:
 * - User eligibility detection
 * - Rule evaluation
 * - Discount application
 * - Audit trail creation
 */
class ReferralCheckoutService
{
    protected ReferralDiscountStackingService $stackingService;
    protected ReferralFraudService $fraudService;

    public function __construct(
        ReferralDiscountStackingService $stackingService,
        ReferralFraudService $fraudService
    ) {
        $this->stackingService = $stackingService;
        $this->fraudService = $fraudService;
    }

    /**
     * Process referral discounts for cart/checkout.
     * 
     * @param Cart|Order $cartOrOrder
     * @param User|null $user
     * @param string $stage 'cart' | 'checkout' | 'payment'
     * @return array Applied discounts and metadata
     */
    public function processReferralDiscounts($cartOrOrder, ?User $user = null, string $stage = 'checkout'): array
    {
        if (!$user) {
            return [
                'applied' => false,
                'reason' => 'User not authenticated',
            ];
        }

        // Check user eligibility
        $eligibility = $this->checkUserEligibility($user);
        if (!$eligibility['eligible']) {
            return [
                'applied' => false,
                'reason' => $eligibility['reason'],
            ];
        }

        // Get active referral attribution
        $attribution = ReferralAttribution::where('referee_user_id', $user->id)
            ->where('status', ReferralAttribution::STATUS_CONFIRMED)
            ->orderBy('priority', 'asc')
            ->first();

        if (!$attribution) {
            return [
                'applied' => false,
                'reason' => 'No active referral attribution',
            ];
        }

        $program = $attribution->program;
        if (!$program || !$program->isActive()) {
            return [
                'applied' => false,
                'reason' => 'Referral program not active',
            ];
        }

        // Get applicable rules based on stage
        $rules = $this->getApplicableRules($program, $stage, $cartOrOrder, $user);

        if ($rules->isEmpty()) {
            return [
                'applied' => false,
                'reason' => 'No applicable rules for current stage',
            ];
        }

        // Apply discounts
        $appliedDiscounts = [];
        $appliedRules = [];

        foreach ($rules as $rule) {
            // Check rule eligibility
            if (!$this->isRuleEligible($rule, $cartOrOrder, $user)) {
                continue;
            }

            // Apply referee discount
            $result = $this->applyRefereeDiscount($rule, $cartOrOrder, $user, $attribution);
            
            if ($result['applied']) {
                $appliedDiscounts[] = $result;
                $appliedRules[] = $rule->id;
            }
        }

        if (empty($appliedDiscounts)) {
            return [
                'applied' => false,
                'reason' => 'No discounts could be applied',
            ];
        }

        // Save audit snapshot
        $auditSnapshot = $this->createAuditSnapshot($cartOrOrder, $user, $attribution, $appliedRules, $appliedDiscounts);

        return [
            'applied' => true,
            'discounts' => $appliedDiscounts,
            'rules' => $appliedRules,
            'attribution' => $attribution->id,
            'audit_snapshot' => $auditSnapshot,
        ];
    }

    /**
     * Check user eligibility for referral discounts.
     */
    protected function checkUserEligibility(User $user): array
    {
        // Check if user is blocked
        $userOverride = ReferralUserOverride::where('user_id', $user->id)
            ->where('block_referrals', true)
            ->first();

        if ($userOverride) {
            return [
                'eligible' => false,
                'reason' => 'User is blocked from referrals',
            ];
        }

        // Check if user has verified email (if required)
        // This would be checked against fraud policy requirements
        // For now, we'll assume basic eligibility

        return [
            'eligible' => true,
            'reason' => null,
        ];
    }

    /**
     * Get applicable rules based on checkout stage.
     */
    protected function getApplicableRules(ReferralProgram $program, string $stage, $cartOrOrder, User $user): \Illuminate\Support\Collection
    {
        $query = ReferralRule::where('referral_program_id', $program->id)
            ->where('is_active', true)
            ->orderBy('priority', 'desc');

        // Filter by stage/trigger event
        switch ($stage) {
            case 'cart':
            case 'checkout':
                // Rules that apply at checkout (signup rewards, first order discounts)
                $query->whereIn('trigger_event', [
                    ReferralRule::TRIGGER_SIGNUP,
                    ReferralRule::TRIGGER_FIRST_ORDER_PAID, // Can apply discount before payment
                ]);
                break;

            case 'payment':
                // Rules that apply after payment
                $query->whereIn('trigger_event', [
                    ReferralRule::TRIGGER_FIRST_ORDER_PAID,
                    ReferralRule::TRIGGER_NTH_ORDER_PAID,
                ]);
                break;

            default:
                return collect();
        }

        return $query->get()->filter(function ($rule) use ($cartOrOrder, $user) {
            return $this->isRuleEligible($rule, $cartOrOrder, $user);
        });
    }

    /**
     * Check if rule is eligible for cart/order.
     */
    protected function isRuleEligible(ReferralRule $rule, $cartOrOrder, User $user): bool
    {
        // Check min order total
        $subtotal = $this->getSubtotal($cartOrOrder);
        if ($rule->min_order_total && $subtotal < $rule->min_order_total) {
            return false;
        }

        // Check eligible products/categories/collections
        if (!$this->checkProductEligibility($rule, $cartOrOrder)) {
            return false;
        }

        // Check redemption limits
        if (!$this->checkRedemptionLimits($rule, $user)) {
            return false;
        }

        // Check validation window
        if (!$this->checkValidationWindow($rule, $user)) {
            return false;
        }

        // Check user/group overrides
        if (!$this->checkOverrides($rule, $user)) {
            return false;
        }

        return true;
    }

    /**
     * Check product eligibility.
     */
    protected function checkProductEligibility(ReferralRule $rule, $cartOrOrder): bool
    {
        // If no restrictions, all products are eligible
        if (empty($rule->eligible_product_ids) && 
            empty($rule->eligible_category_ids) && 
            empty($rule->eligible_collection_ids)) {
            return true;
        }

        // Get cart/order line items
        $lineItems = $cartOrOrder->lines ?? collect();

        foreach ($lineItems as $line) {
            $product = $line->purchasable->product ?? null;
            if (!$product) {
                continue;
            }

            // Check product ID
            if (!empty($rule->eligible_product_ids) && 
                in_array($product->id, $rule->eligible_product_ids)) {
                return true;
            }

            // Check categories
            if (!empty($rule->eligible_category_ids)) {
                $productCategories = $product->categories->pluck('id')->toArray();
                if (array_intersect($rule->eligible_category_ids, $productCategories)) {
                    return true;
                }
            }

            // Check collections
            if (!empty($rule->eligible_collection_ids)) {
                $productCollections = $product->collections->pluck('id')->toArray();
                if (array_intersect($rule->eligible_collection_ids, $productCollections)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check redemption limits.
     */
    protected function checkRedemptionLimits(ReferralRule $rule, User $user): bool
    {
        // Check total redemptions
        if ($rule->max_redemptions_total) {
            $totalRedemptions = \App\Models\ReferralRewardIssuance::where('referral_rule_id', $rule->id)
                ->where('status', \App\Models\ReferralRewardIssuance::STATUS_ISSUED)
                ->count();

            if ($totalRedemptions >= $rule->max_redemptions_total) {
                return false;
            }
        }

        // Check per referee limit
        if ($rule->max_redemptions_per_referee) {
            $refereeRedemptions = \App\Models\ReferralRewardIssuance::where('referral_rule_id', $rule->id)
                ->where('referee_user_id', $user->id)
                ->where('status', \App\Models\ReferralRewardIssuance::STATUS_ISSUED)
                ->count();

            if ($refereeRedemptions >= $rule->max_redemptions_per_referee) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check validation window.
     */
    protected function checkValidationWindow(ReferralRule $rule, User $user): bool
    {
        if (!$rule->validation_window_days) {
            return true;
        }

        $attribution = ReferralAttribution::where('referee_user_id', $user->id)
            ->where('status', ReferralAttribution::STATUS_CONFIRMED)
            ->first();

        if (!$attribution) {
            return false;
        }

        $windowEnd = $attribution->attributed_at->addDays($rule->validation_window_days);
        
        return now()->lessThanOrEqualTo($windowEnd);
    }

    /**
     * Check user/group overrides.
     */
    protected function checkOverrides(ReferralRule $rule, User $user): bool
    {
        // Check user override
        $userOverride = ReferralUserOverride::where('user_id', $user->id)
            ->where(function ($query) use ($rule) {
                $query->whereNull('referral_program_id')
                    ->orWhere('referral_program_id', $rule->referral_program_id);
            })
            ->where(function ($query) use ($rule) {
                $query->whereNull('referral_rule_id')
                    ->orWhere('referral_rule_id', $rule->id);
            })
            ->first();

        if ($userOverride && $userOverride->block_referrals) {
            return false;
        }

        // Check group override
        if ($user->user_group_id) {
            $groupOverride = ReferralGroupOverride::where('user_group_id', $user->user_group_id)
                ->where(function ($query) use ($rule) {
                    $query->whereNull('referral_program_id')
                        ->orWhere('referral_program_id', $rule->referral_program_id);
                })
                ->where(function ($query) use ($rule) {
                    $query->whereNull('referral_rule_id')
                        ->orWhere('referral_rule_id', $rule->id);
                })
                ->first();

            if ($groupOverride && !$groupOverride->enabled) {
                return false;
            }
        }

        return true;
    }

    /**
     * Apply referee discount.
     */
    protected function applyRefereeDiscount(ReferralRule $rule, $cartOrOrder, User $user, ReferralAttribution $attribution): array
    {
        // Get reward value (considering overrides)
        $rewardValue = $this->getRewardValue($rule, $user);

        if (!$rewardValue || $rewardValue <= 0) {
            return [
                'applied' => false,
                'reason' => 'Invalid reward value',
            ];
        }

        // Create or get discount
        $discount = $this->createDiscount($rule, $user, $rewardValue);

        if (!$discount) {
            return [
                'applied' => false,
                'reason' => 'Failed to create discount',
            ];
        }

        // Apply stacking logic
        $stackingResult = $this->stackingService->applyReferralDiscount($cartOrOrder, $rule, $user);

        if (!$stackingResult['applied']) {
            return [
                'applied' => false,
                'reason' => $stackingResult['reason'] ?? 'Stacking logic prevented application',
            ];
        }

        return [
            'applied' => true,
            'rule_id' => $rule->id,
            'discount_id' => $discount->id,
            'discount_code' => $discount->handle ?? null,
            'discount_amount' => $this->calculateDiscountAmount($cartOrOrder, $discount, $rewardValue),
            'reward_type' => $rule->referee_reward_type,
            'reward_value' => $rewardValue,
            'stacking_mode' => $stackingResult['mode'] ?? 'exclusive',
        ];
    }

    /**
     * Get reward value considering overrides.
     */
    protected function getRewardValue(ReferralRule $rule, User $user): ?float
    {
        // Check user override
        $userOverride = ReferralUserOverride::where('user_id', $user->id)
            ->where(function ($query) use ($rule) {
                $query->whereNull('referral_program_id')
                    ->orWhere('referral_program_id', $rule->referral_program_id);
            })
            ->where(function ($query) use ($rule) {
                $query->whereNull('referral_rule_id')
                    ->orWhere('referral_rule_id', $rule->id);
            })
            ->first();

        if ($userOverride && $userOverride->reward_value_override) {
            return $userOverride->reward_value_override;
        }

        // Check group override
        if ($user->user_group_id) {
            $groupOverride = ReferralGroupOverride::where('user_group_id', $user->user_group_id)
                ->where(function ($query) use ($rule) {
                    $query->whereNull('referral_program_id')
                        ->orWhere('referral_program_id', $rule->referral_program_id);
                })
                ->where(function ($query) use ($rule) {
                    $query->whereNull('referral_rule_id')
                        ->orWhere('referral_rule_id', $rule->id);
                })
                ->first();

            if ($groupOverride && $groupOverride->reward_value_override) {
                return $groupOverride->reward_value_override;
            }
        }

        return $rule->referee_reward_value;
    }

    /**
     * Create discount for rule.
     */
    protected function createDiscount(ReferralRule $rule, User $user, float $value): ?Discount
    {
        // Check if discount already exists
        $handle = 'referral-' . $rule->id . '-' . $user->id;
        $discount = Discount::where('handle', $handle)->first();

        if ($discount) {
            return $discount;
        }

        // Create new discount
        $discount = Discount::create([
            'name' => "Referral Discount - {$user->email}",
            'handle' => $handle,
            'type' => $rule->referee_reward_type === ReferralRule::REWARD_PERCENTAGE_DISCOUNT ? 'percentage' : 'fixed',
            'starts_at' => now(),
            'ends_at' => now()->addDays($rule->coupon_validity_days ?? 30),
        ]);

        $discount->data = [
            'value' => $value,
            'min_basket' => $rule->min_order_total ?? 0,
            'rule_id' => $rule->id,
            'user_id' => $user->id,
        ];

        $discount->save();

        return $discount;
    }

    /**
     * Calculate discount amount.
     */
    protected function calculateDiscountAmount($cartOrOrder, Discount $discount, float $value): float
    {
        $subtotal = $this->getSubtotal($cartOrOrder);

        if ($discount->type === 'percentage') {
            return ($subtotal * $value) / 100;
        }

        return min($value, $subtotal); // Fixed discount can't exceed subtotal
    }

    /**
     * Get subtotal from cart/order.
     */
    protected function getSubtotal($cartOrOrder): float
    {
        if ($cartOrOrder instanceof Order) {
            return $cartOrOrder->subTotal->value ?? 0;
        }

        return $cartOrOrder->subTotal->value ?? $cartOrOrder->sub_total->value ?? 0;
    }

    /**
     * Create audit snapshot.
     */
    protected function createAuditSnapshot($cartOrOrder, User $user, ReferralAttribution $attribution, array $ruleIds, array $appliedDiscounts): array
    {
        return [
            'timestamp' => now()->toIso8601String(),
            'user_id' => $user->id,
            'user_email' => $user->email,
            'attribution_id' => $attribution->id,
            'referrer_id' => $attribution->referrer_user_id,
            'program_id' => $attribution->program_id,
            'rule_ids' => $ruleIds,
            'applied_discounts' => $appliedDiscounts,
            'cart_or_order_id' => $cartOrOrder->id,
            'cart_or_order_type' => get_class($cartOrOrder),
            'subtotal' => $this->getSubtotal($cartOrOrder),
            'total_discount_amount' => array_sum(array_column($appliedDiscounts, 'discount_amount')),
        ];
    }

    /**
     * Save discount application to order metadata.
     */
    public function saveDiscountApplication(Order $order, array $applicationData): void
    {
        $meta = $order->meta ?? [];
        $meta['referral_discounts'] = [
            'applied_at' => now()->toIso8601String(),
            'rules' => $applicationData['rules'] ?? [],
            'discounts' => $applicationData['discounts'] ?? [],
            'attribution_id' => $applicationData['attribution'] ?? null,
            'audit_snapshot' => $applicationData['audit_snapshot'] ?? null,
        ];

        $order->update(['meta' => $meta]);
    }

    /**
     * Save discount application record.
     */
    public function saveDiscountApplicationRecord($cartOrOrder, User $user, array $applicationData, string $stage): ReferralDiscountApplication
    {
        $attribution = ReferralAttribution::find($applicationData['attribution']);
        
        return ReferralDiscountApplication::create([
            'order_id' => $cartOrOrder instanceof Order ? $cartOrOrder->id : null,
            'cart_id' => $cartOrOrder instanceof Cart ? $cartOrOrder->id : null,
            'user_id' => $user->id,
            'referral_attribution_id' => $applicationData['attribution'],
            'referral_program_id' => $attribution->program_id ?? null,
            'applied_rule_ids' => $applicationData['rules'] ?? [],
            'applied_discounts' => $applicationData['discounts'] ?? [],
            'total_discount_amount' => array_sum(array_column($applicationData['discounts'] ?? [], 'discount_amount')),
            'stage' => $stage,
            'audit_snapshot' => $applicationData['audit_snapshot'] ?? null,
        ]);
    }
}


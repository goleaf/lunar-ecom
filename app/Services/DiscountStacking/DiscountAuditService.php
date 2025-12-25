<?php

namespace App\Services\DiscountStacking;

use App\Models\DiscountAuditTrail;
use App\Services\DiscountStacking\DTOs\DiscountApplication;
use Illuminate\Support\Collection;
use Lunar\Models\Cart;
use Lunar\Models\Discount;

/**
 * Discount Audit Service
 * 
 * Handles logging and tracking of discount applications for compliance
 * and audit purposes.
 */
class DiscountAuditService
{
    /**
     * Log discount application
     */
    public function logApplication(
        DiscountApplication $application,
        Cart $cart,
        int $priceBeforeDiscount,
        int $priceAfterDiscount,
        string $scope,
        ?string $conflictResolution = null,
        ?Collection $appliedWith = null
    ): DiscountAuditTrail {
        $discount = $application->discount;
        $data = $discount->data ?? [];
        
        return DiscountAuditTrail::create([
            'discount_id' => $discount->id,
            'cart_id' => $cart->id,
            'order_id' => null, // Will be set when order is created
            'user_id' => $cart->user_id ?? auth()->id(),
            'discount_type' => $application->type->value,
            'stacking_mode' => $this->getStackingMode($discount),
            'stacking_strategy' => $this->getStackingStrategy($discount),
            'priority' => $discount->priority ?? 1,
            'price_before_discount' => $priceBeforeDiscount,
            'discount_amount' => $application->amount,
            'price_after_discount' => $priceAfterDiscount,
            'scope' => $scope,
            'reason' => $application->reason,
            'conflict_resolution' => $conflictResolution,
            'applied_with' => $appliedWith?->map(fn($app) => [
                'discount_id' => $app->discount->id,
                'amount' => $app->amount,
            ])->toArray(),
            'jurisdiction' => $data['jurisdiction'] ?? config('app.locale'),
            'map_protected' => $data['map_protected'] ?? false,
            'b2b_contract' => $data['b2b_contract'] ?? false,
            'manual_coupon' => (bool) $discount->coupon,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => [
                'cart_subtotal' => $cart->subTotal?->value ?? 0,
                'cart_currency' => $cart->currency?->code ?? 'USD',
                'customer_id' => $cart->customer_id,
                'channel' => $cart->channel?->handle,
            ],
        ]);
    }

    /**
     * Log multiple discount applications
     */
    public function logApplications(
        Collection $applications,
        Cart $cart,
        int $baseAmount,
        string $scope,
        ?string $conflictResolution = null
    ): Collection {
        $logs = collect();
        $currentAmount = $baseAmount;
        
        foreach ($applications as $application) {
            $priceBefore = $currentAmount;
            $priceAfter = max(0, $currentAmount - $application->amount);
            
            $log = $this->logApplication(
                application: $application,
                cart: $cart,
                priceBeforeDiscount: $priceBefore,
                priceAfterDiscount: $priceAfter,
                scope: $scope,
                conflictResolution: $conflictResolution,
                appliedWith: $applications->reject(fn($app) => $app === $application),
            );
            
            $logs->push($log);
            $currentAmount = $priceAfter;
        }
        
        return $logs;
    }

    /**
     * Get audit trail for a discount
     */
    public function getAuditTrail(Discount $discount, ?int $limit = 100): Collection
    {
        return DiscountAuditTrail::where('discount_id', $discount->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit trail for a cart
     */
    public function getCartAuditTrail(Cart $cart): Collection
    {
        return DiscountAuditTrail::where('cart_id', $cart->id)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get audit trail for jurisdiction compliance
     */
    public function getJurisdictionAuditTrail(string $jurisdiction, $startDate, $endDate): Collection
    {
        return DiscountAuditTrail::inJurisdiction($jurisdiction)
            ->inDateRange($startDate, $endDate)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get stacking mode
     */
    protected function getStackingMode(Discount $discount): ?string
    {
        $data = $discount->data ?? [];
        return $data['stacking_mode'] ?? $discount->stacking_mode ?? null;
    }

    /**
     * Get stacking strategy
     */
    protected function getStackingStrategy(Discount $discount): ?string
    {
        $data = $discount->data ?? [];
        return $data['stacking_strategy'] ?? $discount->stacking_strategy ?? null;
    }
}


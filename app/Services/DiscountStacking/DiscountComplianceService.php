<?php

namespace App\Services\DiscountStacking;

use App\Models\DiscountAuditTrail;
use Illuminate\Support\Collection;
use Lunar\Models\Cart;
use Lunar\Models\Discount;

/**
 * Discount Compliance Service
 * 
 * Handles compliance checks and legal requirements for discount applications.
 */
class DiscountComplianceService
{
    /**
     * Validate discount compliance before application
     */
    public function validateCompliance(Discount $discount, Cart $cart): array
    {
        $violations = [];
        
        // Check MAP protection
        if ($this->isMapProtected($discount, $cart)) {
            $violations[] = [
                'type' => 'map_protection',
                'message' => 'Discount cannot be applied to MAP-protected items',
                'severity' => 'error',
            ];
        }
        
        // Check jurisdiction restrictions
        $jurisdictionViolation = $this->checkJurisdiction($discount, $cart);
        if ($jurisdictionViolation) {
            $violations[] = $jurisdictionViolation;
        }
        
        // Check double discount safeguards
        $doubleDiscountViolation = $this->checkDoubleDiscount($discount, $cart);
        if ($doubleDiscountViolation) {
            $violations[] = $doubleDiscountViolation;
        }
        
        return $violations;
    }

    /**
     * Check if discount violates MAP protection
     */
    protected function isMapProtected(Discount $discount, Cart $cart): bool
    {
        // Check if discount is MAP-protected
        $data = $discount->data ?? [];
        if ($data['map_protected'] ?? $discount->map_protected ?? false) {
            return true;
        }
        
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
     * Check jurisdiction restrictions
     */
    protected function checkJurisdiction(Discount $discount, Cart $cart): ?array
    {
        $data = $discount->data ?? [];
        $discountJurisdiction = $data['jurisdiction'] ?? $discount->jurisdiction;
        
        if (!$discountJurisdiction) {
            return null; // No jurisdiction restriction
        }
        
        // Get cart jurisdiction (from shipping address, customer location, etc.)
        $cartJurisdiction = $this->getCartJurisdiction($cart);
        
        if ($cartJurisdiction && $cartJurisdiction !== $discountJurisdiction) {
            return [
                'type' => 'jurisdiction',
                'message' => "Discount is only valid in {$discountJurisdiction}",
                'severity' => 'error',
            ];
        }
        
        return null;
    }

    /**
     * Get cart jurisdiction
     */
    protected function getCartJurisdiction(Cart $cart): ?string
    {
        // Check shipping address
        if ($cart->shippingAddress) {
            return $cart->shippingAddress->country?->iso2 ?? null;
        }
        
        // Check customer default address
        if ($cart->customer?->defaultAddress) {
            return $cart->customer->defaultAddress->country?->iso2 ?? null;
        }
        
        // Check channel default
        if ($cart->channel) {
            return $cart->channel->default_country ?? null;
        }
        
        return null;
    }

    /**
     * Check for double discount violations
     */
    protected function checkDoubleDiscount(Discount $discount, Cart $cart): ?array
    {
        // Get already applied discounts
        $appliedDiscounts = $this->getAppliedDiscounts($cart);
        
        // Check if this discount conflicts with already applied discounts
        foreach ($appliedDiscounts as $appliedDiscount) {
            if ($this->isDoubleDiscount($discount, $appliedDiscount)) {
                return [
                    'type' => 'double_discount',
                    'message' => 'This discount conflicts with an already applied discount',
                    'severity' => 'warning',
                ];
            }
        }
        
        return null;
    }

    /**
     * Check if two discounts create a double discount scenario
     */
    protected function isDoubleDiscount(Discount $discount1, Discount $discount2): bool
    {
        // Same discount applied twice
        if ($discount1->id === $discount2->id) {
            return true;
        }
        
        // Check if both are exclusive
        $data1 = $discount1->data ?? [];
        $data2 = $discount2->data ?? [];
        
        $stackingMode1 = $data1['stacking_mode'] ?? $discount1->stacking_mode ?? 'non_stackable';
        $stackingMode2 = $data2['stacking_mode'] ?? $discount2->stacking_mode ?? 'non_stackable';
        
        if ($stackingMode1 === 'exclusive' || $stackingMode2 === 'exclusive') {
            return true;
        }
        
        return false;
    }

    /**
     * Get already applied discounts from cart
     */
    protected function getAppliedDiscounts(Cart $cart): Collection
    {
        // Get from cart metadata or pricing snapshot
        $pricingSnapshot = $cart->pricing_snapshot ?? [];
        $appliedRules = $pricingSnapshot['applied_rules'] ?? [];
        
        $discountIds = collect($appliedRules)
            ->pluck('discount_id')
            ->unique()
            ->filter();
        
        if ($discountIds->isEmpty()) {
            return collect();
        }
        
        return Discount::whereIn('id', $discountIds)->get();
    }

    /**
     * Track price before discount for compliance
     */
    public function trackPriceBeforeDiscount(Cart $cart, int $priceBeforeDiscount): void
    {
        // Store in cart metadata
        $metadata = $cart->metadata ?? [];
        $metadata['price_before_discount'] = $priceBeforeDiscount;
        $metadata['price_tracked_at'] = now()->toIso8601String();
        
        $cart->update(['metadata' => $metadata]);
    }

    /**
     * Generate compliance report
     */
    public function generateComplianceReport(Cart $cart): array
    {
        $auditTrails = DiscountAuditTrail::where('cart_id', $cart->id)->get();
        
        return [
            'cart_id' => $cart->id,
            'total_discounts_applied' => $auditTrails->count(),
            'total_discount_amount' => $auditTrails->sum('discount_amount'),
            'price_before_discount' => $cart->metadata['price_before_discount'] ?? null,
            'price_after_discount' => $cart->subTotal?->value ?? 0,
            'discounts' => $auditTrails->map(function($trail) {
                return [
                    'discount_id' => $trail->discount_id,
                    'discount_name' => $trail->discount->name ?? 'Unknown',
                    'amount' => $trail->discount_amount,
                    'reason' => $trail->reason,
                    'applied_at' => $trail->created_at->toIso8601String(),
                    'jurisdiction' => $trail->jurisdiction,
                    'map_protected' => $trail->map_protected,
                    'b2b_contract' => $trail->b2b_contract,
                ];
            })->toArray(),
            'compliance_flags' => $this->checkComplianceFlags($auditTrails),
        ];
    }

    /**
     * Check compliance flags
     */
    protected function checkComplianceFlags(Collection $auditTrails): array
    {
        $flags = [];
        
        // Check for MAP violations
        $mapViolations = $auditTrails->where('map_protected', true)->count();
        if ($mapViolations > 0) {
            $flags[] = [
                'type' => 'map_violation',
                'severity' => 'error',
                'message' => "{$mapViolations} discount(s) applied to MAP-protected items",
            ];
        }
        
        // Check for missing price tracking
        $missingPriceTracking = $auditTrails->whereNull('price_before_discount')->count();
        if ($missingPriceTracking > 0) {
            $flags[] = [
                'type' => 'missing_price_tracking',
                'severity' => 'warning',
                'message' => "{$missingPriceTracking} discount(s) missing price-before-discount tracking",
            ];
        }
        
        return $flags;
    }
}



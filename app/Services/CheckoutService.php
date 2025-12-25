<?php

namespace App\Services;

use App\Models\CheckoutLock;
use App\Services\CheckoutCache;
use App\Services\CheckoutLogger;
use App\Services\CheckoutValidator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lunar\Facades\CartSession;
use Lunar\Models\Cart;

/**
 * Checkout Service
 * 
 * Handles checkout orchestration, edge cases, and cart locking.
 */
class CheckoutService
{
    public function __construct(
        protected CheckoutStateMachine $stateMachine,
        protected StockService $stockService,
        protected CheckoutLogger $logger,
        protected CheckoutCache $cache,
        protected CheckoutValidator $validator
    ) {}

    /**
     * Start checkout and lock cart.
     */
    public function startCheckout(Cart $cart, ?int $ttlMinutes = null): CheckoutLock
    {
        // Validate cart can start checkout
        $validation = $this->validator->canStartCheckout($cart);
        if (!$validation['valid']) {
            throw new \Exception(implode(', ', $validation['errors']));
        }

        return DB::transaction(function () use ($cart, $ttlMinutes) {
            // Check if cart is already locked
            $existingLock = CheckoutLock::where('cart_id', $cart->id)
                ->where('session_id', session()->getId())
                ->active()
                ->first();

            if ($existingLock) {
                return $existingLock;
            }

            // Check for other active locks on this cart
            $otherLock = CheckoutLock::where('cart_id', $cart->id)
                ->where('session_id', '!=', session()->getId())
                ->active()
                ->first();

            if ($otherLock) {
                throw new \Exception('Cart is currently being checked out by another session');
            }

            return $this->stateMachine->startCheckout($cart, $ttlMinutes);
        });
    }

    /**
     * Process checkout with edge case handling.
     */
    public function processCheckout(CheckoutLock $lock, array $paymentData = []): \Lunar\Models\Order
    {
        return DB::transaction(function () use ($lock, $paymentData) {
            // Validate lock is still active
            if (!$lock->isActive()) {
                throw new \Exception('Checkout lock has expired or is invalid');
            }

            // Handle edge cases before proceeding
            $this->handleEdgeCases($lock);

            // Execute checkout phases
            return $this->stateMachine->executeCheckout($lock, $paymentData);
        });
    }

    /**
     * Handle edge cases during checkout.
     */
    protected function handleEdgeCases(CheckoutLock $lock): void
    {
        $cart = $lock->cart->fresh();

        // Edge Case 1: Price changed during checkout
        $this->validatePriceConsistency($lock, $cart);

        // Edge Case 2: Promotion expired mid-checkout
        $this->validatePromotionValidity($lock, $cart);

        // Edge Case 3: Stock changed mid-checkout
        $this->validateStockAvailability($lock, $cart);

        // Edge Case 4: Currency rate changed
        $this->validateCurrencyRate($lock, $cart);
    }

    /**
     * Validate price consistency.
     */
    protected function validatePriceConsistency(CheckoutLock $lock, Cart $cart): void
    {
        $snapshot = \App\Models\PriceSnapshot::where('checkout_lock_id', $lock->id)
            ->whereNull('cart_line_id')
            ->first();

        if (!$snapshot) {
            return; // No snapshot yet, prices will be locked
        }

        // Recalculate cart to check for price changes
        $cart->calculate();
        $currentTotal = $cart->total->value;
        $snapshotTotal = $snapshot->total;

        // Allow small differences due to rounding (1 cent)
        if (abs($currentTotal - $snapshotTotal) > 1) {
            $this->logger->priceDriftDetected($lock, $snapshotTotal, $currentTotal);

            // Use snapshot price (frozen price)
            // Prices are already locked, so we continue with snapshot
        }
    }

    /**
     * Validate promotion validity.
     */
    protected function validatePromotionValidity(CheckoutLock $lock, Cart $cart): void
    {
        $snapshot = \App\Models\PriceSnapshot::where('checkout_lock_id', $lock->id)
            ->whereNull('cart_line_id')
            ->first();

        if (!$snapshot || !$snapshot->coupon_code) {
            return; // No promotion to validate
        }

        // Check if coupon is still valid
        // TODO: Check with discount service if coupon is still active
        // For now, we trust the snapshot (promotion is frozen)
        
        Log::info('Promotion validated', [
            'lock_id' => $lock->id,
            'coupon_code' => $snapshot->coupon_code,
        ]);
    }

    /**
     * Validate stock availability.
     */
    protected function validateStockAvailability(CheckoutLock $lock, Cart $cart): void
    {
        $reservations = \App\Models\StockReservation::where('reference_type', CheckoutLock::class)
            ->where('reference_id', $lock->id)
            ->where('is_released', false)
            ->get();

        foreach ($cart->lines as $line) {
            if ($line->purchasable instanceof \Lunar\Models\ProductVariant) {
                $variant = $line->purchasable;
                
                // Check if we have a reservation for this variant
                $reservation = $reservations->firstWhere('product_variant_id', $variant->id);
                
                if (!$reservation) {
                    throw new \Exception("No stock reservation found for variant {$variant->sku}");
                }

                // Verify reservation is still valid
                if ($reservation->isExpired()) {
                    throw new \Exception("Stock reservation expired for variant {$variant->sku}");
                }

                // Verify quantity matches
                if ($reservation->quantity < $line->quantity) {
                    throw new \Exception("Stock reservation quantity mismatch for variant {$variant->sku}");
                }
            }
        }
    }

    /**
     * Validate currency rate.
     */
    protected function validateCurrencyRate(CheckoutLock $lock, Cart $cart): void
    {
        $snapshot = \App\Models\PriceSnapshot::where('checkout_lock_id', $lock->id)
            ->whereNull('cart_line_id')
            ->first();

        if (!$snapshot) {
            return;
        }

        // Currency rate is frozen in snapshot, so we use it
        // No validation needed as rate is locked
    }

    /**
     * Release checkout lock and restore cart.
     */
    public function releaseCheckout(CheckoutLock $lock): void
    {
        DB::transaction(function () use ($lock) {
            // Release stock reservations
            $reservations = \App\Models\StockReservation::where('reference_type', CheckoutLock::class)
                ->where('reference_id', $lock->id)
                ->where('is_released', false)
                ->get();

            foreach ($reservations as $reservation) {
                $this->stockService->releaseReservation($reservation);
            }

            // Mark lock as released (if not already completed/failed)
            if ($lock->isActive()) {
                $lock->update(['state' => CheckoutLock::STATE_FAILED]);
            }

            // Clear cache
            $this->cache->clearStatus($lock->cart_id);

            Log::info('Checkout released', ['lock_id' => $lock->id]);
        });
    }

    /**
     * Cleanup expired checkout locks.
     */
    public function cleanupExpiredLocks(): int
    {
        $expiredLocks = CheckoutLock::expired()->get();
        $count = 0;

        foreach ($expiredLocks as $lock) {
            try {
                $this->releaseCheckout($lock);
                $count++;
            } catch (\Exception $e) {
                Log::error('Failed to cleanup expired lock', [
                    'lock_id' => $lock->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    /**
     * Check if cart is locked for checkout.
     */
    public function isCartLocked(Cart $cart): bool
    {
        return CheckoutLock::where('cart_id', $cart->id)
            ->where('session_id', '!=', session()->getId())
            ->active()
            ->exists();
    }

    /**
     * Get active checkout lock for cart.
     */
    public function getActiveLock(Cart $cart): ?CheckoutLock
    {
        return CheckoutLock::where('cart_id', $cart->id)
            ->where('session_id', session()->getId())
            ->active()
            ->first();
    }

    /**
     * Resume a checkout that's in progress.
     */
    public function resumeCheckout(CheckoutLock $lock, array $paymentData = []): \Lunar\Models\Order
    {
        // Validate can resume
        $validation = $this->validator->canResume($lock);
        if (!$validation['valid']) {
            throw new \Exception(implode(', ', $validation['errors']));
        }

        // Validate payment data if provided
        if (!empty($paymentData)) {
            $paymentValidation = $this->validator->validatePaymentData($paymentData);
            if (!$paymentValidation['valid']) {
                throw new \Exception(implode(', ', $paymentValidation['errors']));
            }
        }

        // Continue from current state
        return $this->processCheckout($lock, $paymentData);
    }

    /**
     * Cancel checkout and release all resources.
     */
    public function cancelCheckout(CheckoutLock $lock): void
    {
        if ($lock->isCompleted()) {
            throw new \Exception('Cannot cancel completed checkout.');
        }

        $this->releaseCheckout($lock);
    }

    /**
     * Get checkout status for cart.
     */
    public function getCheckoutStatus(Cart $cart): array
    {
        // Try cache first
        $cached = $this->cache->getCachedStatus($cart->id);
        if ($cached !== null) {
            return $cached;
        }

        $lock = $this->getActiveLock($cart);
        
        if (!$lock) {
            $status = [
                'locked' => false,
                'can_checkout' => true,
            ];
        } else {
            $status = [
                'locked' => true,
                'can_checkout' => false,
                'lock_id' => $lock->id,
                'state' => $lock->state,
                'phase' => $lock->phase,
                'expires_at' => $lock->expires_at->toIso8601String(),
                'can_resume' => $lock->canResume(),
            ];
        }

        // Cache the status
        $this->cache->cacheStatus($cart->id, $status);

        return $status;
    }
}


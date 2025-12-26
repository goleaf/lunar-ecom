<?php

namespace App\Helpers;

use App\Models\CheckoutLock;
use App\Services\CheckoutService;
use Lunar\Facades\CartSession;

/**
 * Helper functions for checkout operations.
 */
class CheckoutHelper
{
    /**
     * Check if current cart is locked for checkout.
     */
    public static function isCartLocked(): bool
    {
        $cart = CartSession::current();
        
        if (!$cart) {
            return false;
        }

        $checkoutService = app(CheckoutService::class);
        
        return $checkoutService->isCartLocked($cart);
    }

    /**
     * Get active checkout lock for current cart.
     */
    public static function getActiveLock(): ?CheckoutLock
    {
        $cart = CartSession::current();
        
        if (!$cart) {
            return null;
        }

        $checkoutService = app(CheckoutService::class);
        
        return $checkoutService->getActiveLock($cart);
    }

    /**
     * Get checkout status for current cart.
     */
    public static function getStatus(): array
    {
        $cart = CartSession::current();
        
        if (!$cart) {
            return [
                'locked' => false,
                'can_checkout' => false,
                'message' => 'No cart found',
            ];
        }

        $checkoutService = app(CheckoutService::class);
        
        return $checkoutService->getCheckoutStatus($cart);
    }

    /**
     * Format checkout duration for display.
     */
    public static function formatDuration(CheckoutLock $lock): string
    {
        if (!$lock->completed_at && !$lock->failed_at) {
            $duration = now()->diffInSeconds($lock->locked_at);
        } else {
            $endTime = $lock->completed_at ?? $lock->failed_at;
            $duration = $endTime->diffInSeconds($lock->locked_at);
        }

        if ($duration < 60) {
            return "{$duration}s";
        }

        $minutes = floor($duration / 60);
        $seconds = $duration % 60;

        return "{$minutes}m {$seconds}s";
    }

    /**
     * Get human-readable state name.
     */
    public static function getStateName(string $state): string
    {
        return match($state) {
            CheckoutLock::STATE_PENDING => 'Pending',
            CheckoutLock::STATE_VALIDATING => 'Validating Cart',
            CheckoutLock::STATE_RESERVING => 'Reserving Stock',
            CheckoutLock::STATE_LOCKING_PRICES => 'Locking Prices',
            CheckoutLock::STATE_AUTHORIZING => 'Authorizing Payment',
            CheckoutLock::STATE_CREATING_ORDER => 'Creating Order',
            CheckoutLock::STATE_CAPTURING => 'Capturing Payment',
            CheckoutLock::STATE_COMMITTING => 'Committing Stock',
            CheckoutLock::STATE_COMPLETED => 'Completed',
            CheckoutLock::STATE_FAILED => 'Failed',
            default => ucfirst(str_replace('_', ' ', $state)),
        };
    }

    /**
     * Check if checkout can be resumed.
     */
    public static function canResume(CheckoutLock $lock): bool
    {
        return $lock->canResume();
    }
}



<?php

namespace App\Services;

use App\Models\CheckoutLock;
use Illuminate\Support\Facades\Log;

/**
 * Centralized logging service for checkout operations.
 */
class CheckoutLogger
{
    protected string $channel;
    protected bool $enabled;
    protected bool $logAllPhases;
    protected bool $logFailures;
    protected bool $logRollbacks;

    public function __construct()
    {
        $config = config('checkout.logging', []);
        $this->channel = $config['channel'] ?? 'daily';
        $this->enabled = $config['enabled'] ?? true;
        $this->logAllPhases = $config['log_all_phases'] ?? true;
        $this->logFailures = $config['log_failures'] ?? true;
        $this->logRollbacks = $config['log_rollbacks'] ?? true;
    }

    /**
     * Log checkout started.
     */
    public function checkoutStarted(CheckoutLock $lock): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->log('info', 'Checkout started', [
            'lock_id' => $lock->id,
            'cart_id' => $lock->cart_id,
            'session_id' => $lock->session_id,
            'user_id' => $lock->user_id,
            'expires_at' => $lock->expires_at->toIso8601String(),
        ]);
    }

    /**
     * Log phase transition.
     */
    public function phaseTransition(CheckoutLock $lock, string $phase, array $context = []): void
    {
        if (!$this->enabled || !$this->logAllPhases) {
            return;
        }

        $this->log('info', "Checkout phase: {$phase}", array_merge([
            'lock_id' => $lock->id,
            'cart_id' => $lock->cart_id,
            'state' => $lock->state,
            'phase' => $phase,
        ], $context));
    }

    /**
     * Log checkout completed.
     */
    public function checkoutCompleted(CheckoutLock $lock, $order): void
    {
        if (!$this->enabled) {
            return;
        }

        $duration = $lock->completed_at->diffInSeconds($lock->locked_at);

        $this->log('info', 'Checkout completed', [
            'lock_id' => $lock->id,
            'cart_id' => $lock->cart_id,
            'order_id' => $order->id,
            'order_reference' => $order->reference,
            'duration_seconds' => $duration,
            'total' => $order->total,
            'currency' => $order->currency_code,
        ]);
    }

    /**
     * Log checkout failure.
     */
    public function checkoutFailed(CheckoutLock $lock, \Throwable $exception): void
    {
        if (!$this->enabled || !$this->logFailures) {
            return;
        }

        $this->log('error', 'Checkout failed', [
            'lock_id' => $lock->id,
            'cart_id' => $lock->cart_id,
            'state' => $lock->state,
            'phase' => $lock->phase,
            'error' => $exception->getMessage(),
            'exception' => get_class($exception),
            'trace' => $exception->getTraceAsString(),
            'failure_reason' => $lock->failure_reason,
        ]);
    }

    /**
     * Log rollback operation.
     */
    public function rollbackStarted(CheckoutLock $lock, string $phase, array $rollbackStack): void
    {
        if (!$this->enabled || !$this->logRollbacks) {
            return;
        }

        $this->log('warning', 'Checkout rollback started', [
            'lock_id' => $lock->id,
            'cart_id' => $lock->cart_id,
            'failed_phase' => $phase,
            'rollback_steps' => count($rollbackStack),
        ]);
    }

    /**
     * Log rollback step.
     */
    public function rollbackStep(CheckoutLock $lock, string $step, bool $success, ?string $error = null): void
    {
        if (!$this->enabled || !$this->logRollbacks) {
            return;
        }

        $level = $success ? 'info' : 'error';
        $message = $success ? "Rollback step completed: {$step}" : "Rollback step failed: {$step}";

        $this->log($level, $message, [
            'lock_id' => $lock->id,
            'step' => $step,
            'success' => $success,
            'error' => $error,
        ]);
    }

    /**
     * Log price drift detected.
     */
    public function priceDriftDetected(CheckoutLock $lock, int $snapshotTotal, int $currentTotal): void
    {
        if (!$this->enabled) {
            return;
        }

        $drift = abs($currentTotal - $snapshotTotal);
        $tolerance = config('checkout.price_drift_tolerance_cents', 1);

        if ($drift > $tolerance) {
            $this->log('warning', 'Price drift detected during checkout', [
                'lock_id' => $lock->id,
                'cart_id' => $lock->cart_id,
                'snapshot_total' => $snapshotTotal,
                'current_total' => $currentTotal,
                'drift' => $drift,
                'tolerance' => $tolerance,
            ]);
        }
    }

    /**
     * Log stock reservation.
     */
    public function stockReserved(CheckoutLock $lock, int $reservationCount): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->log('info', 'Stock reserved for checkout', [
            'lock_id' => $lock->id,
            'cart_id' => $lock->cart_id,
            'reservations_count' => $reservationCount,
        ]);
    }

    /**
     * Log stock released.
     */
    public function stockReleased(CheckoutLock $lock, int $reservationCount): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->log('info', 'Stock released from checkout', [
            'lock_id' => $lock->id,
            'cart_id' => $lock->cart_id,
            'reservations_count' => $reservationCount,
        ]);
    }

    /**
     * Log price lock created.
     */
    public function priceLockCreated(CheckoutLock $lock, int $total, string $currency): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->log('info', 'Price lock created', [
            'lock_id' => $lock->id,
            'cart_id' => $lock->cart_id,
            'total' => $total,
            'currency' => $currency,
        ]);
    }

    /**
     * Internal log method.
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        Log::channel($this->channel)->{$level}("[Checkout] {$message}", $context);
    }
}


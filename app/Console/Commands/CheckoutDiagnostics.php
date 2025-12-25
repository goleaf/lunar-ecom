<?php

namespace App\Console\Commands;

use App\Models\CheckoutLock;
use App\Models\PriceSnapshot;
use App\Models\StockReservation;
use Illuminate\Console\Command;

/**
 * Diagnostic command for checkout system troubleshooting.
 */
class CheckoutDiagnostics extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'checkout:diagnostics {--lock-id= : Specific lock ID to diagnose}';

    /**
     * The console command description.
     */
    protected $description = 'Run diagnostics on checkout system or specific lock';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $lockId = $this->option('lock-id');

        if ($lockId) {
            return $this->diagnoseLock($lockId);
        }

        return $this->diagnoseSystem();
    }

    /**
     * Diagnose specific lock.
     */
    protected function diagnoseLock(int $lockId): int
    {
        $lock = CheckoutLock::with(['cart', 'priceSnapshots', 'stockReservations'])->find($lockId);

        if (!$lock) {
            $this->error("Lock #{$lockId} not found");
            return Command::FAILURE;
        }

        $this->info("Diagnostics for Lock #{$lockId}");
        $this->newLine();

        // Basic info
        $this->table(['Property', 'Value'], [
            ['ID', $lock->id],
            ['Cart ID', $lock->cart_id],
            ['State', $lock->state],
            ['Phase', $lock->phase ?? 'N/A'],
            ['Session ID', $lock->session_id],
            ['User ID', $lock->user_id ?? 'Guest'],
            ['Locked At', $lock->locked_at?->toDateTimeString()],
            ['Expires At', $lock->expires_at?->toDateTimeString()],
            ['Is Active', $lock->isActive() ? 'Yes' : 'No'],
            ['Is Expired', $lock->isExpired() ? 'Yes' : 'No'],
        ]);

        // Price snapshots
        $snapshots = $lock->priceSnapshots;
        $this->newLine();
        $this->info("Price Snapshots: {$snapshots->count()}");

        if ($snapshots->isNotEmpty()) {
            $cartSnapshot = $snapshots->whereNull('cart_line_id')->first();
            if ($cartSnapshot) {
                $this->table(['Field', 'Value'], [
                    ['Total', number_format($cartSnapshot->total / 100, 2)],
                    ['Sub Total', number_format($cartSnapshot->sub_total / 100, 2)],
                    ['Discount', number_format($cartSnapshot->discount_total / 100, 2)],
                    ['Tax', number_format($cartSnapshot->tax_total / 100, 2)],
                    ['Currency', $cartSnapshot->currency_code],
                    ['Exchange Rate', $cartSnapshot->exchange_rate],
                    ['Snapshot At', $cartSnapshot->snapshot_at->toDateTimeString()],
                ]);
            }
        }

        // Stock reservations
        $reservations = $lock->stockReservations;
        $this->newLine();
        $this->info("Stock Reservations: {$reservations->count()}");

        if ($reservations->isNotEmpty()) {
            $reservationData = $reservations->map(fn($r) => [
                'ID' => $r->id,
                'Variant ID' => $r->product_variant_id,
                'Quantity' => $r->quantity,
                'Warehouse' => $r->warehouse_id,
                'Is Released' => $r->is_released ? 'Yes' : 'No',
                'Expires At' => $r->expires_at->toDateTimeString(),
            ])->toArray();

            $this->table(['ID', 'Variant', 'Qty', 'Warehouse', 'Released', 'Expires'], $reservationData);
        }

        // Failure reason
        if ($lock->isFailed() && $lock->failure_reason) {
            $this->newLine();
            $this->error('Failure Reason:');
            $this->line(json_encode($lock->failure_reason, JSON_PRETTY_PRINT));
        }

        return Command::SUCCESS;
    }

    /**
     * Diagnose entire system.
     */
    protected function diagnoseSystem(): int
    {
        $this->info('Checkout System Diagnostics');
        $this->newLine();

        // Active locks
        $active = CheckoutLock::active()->count();
        $this->info("Active Locks: {$active}");

        // Expired locks
        $expired = CheckoutLock::expired()->count();
        $this->info("Expired Locks: {$expired}");

        // Completed today
        $completed = CheckoutLock::where('state', CheckoutLock::STATE_COMPLETED)
            ->where('completed_at', '>=', now()->startOfDay())
            ->count();
        $this->info("Completed Today: {$completed}");

        // Failed today
        $failed = CheckoutLock::where('state', CheckoutLock::STATE_FAILED)
            ->where('failed_at', '>=', now()->startOfDay())
            ->count();
        $this->info("Failed Today: {$failed}");

        // Orphaned reservations
        $orphaned = StockReservation::where('reference_type', CheckoutLock::class)
            ->where('is_released', false)
            ->whereHas('reference', function($q) {
                $q->whereIn('state', [CheckoutLock::STATE_COMPLETED, CheckoutLock::STATE_FAILED]);
            })
            ->count();
        
        if ($orphaned > 0) {
            $this->warn("Orphaned Reservations: {$orphaned}");
        } else {
            $this->info("Orphaned Reservations: 0");
        }

        // Orphaned snapshots
        $orphanedSnapshots = PriceSnapshot::whereHas('checkoutLock', function($q) {
            $q->whereIn('state', [CheckoutLock::STATE_COMPLETED, CheckoutLock::STATE_FAILED]);
        })->count();

        $this->info("Price Snapshots: {$orphanedSnapshots}");

        // Recommendations
        $this->newLine();
        $this->info('Recommendations:');

        if ($expired > 10) {
            $this->warn("  - Run cleanup: php artisan checkout:cleanup-expired-locks");
        }

        if ($orphaned > 0) {
            $this->warn("  - Review orphaned reservations");
        }

        if ($failed > $completed) {
            $this->warn("  - High failure rate detected. Review failure reasons.");
        }

        return Command::SUCCESS;
    }
}


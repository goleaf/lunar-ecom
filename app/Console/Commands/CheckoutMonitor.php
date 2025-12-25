<?php

namespace App\Console\Commands;

use App\Models\CheckoutLock;
use Illuminate\Console\Command;

/**
 * Monitor checkout locks and provide statistics.
 */
class CheckoutMonitor extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'checkout:monitor {--hours=24 : Hours to look back}';

    /**
     * The console command description.
     */
    protected $description = 'Monitor checkout locks and provide statistics';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $since = now()->subHours($hours);

        $this->info("Checkout Monitor - Last {$hours} hours");
        $this->newLine();

        // Active checkouts
        $active = CheckoutLock::where('created_at', '>=', $since)
            ->whereNotIn('state', [CheckoutLock::STATE_COMPLETED, CheckoutLock::STATE_FAILED])
            ->count();

        $this->info("Active Checkouts: {$active}");

        // Completed checkouts
        $completed = CheckoutLock::where('state', CheckoutLock::STATE_COMPLETED)
            ->where('completed_at', '>=', $since)
            ->count();

        $this->info("Completed Checkouts: {$completed}");

        // Failed checkouts
        $failed = CheckoutLock::where('state', CheckoutLock::STATE_FAILED)
            ->where('failed_at', '>=', $since)
            ->count();

        $this->info("Failed Checkouts: {$failed}");

        // Success rate
        $total = $completed + $failed;
        if ($total > 0) {
            $successRate = round(($completed / $total) * 100, 2);
            $this->info("Success Rate: {$successRate}%");
        }

        $this->newLine();

        // State breakdown
        $this->info('State Breakdown:');
        $states = CheckoutLock::where('created_at', '>=', $since)
            ->selectRaw('state, count(*) as count')
            ->groupBy('state')
            ->orderBy('count', 'desc')
            ->get();

        foreach ($states as $state) {
            $this->line("  {$state->state}: {$state->count}");
        }

        $this->newLine();

        // Failure reasons
        if ($failed > 0) {
            $this->info('Top Failure Reasons:');
            $failures = CheckoutLock::where('state', CheckoutLock::STATE_FAILED)
                ->where('failed_at', '>=', $since)
                ->get()
                ->pluck('failure_reason.phase')
                ->filter()
                ->countBy()
                ->sortDesc()
                ->take(5);

            foreach ($failures as $phase => $count) {
                $this->line("  {$phase}: {$count}");
            }
        }

        $this->newLine();

        // Expired locks
        $expired = CheckoutLock::expired()
            ->where('created_at', '>=', $since)
            ->count();

        $this->info("Expired Locks (need cleanup): {$expired}");

        // Average checkout duration
        $avgDuration = CheckoutLock::where('state', CheckoutLock::STATE_COMPLETED)
            ->where('completed_at', '>=', $since)
            ->get()
            ->map(fn($lock) => $lock->completed_at->diffInSeconds($lock->locked_at))
            ->avg();

        if ($avgDuration) {
            $this->info("Average Checkout Duration: " . round($avgDuration, 2) . " seconds");
        }

        return Command::SUCCESS;
    }
}


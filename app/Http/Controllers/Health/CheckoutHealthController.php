<?php

namespace App\Http\Controllers\Health;

use App\Http\Controllers\Controller;
use App\Models\CheckoutLock;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Health check controller for checkout system.
 */
class CheckoutHealthController extends Controller
{
    /**
     * Check checkout system health.
     */
    public function check(): JsonResponse
    {
        $health = [
            'status' => 'healthy',
            'checks' => [],
            'timestamp' => now()->toIso8601String(),
        ];

        // Check for expired locks that haven't been cleaned up
        $expiredCount = CheckoutLock::expired()
            ->where('created_at', '>', now()->subHour())
            ->count();

        $health['checks']['expired_locks'] = [
            'status' => $expiredCount < 10 ? 'ok' : 'warning',
            'count' => $expiredCount,
            'message' => $expiredCount < 10 
                ? "{$expiredCount} expired locks (normal)"
                : "{$expiredCount} expired locks (cleanup may be needed)",
        ];

        // Check for stuck checkouts (active for more than 30 minutes)
        $stuckCount = CheckoutLock::where('state', '!=', CheckoutLock::STATE_COMPLETED)
            ->where('state', '!=', CheckoutLock::STATE_FAILED)
            ->where('created_at', '<', now()->subMinutes(30))
            ->count();

        $health['checks']['stuck_checkouts'] = [
            'status' => $stuckCount === 0 ? 'ok' : 'warning',
            'count' => $stuckCount,
            'message' => $stuckCount === 0
                ? 'No stuck checkouts'
                : "{$stuckCount} checkouts stuck for >30 minutes",
        ];

        // Check database connectivity
        try {
            DB::connection()->getPdo();
            $health['checks']['database'] = [
                'status' => 'ok',
                'message' => 'Database connection healthy',
            ];
        } catch (\Exception $e) {
            $health['checks']['database'] = [
                'status' => 'error',
                'message' => 'Database connection failed',
            ];
            $health['status'] = 'unhealthy';
        }

        // Overall status
        $hasErrors = collect($health['checks'])->contains(fn($check) => $check['status'] === 'error');
        $hasWarnings = collect($health['checks'])->contains(fn($check) => $check['status'] === 'warning');

        if ($hasErrors) {
            $health['status'] = 'unhealthy';
        } elseif ($hasWarnings) {
            $health['status'] = 'degraded';
        }

        $statusCode = match($health['status']) {
            'healthy' => 200,
            'degraded' => 200,
            'unhealthy' => 503,
            default => 200,
        };

        return response()->json($health, $statusCode);
    }
}



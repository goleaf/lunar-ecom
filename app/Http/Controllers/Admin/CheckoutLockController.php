<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Filament\Resources\CheckoutLockResource as FilamentCheckoutLockResource;
use App\Http\Resources\CheckoutLockResource;
use App\Models\CheckoutLock;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

/**
 * Admin controller for managing checkout locks.
 */
class CheckoutLockController extends Controller
{
    public function __construct(
        protected CheckoutService $checkoutService
    ) {
        // Align legacy admin routes with Filament's staff guard.
        $this->middleware('auth:staff');
    }

    /**
     * Display a listing of checkout locks.
     */
    public function index(Request $request): RedirectResponse
    {
        // Prefer Filament for the admin UI.
        return redirect()->route('filament.admin.resources.' . FilamentCheckoutLockResource::getSlug() . '.index', $request->query());
    }

    /**
     * Display the specified checkout lock.
     */
    public function show(CheckoutLock $checkoutLock): RedirectResponse
    {
        // Prefer Filament for the admin UI.
        return redirect()->route('filament.admin.resources.' . FilamentCheckoutLockResource::getSlug() . '.view', [
            'record' => $checkoutLock->getKey(),
        ]);
    }

    /**
     * Release a checkout lock manually.
     */
    public function release(CheckoutLock $checkoutLock)
    {
        try {
            $this->checkoutService->releaseCheckout($checkoutLock);

            return redirect()->route('filament.admin.resources.' . FilamentCheckoutLockResource::getSlug() . '.index')
                ->with('success', 'Checkout lock released successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to release lock: ' . $e->getMessage());
        }
    }

    /**
     * Get checkout statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $hours = (int) ($request->input('hours', 24));
        $since = now()->subHours($hours);

        $stats = [
            'active' => CheckoutLock::where('created_at', '>=', $since)
                ->whereNotIn('state', [CheckoutLock::STATE_COMPLETED, CheckoutLock::STATE_FAILED])
                ->count(),
            'completed' => CheckoutLock::where('state', CheckoutLock::STATE_COMPLETED)
                ->where('completed_at', '>=', $since)
                ->count(),
            'failed' => CheckoutLock::where('state', CheckoutLock::STATE_FAILED)
                ->where('failed_at', '>=', $since)
                ->count(),
            'expired' => CheckoutLock::expired()
                ->where('created_at', '>=', $since)
                ->count(),
            'states' => CheckoutLock::where('created_at', '>=', $since)
                ->selectRaw('state, count(*) as count')
                ->groupBy('state')
                ->pluck('count', 'state')
                ->toArray(),
        ];

        $total = $stats['completed'] + $stats['failed'];
        $stats['success_rate'] = $total > 0 
            ? round(($stats['completed'] / $total) * 100, 2) 
            : 0;

        return response()->json($stats);
    }

    /**
     * Get checkout lock as JSON (API endpoint).
     */
    public function showJson(CheckoutLock $checkoutLock): JsonResponse
    {
        $checkoutLock->load([
            'cart.lines.purchasable',
            'priceSnapshots',
            'stockReservations.productVariant',
            'user',
        ]);

        return (new CheckoutLockResource($checkoutLock))->response();
    }
}


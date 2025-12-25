<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CheckoutLock;
use App\Services\CheckoutService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin controller for managing checkout locks.
 */
class CheckoutLockController extends Controller
{
    public function __construct(
        protected CheckoutService $checkoutService
    ) {
        $this->middleware('auth');
    }

    /**
     * Display a listing of checkout locks.
     */
    public function index(Request $request): View
    {
        $query = CheckoutLock::with(['cart', 'user'])
            ->orderBy('created_at', 'desc');

        // Filter by state
        if ($request->has('state')) {
            $query->where('state', $request->state);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $locks = $query->paginate(50);

        return view('admin.checkout-locks.index', compact('locks'));
    }

    /**
     * Display the specified checkout lock.
     */
    public function show(CheckoutLock $checkoutLock): View
    {
        $checkoutLock->load([
            'cart.lines.purchasable',
            'priceSnapshots',
            'stockReservations.productVariant',
            'user',
        ]);

        $order = $checkoutLock->getOrder();

        return view('admin.checkout-locks.show', compact('checkoutLock', 'order'));
    }

    /**
     * Release a checkout lock manually.
     */
    public function release(CheckoutLock $checkoutLock)
    {
        try {
            $this->checkoutService->releaseCheckout($checkoutLock);

            return redirect()->route('admin.checkout-locks.index')
                ->with('success', 'Checkout lock released successfully');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to release lock: ' . $e->getMessage());
        }
    }

    /**
     * Get checkout statistics.
     */
    public function statistics(Request $request)
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
}


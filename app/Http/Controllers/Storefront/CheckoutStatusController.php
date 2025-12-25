<?php

namespace App\Http\Controllers\Storefront;

use App\Helpers\CheckoutHelper;
use App\Http\Controllers\Controller;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lunar\Facades\CartSession;

/**
 * Controller for checking checkout status.
 */
class CheckoutStatusController extends Controller
{
    public function __construct(
        protected CheckoutService $checkoutService
    ) {}

    /**
     * Get checkout status for current cart.
     */
    public function status(Request $request): JsonResponse
    {
        $cart = CartSession::current();

        if (!$cart) {
            return response()->json([
                'locked' => false,
                'can_checkout' => false,
                'message' => 'No cart found',
            ]);
        }

        $status = $this->checkoutService->getCheckoutStatus($cart);
        
        // Add human-readable state name
        if (isset($status['state'])) {
            $status['state_name'] = CheckoutHelper::getStateName($status['state']);
        }

        return response()->json($status);
    }

    /**
     * Cancel active checkout.
     */
    public function cancel(Request $request): JsonResponse
    {
        $cart = CartSession::current();

        if (!$cart) {
            return response()->json([
                'success' => false,
                'message' => 'No cart found',
            ], 404);
        }

        $lock = $this->checkoutService->getActiveLock($cart);

        if (!$lock) {
            return response()->json([
                'success' => false,
                'message' => 'No active checkout found',
            ], 404);
        }

        try {
            $this->checkoutService->cancelCheckout($lock);

            return response()->json([
                'success' => true,
                'message' => 'Checkout cancelled successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}


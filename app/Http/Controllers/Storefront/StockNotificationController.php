<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Services\StockNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Lunar\Models\ProductVariant;

/**
 * Controller for stock notification subscriptions.
 */
class StockNotificationController extends Controller
{
    public function __construct(
        protected StockNotificationService $notificationService
    ) {}

    /**
     * Subscribe to back-in-stock notifications.
     *
     * @param  Request  $request
     * @param  ProductVariant  $variant
     * @return JsonResponse
     */
    public function subscribe(Request $request, ProductVariant $variant): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        try {
            $notification = $this->notificationService->subscribe(
                $variant,
                $validated['email'],
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'You will be notified when this product is back in stock',
                'notification' => $notification,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Unsubscribe from notifications.
     *
     * @param  string  $token
     * @return \Illuminate\View\View|JsonResponse
     */
    public function unsubscribe(string $token)
    {
        $success = $this->notificationService->unsubscribe($token);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => $success,
                'message' => $success ? 'You have been unsubscribed' : 'Invalid unsubscribe link',
            ]);
        }

        return view('storefront.stock-notifications.unsubscribe', [
            'success' => $success,
        ]);
    }

    /**
     * Check if email is already subscribed.
     *
     * @param  Request  $request
     * @param  ProductVariant  $variant
     * @return JsonResponse
     */
    public function check(Request $request, ProductVariant $variant): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $subscription = \App\Models\StockNotification::where('product_variant_id', $variant->id)
            ->where('customer_email', $validated['email'])
            ->where('is_active', true)
            ->first();

        return response()->json([
            'subscribed' => $subscription !== null,
        ]);
    }
}

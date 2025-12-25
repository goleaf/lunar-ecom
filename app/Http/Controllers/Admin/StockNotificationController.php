<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockNotification;
use App\Services\StockNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Lunar\Models\ProductVariant;

/**
 * Admin controller for stock notification management.
 */
class StockNotificationController extends Controller
{
    public function __construct(
        protected StockNotificationService $notificationService
    ) {}

    /**
     * Get subscriptions for a product variant.
     *
     * @param  Request  $request
     * @param  int  $variant
     * @return JsonResponse
     */
    public function subscriptions(Request $request, int $variant): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $variantModel = ProductVariant::findOrFail($variant);
        $subscriptions = $this->notificationService->getSubscriptions($variantModel);

        return response()->json([
            'variant_id' => $variantModel->id,
            'product_name' => $variantModel->product->translateAttribute('name'),
            'subscriptions' => $subscriptions->map(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'email' => $subscription->email,
                    'customer_name' => $subscription->customer?->fullName ?? $subscription->name,
                    'subscribed_at' => $subscription->created_at,
                    'notification_sent_at' => $subscription->notified_at,
                    'status' => $subscription->status,
                ];
            }),
            'total_count' => $subscriptions->count(),
        ]);
    }

    /**
     * Get notification metrics for a product variant.
     *
     * @param  Request  $request
     * @param  int  $variant
     * @return JsonResponse
     */
    public function metrics(Request $request, int $variant): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $variantModel = ProductVariant::findOrFail($variant);
        $metrics = $this->notificationService->getMetrics($variantModel);

        return response()->json($metrics);
    }

    /**
     * Get all subscriptions (admin dashboard).
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $query = StockNotification::with(['productVariant.product', 'customer']);

        if ($request->has('variant_id')) {
            $query->where('product_variant_id', $request->input('variant_id'));
        }

        if ($request->has('status')) {
            if ($request->input('status') === 'sent') {
                $query->sent();
            } elseif ($request->input('status') === 'pending') {
                $query->pending();
            } elseif ($request->input('status') === 'cancelled') {
                $query->where('status', 'cancelled');
            }
        }

        $subscriptions = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 25));

        return response()->json($subscriptions);
    }
}


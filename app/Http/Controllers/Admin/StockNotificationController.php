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
     * @param  ProductVariant  $variant
     * @return JsonResponse
     */
    public function subscriptions(Request $request, ProductVariant $variant): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $subscriptions = $this->notificationService->getSubscriptions($variant);

        return response()->json([
            'variant_id' => $variant->id,
            'product_name' => $variant->product->translateAttribute('name'),
            'subscriptions' => $subscriptions->map(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'email' => $subscription->customer_email,
                    'customer_name' => $subscription->customer?->fullName,
                    'subscribed_at' => $subscription->subscribed_at,
                    'notification_sent_at' => $subscription->notification_sent_at,
                    'expires_at' => $subscription->expires_at,
                ];
            }),
            'total_count' => $subscriptions->count(),
        ]);
    }

    /**
     * Get notification metrics for a product variant.
     *
     * @param  Request  $request
     * @param  ProductVariant  $variant
     * @return JsonResponse
     */
    public function metrics(Request $request, ProductVariant $variant): JsonResponse
    {
        // Admin-only: require staff authentication
        if (!auth('staff')->check()) {
            abort(403, 'Unauthorized');
        }

        $metrics = $this->notificationService->getMetrics($variant);

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
            if ($request->input('status') === 'active') {
                $query->active();
            } elseif ($request->input('status') === 'sent') {
                $query->whereNotNull('notification_sent_at');
            } elseif ($request->input('status') === 'pending') {
                $query->pending();
            }
        }

        $subscriptions = $query->orderBy('subscribed_at', 'desc')
            ->paginate($request->input('per_page', 25));

        return response()->json($subscriptions);
    }
}


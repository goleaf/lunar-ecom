<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\ComingSoonNotification;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * Controller for coming soon product notifications.
 */
class ComingSoonController extends Controller
{
    /**
     * Subscribe to coming soon notifications.
     *
     * @param  Request  $request
     * @param  Product  $product
     * @return JsonResponse
     */
    public function subscribe(Request $request, Product $product): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if product is coming soon
        if (!$product->isComingSoon()) {
            return response()->json([
                'success' => false,
                'message' => 'This product is not marked as coming soon',
            ], 422);
        }

        // Check if already subscribed
        $existing = ComingSoonNotification::where('product_id', $product->id)
            ->where('email', $request->email)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'message' => 'You are already subscribed to notifications for this product',
            ]);
        }

        // Create notification subscription
        $notification = ComingSoonNotification::create([
            'product_id' => $product->id,
            'email' => $request->email,
            'customer_id' => auth()->user()?->customer?->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'You have been subscribed to notifications for this product',
        ]);
    }

    /**
     * Unsubscribe from coming soon notifications.
     *
     * @param  string  $token
     * @return \Illuminate\View\View
     */
    public function unsubscribe(string $token)
    {
        $notification = ComingSoonNotification::where('token', $token)->firstOrFail();
        $notification->delete();

        return view('frontend.coming-soon.unsubscribed', [
            'product' => $notification->product,
        ]);
    }
}





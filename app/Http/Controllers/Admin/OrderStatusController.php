<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\OrderStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Lunar\Models\Order;

/**
 * Admin Controller for Order Status Management
 * 
 * Provides endpoints for managing order statuses in the admin panel.
 */
class OrderStatusController extends Controller
{
    protected OrderStatusService $orderStatusService;

    public function __construct(OrderStatusService $orderStatusService)
    {
        $this->orderStatusService = $orderStatusService;
        $this->middleware('auth');
    }

    /**
     * Update order status.
     * 
     * @param Request $request
     * @param Order $order
     * @return JsonResponse
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string',
            'notes' => 'nullable|string|max:1000',
            'meta' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Validate status transition
            if (!$this->orderStatusService->isValidTransition($order->status, $request->status)) {
                return response()->json([
                    'success' => false,
                    'message' => "Invalid status transition from '{$order->status}' to '{$request->status}'",
                ], 400);
            }

            $updatedOrder = $this->orderStatusService->updateStatus(
                $order,
                $request->status,
                $request->notes,
                $request->meta
            );

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully',
                'order' => $updatedOrder,
                'status_label' => $this->orderStatusService->getStatusLabel($updatedOrder->status),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get order status history.
     * 
     * @param Order $order
     * @return JsonResponse
     */
    public function getStatusHistory(Order $order): JsonResponse
    {
        $history = $this->orderStatusService->getStatusHistory($order);

        return response()->json([
            'success' => true,
            'history' => $history,
        ]);
    }

    /**
     * Get complete order history.
     * 
     * @param Order $order
     * @return JsonResponse
     */
    public function getOrderHistory(Order $order): JsonResponse
    {
        $history = $this->orderStatusService->getOrderHistory($order);

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Get orders filtered by status.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrdersByStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|string',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $orders = $this->orderStatusService->getOrdersByStatus(
            $request->status,
            $request->limit
        );

        return response()->json([
            'success' => true,
            'orders' => $orders,
            'count' => $orders->count(),
        ]);
    }

    /**
     * Get all available statuses.
     * 
     * @return JsonResponse
     */
    public function getAvailableStatuses(): JsonResponse
    {
        $statuses = $this->orderStatusService->getAvailableStatuses();

        return response()->json([
            'success' => true,
            'statuses' => $statuses,
        ]);
    }
}


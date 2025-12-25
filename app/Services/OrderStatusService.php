<?php

namespace App\Services;

use App\Models\OrderStatusHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lunar\Models\Order;

/**
 * Order Status Service
 * 
 * Handles order status updates with history tracking and notifications.
 */
class OrderStatusService
{
    /**
     * Update order status with history tracking.
     * 
     * @param Order $order
     * @param string $newStatus
     * @param string|null $notes Optional notes about the status change
     * @param array|null $meta Optional metadata
     * @return Order
     * @throws \Exception
     */
    public function updateStatus(Order $order, string $newStatus, ?string $notes = null, ?array $meta = null): Order
    {
        // Validate status exists in config
        $statuses = config('lunar.orders.statuses', []);
        if (!isset($statuses[$newStatus])) {
            throw new \InvalidArgumentException("Status '{$newStatus}' is not defined in order statuses configuration.");
        }

        $previousStatus = $order->status;

        // Don't update if status hasn't changed
        if ($previousStatus === $newStatus) {
            return $order;
        }

        return DB::transaction(function () use ($order, $newStatus, $previousStatus, $notes, $meta) {
            // Update order status
            $order->update(['status' => $newStatus]);

            // Create status history entry
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => $newStatus,
                'previous_status' => $previousStatus,
                'notes' => $notes,
                'changed_by' => Auth::id(),
                'meta' => $meta,
            ]);

            // Send notifications
            $this->sendNotifications($order, $newStatus);

            // Log the status change
            Log::info("Order status updated", [
                'order_id' => $order->id,
                'order_reference' => $order->reference,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'changed_by' => Auth::id(),
            ]);

            return $order->fresh();
        });
    }

    /**
     * Get status history for an order.
     * 
     * @param Order $order
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getStatusHistory(Order $order)
    {
        return OrderStatusHistory::forOrder($order->id)
            ->recent()
            ->with('changedBy')
            ->get();
    }

    /**
     * Get orders filtered by status.
     * 
     * @param string $status
     * @param int|null $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getOrdersByStatus(string $status, ?int $limit = null)
    {
        $query = Order::where('status', $status)
            ->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get order history with status changes.
     * 
     * @param Order $order
     * @return array
     */
    public function getOrderHistory(Order $order): array
    {
        $history = $this->getStatusHistory($order);

        return [
            'order' => $order,
            'status_history' => $history,
            'current_status' => $order->status,
            'status_label' => $this->getStatusLabel($order->status),
            'total_status_changes' => $history->count(),
        ];
    }

    /**
     * Get status label from config.
     * 
     * @param string $status
     * @return string
     */
    public function getStatusLabel(string $status): string
    {
        $statuses = config('lunar.orders.statuses', []);
        return $statuses[$status]['label'] ?? $status;
    }

    /**
     * Get all available statuses.
     * 
     * @return array
     */
    public function getAvailableStatuses(): array
    {
        return config('lunar.orders.statuses', []);
    }

    /**
     * Check if status transition is valid.
     * 
     * @param string $fromStatus
     * @param string $toStatus
     * @return bool
     */
    public function isValidTransition(string $fromStatus, string $toStatus): bool
    {
        // Define valid transitions (can be customized)
        $validTransitions = [
            'pending' => ['processing', 'cancelled'],
            'processing' => ['shipped', 'cancelled'],
            'shipped' => ['completed'],
            'completed' => [], // Terminal state
            'cancelled' => [], // Terminal state
        ];

        // Allow any transition if not explicitly restricted
        if (!isset($validTransitions[$fromStatus])) {
            return true;
        }

        // Check if transition is allowed
        return in_array($toStatus, $validTransitions[$fromStatus]) || empty($validTransitions[$fromStatus]);
    }

    /**
     * Send notifications for status change.
     * 
     * @param Order $order
     * @param string $status
     * @return void
     */
    protected function sendNotifications(Order $order, string $status): void
    {
        $statusConfig = config("lunar.orders.statuses.{$status}", []);
        $notifications = $statusConfig['notifications'] ?? [];

        if (empty($notifications)) {
            return;
        }

        // Get customer/user to notify
        $notifiable = $order->user ?? $order->customer?->users->first();

        if (!$notifiable) {
            return;
        }

        // Send each notification
        foreach ($notifications as $notificationClass) {
            if (class_exists($notificationClass)) {
                try {
                    $notifiable->notify(new $notificationClass($order, $status));
                } catch (\Exception $e) {
                    Log::error("Failed to send order status notification", [
                        'order_id' => $order->id,
                        'status' => $status,
                        'notification' => $notificationClass,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }
}


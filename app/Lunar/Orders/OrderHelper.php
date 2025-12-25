<?php

namespace App\Lunar\Orders;

use Illuminate\Support\Collection;
use Lunar\Facades\CartSession;
use Lunar\Models\Cart;
use Lunar\Models\Order;
use Lunar\Models\OrderAddress;
use Lunar\Models\OrderLine;
use Lunar\Models\Transaction;

/**
 * Helper class for working with Lunar Orders.
 * 
 * Provides convenience methods for managing orders, order lines, addresses, and transactions.
 * See: https://docs.lunarphp.com/1.x/reference/orders
 */
class OrderHelper
{
    /**
     * Create an order from a cart.
     * 
     * This is the recommended way to create orders.
     * 
     * @param Cart|null $cart If null, uses current cart from session
     * @param bool $allowMultipleOrders Whether to allow multiple orders per cart
     * @param int|null $orderIdToUpdate Optional order ID to update instead of creating new
     * @return Order
     */
    public static function createFromCart(?Cart $cart = null, bool $allowMultipleOrders = false, ?int $orderIdToUpdate = null): Order
    {
        $cart = $cart ?? CartSession::current();

        if (!$cart) {
            throw new \RuntimeException('No cart available to create order from');
        }

        return $cart->createOrder(
            allowMultipleOrders: $allowMultipleOrders,
            orderIdToUpdate: $orderIdToUpdate
        );
    }

    /**
     * Check if a cart can create an order.
     * 
     * @param Cart|null $cart If null, uses current cart from session
     * @return bool
     */
    public static function canCreateOrder(?Cart $cart = null): bool
    {
        $cart = $cart ?? CartSession::current();

        if (!$cart) {
            return false;
        }

        try {
            return $cart->canCreateOrder();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create an order directly (not recommended, use createFromCart instead).
     * 
     * @param array $data Order data
     * @return Order
     */
    public static function create(array $data): Order
    {
        return Order::create($data);
    }

    /**
     * Find an order by ID.
     * 
     * @param int $id
     * @return Order|null
     */
    public static function find(int $id): ?Order
    {
        return Order::find($id);
    }

    /**
     * Find an order by reference.
     * 
     * @param string $reference
     * @return Order|null
     */
    public static function findByReference(string $reference): ?Order
    {
        return Order::where('reference', $reference)->first();
    }

    /**
     * Get all orders for a user.
     * 
     * @param int $userId
     * @return Collection
     */
    public static function getForUser(int $userId): Collection
    {
        return Order::where('user_id', $userId)->get();
    }

    /**
     * Get all orders for a customer.
     * 
     * @param int $customerId
     * @return Collection
     */
    public static function getForCustomer(int $customerId): Collection
    {
        return Order::where('customer_id', $customerId)->get();
    }

    /**
     * Check if an order is a draft.
     * 
     * @param Order $order
     * @return bool
     */
    public static function isDraft(Order $order): bool
    {
        return $order->isDraft();
    }

    /**
     * Check if an order is placed.
     * 
     * @param Order $order
     * @return bool
     */
    public static function isPlaced(Order $order): bool
    {
        return $order->isPlaced();
    }

    /**
     * Mark an order as placed.
     * 
     * @param Order $order
     * @param \DateTime|null $placedAt If null, uses current time
     * @return Order
     */
    public static function markAsPlaced(Order $order, ?\DateTime $placedAt = null): Order
    {
        $order->update([
            'placed_at' => $placedAt ?? now(),
        ]);

        return $order->fresh();
    }

    /**
     * Update order status with history tracking and notifications.
     * 
     * @param Order $order
     * @param string $status
     * @param string|null $notes Optional notes about the status change
     * @param array|null $meta Optional metadata
     * @return Order
     */
    public static function updateStatus(Order $order, string $status, ?string $notes = null, ?array $meta = null): Order
    {
        $service = app(\App\Services\OrderStatusService::class);
        return $service->updateStatus($order, $status, $notes, $meta);
    }

    /**
     * Get status history for an order.
     * 
     * @param Order $order
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getStatusHistory(Order $order)
    {
        $service = app(\App\Services\OrderStatusService::class);
        return $service->getStatusHistory($order);
    }

    /**
     * Get orders filtered by status.
     * 
     * @param string $status
     * @param int|null $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getOrdersByStatus(string $status, ?int $limit = null)
    {
        $service = app(\App\Services\OrderStatusService::class);
        return $service->getOrdersByStatus($status, $limit);
    }

    /**
     * Get complete order history with status changes.
     * 
     * @param Order $order
     * @return array
     */
    public static function getOrderHistory(Order $order): array
    {
        $service = app(\App\Services\OrderStatusService::class);
        return $service->getOrderHistory($order);
    }

    /**
     * Get status label for an order.
     * 
     * @param Order $order
     * @return string
     */
    public static function getStatusLabel(Order $order): string
    {
        $service = app(\App\Services\OrderStatusService::class);
        return $service->getStatusLabel($order->status);
    }

    /**
     * Create an order line.
     * 
     * @param Order $order
     * @param array $data Order line data
     * @return OrderLine
     */
    public static function createLine(Order $order, array $data): OrderLine
    {
        return $order->lines()->create($data);
    }

    /**
     * Create an order address.
     * 
     * @param Order $order
     * @param array $data Address data (including 'type' which should be 'shipping' or 'billing')
     * @return OrderAddress
     */
    public static function createAddress(Order $order, array $data): OrderAddress
    {
        return $order->addresses()->create($data);
    }

    /**
     * Get shipping address for an order.
     * 
     * @param Order $order
     * @return OrderAddress|null
     */
    public static function getShippingAddress(Order $order): ?OrderAddress
    {
        return $order->shippingAddress;
    }

    /**
     * Get billing address for an order.
     * 
     * @param Order $order
     * @return OrderAddress|null
     */
    public static function getBillingAddress(Order $order): ?OrderAddress
    {
        return $order->billingAddress;
    }

    /**
     * Create a transaction for an order.
     * 
     * @param Order $order
     * @param array $data Transaction data (success, refund, driver, amount, reference, status, notes, card_type, last_four, meta)
     * @return Transaction
     */
    public static function createTransaction(Order $order, array $data): Transaction
    {
        return $order->transactions()->create($data);
    }

    /**
     * Get all transactions for an order.
     * 
     * @param Order $order
     * @return Collection
     */
    public static function getTransactions(Order $order): Collection
    {
        return $order->transactions;
    }

    /**
     * Get all charges (non-refund transactions) for an order.
     * 
     * @param Order $order
     * @return Collection
     */
    public static function getCharges(Order $order): Collection
    {
        return $order->charges;
    }

    /**
     * Get all refunds for an order.
     * 
     * @param Order $order
     * @return Collection
     */
    public static function getRefunds(Order $order): Collection
    {
        return $order->refunds;
    }

    /**
     * Get total amount charged for an order.
     * 
     * @param Order $order
     * @return int Amount in cents/smallest currency unit
     */
    public static function getTotalCharged(Order $order): int
    {
        return $order->charges->sum('amount');
    }

    /**
     * Get total amount refunded for an order.
     * 
     * @param Order $order
     * @return int Amount in cents/smallest currency unit
     */
    public static function getTotalRefunded(Order $order): int
    {
        return $order->refunds->sum('amount');
    }

    /**
     * Get net amount (charged - refunded) for an order.
     * 
     * @param Order $order
     * @return int Amount in cents/smallest currency unit
     */
    public static function getNetAmount(Order $order): int
    {
        return static::getTotalCharged($order) - static::getTotalRefunded($order);
    }
}



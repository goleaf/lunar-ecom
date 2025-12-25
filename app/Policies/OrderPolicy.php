<?php

namespace App\Policies;

use App\Models\User;
use Lunar\Models\Order;
use Lunar\Admin\Models\Staff;
use App\Lunar\Customers\CustomerHelper;

class OrderPolicy
{
    /**
     * Determine if the user can view any orders.
     */
    public function viewAny(User|Staff|null $user): bool
    {
        // Guests cannot view orders
        if (!$user) {
            return false;
        }
        
        // Staff members can view all orders
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('orders:read');
        }
        
        // Regular users can view their own orders
        return true;
    }

    /**
     * Determine if the user can view the order.
     */
    public function view(User|Staff|null $user, Order $order): bool
    {
        // Guests cannot view orders
        if (!$user) {
            return false;
        }
        
        // Staff members can view any order
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('orders:read');
        }
        
        // Regular users can only view their own orders
        if ($user instanceof User) {
            // Check if order belongs to user directly
            if ($order->user_id && $order->user_id === $user->id) {
                return true;
            }
            
            // Check if order belongs to user's customer record
            $customer = CustomerHelper::getOrCreateCustomerForUser($user);
            if ($order->customer_id && $order->customer_id === $customer->id) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Determine if the user can create orders.
     */
    public function create(User|Staff $user): bool
    {
        // Staff members can create orders
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('orders:create');
        }
        
        // Regular users can create orders (checkout)
        return true;
    }

    /**
     * Determine if the user can update the order.
     */
    public function update(User|Staff $user, Order $order): bool
    {
        // Staff members can update any order
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('orders:update');
        }
        
        // Regular users cannot update orders (only staff can)
        return false;
    }

    /**
     * Determine if the user can delete the order.
     */
    public function delete(User|Staff $user, Order $order): bool
    {
        // Only staff members can delete orders
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('orders:delete');
        }
        
        return false;
    }

    /**
     * Determine if the user can cancel the order.
     */
    public function cancel(User|Staff $user, Order $order): bool
    {
        // Staff members can cancel any order
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('orders:update');
        }
        
        // Regular users can only cancel their own orders if not yet shipped
        if ($user instanceof User) {
            // Check ownership
            $canView = $this->view($user, $order);
            if (!$canView) {
                return false;
            }
            
            // Can only cancel if order is not shipped or delivered
            return !in_array($order->status, ['shipped', 'delivered', 'cancelled']);
        }
        
        return false;
    }
}


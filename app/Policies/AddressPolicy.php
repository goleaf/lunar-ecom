<?php

namespace App\Policies;

use App\Models\User;
use Lunar\Models\Address;
use Lunar\Admin\Models\Staff;
use App\Lunar\Customers\CustomerHelper;

class AddressPolicy
{
    /**
     * Determine if the user can view any addresses.
     */
    public function viewAny(User|Staff $user): bool
    {
        // Staff members can view all addresses
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('customers:addresses:read');
        }
        
        // Regular users can view their own addresses
        return true;
    }

    /**
     * Determine if the user can view the address.
     */
    public function view(User|Staff $user, Address $address): bool
    {
        // Staff members can view any address
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('customers:addresses:read');
        }
        
        // Regular users can only view their own addresses
        if ($user instanceof User) {
            $customer = CustomerHelper::getOrCreateCustomerForUser($user);
            return $address->customer_id === $customer->id;
        }
        
        return false;
    }

    /**
     * Determine if the user can create addresses.
     */
    public function create(User|Staff $user): bool
    {
        // Staff members can create addresses for any customer
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('customers:addresses:create');
        }
        
        // Regular users can create their own addresses
        return true;
    }

    /**
     * Determine if the user can update the address.
     */
    public function update(User|Staff $user, Address $address): bool
    {
        // Staff members can update any address
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('customers:addresses:update');
        }
        
        // Regular users can only update their own addresses
        if ($user instanceof User) {
            $customer = CustomerHelper::getOrCreateCustomerForUser($user);
            return $address->customer_id === $customer->id;
        }
        
        return false;
    }

    /**
     * Determine if the user can delete the address.
     */
    public function delete(User|Staff $user, Address $address): bool
    {
        // Staff members can delete any address
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('customers:addresses:delete');
        }
        
        // Regular users can only delete their own addresses
        if ($user instanceof User) {
            $customer = CustomerHelper::getOrCreateCustomerForUser($user);
            return $address->customer_id === $customer->id;
        }
        
        return false;
    }

    /**
     * Determine if the user can set default shipping address.
     */
    public function setDefaultShipping(User|Staff $user, Address $address): bool
    {
        // Staff members can set default shipping for any customer
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('customers:addresses:update');
        }
        
        // Regular users can only set default shipping for their own addresses
        if ($user instanceof User) {
            $customer = CustomerHelper::getOrCreateCustomerForUser($user);
            return $address->customer_id === $customer->id;
        }
        
        return false;
    }

    /**
     * Determine if the user can set default billing address.
     */
    public function setDefaultBilling(User|Staff $user, Address $address): bool
    {
        // Staff members can set default billing for any customer
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('customers:addresses:update');
        }
        
        // Regular users can only set default billing for their own addresses
        if ($user instanceof User) {
            $customer = CustomerHelper::getOrCreateCustomerForUser($user);
            return $address->customer_id === $customer->id;
        }
        
        return false;
    }
}


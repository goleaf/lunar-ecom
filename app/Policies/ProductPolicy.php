<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Product;
use Lunar\Admin\Models\Staff;

class ProductPolicy
{
    /**
     * Determine if the user can view any products.
     */
    public function viewAny(User|Staff|null $user): bool
    {
        // Staff members can view products in admin panel
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('catalog:products:read');
        }
        
        // Regular users and guests can view published products
        return true;
    }

    /**
     * Determine if the user can view the product.
     */
    public function view(User|Staff|null $user, Product $product): bool
    {
        // Staff members can view any product
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('catalog:products:read');
        }
        
        // Regular users and guests can only view published products
        return $product->status === 'published';
    }

    /**
     * Determine if the user can create products.
     */
    public function create(User|Staff $user): bool
    {
        // Only staff members can create products
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('catalog:products:create');
        }
        
        return false;
    }

    /**
     * Determine if the user can update the product.
     */
    public function update(User|Staff $user, Product $product): bool
    {
        // Only staff members can update products
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('catalog:products:update');
        }
        
        return false;
    }

    /**
     * Determine if the user can delete the product.
     */
    public function delete(User|Staff $user, Product $product): bool
    {
        // Only staff members can delete products
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('catalog:products:delete');
        }
        
        return false;
    }

    /**
     * Determine if the user can restore the product.
     */
    public function restore(User|Staff $user, Product $product): bool
    {
        // Only staff members can restore products
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('catalog:products:restore');
        }
        
        return false;
    }

    /**
     * Determine if the user can permanently delete the product.
     */
    public function forceDelete(User|Staff $user, Product $product): bool
    {
        // Only admins can permanently delete products
        if ($user instanceof Staff) {
            return $user->hasRole('admin');
        }
        
        return false;
    }
}


<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ProductVariant;
use Lunar\Admin\Models\Staff;

class ProductVariantPolicy
{
    /**
     * Determine if the user can view any product variants.
     */
    public function viewAny(User|Staff|null $user): bool
    {
        // Staff members can view variants in admin panel
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('catalog:variants:read');
        }
        
        // Regular users and guests can view variants of published products
        return true;
    }

    /**
     * Determine if the user can view the product variant.
     */
    public function view(User|Staff|null $user, ProductVariant $variant): bool
    {
        // Staff members can view any variant
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('catalog:variants:read');
        }
        
        // Regular users and guests can only view variants of published products
        return $variant->product && $variant->product->isPublished();
    }

    /**
     * Determine if the user can create product variants.
     */
    public function create(User|Staff $user): bool
    {
        // Only staff members can create variants
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('catalog:variants:create');
        }
        
        return false;
    }

    /**
     * Determine if the user can update the product variant.
     */
    public function update(User|Staff $user, ProductVariant $variant): bool
    {
        // Only staff members can update variants
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('catalog:variants:update');
        }
        
        return false;
    }

    /**
     * Determine if the user can delete the product variant.
     */
    public function delete(User|Staff $user, ProductVariant $variant): bool
    {
        // Only staff members can delete variants
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('catalog:variants:delete');
        }
        
        return false;
    }

    /**
     * Determine if the user can restore the product variant.
     */
    public function restore(User|Staff $user, ProductVariant $variant): bool
    {
        // Only staff members can restore variants
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('catalog:variants:restore');
        }
        
        return false;
    }

    /**
     * Determine if the user can permanently delete the product variant.
     */
    public function forceDelete(User|Staff $user, ProductVariant $variant): bool
    {
        // Only admins can permanently delete variants
        if ($user instanceof Staff) {
            return $user->hasRole('admin');
        }
        
        return false;
    }
}

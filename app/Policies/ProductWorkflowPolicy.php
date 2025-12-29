<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Product;
use App\Models\ProductWorkflow;
use Lunar\Admin\Models\Staff;

/**
 * Policy for product workflow operations.
 */
class ProductWorkflowPolicy
{
    /**
     * Determine if user can submit product for review.
     *
     * @param  User  $user
     * @param  Product  $product
     * @return bool
     */
    public function submitForReview(User|Staff|null $user, Product $product): bool
    {
        if (!$user) {
            return false;
        }
        // Editors and above can submit
        return $user->hasRole(['editor', 'admin', 'super_admin']);
    }

    /**
     * Determine if user can approve product.
     *
     * @param  User  $user
     * @param  Product  $product
     * @return bool
     */
    public function approve(User|Staff|null $user, Product $product): bool
    {
        if (!$user) {
            return false;
        }
        // Only admins and managers can approve
        return $user->hasRole(['admin', 'manager', 'super_admin']);
    }

    /**
     * Determine if user can reject product.
     *
     * @param  User  $user
     * @param  Product  $product
     * @return bool
     */
    public function reject(User|Staff|null $user, Product $product): bool
    {
        if (!$user) {
            return false;
        }
        // Only admins and managers can reject
        return $user->hasRole(['admin', 'manager', 'super_admin']);
    }

    /**
     * Determine if user can publish product.
     *
     * @param  User  $user
     * @param  Product  $product
     * @return bool
     */
    public function publish(User|Staff|null $user, Product $product): bool
    {
        if (!$user) {
            return false;
        }
        // Editors and above can publish
        return $user->hasRole(['editor', 'admin', 'manager', 'super_admin']);
    }

    /**
     * Determine if user can edit product.
     *
     * @param  User  $user
     * @param  Product  $product
     * @return bool
     */
    public function edit(User|Staff|null $user, Product $product): bool
    {
        if (!$user) {
            return false;
        }
        // Check if user has edit permission
        if (!$user->hasPermissionTo('edit products')) {
            return false;
        }
        
        // Check if product is locked
        if ($product->is_locked) {
            // Only admins can edit locked products
            return $user->hasRole(['admin', 'super_admin']);
        }
        
        return true;
    }

    /**
     * Determine if user can delete product.
     *
     * @param  User  $user
     * @param  Product  $product
     * @return bool
     */
    public function delete(User|Staff|null $user, Product $product): bool
    {
        if (!$user) {
            return false;
        }
        // Only admins can delete
        return $user->hasRole(['admin', 'super_admin']);
    }

    /**
     * Determine if user can perform bulk actions.
     *
     * @param  User  $user
     * @return bool
     */
    public function bulkAction(User|Staff|null $user): bool
    {
        if (!$user) {
            return false;
        }
        // Editors and above can perform bulk actions
        return $user->hasRole(['editor', 'admin', 'manager', 'super_admin']);
    }
}


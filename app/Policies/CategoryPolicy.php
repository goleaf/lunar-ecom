<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Category;
use Lunar\Admin\Models\Staff;

class CategoryPolicy
{
    /**
     * Determine if the user can view any categories.
     */
    public function viewAny(User|Staff|null $user): bool
    {
        // Staff members can view categories in admin panel
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('catalog:categories:read');
        }
        
        // Regular users and guests can view active categories
        return true;
    }

    /**
     * Determine if the user can view the category.
     */
    public function view(User|Staff|null $user, Category $category): bool
    {
        // Staff members can view any category
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('catalog:categories:read');
        }
        
        // Regular users and guests can only view active categories
        return $category->is_active;
    }

    /**
     * Determine if the user can create categories.
     */
    public function create(User|Staff $user): bool
    {
        // Only staff members can create categories
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('catalog:categories:create');
        }
        
        return false;
    }

    /**
     * Determine if the user can update the category.
     */
    public function update(User|Staff $user, Category $category): bool
    {
        // Only staff members can update categories
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('catalog:categories:update');
        }
        
        return false;
    }

    /**
     * Determine if the user can delete the category.
     */
    public function delete(User|Staff $user, Category $category): bool
    {
        // Only staff members can delete categories
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('catalog:categories:delete');
        }
        
        return false;
    }

    /**
     * Determine if the user can restore the category.
     */
    public function restore(User|Staff $user, Category $category): bool
    {
        // Only staff members can restore categories
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('catalog:categories:restore');
        }
        
        return false;
    }

    /**
     * Determine if the user can permanently delete the category.
     */
    public function forceDelete(User|Staff $user, Category $category): bool
    {
        // Only admins can permanently delete categories
        if ($user instanceof Staff) {
            return $user->hasRole('admin');
        }
        
        return false;
    }
}


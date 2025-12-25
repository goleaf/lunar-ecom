<?php

namespace App\Policies;

use App\Models\User;
use Lunar\Admin\Models\Staff;

class UserPolicy
{
    /**
     * Determine if the user can view any users.
     */
    public function viewAny(User|Staff $user): bool
    {
        // Only staff members can view users list
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('customers:read');
        }
        
        return false;
    }

    /**
     * Determine if the user can view the user.
     */
    public function view(User|Staff $user, User $model): bool
    {
        // Staff members can view any user
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('customers:read');
        }
        
        // Regular users can only view their own profile
        return $user instanceof User && $user->id === $model->id;
    }

    /**
     * Determine if the user can create users.
     */
    public function create(User|Staff $user): bool
    {
        // Only staff members can create users
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('customers:create');
        }
        
        return false;
    }

    /**
     * Determine if the user can update the user.
     */
    public function update(User|Staff $user, User $model): bool
    {
        // Staff members can update any user
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('customers:update');
        }
        
        // Regular users can only update their own profile
        return $user instanceof User && $user->id === $model->id;
    }

    /**
     * Determine if the user can delete the user.
     */
    public function delete(User|Staff $user, User $model): bool
    {
        // Only staff members can delete users
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('customers:delete');
        }
        
        return false;
    }

    /**
     * Determine if the user can restore the user.
     */
    public function restore(User|Staff $user, User $model): bool
    {
        // Only staff members can restore users
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('customers:restore');
        }
        
        return false;
    }

    /**
     * Determine if the user can permanently delete the user.
     */
    public function forceDelete(User|Staff $user, User $model): bool
    {
        // Only admins can permanently delete users
        if ($user instanceof Staff) {
            return $user->hasRole('admin');
        }
        
        return false;
    }
}


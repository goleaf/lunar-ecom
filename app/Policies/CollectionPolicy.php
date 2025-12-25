<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Collection;
use Lunar\Admin\Models\Staff;

class CollectionPolicy
{
    /**
     * Determine if the user can view any collections.
     */
    public function viewAny(User|Staff|null $user): bool
    {
        // Staff members can view collections in admin panel
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('catalog:collections:read');
        }
        
        // Regular users and guests can view collections
        return true;
    }

    /**
     * Determine if the user can view the collection.
     */
    public function view(User|Staff|null $user, Collection $collection): bool
    {
        // Staff members can view any collection
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('catalog:collections:read');
        }
        
        // Regular users and guests can view collections
        return true;
    }

    /**
     * Determine if the user can create collections.
     */
    public function create(User|Staff $user): bool
    {
        // Only staff members can create collections
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('catalog:collections:create');
        }
        
        return false;
    }

    /**
     * Determine if the user can update the collection.
     */
    public function update(User|Staff $user, Collection $collection): bool
    {
        // Only staff members can update collections
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('catalog:collections:update');
        }
        
        return false;
    }

    /**
     * Determine if the user can delete the collection.
     */
    public function delete(User|Staff $user, Collection $collection): bool
    {
        // Only staff members can delete collections
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('catalog:collections:delete');
        }
        
        return false;
    }

    /**
     * Determine if the user can restore the collection.
     */
    public function restore(User|Staff $user, Collection $collection): bool
    {
        // Only staff members can restore collections
        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->hasPermissionTo('catalog:collections:restore');
        }
        
        return false;
    }

    /**
     * Determine if the user can permanently delete the collection.
     */
    public function forceDelete(User|Staff $user, Collection $collection): bool
    {
        // Only admins can permanently delete collections
        if ($user instanceof Staff) {
            return $user->hasRole('admin');
        }
        
        return false;
    }
}


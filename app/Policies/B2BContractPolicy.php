<?php

namespace App\Policies;

use App\Models\B2BContract;
use App\Models\User;
use Lunar\Admin\Models\Staff;

class B2BContractPolicy
{
    /**
     * Determine if the user can view any contracts.
     */
    public function viewAny(User|Staff|null $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->can('view_any_b2b_contract');
        }

        return $user->can('view_any_b2b_contract');
    }

    /**
     * Determine if the user can view the contract.
     */
    public function view(User|Staff|null $user, B2BContract $contract): bool
    {
        if (!$user) {
            return false;
        }

        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->can('view_b2b_contract');
        }

        return $user->can('view_b2b_contract');
    }

    /**
     * Determine if the user can create contracts.
     */
    public function create(User|Staff|null $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->can('create_b2b_contract');
        }

        return $user->can('create_b2b_contract');
    }

    /**
     * Determine if the user can update the contract.
     */
    public function update(User|Staff|null $user, B2BContract $contract): bool
    {
        if (!$user) {
            return false;
        }

        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->can('update_b2b_contract');
        }

        return $user->can('update_b2b_contract');
    }

    /**
     * Determine if the user can delete the contract.
     */
    public function delete(User|Staff|null $user, B2BContract $contract): bool
    {
        if (!$user) {
            return false;
        }

        if ($user instanceof Staff) {
            return $user->hasRole('admin') || $user->can('delete_b2b_contract');
        }

        return $user->can('delete_b2b_contract');
    }

    /**
     * Determine if the user can approve the contract.
     */
    public function approve(User|Staff|null $user, B2BContract $contract): bool
    {
        if (!$user) {
            return false;
        }

        $canApprove = $user instanceof Staff
            ? ($user->hasRole('admin') || $user->can('approve_b2b_contract'))
            : $user->can('approve_b2b_contract');

        return $canApprove
            && $contract->approval_state === B2BContract::APPROVAL_PENDING;
    }

    /**
     * Determine if the user can reject the contract.
     */
    public function reject(User|Staff|null $user, B2BContract $contract): bool
    {
        if (!$user) {
            return false;
        }

        $canReject = $user instanceof Staff
            ? ($user->hasRole('admin') || $user->can('reject_b2b_contract'))
            : $user->can('reject_b2b_contract');

        return $canReject
            && $contract->approval_state === B2BContract::APPROVAL_PENDING;
    }
}



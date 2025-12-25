<?php

namespace App\Policies;

use App\Models\B2BContract;
use App\Models\User;

class B2BContractPolicy
{
    /**
     * Determine if the user can view any contracts.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_b2b_contract');
    }

    /**
     * Determine if the user can view the contract.
     */
    public function view(User $user, B2BContract $contract): bool
    {
        return $user->can('view_b2b_contract');
    }

    /**
     * Determine if the user can create contracts.
     */
    public function create(User $user): bool
    {
        return $user->can('create_b2b_contract');
    }

    /**
     * Determine if the user can update the contract.
     */
    public function update(User $user, B2BContract $contract): bool
    {
        return $user->can('update_b2b_contract');
    }

    /**
     * Determine if the user can delete the contract.
     */
    public function delete(User $user, B2BContract $contract): bool
    {
        return $user->can('delete_b2b_contract');
    }

    /**
     * Determine if the user can approve the contract.
     */
    public function approve(User $user, B2BContract $contract): bool
    {
        return $user->can('approve_b2b_contract') 
            && $contract->approval_state === B2BContract::APPROVAL_PENDING;
    }

    /**
     * Determine if the user can reject the contract.
     */
    public function reject(User $user, B2BContract $contract): bool
    {
        return $user->can('reject_b2b_contract')
            && $contract->approval_state === B2BContract::APPROVAL_PENDING;
    }
}


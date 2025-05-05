<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContractPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user)
    {
        return $user->role === 'client';
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Contract $contract)
    {
        return $user->company_id === $contract->insurer_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user)
    {
        return $user->role === 'client';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Contract $contract)
    {
        return $user->company_id === $contract->insurer_id 
            && $contract->status === 'pending';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Contract $contract)
    {
        return $user->company_id === $contract->insurer_id 
            && $contract->status === 'pending';
    }
}
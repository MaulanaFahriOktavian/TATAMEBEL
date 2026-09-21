<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;
use App\Policies\Concerns\EnforcesWorkshopTenancy;

class CustomerPolicy
{
    use EnforcesWorkshopTenancy;

    /**
     * Determine whether the user can view any customers.
     */
    public function viewAny(User $user): bool
    {
        return $user->workshop_id !== null && $user->is_active;
    }

    /**
     * Determine whether the user can view the customer.
     */
    public function view(User $user, Customer $customer): bool
    {
        return $this->belongsToSameWorkshop($user, $customer);
    }

    /**
     * Determine whether the user can create customers.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole([UserRole::OWNER, UserRole::ADMIN]);
    }

    /**
     * Determine whether the user can update the customer.
     */
    public function update(User $user, Customer $customer): bool
    {
        return $user->hasAnyRole([UserRole::OWNER, UserRole::ADMIN])
            && $this->belongsToSameWorkshop($user, $customer);
    }

    /**
     * Determine whether the user can delete the customer.
     */
    public function delete(User $user, Customer $customer): bool
    {
        return $user->hasAnyRole([UserRole::OWNER, UserRole::ADMIN])
            && $this->belongsToSameWorkshop($user, $customer);
    }
}

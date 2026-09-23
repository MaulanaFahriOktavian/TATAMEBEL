<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Shipping;
use App\Models\User;
use App\Policies\Concerns\EnforcesWorkshopTenancy;

class ShippingPolicy
{
    use EnforcesWorkshopTenancy;

    /**
     * Determine whether the user can view the shipping record.
     */
    public function view(User $user, Shipping $shipping): bool
    {
        return $this->belongsToSameWorkshop($user, $shipping);
    }

    /**
     * Determine whether the user can create shipping for the order.
     */
    public function create(User $user, Order $order): bool
    {
        return $user->hasAnyRole([UserRole::OWNER, UserRole::ADMIN])
            && $this->belongsToSameWorkshop($user, $order);
    }

    /**
     * Determine whether the user can update the shipping record.
     */
    public function update(User $user, Shipping $shipping): bool
    {
        return $user->hasAnyRole([UserRole::OWNER, UserRole::ADMIN])
            && $this->belongsToSameWorkshop($user, $shipping);
    }
}

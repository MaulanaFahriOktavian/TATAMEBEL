<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use App\Policies\Concerns\EnforcesWorkshopTenancy;

class OrderPolicy
{
    use EnforcesWorkshopTenancy;

    /**
     * Determine whether the user can view any orders.
     */
    public function viewAny(User $user): bool
    {
        return $user->workshop_id !== null && $user->is_active;
    }

    /**
     * Determine whether the user can view the order.
     */
    public function view(User $user, Order $order): bool
    {
        return $this->belongsToSameWorkshop($user, $order);
    }

    /**
     * Determine whether the user can create orders.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole([UserRole::OWNER, UserRole::ADMIN]);
    }

    /**
     * Determine whether the user can change the order status.
     */
    public function changeStatus(User $user, Order $order): bool
    {
        return $user->hasAnyRole([UserRole::OWNER, UserRole::ADMIN])
            && $this->belongsToSameWorkshop($user, $order);
    }

    /**
     * Determine whether the user can generate WhatsApp share data for the order.
     */
    public function shareWhatsApp(User $user, Order $order): bool
    {
        return $user->hasAnyRole([UserRole::OWNER, UserRole::ADMIN])
            && $this->belongsToSameWorkshop($user, $order);
    }
}

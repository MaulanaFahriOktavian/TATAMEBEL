<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ProductionUpdate;
use App\Models\User;
use App\Policies\Concerns\EnforcesWorkshopTenancy;

class ProductionUpdatePolicy
{
    use EnforcesWorkshopTenancy;

    public function viewAny(User $user): bool
    {
        return $user->workshop_id !== null && $user->is_active;
    }

    public function view(User $user, ProductionUpdate $update): bool
    {
        return $this->belongsToSameWorkshop($user, $update);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([UserRole::OWNER, UserRole::ADMIN, UserRole::PRODUCTION]);
    }
}

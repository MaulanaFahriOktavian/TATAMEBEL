<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Specification;
use App\Models\User;
use App\Policies\Concerns\EnforcesWorkshopTenancy;

class SpecificationPolicy
{
    use EnforcesWorkshopTenancy;

    public function viewAny(User $user): bool
    {
        return $user->workshop_id !== null && $user->is_active;
    }

    public function view(User $user, Specification $specification): bool
    {
        return $this->belongsToSameWorkshop($user, $specification);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([UserRole::OWNER, UserRole::ADMIN]);
    }

    public function update(User $user, Specification $specification): bool
    {
        return $user->hasAnyRole([UserRole::OWNER, UserRole::ADMIN])
            && $this->belongsToSameWorkshop($user, $specification);
    }

    public function lock(User $user, Specification $specification): bool
    {
        return $user->hasAnyRole([UserRole::OWNER, UserRole::ADMIN])
            && $this->belongsToSameWorkshop($user, $specification);
    }
}

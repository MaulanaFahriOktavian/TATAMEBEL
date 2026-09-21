<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ProductionStage;
use App\Models\User;
use App\Policies\Concerns\EnforcesWorkshopTenancy;

class ProductionStagePolicy
{
    use EnforcesWorkshopTenancy;

    public function viewAny(User $user): bool
    {
        return $user->workshop_id !== null && $user->is_active;
    }

    public function view(User $user, ProductionStage $stage): bool
    {
        return $this->belongsToSameWorkshop($user, $stage);
    }

    public function create(User $user): bool
    {
        // Only Owner and Admin can initialize or add stages
        return $user->hasAnyRole([UserRole::OWNER, UserRole::ADMIN]);
    }

    public function updateStatus(User $user, ProductionStage $stage): bool
    {
        // Owner, Admin, and Production workers can update progress status
        return $user->hasAnyRole([UserRole::OWNER, UserRole::ADMIN, UserRole::PRODUCTION])
            && $this->belongsToSameWorkshop($user, $stage);
    }

    public function updateProperties(User $user, ProductionStage $stage): bool
    {
        // Reordering, renaming, or deactivating is restricted to Owner and Admin
        return $user->hasAnyRole([UserRole::OWNER, UserRole::ADMIN])
            && $this->belongsToSameWorkshop($user, $stage);
    }

    public function delete(User $user, ProductionStage $stage): bool
    {
        return $user->hasAnyRole([UserRole::OWNER, UserRole::ADMIN])
            && $this->belongsToSameWorkshop($user, $stage);
    }
}

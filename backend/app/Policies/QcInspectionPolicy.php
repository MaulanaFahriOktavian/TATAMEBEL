<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\QcInspection;
use App\Models\User;
use App\Policies\Concerns\EnforcesWorkshopTenancy;

class QcInspectionPolicy
{
    use EnforcesWorkshopTenancy;

    public function viewAny(User $user): bool
    {
        return $user->workshop_id !== null && $user->is_active;
    }

    public function view(User $user, QcInspection $inspection): bool
    {
        return $this->belongsToSameWorkshop($user, $inspection);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([UserRole::OWNER, UserRole::ADMIN, UserRole::QC]);
    }

    public function evaluate(User $user, QcInspection $inspection): bool
    {
        return $user->hasAnyRole([UserRole::OWNER, UserRole::ADMIN, UserRole::QC])
            && $this->belongsToSameWorkshop($user, $inspection);
    }

    public function finalize(User $user, QcInspection $inspection): bool
    {
        return $user->hasAnyRole([UserRole::OWNER, UserRole::ADMIN, UserRole::QC])
            && $this->belongsToSameWorkshop($user, $inspection);
    }
}

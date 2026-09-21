<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\QcDefect;
use App\Models\User;
use App\Policies\Concerns\EnforcesWorkshopTenancy;

class QcDefectPolicy
{
    use EnforcesWorkshopTenancy;

    public function viewAny(User $user): bool
    {
        return $user->workshop_id !== null && $user->is_active;
    }

    public function view(User $user, QcDefect $defect): bool
    {
        return $this->belongsToSameWorkshop($user, $defect);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([UserRole::OWNER, UserRole::ADMIN, UserRole::QC]);
    }

    public function updateStatus(User $user, QcDefect $defect): bool
    {
        return $user->hasAnyRole([UserRole::OWNER, UserRole::ADMIN, UserRole::PRODUCTION, UserRole::QC])
            && $this->belongsToSameWorkshop($user, $defect);
    }

    public function accept(User $user, QcDefect $defect): bool
    {
        return $user->hasAnyRole([UserRole::OWNER, UserRole::ADMIN])
            && $this->belongsToSameWorkshop($user, $defect);
    }

    public function delete(User $user, QcDefect $defect): bool
    {
        return $user->hasAnyRole([UserRole::OWNER, UserRole::ADMIN, UserRole::QC])
            && $this->belongsToSameWorkshop($user, $defect);
    }
}

<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ChangeRequest;
use App\Models\User;
use App\Policies\Concerns\EnforcesWorkshopTenancy;

class ChangeRequestPolicy
{
    use EnforcesWorkshopTenancy;

    public function viewAny(User $user): bool
    {
        return $user->workshop_id !== null && $user->is_active;
    }

    public function view(User $user, ChangeRequest $changeRequest): bool
    {
        return $this->belongsToSameWorkshop($user, $changeRequest);
    }

    public function create(User $user): bool
    {
        // Owner, Admin, and Production floor staff can submit change requests
        return $user->hasAnyRole([UserRole::OWNER, UserRole::ADMIN, UserRole::PRODUCTION]);
    }

    public function approve(User $user, ChangeRequest $changeRequest): bool
    {
        // Only Owner and Admin can approve change requests
        return $user->hasAnyRole([UserRole::OWNER, UserRole::ADMIN])
            && $this->belongsToSameWorkshop($user, $changeRequest);
    }

    public function reject(User $user, ChangeRequest $changeRequest): bool
    {
        // Only Owner and Admin can reject change requests
        return $user->hasAnyRole([UserRole::OWNER, UserRole::ADMIN])
            && $this->belongsToSameWorkshop($user, $changeRequest);
    }

    public function cancel(User $user, ChangeRequest $changeRequest): bool
    {
        if (! $this->belongsToSameWorkshop($user, $changeRequest)) {
            return false;
        }

        // Requester can cancel their own, or Owner/Admin can cancel
        return $user->hasAnyRole([UserRole::OWNER, UserRole::ADMIN])
            || ($changeRequest->user_id !== null && (int) $user->id === (int) $changeRequest->user_id);
    }
}

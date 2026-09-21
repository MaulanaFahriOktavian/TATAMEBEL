<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Media;
use App\Models\User;
use App\Policies\Concerns\EnforcesWorkshopTenancy;

class MediaPolicy
{
    use EnforcesWorkshopTenancy;

    public function viewAny(User $user): bool
    {
        return $user->workshop_id !== null && $user->is_active;
    }

    public function view(User $user, Media $media): bool
    {
        return $this->belongsToSameWorkshop($user, $media);
    }

    public function upload(User $user): bool
    {
        return $user->hasAnyRole([UserRole::OWNER, UserRole::ADMIN, UserRole::PRODUCTION]);
    }

    public function delete(User $user, Media $media): bool
    {
        if (! $this->belongsToSameWorkshop($user, $media)) {
            return false;
        }

        return $user->hasAnyRole([UserRole::OWNER, UserRole::ADMIN])
            || ((int) $user->id === (int) $media->uploaded_by);
    }
}

<?php

namespace App\Policies\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait EnforcesWorkshopTenancy
{
    /**
     * Determine whether the given user belongs to the same workshop as the resource.
     */
    public function belongsToSameWorkshop(User $user, Model $resource): bool
    {
        if (! $user->workshop_id || ! isset($resource->workshop_id) || ! $resource->workshop_id) {
            return false;
        }

        return (int) $user->workshop_id === (int) $resource->workshop_id;
    }
}

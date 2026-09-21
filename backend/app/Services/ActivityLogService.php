<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogService
{
    /**
     * Record an audit activity log entry.
     *
     * @param  array<string, mixed>|null  $metadata
     */
    public static function log(
        int $workshopId,
        string $action,
        Model $entity,
        string $description,
        ?User $user = null,
        ?int $orderId = null,
        ?array $metadata = null
    ): ActivityLog {
        return ActivityLog::create([
            'workshop_id' => $workshopId,
            'user_id' => $user?->id,
            'order_id' => $orderId,
            'action' => $action,
            'entity_type' => class_basename($entity),
            'entity_id' => $entity->getKey(),
            'description' => $description,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}

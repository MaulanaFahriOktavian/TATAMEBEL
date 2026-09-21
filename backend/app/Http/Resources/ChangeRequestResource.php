<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\ChangeRequest
 */
class ChangeRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'order_item_id' => $this->order_item_id,
            'product_name' => $this->orderItem?->product_name,
            'specification_id' => $this->specification_id,
            'current_version' => (int) $this->current_version,
            'requested_by' => $this->requested_by,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ] : null,
            'description' => $this->description,
            'reason' => $this->reason,
            'requested_changes' => $this->requested_changes,
            'status' => $this->status->value,
            'approver' => $this->approver ? [
                'id' => $this->approver->id,
                'name' => $this->approver->name,
            ] : null,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'review_note' => $this->review_note,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

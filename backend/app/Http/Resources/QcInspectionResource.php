<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\QcInspection
 */
class QcInspectionResource extends JsonResource
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
            'status' => $this->status->value,
            'notes' => $this->notes,
            'inspected_at' => $this->inspected_at?->toIso8601String(),
            'inspector' => $this->inspector ? [
                'id' => $this->inspector->id,
                'name' => $this->inspector->name,
                'role' => $this->inspector->role->value,
            ] : null,
            'items_count' => $this->qc_items_count ?? $this->qcItems->count(),
            'defects_count' => $this->qc_defects_count ?? $this->qcDefects->count(),
            'items' => QcItemResource::collection($this->whenLoaded('qcItems')),
            'defects' => QcDefectResource::collection($this->whenLoaded('qcDefects')),
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

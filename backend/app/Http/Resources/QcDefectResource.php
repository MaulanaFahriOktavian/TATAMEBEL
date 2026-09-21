<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\QcDefect
 */
class QcDefectResource extends JsonResource
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
            'qc_inspection_id' => $this->qc_inspection_id,
            'qc_item_id' => $this->qc_item_id,
            'description' => $this->description,
            'severity' => $this->severity->value,
            'status' => $this->status->value,
            'resolution' => $this->resolution,
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'qc_item' => $this->qcItem ? [
                'id' => $this->qcItem->id,
                'category' => $this->qcItem->category,
                'item' => $this->qcItem->item,
            ] : null,
            'media' => MediaResource::collection($this->whenLoaded('media')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

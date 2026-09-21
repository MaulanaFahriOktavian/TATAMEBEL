<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Specification
 */
class SpecificationResource extends JsonResource
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
            'order_item_id' => $this->order_item_id,
            // Decision 2: Product Identity sourced from OrderItem
            'product_name' => $this->orderItem?->product_name,
            'product_code' => $this->orderItem?->product_code,
            'quantity' => $this->orderItem ? (int) $this->orderItem->quantity : null,
            'version' => (int) $this->version,
            'width' => $this->width !== null ? (float) $this->width : null,
            'height' => $this->height !== null ? (float) $this->height : null,
            'depth' => $this->depth !== null ? (float) $this->depth : null,
            'dimension_unit' => $this->dimension_unit,
            'material' => $this->material,
            'wood_grade' => $this->wood_grade,
            'finishing' => $this->finishing,
            'color' => $this->color,
            'fabric' => $this->fabric,
            'design_reference' => $this->design_reference,
            'special_request' => $this->special_request,
            'production_note' => $this->production_note,
            'status' => $this->status->value,
            'locked_at' => $this->locked_at?->toIso8601String(),
            'locked_by' => $this->lockedBy ? [
                'id' => $this->lockedBy->id,
                'name' => $this->lockedBy->name,
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

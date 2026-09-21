<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\QcItem
 */
class QcItemResource extends JsonResource
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
            'order_item_id' => $this->order_item_id,
            'order_item' => $this->orderItem ? [
                'id' => $this->orderItem->id,
                'product_name' => $this->orderItem->product_name,
                'product_code' => $this->orderItem->product_code,
            ] : null,
            'category' => $this->category,
            'item' => $this->item,
            'status' => $this->status?->value,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

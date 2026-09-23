<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Staff-facing resource for shipping management.
 *
 * @mixin \App\Models\Shipping
 */
class ShippingResource extends JsonResource
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
            'courier' => $this->courier,
            'tracking_number' => $this->tracking_number,
            'shipping_address' => $this->shipping_address,
            'shipped_at' => $this->shipped_at?->toIso8601String(),
            'estimated_arrival' => $this->estimated_arrival?->format('Y-m-d'),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'status' => $this->status->value,
            'status_label' => $this->status->publicLabel(),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

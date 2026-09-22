<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Order
 */
class OrderResource extends JsonResource
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
            'order_number' => $this->order_number,
            'title' => $this->title,
            'status' => $this->status->value,
            'total_amount' => (float) $this->total_amount,
            'notes' => $this->notes,
            'public_token' => $this->public_token,
            'tracking_url' => $this->public_token ? rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')), '/').'/track/'.$this->public_token : null,
            'confirmed_at' => $this->confirmed_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'items' => OrderItemResource::collection($this->whenLoaded('orderItems')),
        ];
    }
}

<?php

namespace App\Http\Resources;

use App\Enums\ProductionStageStatus;
use App\Services\ProductionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Order
 */
class ProductionOverviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $productionService = app(ProductionService::class);
        $progress = $productionService->calculateProgress($this->resource);

        $totalActive = $this->productionStages->where('is_active', true)->count();
        $completedActive = $this->productionStages->where('is_active', true)->where('status', ProductionStageStatus::COMPLETED)->count();

        return [
            'order_id' => $this->id,
            'order_number' => $this->order_number,
            'order_title' => $this->title,
            'order_status' => $this->status->value,
            'progress_percentage' => $progress,
            'total_active_stages' => $totalActive,
            'completed_active_stages' => $completedActive,
            'stages' => ProductionStageResource::collection($this->productionStages->sortBy('sequence')->values()),
            'recent_updates' => ProductionUpdateResource::collection($this->productionUpdates->sortByDesc('id')->take(10)->values()),
        ];
    }
}

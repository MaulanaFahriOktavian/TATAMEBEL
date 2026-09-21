<?php

namespace App\Http\Resources;

use App\Enums\MediaVisibility;
use App\Enums\OrderStatus;
use App\Enums\ProductionStageStatus;
use App\Enums\QcInspectionStatus;
use App\Enums\SpecificationStatus;
use App\Services\ProductionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Public projection resource for customer tracking portal.
 *
 * Excludes all internal IDs, financial values, internal notes,
 * audit/activity logs, and internal-only media or QC defects.
 *
 * @mixin \App\Models\Order
 */
class CustomerPortalOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $productionService = app(ProductionService::class);

        // Resolve active stages
        $activeStages = $this->productionStages
            ->where('is_active', true)
            ->sortBy('sequence')
            ->values();

        // Current stage in progress (if any)
        $currentStage = $activeStages->firstWhere('status', ProductionStageStatus::IN_PROGRESS);

        // Quality control projection
        $qcProjection = $this->resolveQualityControl();

        // Customer visible photos
        $photos = $this->media
            ->where('visibility', MediaVisibility::CUSTOMER)
            ->sortByDesc('created_at')
            ->values()
            ->map(function ($media) {
                return [
                    'url' => Storage::disk('public')->url($media->file_path),
                    'caption' => $media->caption,
                    'uploaded_at' => $media->created_at?->toIso8601String(),
                ];
            });

        // Shipping projection
        $shippingProjection = null;
        if ($this->shipping) {
            $shippingProjection = [
                'courier' => $this->shipping->courier,
                'tracking_number' => $this->shipping->tracking_number,
                'status' => $this->shipping->status->value,
                'status_label' => $this->shipping->status->publicLabel(),
                'shipped_at' => $this->shipping->shipped_at?->toIso8601String(),
                'estimated_arrival' => $this->shipping->estimated_arrival?->format('Y-m-d'),
                'delivered_at' => $this->shipping->delivered_at?->toIso8601String(),
            ];
        }

        return [
            'order' => [
                'order_number' => $this->order_number,
                'title' => $this->title,
                'status' => $this->status->value,
                'status_label' => $this->status->publicLabel(),
                'created_at' => $this->created_at?->toIso8601String(),
                'confirmed_at' => $this->confirmed_at?->toIso8601String(),
                'customer_name' => $this->customer?->name,
                'workshop' => [
                    'name' => $this->workshop?->name,
                    'phone' => $this->workshop?->phone,
                    'address' => $this->workshop?->address,
                ],
            ],
            'items' => $this->orderItems->map(function ($item) {
                // Strictly resolve highest LOCKED specification
                $currentSpec = $item->currentSpecification();

                $specificationData = null;
                if ($currentSpec && $currentSpec->status === SpecificationStatus::LOCKED) {
                    $specificationData = [
                        'version' => (int) $currentSpec->version,
                        'dimensions' => [
                            'width' => $currentSpec->width,
                            'height' => $currentSpec->height,
                            'depth' => $currentSpec->depth,
                            'unit' => $currentSpec->dimension_unit,
                        ],
                        'material' => $currentSpec->material,
                        'wood_grade' => $currentSpec->wood_grade,
                        'finishing' => $currentSpec->finishing,
                        'color' => $currentSpec->color,
                        'fabric' => $currentSpec->fabric,
                        'design_reference' => $currentSpec->design_reference,
                        'special_request' => $currentSpec->special_request,
                    ];
                }

                return [
                    'product_name' => $item->product_name,
                    'product_code' => $item->product_code,
                    'quantity' => (int) $item->quantity,
                    'notes' => $item->notes,
                    'specification' => $specificationData,
                ];
            })->values(),
            'production' => [
                'progress_percentage' => $productionService->calculateProgress($this->resource),
                'current_stage' => $currentStage?->name,
                'stages' => $activeStages->map(function ($stage) {
                    return [
                        'sequence' => (int) $stage->sequence,
                        'name' => $stage->name,
                        'status' => $stage->status->value,
                        'status_label' => $stage->status->publicLabel(),
                        'started_at' => $stage->started_at?->toIso8601String(),
                        'completed_at' => $stage->completed_at?->toIso8601String(),
                    ];
                })->values(),
            ],
            'photos' => $photos,
            'quality_control' => $qcProjection,
            'shipping' => $shippingProjection,
        ];
    }

    /**
     * Resolve customer-safe quality control projection.
     *
     * @return array<string, mixed>
     */
    private function resolveQualityControl(): array
    {
        // Check if there is any inspection that passed
        $passedInspection = $this->qcInspections
            ->where('status', QcInspectionStatus::PASSED)
            ->sortByDesc('inspected_at')
            ->first();

        if ($passedInspection) {
            return [
                'status' => 'PASSED',
                'status_label' => 'Lolos Pengecekan Kualitas',
                'passed_at' => $passedInspection->inspected_at?->toIso8601String(),
                'note' => 'Mebel telah lolos pemeriksaan standar konstruksi, presisi, finishing, dan fungsionalitas.',
            ];
        }

        // If order is currently at QC status or active inspection exists
        if ($this->status === OrderStatus::QC || $this->qcInspections->isNotEmpty()) {
            return [
                'status' => 'IN_PROGRESS',
                'status_label' => 'Sedang dalam Pengecekan Kualitas',
                'passed_at' => null,
                'note' => 'Pesanan sedang dalam tahap evaluasi kualitas komprehensif.',
            ];
        }

        // Before QC stage
        return [
            'status' => 'PENDING',
            'status_label' => 'Menunggu Pengecekan Kualitas',
            'passed_at' => null,
            'note' => 'Pengecekan kualitas akan dilakukan setelah seluruh tahapan produksi selesai.',
        ];
    }
}

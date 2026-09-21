<?php

namespace App\Services;

use App\Enums\ProductionStageStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\ProductionStage;
use App\Models\ProductionUpdate;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductionService
{
    /**
     * Authoritative backend calculation for total order progress percentage.
     *
     * Formula per SDD:
     * (completed active stages / total active stages) * 100
     * Returns 0.00 if no active stages exist.
     */
    public function calculateProgress(Order $order): float
    {
        $totalActive = $order->productionStages()
            ->where('is_active', true)
            ->count();

        if ($totalActive === 0) {
            return 0.00;
        }

        $completedActive = $order->productionStages()
            ->where('is_active', true)
            ->where('status', ProductionStageStatus::COMPLETED)
            ->count();

        return round(($completedActive / $totalActive) * 100, 2);
    }

    /**
     * Instantiate the 8 default production stages for an order if not already present.
     *
     * @return Collection<int, ProductionStage>
     */
    public function initializeDefaultStages(Order $order, ?User $actor = null): Collection
    {
        if ($order->productionStages()->exists()) {
            return $order->productionStages()->orderBy('sequence')->get();
        }

        $defaultStages = config('production.default_stages', []);

        return DB::transaction(function () use ($order, $defaultStages, $actor) {
            $createdStages = collect();

            foreach ($defaultStages as $stageConfig) {
                $stage = ProductionStage::create([
                    'workshop_id' => $order->workshop_id,
                    'order_id' => $order->id,
                    'name' => $stageConfig['name'],
                    'sequence' => $stageConfig['sequence'],
                    'status' => ProductionStageStatus::PENDING,
                    'is_active' => true,
                ]);

                $createdStages->push($stage);
            }

            ActivityLogService::log(
                workshopId: $order->workshop_id,
                action: 'PRODUCTION_STAGE_CREATED',
                entity: $order,
                description: "8 tahapan produksi standar diinisialisasi untuk pesanan {$order->order_number}.",
                user: $actor,
                orderId: $order->id,
                metadata: [
                    'stages_count' => $createdStages->count(),
                ]
            );

            return $createdStages;
        });
    }

    /**
     * Add a custom production stage to an order (Restricted to OWNER / ADMIN).
     *
     * @param  array<string, mixed>  $data
     */
    public function addStage(Order $order, array $data, ?User $actor = null): ProductionStage
    {
        $maxSequence = (int) ($order->productionStages()->max('sequence') ?? 0);
        $sequence = isset($data['sequence']) ? (int) $data['sequence'] : $maxSequence + 1;

        $stage = ProductionStage::create([
            'workshop_id' => $order->workshop_id,
            'order_id' => $order->id,
            'name' => $data['name'],
            'sequence' => $sequence,
            'status' => ProductionStageStatus::PENDING,
            'is_active' => $data['is_active'] ?? true,
        ]);

        ActivityLogService::log(
            workshopId: $order->workshop_id,
            action: 'PRODUCTION_STAGE_CREATED',
            entity: $stage,
            description: "Tahapan produksi custom '{$stage->name}' ditambahkan pada pesanan {$order->order_number}.",
            user: $actor,
            orderId: $order->id,
            metadata: [
                'stage_id' => $stage->id,
                'name' => $stage->name,
                'sequence' => $stage->sequence,
            ]
        );

        return $stage;
    }

    /**
     * Update stage operational status (e.g. PENDING -> IN_PROGRESS -> COMPLETED).
     *
     * @throws ValidationException
     */
    public function updateStageStatus(ProductionStage $stage, ProductionStageStatus $newStatus, ?User $actor = null): ProductionStage
    {
        // Clarification 3: PRODUCTION cannot complete QC stage via ordinary production tracking.
        // QC stage is the handoff point for the Phase 5 QC inspection module.
        if (strtoupper(trim($stage->name)) === 'QC' && $newStatus === ProductionStageStatus::COMPLETED) {
            throw ValidationException::withMessages([
                'status' => ['Tahapan QC tidak dapat diselesaikan melalui production tracking biasa. Penyelesaian QC memerlukan modul QC Inspection (Phase 5).'],
            ]);
        }

        $updates = ['status' => $newStatus];

        if ($newStatus === ProductionStageStatus::IN_PROGRESS && is_null($stage->started_at)) {
            $updates['started_at'] = now();
        } elseif ($newStatus === ProductionStageStatus::COMPLETED && is_null($stage->completed_at)) {
            $updates['completed_at'] = now();
        }

        $stage->update($updates);

        ActivityLogService::log(
            workshopId: $stage->workshop_id,
            action: 'PRODUCTION_STAGE_UPDATED',
            entity: $stage,
            description: "Status tahapan '{$stage->name}' diubah menjadi {$newStatus->value}.",
            user: $actor,
            orderId: $stage->order_id,
            metadata: [
                'stage_id' => $stage->id,
                'name' => $stage->name,
                'status' => $newStatus->value,
                'started_at' => $stage->started_at?->toIso8601String(),
                'completed_at' => $stage->completed_at?->toIso8601String(),
            ]
        );

        return $stage;
    }

    /**
     * Update stage properties like name, sequence, or active status (Restricted to OWNER / ADMIN).
     *
     * @param  array<string, mixed>  $data
     */
    public function updateStageProperties(ProductionStage $stage, array $data, ?User $actor = null): ProductionStage
    {
        $stage->update(array_filter([
            'name' => $data['name'] ?? $stage->name,
            'sequence' => isset($data['sequence']) ? (int) $data['sequence'] : $stage->sequence,
            'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : $stage->is_active,
        ], fn ($val) => $val !== null));

        return $stage;
    }

    /**
     * Create a production progress update with snapshot and optional photo evidence.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, \Illuminate\Http\UploadedFile>  $mediaFiles
     */
    public function createUpdate(
        ProductionStage $stage,
        array $data,
        ?User $actor = null,
        array $mediaFiles = []
    ): ProductionUpdate {
        return DB::transaction(function () use ($stage, $data, $actor, $mediaFiles) {
            // Calculate current order progress snapshot
            $progressSnapshot = $this->calculateProgress($stage->order);

            $update = ProductionUpdate::create([
                'workshop_id' => $stage->workshop_id,
                'order_id' => $stage->order_id,
                'production_stage_id' => $stage->id,
                'user_id' => $actor->id,
                'description' => $data['description'],
                'progress_snapshot' => $progressSnapshot,
            ]);

            // If media files attached, save via MediaService
            if (! empty($mediaFiles)) {
                $mediaService = app(MediaService::class);
                foreach ($mediaFiles as $file) {
                    $mediaService->upload(
                        order: $stage->order,
                        file: $file,
                        data: [
                            'visibility' => $data['media_visibility'] ?? \App\Enums\MediaVisibility::INTERNAL->value,
                            'caption' => $data['media_caption'] ?? null,
                        ],
                        actor: $actor,
                        update: $update
                    );
                }
            }

            ActivityLogService::log(
                workshopId: $stage->workshop_id,
                action: 'PRODUCTION_UPDATE_CREATED',
                entity: $update,
                description: "Update pengerjaan dicatat pada tahapan '{$stage->name}'.",
                user: $actor,
                orderId: $stage->order_id,
                metadata: [
                    'stage_id' => $stage->id,
                    'stage_name' => $stage->name,
                    'progress_snapshot' => $progressSnapshot,
                    'media_count' => count($mediaFiles),
                ]
            );

            return $update->load(['media', 'user', 'productionStage']);
        });
    }

    /**
     * Delete a production stage (Restricted to OWNER / ADMIN).
     *
     * @throws ValidationException
     */
    public function deleteStage(ProductionStage $stage, ?User $actor = null): bool
    {
        if ($stage->productionUpdates()->exists()) {
            throw ValidationException::withMessages([
                'stage' => ['Tahapan produksi tidak dapat dihapus karena sudah memiliki riwayat catatan pengerjaan.'],
            ]);
        }

        $stage->delete();

        return true;
    }
}

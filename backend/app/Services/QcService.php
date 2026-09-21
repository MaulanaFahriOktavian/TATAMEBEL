<?php

namespace App\Services;

use App\Enums\QcDefectStatus;
use App\Enums\QcInspectionStatus;
use App\Enums\QcItemStatus;
use App\Models\Order;
use App\Models\QcInspection;
use App\Models\QcItem;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QcService
{
    /**
     * List all QC inspections for an order.
     */
    public function list(Order $order, int $perPage = 15): LengthAwarePaginator
    {
        return $order->qcInspections()
            ->with(['inspector', 'qcItems.orderItem', 'qcDefects.media', 'media'])
            ->withCount(['qcItems', 'qcDefects'])
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * Retrieve single QC inspection ensuring workshop isolation.
     */
    public function show(Workshop $workshop, int $id): QcInspection
    {
        return QcInspection::where('workshop_id', $workshop->id)
            ->with(['inspector', 'qcItems.orderItem', 'qcDefects.media', 'media.uploader', 'order'])
            ->withCount(['qcItems', 'qcDefects'])
            ->findOrFail($id);
    }

    /**
     * Create a new QC inspection with default checklist template.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(Order $order, array $data, User $actor): QcInspection
    {
        return DB::transaction(function () use ($order, $data, $actor) {
            $inspection = QcInspection::create([
                'workshop_id' => $order->workshop_id,
                'order_id' => $order->id,
                'inspected_by' => $actor->id,
                'status' => QcInspectionStatus::PENDING,
                'notes' => $data['notes'] ?? null,
                'inspected_at' => null,
            ]);

            // Ensure QC production stage transitions from PENDING to IN_PROGRESS
            $qcStage = $order->productionStages()
                ->whereRaw("UPPER(TRIM(name)) = 'QC'")
                ->where('status', \App\Enums\ProductionStageStatus::PENDING)
                ->first();

            if ($qcStage) {
                $qcStage->update([
                    'status' => \App\Enums\ProductionStageStatus::IN_PROGRESS,
                    'started_at' => now(),
                ]);
            }

            // Instantiate standard checklist template (status starts as null: unevaluated)
            $template = config('qc.default_checklist', []);
            $orderItemId = $data['order_item_id'] ?? null;

            foreach ($template as $itemConfig) {
                QcItem::create([
                    'workshop_id' => $order->workshop_id,
                    'qc_inspection_id' => $inspection->id,
                    'order_item_id' => $orderItemId,
                    'category' => $itemConfig['category'],
                    'item' => $itemConfig['item'],
                    'status' => null,
                    'notes' => null,
                ]);
            }

            // Optional custom checklist items
            if (! empty($data['custom_items']) && is_array($data['custom_items'])) {
                foreach ($data['custom_items'] as $customItem) {
                    QcItem::create([
                        'workshop_id' => $order->workshop_id,
                        'qc_inspection_id' => $inspection->id,
                        'order_item_id' => $customItem['order_item_id'] ?? $orderItemId,
                        'category' => $customItem['category'] ?? 'custom',
                        'item' => $customItem['item'],
                        'status' => null,
                        'notes' => $customItem['notes'] ?? null,
                    ]);
                }
            }

            ActivityLogService::log(
                workshopId: $order->workshop_id,
                action: 'QC_INSPECTION_CREATED',
                entity: $inspection,
                description: "Sesi inspeksi QC baru dibuat untuk pesanan {$order->order_number}.",
                user: $actor,
                orderId: $order->id,
                metadata: [
                    'inspection_id' => $inspection->id,
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'template_items_count' => count($template),
                ]
            );

            return $inspection->load(['inspector', 'qcItems.orderItem']);
        });
    }

    /**
     * Evaluate batch of checklist items while inspection is still PENDING.
     *
     * @param  array<int, array<string, mixed>>  $itemsData
     *
     * @throws ValidationException
     */
    public function evaluateItems(QcInspection $inspection, array $itemsData, User $actor): QcInspection
    {
        if ($inspection->status !== QcInspectionStatus::PENDING) {
            throw ValidationException::withMessages([
                'inspection' => ['Inspeksi QC yang sudah difinalisasi bersifat permanen (immutable) dan tidak dapat diubah.'],
            ]);
        }

        return DB::transaction(function () use ($inspection, $itemsData, $actor) {
            $updatedCount = 0;

            foreach ($itemsData as $itemData) {
                $qcItem = $inspection->qcItems()->where('id', $itemData['id'])->first();

                if (! $qcItem) {
                    continue;
                }

                $status = isset($itemData['status']) && $itemData['status'] !== null
                    ? QcItemStatus::from($itemData['status'])
                    : null;

                $qcItem->update([
                    'status' => $status,
                    'notes' => $itemData['notes'] ?? $qcItem->notes,
                    'order_item_id' => array_key_exists('order_item_id', $itemData)
                        ? $itemData['order_item_id']
                        : $qcItem->order_item_id,
                ]);

                $updatedCount++;
            }

            ActivityLogService::log(
                workshopId: $inspection->workshop_id,
                action: 'QC_ITEMS_EVALUATED',
                entity: $inspection,
                description: "{$updatedCount} butir checklist QC dinilai pada pesanan {$inspection->order->order_number}.",
                user: $actor,
                orderId: $inspection->order_id,
                metadata: [
                    'inspection_id' => $inspection->id,
                    'evaluated_items_count' => $updatedCount,
                ]
            );

            return $inspection->load(['qcItems.orderItem', 'inspector']);
        });
    }

    /**
     * Finalize QC inspection into PASSED, REWORK, or FAILED.
     * Enforces immutability, zero unresolved defects, and completes QC production stage if PASSED.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function finalize(QcInspection $inspection, array $data, User $actor): QcInspection
    {
        if ($inspection->status !== QcInspectionStatus::PENDING) {
            throw ValidationException::withMessages([
                'inspection' => ['Inspeksi QC yang sudah difinalisasi bersifat permanen (immutable) dan tidak dapat diubah.'],
            ]);
        }

        $targetStatus = QcInspectionStatus::from($data['status']);

        return DB::transaction(function () use ($inspection, $targetStatus, $data, $actor) {
            if ($targetStatus === QcInspectionStatus::PASSED) {
                // 1. All checklist items must be evaluated
                $unevaluatedCount = $inspection->qcItems()->whereNull('status')->count();
                if ($unevaluatedCount > 0) {
                    throw ValidationException::withMessages([
                        'items' => ["Terdapat {$unevaluatedCount} butir checklist yang belum dinilai. Seluruh butir checklist wajib dinilai (PASS atau NA) sebelum inspeksi dapat dinyatakan LULUS (PASSED)."],
                    ]);
                }

                // 2. No failing checklist items
                $failingCount = $inspection->qcItems()->where('status', QcItemStatus::FAIL)->count();
                if ($failingCount > 0) {
                    throw ValidationException::withMessages([
                        'items' => ["Inspeksi tidak dapat dinyatakan LULUS (PASSED) karena masih terdapat {$failingCount} butir checklist yang GAGAL (FAIL)."],
                    ]);
                }

                // 3. No unresolved defects on this inspection
                $unresolvedDefects = $inspection->qcDefects()
                    ->whereIn('status', [QcDefectStatus::OPEN, QcDefectStatus::IN_REWORK])
                    ->count();

                if ($unresolvedDefects > 0) {
                    throw ValidationException::withMessages([
                        'defects' => ["Inspeksi tidak dapat dinyatakan LULUS (PASSED) karena masih terdapat {$unresolvedDefects} temuan cacat yang belum diselesaikan (status OPEN atau IN_REWORK)."],
                    ]);
                }

                // 4. Synchronize QC production stage
                app(ProductionService::class)->completeQcStageFromInspection($inspection->order, $actor);
            } elseif ($targetStatus === QcInspectionStatus::REWORK) {
                // Must have at least one failing item OR at least one defect logged
                $hasFailures = $inspection->qcItems()->where('status', QcItemStatus::FAIL)->exists()
                    || $inspection->qcDefects()->exists();

                if (! $hasFailures) {
                    throw ValidationException::withMessages([
                        'status' => ['Inspeksi hanya dapat berstatus REWORK jika terdapat butir checklist yang GAGAL (FAIL) atau temuan cacat yang perlu diperbaiki.'],
                    ]);
                }
            } elseif ($targetStatus === QcInspectionStatus::FAILED) {
                if (empty($data['notes'])) {
                    throw ValidationException::withMessages([
                        'notes' => ['Catatan penjelasan alasan kegagalan inspeksi wajib diisi jika status inspeksi FAILED.'],
                    ]);
                }
            }

            $inspection->update([
                'status' => $targetStatus,
                'notes' => $data['notes'] ?? $inspection->notes,
                'inspected_at' => now(),
            ]);

            ActivityLogService::log(
                workshopId: $inspection->workshop_id,
                action: 'QC_INSPECTION_FINALIZED',
                entity: $inspection,
                description: "Inspeksi QC untuk pesanan {$inspection->order->order_number} difinalisasi dengan hasil {$targetStatus->value}.",
                user: $actor,
                orderId: $inspection->order_id,
                metadata: [
                    'inspection_id' => $inspection->id,
                    'status' => $targetStatus->value,
                    'inspected_at' => $inspection->inspected_at?->toIso8601String(),
                ]
            );

            return $inspection->load(['qcItems.orderItem', 'qcDefects.media', 'inspector', 'media']);
        });
    }
}

<?php

namespace App\Services;

use App\Enums\QcDefectSeverity;
use App\Enums\QcDefectStatus;
use App\Enums\QcInspectionStatus;
use App\Enums\UserRole;
use App\Models\QcDefect;
use App\Models\QcInspection;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DefectService
{
    /**
     * Log a new defect discovery during a PENDING inspection.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function create(QcInspection $inspection, array $data, User $actor): QcDefect
    {
        if ($inspection->status !== QcInspectionStatus::PENDING) {
            throw ValidationException::withMessages([
                'inspection' => ['Temuan cacat (defect) hanya dapat dicatat saat inspeksi masih dalam status DRAFT/PENDING.'],
            ]);
        }

        // Verify optional qc_item_id belongs to this inspection
        $qcItemId = $data['qc_item_id'] ?? null;
        if ($qcItemId) {
            $itemExists = $inspection->qcItems()->where('id', $qcItemId)->exists();
            if (! $itemExists) {
                throw ValidationException::withMessages([
                    'qc_item_id' => ['Poin checklist QC tidak valid atau tidak terkait dengan sesi inspeksi ini.'],
                ]);
            }
        }

        return DB::transaction(function () use ($inspection, $data, $qcItemId, $actor) {
            $severity = QcDefectSeverity::from($data['severity']);

            $defect = QcDefect::create([
                'workshop_id' => $inspection->workshop_id,
                'qc_inspection_id' => $inspection->id,
                'qc_item_id' => $qcItemId,
                'description' => $data['description'],
                'severity' => $severity,
                'status' => QcDefectStatus::OPEN,
                'resolution' => null,
                'resolved_at' => null,
            ]);

            ActivityLogService::log(
                workshopId: $inspection->workshop_id,
                action: 'QC_DEFECT_CREATED',
                entity: $defect,
                description: "Temuan cacat [{$severity->value}] dicatat pada pesanan {$inspection->order->order_number}: {$defect->description}",
                user: $actor,
                orderId: $inspection->order_id,
                metadata: [
                    'defect_id' => $defect->id,
                    'inspection_id' => $inspection->id,
                    'severity' => $severity->value,
                    'description' => $defect->description,
                ]
            );

            return $defect->load(['qcItem', 'media']);
        });
    }

    /**
     * Update defect lifecycle status (IN_REWORK, RESOLVED, or ACCEPTED).
     * Enforces role authority, resolution note requirements, and ACCEPTED finality.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function updateStatus(QcDefect $defect, array $data, User $actor): QcDefect
    {
        // ACCEPTED defects cannot be transitioned again
        if ($defect->status === QcDefectStatus::ACCEPTED) {
            throw ValidationException::withMessages([
                'status' => ['Defek yang sudah berstatus ACCEPTED bersifat final dan tidak dapat diubah statusnya lagi.'],
            ]);
        }

        $targetStatus = QcDefectStatus::from($data['status']);

        // Only OWNER or ADMIN can accept defects as concession/waiver
        if ($targetStatus === QcDefectStatus::ACCEPTED) {
            if (! $actor->hasAnyRole([UserRole::OWNER, UserRole::ADMIN])) {
                abort(403, 'Hanya OWNER atau ADMIN yang memiliki wewenang untuk menyetujui toleransi/waiver cacat (ACCEPTED).');
            }

            if (empty($data['resolution'])) {
                throw ValidationException::withMessages([
                    'resolution' => ['Catatan justifikasi/alasan persetujuan toleransi (resolution) wajib diisi saat menandai defek sebagai ACCEPTED.'],
                ]);
            }
        } elseif ($targetStatus === QcDefectStatus::RESOLVED) {
            if (empty($data['resolution'])) {
                throw ValidationException::withMessages([
                    'resolution' => ['Catatan tindakan perbaikan fisik (resolution) wajib diisi saat menyelesaikan defek (RESOLVED).'],
                ]);
            }
        }

        return DB::transaction(function () use ($defect, $targetStatus, $data, $actor) {
            $updates = ['status' => $targetStatus];

            if ($targetStatus === QcDefectStatus::RESOLVED || $targetStatus === QcDefectStatus::ACCEPTED) {
                $updates['resolution'] = $data['resolution'];
                $updates['resolved_at'] = now();
            }

            $defect->update($updates);

            ActivityLogService::log(
                workshopId: $defect->workshop_id,
                action: 'QC_DEFECT_UPDATED',
                entity: $defect,
                description: "Status temuan cacat diubah menjadi {$targetStatus->value} pada pesanan {$defect->qcInspection->order->order_number}.",
                user: $actor,
                orderId: $defect->qcInspection->order_id,
                metadata: [
                    'defect_id' => $defect->id,
                    'status' => $targetStatus->value,
                    'resolution' => $defect->resolution,
                    'resolved_at' => $defect->resolved_at?->toIso8601String(),
                ]
            );

            return $defect->load(['qcItem', 'media', 'qcInspection']);
        });
    }

    /**
     * Delete a defect record (only allowed while inspection is PENDING).
     *
     * @throws ValidationException
     */
    public function delete(QcDefect $defect, User $actor): bool
    {
        if ($defect->qcInspection->status !== QcInspectionStatus::PENDING) {
            throw ValidationException::withMessages([
                'defect' => ['Defek pada inspeksi yang sudah difinalisasi tidak dapat dihapus.'],
            ]);
        }

        return DB::transaction(function () use ($defect, $actor) {
            // Delete associated media
            foreach ($defect->media as $media) {
                app(MediaService::class)->delete($media, $actor);
            }

            $defectId = $defect->id;
            $orderId = $defect->qcInspection->order_id;
            $workshopId = $defect->workshop_id;

            ActivityLogService::log(
                workshopId: $workshopId,
                action: 'QC_DEFECT_DELETED',
                entity: $defect,
                description: "Temuan cacat #{$defectId} dihapus dari sesi inspeksi draft.",
                user: $actor,
                orderId: $orderId,
                metadata: [
                    'defect_id' => $defectId,
                ]
            );

            $defect->delete();

            return true;
        });
    }
}

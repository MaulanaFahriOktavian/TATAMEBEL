<?php

namespace App\Http\Controllers\Api;

use App\Enums\MediaVisibility;
use App\Enums\QcInspectionStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Qc\StoreQcDefectRequest;
use App\Http\Requests\Qc\UpdateQcDefectStatusRequest;
use App\Http\Resources\MediaResource;
use App\Http\Resources\QcDefectResource;
use App\Models\QcDefect;
use App\Models\QcInspection;
use App\Services\DefectService;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QcDefectController extends Controller
{
    public function __construct(
        private readonly DefectService $defectService,
        private readonly MediaService $mediaService
    ) {}

    /**
     * Log a new defect for a PENDING inspection.
     */
    public function store(StoreQcDefectRequest $request, int $inspectionId): JsonResponse
    {
        Gate::authorize('create', QcDefect::class);

        $inspection = QcInspection::where('id', $inspectionId)
            ->where('workshop_id', $request->user()->workshop_id)
            ->first();

        if (! $inspection) {
            return response()->json([
                'success' => false,
                'message' => 'QC inspection not found.',
            ], 404);
        }

        $defect = $this->defectService->create($inspection, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'QC defect logged successfully.',
            'data' => new QcDefectResource($defect),
        ], 201);
    }

    /**
     * Update defect lifecycle status (IN_REWORK, RESOLVED, or ACCEPTED).
     */
    public function updateStatus(UpdateQcDefectStatusRequest $request, int $id): JsonResponse
    {
        $defect = QcDefect::where('id', $id)
            ->where('workshop_id', $request->user()->workshop_id)
            ->first();

        if (! $defect) {
            return response()->json([
                'success' => false,
                'message' => 'QC defect not found.',
            ], 404);
        }

        Gate::authorize('updateStatus', $defect);

        $targetStatus = $request->validated('status');
        if ($targetStatus === 'ACCEPTED') {
            Gate::authorize('accept', $defect);
        }

        $updated = $this->defectService->updateStatus($defect, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => "Defect status updated to {$updated->status->value}.",
            'data' => new QcDefectResource($updated),
        ], 200);
    }

    /**
     * Upload photo evidence specifically for a defect.
     */
    public function uploadMedia(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'visibility' => ['nullable', 'string', 'in:INTERNAL,CUSTOMER'],
            'caption' => ['nullable', 'string', 'max:255'],
        ]);

        $defect = QcDefect::where('id', $id)
            ->where('workshop_id', $request->user()->workshop_id)
            ->first();

        if (! $defect) {
            return response()->json([
                'success' => false,
                'message' => 'QC defect not found.',
            ], 404);
        }

        Gate::authorize('updateStatus', $defect);

        if ($defect->qcInspection->status !== QcInspectionStatus::PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'The given data was invalid.',
                'errors' => [
                    'defect' => ['Inspeksi QC yang sudah difinalisasi bersifat permanen (immutable) dan tidak dapat ditambahkan foto bukti defek.'],
                ],
            ], 422);
        }

        $visibility = $request->input('visibility', MediaVisibility::INTERNAL->value);

        $media = $this->mediaService->upload(
            order: $defect->qcInspection->order,
            file: $request->file('file'),
            data: [
                'visibility' => $visibility,
                'caption' => $request->input('caption'),
                'qc_inspection_id' => $defect->qc_inspection_id,
                'qc_defect_id' => $defect->id,
            ],
            actor: $request->user(),
            qcInspection: $defect->qcInspection,
            qcDefect: $defect
        );

        return response()->json([
            'success' => true,
            'message' => 'Defect photo uploaded successfully.',
            'data' => new MediaResource($media->load('uploader')),
        ], 201);
    }

    /**
     * Delete a defect from a draft inspection.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $defect = QcDefect::where('id', $id)
            ->where('workshop_id', $request->user()->workshop_id)
            ->first();

        if (! $defect) {
            return response()->json([
                'success' => false,
                'message' => 'QC defect not found.',
            ], 404);
        }

        Gate::authorize('delete', $defect);

        $this->defectService->delete($defect, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'QC defect deleted successfully.',
        ], 200);
    }
}

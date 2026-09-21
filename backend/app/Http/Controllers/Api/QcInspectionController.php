<?php

namespace App\Http\Controllers\Api;

use App\Enums\MediaVisibility;
use App\Enums\QcInspectionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Qc\EvaluateQcItemsRequest;
use App\Http\Requests\Qc\FinalizeQcInspectionRequest;
use App\Http\Requests\Qc\StoreQcInspectionRequest;
use App\Http\Resources\MediaResource;
use App\Http\Resources\QcInspectionResource;
use App\Models\Order;
use App\Models\QcInspection;
use App\Services\MediaService;
use App\Services\QcService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class QcInspectionController extends Controller
{
    public function __construct(
        private readonly QcService $qcService,
        private readonly MediaService $mediaService
    ) {}

    /**
     * List all QC inspections for an order.
     */
    public function index(Request $request, int $orderId): JsonResponse
    {
        Gate::authorize('viewAny', QcInspection::class);

        $order = $request->user()->workshop->orders()->whereKey($orderId)->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        $inspections = $this->qcService->list($order);

        return response()->json([
            'success' => true,
            'message' => 'QC inspections retrieved successfully.',
            'data' => QcInspectionResource::collection($inspections),
            'meta' => [
                'current_page' => $inspections->currentPage(),
                'last_page' => $inspections->lastPage(),
                'per_page' => $inspections->perPage(),
                'total' => $inspections->total(),
            ],
        ], 200);
    }

    /**
     * Create a new QC inspection with default checklist template.
     */
    public function store(StoreQcInspectionRequest $request, int $orderId): JsonResponse
    {
        Gate::authorize('create', QcInspection::class);

        $order = $request->user()->workshop->orders()->whereKey($orderId)->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        $inspection = $this->qcService->create($order, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'QC inspection created successfully.',
            'data' => new QcInspectionResource($inspection),
        ], 201);
    }

    /**
     * Retrieve single QC inspection detail.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $inspection = QcInspection::where('id', $id)
            ->where('workshop_id', $request->user()->workshop_id)
            ->first();

        if (! $inspection) {
            return response()->json([
                'success' => false,
                'message' => 'QC inspection not found.',
            ], 404);
        }

        Gate::authorize('view', $inspection);

        $detailed = $this->qcService->show($request->user()->workshop, $id);

        return response()->json([
            'success' => true,
            'message' => 'QC inspection retrieved successfully.',
            'data' => new QcInspectionResource($detailed),
        ], 200);
    }

    /**
     * Evaluate batch of checklist items for a PENDING inspection.
     */
    public function evaluateItems(EvaluateQcItemsRequest $request, int $id): JsonResponse
    {
        $inspection = QcInspection::where('id', $id)
            ->where('workshop_id', $request->user()->workshop_id)
            ->first();

        if (! $inspection) {
            return response()->json([
                'success' => false,
                'message' => 'QC inspection not found.',
            ], 404);
        }

        Gate::authorize('evaluate', $inspection);

        $updated = $this->qcService->evaluateItems($inspection, $request->validated('items'), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Checklist items evaluated successfully.',
            'data' => new QcInspectionResource($updated),
        ], 200);
    }

    /**
     * Finalize QC inspection into PASSED, REWORK, or FAILED.
     */
    public function finalize(FinalizeQcInspectionRequest $request, int $id): JsonResponse
    {
        $inspection = QcInspection::where('id', $id)
            ->where('workshop_id', $request->user()->workshop_id)
            ->first();

        if (! $inspection) {
            return response()->json([
                'success' => false,
                'message' => 'QC inspection not found.',
            ], 404);
        }

        Gate::authorize('finalize', $inspection);

        $finalized = $this->qcService->finalize($inspection, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => "QC inspection finalized with result {$finalized->status->value}.",
            'data' => new QcInspectionResource($finalized),
        ], 200);
    }

    /**
     * Upload photo evidence for a QC inspection.
     */
    public function uploadMedia(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'visibility' => ['nullable', 'string', 'in:INTERNAL,CUSTOMER'],
            'caption' => ['nullable', 'string', 'max:255'],
        ]);

        $inspection = QcInspection::where('id', $id)
            ->where('workshop_id', $request->user()->workshop_id)
            ->first();

        if (! $inspection) {
            return response()->json([
                'success' => false,
                'message' => 'QC inspection not found.',
            ], 404);
        }

        Gate::authorize('evaluate', $inspection);

        if ($inspection->status !== QcInspectionStatus::PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'The given data was invalid.',
                'errors' => [
                    'inspection' => ['Inspeksi QC yang sudah difinalisasi bersifat permanen (immutable) dan tidak dapat ditambahkan foto bukti.'],
                ],
            ], 422);
        }

        $visibility = $request->input('visibility', MediaVisibility::INTERNAL->value);

        $media = $this->mediaService->upload(
            order: $inspection->order,
            file: $request->file('file'),
            data: [
                'visibility' => $visibility,
                'caption' => $request->input('caption'),
                'qc_inspection_id' => $inspection->id,
            ],
            actor: $request->user(),
            qcInspection: $inspection
        );

        return response()->json([
            'success' => true,
            'message' => 'QC evidence photo uploaded successfully.',
            'data' => new MediaResource($media->load('uploader')),
        ], 201);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProductionStageStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Production\StoreProductionStageRequest;
use App\Http\Requests\Production\StoreProductionUpdateRequest;
use App\Http\Requests\Production\UpdateProductionStageStatusRequest;
use App\Http\Resources\ProductionOverviewResource;
use App\Http\Resources\ProductionStageResource;
use App\Http\Resources\ProductionUpdateResource;
use App\Models\ProductionStage;
use App\Models\ProductionUpdate;
use App\Services\ProductionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductionController extends Controller
{
    public function __construct(
        private readonly ProductionService $productionService
    ) {}

    /**
     * Get complete production tracking overview for an order.
     */
    public function overview(Request $request, int $orderId): JsonResponse
    {
        $order = $request->user()->workshop->orders()
            ->with(['productionStages', 'productionUpdates.media', 'productionUpdates.user'])
            ->whereKey($orderId)
            ->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        Gate::authorize('view', $order);

        return response()->json([
            'success' => true,
            'message' => 'Production overview retrieved successfully.',
            'data' => new ProductionOverviewResource($order),
        ], 200);
    }

    /**
     * Explicitly initialize the 8 default production stages for an order.
     */
    public function initStages(Request $request, int $orderId): JsonResponse
    {
        Gate::authorize('create', ProductionStage::class);

        $order = $request->user()->workshop->orders()->whereKey($orderId)->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        $stages = $this->productionService->initializeDefaultStages($order, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Production stages initialized successfully.',
            'data' => ProductionStageResource::collection($stages),
        ], 200);
    }

    /**
     * Add a custom production stage to an order (Restricted to OWNER / ADMIN).
     */
    public function storeStage(StoreProductionStageRequest $request, int $orderId): JsonResponse
    {
        Gate::authorize('create', ProductionStage::class);

        $order = $request->user()->workshop->orders()->whereKey($orderId)->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        $stage = $this->productionService->addStage(
            $order,
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Production stage added successfully.',
            'data' => new ProductionStageResource($stage),
        ], 201);
    }

    /**
     * Update operational status of a production stage (e.g. IN_PROGRESS, COMPLETED).
     */
    public function updateStageStatus(UpdateProductionStageStatusRequest $request, int $id): JsonResponse
    {
        $stage = ProductionStage::where('id', $id)
            ->where('workshop_id', $request->user()->workshop_id)
            ->first();

        if (! $stage) {
            return response()->json([
                'success' => false,
                'message' => 'Production stage not found.',
            ], 404);
        }

        Gate::authorize('updateStatus', $stage);

        $newStatus = ProductionStageStatus::from($request->validated('status'));
        $updated = $this->productionService->updateStageStatus($stage, $newStatus, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Production stage status updated successfully.',
            'data' => new ProductionStageResource($updated),
        ], 200);
    }

    /**
     * Post a progress update note on a production stage, with optional photo evidence.
     */
    public function storeUpdate(StoreProductionUpdateRequest $request, int $id): JsonResponse
    {
        Gate::authorize('create', ProductionUpdate::class);

        $stage = ProductionStage::where('id', $id)
            ->where('workshop_id', $request->user()->workshop_id)
            ->with('order')
            ->first();

        if (! $stage) {
            return response()->json([
                'success' => false,
                'message' => 'Production stage not found.',
            ], 404);
        }

        $mediaFiles = $request->file('media', []);
        if ($mediaFiles && ! is_array($mediaFiles)) {
            $mediaFiles = [$mediaFiles];
        }

        $update = $this->productionService->createUpdate(
            $stage,
            $request->validated(),
            $request->user(),
            $mediaFiles
        );

        return response()->json([
            'success' => true,
            'message' => 'Production update recorded successfully.',
            'data' => new ProductionUpdateResource($update),
        ], 201);
    }

    /**
     * List all production updates for an order.
     */
    public function indexUpdates(Request $request, int $orderId): JsonResponse
    {
        $order = $request->user()->workshop->orders()->whereKey($orderId)->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        Gate::authorize('view', $order);

        $updates = $order->productionUpdates()
            ->with(['productionStage', 'user', 'media'])
            ->latest('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Production updates retrieved successfully.',
            'data' => ProductionUpdateResource::collection($updates),
        ], 200);
    }
}

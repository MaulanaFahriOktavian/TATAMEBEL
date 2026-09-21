<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Specification\StoreSpecificationRequest;
use App\Http\Requests\Specification\UpdateSpecificationRequest;
use App\Http\Resources\SpecificationResource;
use App\Models\OrderItem;
use App\Models\Specification;
use App\Services\SpecificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SpecificationController extends Controller
{
    public function __construct(
        private readonly SpecificationService $specificationService
    ) {}

    /**
     * Create an initial DRAFT specification for an order item.
     */
    public function store(StoreSpecificationRequest $request, int $orderId, int $itemId): JsonResponse
    {
        Gate::authorize('create', Specification::class);

        $order = $request->user()->workshop->orders()->whereKey($orderId)->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        $orderItem = $order->orderItems()->whereKey($itemId)->first();

        if (! $orderItem) {
            return response()->json([
                'success' => false,
                'message' => 'Order item not found.',
            ], 404);
        }

        $specification = $this->specificationService->create(
            $orderItem,
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Specification created successfully.',
            'data' => new SpecificationResource($specification),
        ], 201);
    }

    /**
     * List all specification version history for an order item.
     */
    public function index(Request $request, int $orderId, int $itemId): JsonResponse
    {
        Gate::authorize('viewAny', Specification::class);

        $order = $request->user()->workshop->orders()->whereKey($orderId)->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        $orderItem = $order->orderItems()->whereKey($itemId)->first();

        if (! $orderItem) {
            return response()->json([
                'success' => false,
                'message' => 'Order item not found.',
            ], 404);
        }

        $versions = $this->specificationService->listVersions($orderItem);

        return response()->json([
            'success' => true,
            'message' => 'Specification versions retrieved successfully.',
            'data' => SpecificationResource::collection($versions),
        ], 200);
    }

    /**
     * Get the current operational specification (highest LOCKED version).
     */
    public function current(Request $request, int $orderId, int $itemId): JsonResponse
    {
        Gate::authorize('viewAny', Specification::class);

        $order = $request->user()->workshop->orders()->whereKey($orderId)->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        $orderItem = $order->orderItems()->whereKey($itemId)->first();

        if (! $orderItem) {
            return response()->json([
                'success' => false,
                'message' => 'Order item not found.',
            ], 404);
        }

        $currentSpec = $this->specificationService->getCurrent($orderItem);

        return response()->json([
            'success' => true,
            'message' => 'Current operational specification retrieved successfully.',
            'data' => $currentSpec ? new SpecificationResource($currentSpec) : null,
        ], 200);
    }

    /**
     * Get single specification detail by ID.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $specification = Specification::where('id', $id)
            ->where('workshop_id', $request->user()->workshop_id)
            ->first();

        if (! $specification) {
            return response()->json([
                'success' => false,
                'message' => 'Specification not found.',
            ], 404);
        }

        Gate::authorize('view', $specification);

        return response()->json([
            'success' => true,
            'message' => 'Specification retrieved successfully.',
            'data' => new SpecificationResource($specification->load(['orderItem', 'lockedBy'])),
        ], 200);
    }

    /**
     * Update a specification in DRAFT status.
     */
    public function update(UpdateSpecificationRequest $request, int $id): JsonResponse
    {
        $specification = Specification::where('id', $id)
            ->where('workshop_id', $request->user()->workshop_id)
            ->first();

        if (! $specification) {
            return response()->json([
                'success' => false,
                'message' => 'Specification not found.',
            ], 404);
        }

        Gate::authorize('update', $specification);

        $updated = $this->specificationService->update(
            $specification,
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Specification updated successfully.',
            'data' => new SpecificationResource($updated),
        ], 200);
    }

    /**
     * Lock a specification so it becomes the immutable operational blueprint.
     */
    public function lock(Request $request, int $id): JsonResponse
    {
        $specification = Specification::where('id', $id)
            ->where('workshop_id', $request->user()->workshop_id)
            ->first();

        if (! $specification) {
            return response()->json([
                'success' => false,
                'message' => 'Specification not found.',
            ], 404);
        }

        Gate::authorize('lock', $specification);

        $locked = $this->specificationService->lock($specification, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Specification locked successfully.',
            'data' => new SpecificationResource($locked),
        ], 200);
    }
}

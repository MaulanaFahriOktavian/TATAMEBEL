<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeRequest\ReviewChangeRequestRequest;
use App\Http\Requests\ChangeRequest\StoreChangeRequestRequest;
use App\Http\Resources\ChangeRequestResource;
use App\Http\Resources\SpecificationResource;
use App\Models\ChangeRequest;
use App\Services\ChangeRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ChangeRequestController extends Controller
{
    public function __construct(
        private readonly ChangeRequestService $changeRequestService
    ) {}

    /**
     * Submit a formal Change Request for an order item.
     */
    public function store(StoreChangeRequestRequest $request, int $orderId): JsonResponse
    {
        Gate::authorize('create', ChangeRequest::class);

        $order = $request->user()->workshop->orders()->whereKey($orderId)->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        $changeRequest = $this->changeRequestService->create(
            $order,
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Change request submitted successfully.',
            'data' => new ChangeRequestResource($changeRequest),
        ], 201);
    }

    /**
     * List all change requests for an order.
     */
    public function index(Request $request, int $orderId): JsonResponse
    {
        Gate::authorize('viewAny', ChangeRequest::class);

        $order = $request->user()->workshop->orders()->whereKey($orderId)->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        $requests = $order->changeRequests()
            ->with(['orderItem', 'approver', 'user'])
            ->latest('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Change requests retrieved successfully.',
            'data' => ChangeRequestResource::collection($requests),
        ], 200);
    }

    /**
     * Get single change request detail.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $changeRequest = ChangeRequest::where('id', $id)
            ->where('workshop_id', $request->user()->workshop_id)
            ->with(['orderItem', 'approver', 'user'])
            ->first();

        if (! $changeRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Change request not found.',
            ], 404);
        }

        Gate::authorize('view', $changeRequest);

        return response()->json([
            'success' => true,
            'message' => 'Change request retrieved successfully.',
            'data' => new ChangeRequestResource($changeRequest),
        ], 200);
    }

    /**
     * Approve a change request, creating a new specification version in DRAFT status.
     */
    public function approve(ReviewChangeRequestRequest $request, int $id): JsonResponse
    {
        $changeRequest = ChangeRequest::where('id', $id)
            ->where('workshop_id', $request->user()->workshop_id)
            ->first();

        if (! $changeRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Change request not found.',
            ], 404);
        }

        Gate::authorize('approve', $changeRequest);

        $newSpecification = $this->changeRequestService->approve(
            $changeRequest,
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Change request approved. New specification version created in DRAFT status.',
            'data' => [
                'change_request' => new ChangeRequestResource($changeRequest->fresh(['orderItem', 'approver'])),
                'new_specification' => new SpecificationResource($newSpecification),
            ],
        ], 200);
    }

    /**
     * Reject a change request with a recorded reason.
     */
    public function reject(ReviewChangeRequestRequest $request, int $id): JsonResponse
    {
        $changeRequest = ChangeRequest::where('id', $id)
            ->where('workshop_id', $request->user()->workshop_id)
            ->first();

        if (! $changeRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Change request not found.',
            ], 404);
        }

        Gate::authorize('reject', $changeRequest);

        $rejected = $this->changeRequestService->reject(
            $changeRequest,
            $request->validated(),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Change request rejected successfully.',
            'data' => new ChangeRequestResource($rejected),
        ], 200);
    }

    /**
     * Cancel a pending change request.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $changeRequest = ChangeRequest::where('id', $id)
            ->where('workshop_id', $request->user()->workshop_id)
            ->first();

        if (! $changeRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Change request not found.',
            ], 404);
        }

        Gate::authorize('cancel', $changeRequest);

        $cancelled = $this->changeRequestService->cancel($changeRequest, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Change request cancelled successfully.',
            'data' => new ChangeRequestResource($cancelled),
        ], 200);
    }
}

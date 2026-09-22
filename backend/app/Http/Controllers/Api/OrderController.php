<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\ChangeOrderStatusRequest;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\WhatsAppMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * Display a listing of the workshop's orders.
     */
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        $workshop = $request->attributes->get('workshop') ?? $request->user()->workshop;
        $orders = $this->orderService->list($workshop);

        return response()->json([
            'success' => true,
            'message' => 'Orders retrieved successfully.',
            'data' => OrderResource::collection($orders->items()),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'last_page' => $orders->lastPage(),
            ],
        ], 200);
    }

    /**
     * Store a newly created order with items in storage.
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        Gate::authorize('create', Order::class);

        $workshop = $request->attributes->get('workshop') ?? $request->user()->workshop;
        $order = $this->orderService->create($workshop, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully.',
            'data' => new OrderResource($order),
        ], 201);
    }

    /**
     * Display the specified order with customer and items.
     */
    public function show(Request $request, int|string $id): JsonResponse
    {
        $workshop = $request->attributes->get('workshop') ?? $request->user()->workshop;
        $order = $workshop->orders()->with(['customer', 'orderItems'])->whereKey($id)->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
                'errors' => new \stdClass(),
            ], 404);
        }

        Gate::authorize('view', $order);

        return response()->json([
            'success' => true,
            'message' => 'Order retrieved successfully.',
            'data' => new OrderResource($order),
        ], 200);
    }

    /**
     * Change the status of the specified order according to state machine rules.
     */
    public function changeStatus(ChangeOrderStatusRequest $request, int|string $id): JsonResponse
    {
        $workshop = $request->attributes->get('workshop') ?? $request->user()->workshop;
        $order = $workshop->orders()->whereKey($id)->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
                'errors' => new \stdClass(),
            ], 404);
        }

        Gate::authorize('changeStatus', $order);

        $newStatus = OrderStatus::from($request->validated('status'));
        $updated = $this->orderService->changeStatus($order, $newStatus, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully.',
            'data' => new OrderResource($updated),
        ], 200);
    }

    /**
     * Generate WhatsApp share message and wa.me URL for the order.
     */
    public function shareWhatsApp(Request $request, int|string $id, WhatsAppMessageService $whatsAppService): JsonResponse
    {
        $workshop = $request->attributes->get('workshop') ?? $request->user()->workshop;
        $order = $workshop->orders()->with(['customer', 'orderItems', 'workshop'])->whereKey($id)->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
                'errors' => new \stdClass(),
            ], 404);
        }

        Gate::authorize('shareWhatsApp', $order);

        $data = $whatsAppService->generateShareData($order, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'WhatsApp share data generated.',
            'data' => $data,
        ], 200);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shipping\StoreShippingRequest;
use App\Http\Requests\Shipping\UpdateShippingRequest;
use App\Http\Resources\ShippingResource;
use App\Models\Order;
use App\Models\Shipping;
use App\Services\ShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ShippingController extends Controller
{
    public function __construct(
        protected ShippingService $shippingService
    ) {}

    /**
     * Display the shipping record for the specified order.
     */
    public function show(Request $request, int $orderId): JsonResponse
    {
        $workshop = $request->attributes->get('workshop') ?? $request->user()->workshop;
        $order = $workshop->orders()->whereKey($orderId)->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan.',
            ], 404);
        }

        $shipping = $this->shippingService->getForOrder($order);

        if (! $shipping) {
            Gate::authorize('view', $order);

            return response()->json([
                'success' => true,
                'message' => 'Data pengiriman belum tersedia.',
                'data' => null,
            ], 200);
        }

        Gate::authorize('view', $shipping);

        return response()->json([
            'success' => true,
            'message' => 'Data pengiriman berhasil diambil.',
            'data' => new ShippingResource($shipping),
        ], 200);
    }

    /**
     * Store a newly created shipping record for the specified order.
     */
    public function store(StoreShippingRequest $request, int $orderId): JsonResponse
    {
        $workshop = $request->attributes->get('workshop') ?? $request->user()->workshop;
        $order = $workshop->orders()->whereKey($orderId)->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan.',
            ], 404);
        }

        Gate::authorize('create', [Shipping::class, $order]);

        $shipping = $this->shippingService->createForOrder($order, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Data pengiriman berhasil dibuat.',
            'data' => new ShippingResource($shipping),
        ], 201);
    }

    /**
     * Update an existing shipping record for the specified order.
     */
    public function update(UpdateShippingRequest $request, int $orderId): JsonResponse
    {
        $workshop = $request->attributes->get('workshop') ?? $request->user()->workshop;
        $order = $workshop->orders()->whereKey($orderId)->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan.',
            ], 404);
        }

        $shipping = $this->shippingService->getForOrder($order);

        if (! $shipping) {
            return response()->json([
                'success' => false,
                'message' => 'Data pengiriman belum ditemukan untuk pesanan ini.',
            ], 404);
        }

        Gate::authorize('update', $shipping);

        $updated = $this->shippingService->updateForOrder($order, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Data pengiriman berhasil diperbarui.',
            'data' => new ShippingResource($updated),
        ], 200);
    }
}

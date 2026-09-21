<?php

namespace App\Http\Controllers\Api;

use App\Enums\MediaVisibility;
use App\Enums\SpecificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerPortalOrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

class CustomerPortalController extends Controller
{
    /**
     * Display public order tracking details by public token.
     *
     * Unauthenticated public endpoint protected by rate limiting.
     * Non-leaking generic 404 for invalid/inaccessible tokens.
     */
    public function show(string $publicToken): JsonResponse
    {
        $order = Order::where('public_token', $publicToken)
            ->with([
                'workshop:id,name,phone,address',
                'customer:id,name',
                'orderItems.specifications' => function ($q) {
                    $q->where('status', SpecificationStatus::LOCKED)->orderByDesc('version');
                },
                'productionStages' => function ($q) {
                    $q->where('is_active', true)->orderBy('sequence');
                },
                'media' => function ($q) {
                    $q->where('visibility', MediaVisibility::CUSTOMER)->latest();
                },
                'qcInspections' => function ($q) {
                    $q->latest('inspected_at');
                },
                'shipping',
            ])
            ->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan atau tautan pelacakan tidak valid.',
                'errors' => new \stdClass(),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order tracking details retrieved successfully.',
            'data' => new CustomerPortalOrderResource($order),
        ], 200);
    }
}

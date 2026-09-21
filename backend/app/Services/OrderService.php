<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * Map of allowed next statuses for each current status.
     *
     * @var array<string, list<string>>
     */
    private const ALLOWED_TRANSITIONS = [
        'DRAFT' => ['QUOTATION', 'CONFIRMED', 'CANCELLED'],
        'QUOTATION' => ['CONFIRMED', 'CANCELLED'],
        'CONFIRMED' => ['WAITING_DP', 'CANCELLED'],
        'WAITING_DP' => ['READY_FOR_PRODUCTION', 'CANCELLED'],
        'READY_FOR_PRODUCTION' => ['IN_PRODUCTION', 'CANCELLED'],
        'IN_PRODUCTION' => ['QC', 'CANCELLED'],
        'QC' => ['PACKING'],
        'PACKING' => ['READY_TO_SHIP'],
        'READY_TO_SHIP' => ['SHIPPED'],
        'SHIPPED' => ['COMPLETED'],
        'COMPLETED' => [],
        'CANCELLED' => [],
    ];

    /**
     * Retrieve paginated orders for a workshop.
     */
    public function list(Workshop $workshop, int $perPage = 15): LengthAwarePaginator
    {
        return $workshop->orders()
            ->with(['customer', 'orderItems'])
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * Create an order with items within a single database transaction.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function create(Workshop $workshop, array $data, ?User $actor = null): Order
    {
        // 1. Verify that customer exists and strictly belongs to the same workshop
        $customer = Customer::where('id', $data['customer_id'])
            ->where('workshop_id', $workshop->id)
            ->first();

        if (! $customer) {
            throw ValidationException::withMessages([
                'customer_id' => ['Pelanggan tidak ditemukan atau tidak terdaftar pada workshop ini.'],
            ]);
        }

        // 2. Validate items presence
        $itemsData = $data['items'] ?? [];
        if (empty($itemsData)) {
            throw ValidationException::withMessages([
                'items' => ['Pesanan harus memiliki minimal 1 item.'],
            ]);
        }

        return DB::transaction(function () use ($workshop, $customer, $data, $itemsData, $actor) {
            // Lock workshop to prevent concurrent order_number collisions
            Workshop::where('id', $workshop->id)->lockForUpdate()->first();

            // Generate sequential order number: ORD-YYYYMM-XXXX
            $orderNumber = $this->generateOrderNumber($workshop->id);

            // Generate high-entropy unguessable public token
            $publicToken = $this->generatePublicToken();

            // Calculate item subtotals and total order amount
            $totalAmount = 0.00;
            $preparedItems = [];

            foreach ($itemsData as $item) {
                $qty = (int) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $subtotal = round($qty * $unitPrice, 2);
                $totalAmount += $subtotal;

                $preparedItems[] = [
                    'workshop_id' => $workshop->id,
                    'product_name' => $item['product_name'],
                    'product_code' => $item['product_code'] ?? null,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'notes' => $item['notes'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Create Order
            $order = Order::create([
                'workshop_id' => $workshop->id,
                'customer_id' => $customer->id,
                'order_number' => $orderNumber,
                'title' => $data['title'],
                'status' => OrderStatus::DRAFT,
                'total_amount' => round($totalAmount, 2),
                'notes' => $data['notes'] ?? null,
                'public_token' => $publicToken,
            ]);

            // Save order items
            foreach ($preparedItems as $preparedItem) {
                $preparedItem['order_id'] = $order->id;
                OrderItem::create($preparedItem);
            }

            // Record activity log
            ActivityLogService::log(
                workshopId: $workshop->id,
                action: 'ORDER_CREATED',
                entity: $order,
                description: "Pesanan {$order->order_number} dibuat untuk pelanggan {$customer->name}.",
                user: $actor,
                orderId: $order->id,
                metadata: [
                    'order_number' => $order->order_number,
                    'total_amount' => $order->total_amount,
                    'items_count' => count($preparedItems),
                ]
            );

            return $order->load(['customer', 'orderItems']);
        });
    }

    /**
     * Transition order to a new status following the strict state machine matrix.
     *
     * @throws ValidationException
     */
    public function changeStatus(Order $order, OrderStatus $newStatus, ?User $actor = null): Order
    {
        $currentStatusValue = $order->status->value;
        $newStatusValue = $newStatus->value;

        // No-op if identical status
        if ($currentStatusValue === $newStatusValue) {
            return $order;
        }

        $allowedTransitions = self::ALLOWED_TRANSITIONS[$currentStatusValue] ?? [];

        if (! in_array($newStatusValue, $allowedTransitions, true)) {
            throw ValidationException::withMessages([
                'status' => ["Transisi status pesanan dari {$currentStatusValue} ke {$newStatusValue} tidak diperbolehkan."],
            ]);
        }

        // Apply timestamp rules based on state
        $updates = ['status' => $newStatus];

        if ($newStatus === OrderStatus::CONFIRMED && is_null($order->confirmed_at)) {
            $updates['confirmed_at'] = now();
        } elseif ($newStatus === OrderStatus::COMPLETED && is_null($order->completed_at)) {
            $updates['completed_at'] = now();
        } elseif ($newStatus === OrderStatus::CANCELLED && is_null($order->cancelled_at)) {
            $updates['cancelled_at'] = now();
        }

        $order->update($updates);

        ActivityLogService::log(
            workshopId: $order->workshop_id,
            action: 'ORDER_STATUS_CHANGED',
            entity: $order,
            description: "Status pesanan {$order->order_number} diubah dari {$currentStatusValue} menjadi {$newStatusValue}.",
            user: $actor,
            orderId: $order->id,
            metadata: [
                'from_status' => $currentStatusValue,
                'to_status' => $newStatusValue,
            ]
        );

        return $order->load(['customer', 'orderItems']);
    }

    /**
     * Concurrency-safe sequential order number generator.
     * Pattern: ORD-YYYYMM-XXXX (e.g. ORD-202609-0001)
     */
    private function generateOrderNumber(int $workshopId): string
    {
        $yearMonth = now()->format('Ym');
        $prefix = "ORD-{$yearMonth}-";

        $latestOrder = Order::where('workshop_id', $workshopId)
            ->where('order_number', 'like', "{$prefix}%")
            ->orderByDesc('order_number')
            ->first();

        if ($latestOrder && preg_match('/-(\d{4})$/', $latestOrder->order_number, $matches)) {
            $sequence = (int) $matches[1] + 1;
        } else {
            $sequence = 1;
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate high-entropy unguessable public token.
     */
    private function generatePublicToken(): string
    {
        do {
            $token = Str::random(40);
        } while (Order::where('public_token', $token)->exists());

        return $token;
    }
}

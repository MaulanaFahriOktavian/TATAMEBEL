<?php

namespace App\Services;

use App\Enums\ShippingStatus;
use App\Models\Order;
use App\Models\Shipping;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShippingService
{
    /**
     * Allowed status transitions for shipping.
     */
    public const ALLOWED_STATUS_TRANSITIONS = [
        'PENDING' => ['READY', 'SHIPPED'],
        'READY' => ['SHIPPED'],
        'SHIPPED' => ['DELIVERED'],
        'DELIVERED' => [],
    ];

    /**
     * Retrieve the shipping record for a given order within the current workshop.
     */
    public function getForOrder(Order $order): ?Shipping
    {
        return $order->shipping()->first();
    }

    /**
     * Create a new shipping record for the specified order.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function createForOrder(Order $order, array $data, ?User $actor = null): Shipping
    {
        // One-to-one enforcement: check if shipping already exists
        if ($order->shipping()->exists()) {
            throw ValidationException::withMessages([
                'order_id' => ['Data pengiriman untuk pesanan ini sudah ada. Gunakan pembaruan (PATCH) untuk mengubah data pengiriman.'],
            ]);
        }

        $initialStatus = isset($data['status'])
            ? ($data['status'] instanceof ShippingStatus ? $data['status'] : ShippingStatus::from((string) $data['status']))
            : ShippingStatus::PENDING;

        return DB::transaction(function () use ($order, $data, $initialStatus, $actor) {
            $shipping = Shipping::create([
                'workshop_id' => $order->workshop_id,
                'order_id' => $order->id,
                'courier' => $data['courier'],
                'tracking_number' => $data['tracking_number'] ?? null,
                'shipping_address' => $data['shipping_address'],
                'shipped_at' => $data['shipped_at'] ?? ($initialStatus === ShippingStatus::SHIPPED ? now() : null),
                'estimated_arrival' => $data['estimated_arrival'] ?? null,
                'delivered_at' => $data['delivered_at'] ?? ($initialStatus === ShippingStatus::DELIVERED ? now() : null),
                'status' => $initialStatus,
                'notes' => $data['notes'] ?? null,
            ]);

            ActivityLogService::log(
                workshopId: $order->workshop_id,
                action: 'SHIPPING_CREATED',
                entity: $shipping,
                description: "Data pengiriman pesanan {$order->order_number} dibuat dengan kurir {$shipping->courier}.",
                user: $actor,
                orderId: $order->id,
                metadata: [
                    'shipping_id' => $shipping->id,
                    'courier' => $shipping->courier,
                    'tracking_number' => $shipping->tracking_number,
                    'status' => $shipping->status->value,
                ]
            );

            return $shipping;
        });
    }

    /**
     * Update an existing shipping record for the specified order.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function updateForOrder(Order $order, array $data, ?User $actor = null): Shipping
    {
        $shipping = $order->shipping()->first();

        if (! $shipping) {
            throw ValidationException::withMessages([
                'shipping' => ['Data pengiriman belum ditemukan untuk pesanan ini. Buat data pengiriman terlebih dahulu.'],
            ]);
        }

        return DB::transaction(function () use ($order, $shipping, $data, $actor) {
            $updates = [];

            // Handle status change with transition validation
            if (isset($data['status'])) {
                $targetStatus = $data['status'] instanceof ShippingStatus
                    ? $data['status']
                    : ShippingStatus::from((string) $data['status']);

                if ($targetStatus !== $shipping->status) {
                    $this->validateTransition($shipping->status, $targetStatus);
                    $updates['status'] = $targetStatus;

                    // Automatically timestamp based on status if not explicitly provided
                    if ($targetStatus === ShippingStatus::SHIPPED && is_null($shipping->shipped_at) && ! isset($data['shipped_at'])) {
                        $updates['shipped_at'] = now();
                    } elseif ($targetStatus === ShippingStatus::DELIVERED && is_null($shipping->delivered_at) && ! isset($data['delivered_at'])) {
                        $updates['delivered_at'] = now();
                    }
                }
            }

            // Copy allowed attributes if present in payload
            $fillableKeys = [
                'courier',
                'tracking_number',
                'shipping_address',
                'estimated_arrival',
                'shipped_at',
                'delivered_at',
                'notes',
            ];

            foreach ($fillableKeys as $key) {
                if (array_key_exists($key, $data)) {
                    $updates[$key] = $data[$key];
                }
            }

            $oldStatus = $shipping->status;
            $shipping->update($updates);

            // Record activity log
            $statusChanged = isset($updates['status']) && $updates['status'] !== $oldStatus;
            ActivityLogService::log(
                workshopId: $order->workshop_id,
                action: $statusChanged ? 'SHIPPING_STATUS_CHANGED' : 'SHIPPING_UPDATED',
                entity: $shipping,
                description: $statusChanged
                    ? "Status pengiriman pesanan {$order->order_number} diperbarui dari {$oldStatus->value} ke {$shipping->status->value}."
                    : "Data pengiriman pesanan {$order->order_number} diperbarui.",
                user: $actor,
                orderId: $order->id,
                metadata: [
                    'shipping_id' => $shipping->id,
                    'status' => $shipping->status->value,
                    'courier' => $shipping->courier,
                    'tracking_number' => $shipping->tracking_number,
                ]
            );

            return $shipping->fresh();
        });
    }

    /**
     * Validate status transition legality.
     *
     * @throws ValidationException
     */
    public function validateTransition(ShippingStatus $from, ShippingStatus $to): void
    {
        if ($from === $to) {
            return;
        }

        $allowed = self::ALLOWED_STATUS_TRANSITIONS[$from->value] ?? [];

        if (! in_array($to->value, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => ["Transisi status pengiriman dari {$from->value} ke {$to->value} tidak diperbolehkan."],
            ]);
        }
    }
}

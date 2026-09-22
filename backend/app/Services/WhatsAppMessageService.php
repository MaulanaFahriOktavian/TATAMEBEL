<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Support\WhatsAppNumberNormalizer;
use Illuminate\Validation\ValidationException;

class WhatsAppMessageService
{
    /**
     * Generate WhatsApp message, wa.me URL, and record an audit log entry.
     *
     * @return array{phone: string, message: string, url: string}
     *
     * @throws ValidationException
     */
    public function generateShareData(Order $order, User $actor): array
    {
        // 1. Enforce business rule: Cancelled orders cannot be shared via WhatsApp
        if ($order->status === OrderStatus::CANCELLED) {
            throw ValidationException::withMessages([
                'order' => ['WhatsApp sharing is unavailable for cancelled orders.'],
            ]);
        }

        // 2. Validate and normalize customer phone number
        $customer = $order->customer;
        $rawPhone = $customer?->phone;
        $normalizedPhone = WhatsAppNumberNormalizer::normalize($rawPhone);

        if (! $normalizedPhone) {
            throw ValidationException::withMessages([
                'phone' => ['Nomor WhatsApp pelanggan tidak valid atau belum diisi.'],
            ]);
        }

        // 3. Resolve context variables safely
        $customerName = $customer?->name ?: 'Pelanggan';
        $workshopName = $order->workshop?->name ?: 'Workshop';
        $orderNumber = $order->order_number;

        $productName = $order->orderItems->pluck('product_name')->filter()->implode(', ');
        if (empty($productName)) {
            $productName = $order->title ?: 'Pesanan Mebel';
        }

        $baseUrl = rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173')), '/');
        $trackingUrl = $baseUrl.'/track/'.$order->public_token;

        // 4. Generate message based on OrderStatus mapping
        $message = $this->buildMessage($order->status, [
            'customer_name' => $customerName,
            'workshop_name' => $workshopName,
            'order_number' => $orderNumber,
            'product_name' => $productName,
            'tracking_url' => $trackingUrl,
        ]);

        // 5. Construct URL-encoded wa.me link
        $encodedText = rawurlencode($message);
        $url = "https://wa.me/{$normalizedPhone}?text={$encodedText}";

        // 6. Record audit log without storing sensitive full message or phone
        ActivityLogService::log(
            workshopId: $order->workshop_id,
            action: 'WHATSAPP_SHARE_GENERATED',
            entity: $order,
            description: "WhatsApp share data generated for order {$order->order_number}",
            user: $actor,
            orderId: $order->id,
            metadata: [
                'order_id' => $order->id,
                'channel' => 'whatsapp',
            ]
        );

        return [
            'phone' => $normalizedPhone,
            'message' => $message,
            'url' => $url,
        ];
    }

    /**
     * Build the message string from templates.
     *
     * @param  array<string, string>  $vars
     */
    protected function buildMessage(OrderStatus $status, array $vars): string
    {
        return match ($status) {
            OrderStatus::READY_FOR_PRODUCTION,
            OrderStatus::IN_PRODUCTION => "Halo {$vars['customer_name']},\n\n"
                ."Pesanan {$vars['order_number']} sudah masuk tahap produksi.\n\n"
                ."Pantau proses pengerjaan:\n"
                ."{$vars['tracking_url']}\n\n"
                .'Terima kasih.',

            OrderStatus::QC,
            OrderStatus::PACKING,
            OrderStatus::READY_TO_SHIP,
            OrderStatus::SHIPPED => "Halo {$vars['customer_name']},\n\n"
                ."Pesanan {$vars['order_number']} sedang dalam proses pengerjaan.\n\n"
                ."Pantau perkembangan pesanan:\n"
                ."{$vars['tracking_url']}\n\n"
                .'Terima kasih.',

            OrderStatus::COMPLETED => "Halo {$vars['customer_name']},\n\n"
                ."Pesanan {$vars['order_number']} telah selesai diproses.\n\n"
                ."Detail dan perkembangan pesanan:\n"
                ."{$vars['tracking_url']}\n\n"
                .'Terima kasih.',

            default => "Halo {$vars['customer_name']},\n\n"
                ."Pesanan Anda telah dicatat oleh {$vars['workshop_name']}.\n\n"
                ."Nomor Pesanan: {$vars['order_number']}\n"
                ."Produk: {$vars['product_name']}\n\n"
                ."Pantau perkembangan pesanan:\n"
                ."{$vars['tracking_url']}\n\n"
                .'Terima kasih.',
        };
    }
}

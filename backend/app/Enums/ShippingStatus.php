<?php

namespace App\Enums;

enum ShippingStatus: string
{
    case PENDING = 'PENDING';
    case READY = 'READY';
    case SHIPPED = 'SHIPPED';
    case DELIVERED = 'DELIVERED';

    /**
     * Customer-facing public status label.
     */
    public function publicLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Pengiriman',
            self::READY => 'Siap Dikirim',
            self::SHIPPED => 'Dalam Pengiriman',
            self::DELIVERED => 'Terkirim & Diterima',
        };
    }
}

<?php

namespace App\Enums;

enum OrderStatus: string
{
    case DRAFT = 'DRAFT';
    case QUOTATION = 'QUOTATION';
    case CONFIRMED = 'CONFIRMED';
    case WAITING_DP = 'WAITING_DP';
    case READY_FOR_PRODUCTION = 'READY_FOR_PRODUCTION';
    case IN_PRODUCTION = 'IN_PRODUCTION';
    case QC = 'QC';
    case PACKING = 'PACKING';
    case READY_TO_SHIP = 'READY_TO_SHIP';
    case SHIPPED = 'SHIPPED';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';

    /**
     * Customer-facing public status label.
     */
    public function publicLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft Pesanan',
            self::QUOTATION => 'Menunggu Persetujuan',
            self::CONFIRMED => 'Pesanan Dikonfirmasi',
            self::WAITING_DP => 'Menunggu Pembayaran DP',
            self::READY_FOR_PRODUCTION => 'Siap Masuk Produksi',
            self::IN_PRODUCTION => 'Sedang Diproduksi',
            self::QC => 'Pengecekan Kualitas (QC)',
            self::PACKING => 'Pengemasan (Packing)',
            self::READY_TO_SHIP => 'Siap Dikirim',
            self::SHIPPED => 'Dalam Pengiriman',
            self::COMPLETED => 'Selesai & Diterima',
            self::CANCELLED => 'Pesanan Dibatalkan',
        };
    }
}

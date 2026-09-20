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
}

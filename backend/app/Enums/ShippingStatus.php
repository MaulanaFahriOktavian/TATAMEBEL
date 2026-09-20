<?php

namespace App\Enums;

enum ShippingStatus: string
{
    case PENDING = 'PENDING';
    case READY = 'READY';
    case SHIPPED = 'SHIPPED';
    case DELIVERED = 'DELIVERED';
}

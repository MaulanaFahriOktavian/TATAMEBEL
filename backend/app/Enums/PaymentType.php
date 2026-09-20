<?php

namespace App\Enums;

enum PaymentType: string
{
    case DP = 'DP';
    case PARTIAL = 'PARTIAL';
    case FINAL = 'FINAL';
    case OTHER = 'OTHER';
}

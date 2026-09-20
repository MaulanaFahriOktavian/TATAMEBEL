<?php

namespace App\Enums;

enum QcInspectionStatus: string
{
    case PENDING = 'PENDING';
    case PASSED = 'PASSED';
    case FAILED = 'FAILED';
    case REWORK = 'REWORK';
}

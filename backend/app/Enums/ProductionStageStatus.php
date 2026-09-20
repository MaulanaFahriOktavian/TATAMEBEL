<?php

namespace App\Enums;

enum ProductionStageStatus: string
{
    case PENDING = 'PENDING';
    case IN_PROGRESS = 'IN_PROGRESS';
    case COMPLETED = 'COMPLETED';
    case SKIPPED = 'SKIPPED';
}

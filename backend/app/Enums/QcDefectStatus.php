<?php

namespace App\Enums;

enum QcDefectStatus: string
{
    case OPEN = 'OPEN';
    case IN_REWORK = 'IN_REWORK';
    case RESOLVED = 'RESOLVED';
    case ACCEPTED = 'ACCEPTED';
}

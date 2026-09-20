<?php

namespace App\Enums;

enum UserRole: string
{
    case OWNER = 'OWNER';
    case ADMIN = 'ADMIN';
    case PRODUCTION = 'PRODUCTION';
    case QC = 'QC';
}

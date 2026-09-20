<?php

namespace App\Enums;

enum QcItemStatus: string
{
    case PASS = 'PASS';
    case FAIL = 'FAIL';
    case NA = 'NA';
}

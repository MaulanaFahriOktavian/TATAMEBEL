<?php

namespace App\Enums;

enum ProductionStageStatus: string
{
    case PENDING = 'PENDING';
    case IN_PROGRESS = 'IN_PROGRESS';
    case COMPLETED = 'COMPLETED';
    case SKIPPED = 'SKIPPED';

    /**
     * Customer-facing public status label.
     */
    public function publicLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu',
            self::IN_PROGRESS => 'Sedang Dikerjakan',
            self::COMPLETED => 'Selesai',
            self::SKIPPED => 'Dilewati',
        };
    }
}

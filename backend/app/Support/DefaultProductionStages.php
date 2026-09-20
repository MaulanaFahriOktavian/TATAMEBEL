<?php

namespace App\Support;

class DefaultProductionStages
{
    /**
     * Standard sequence of production stages for custom furniture manufacturing.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            1 => 'Material Preparation',
            2 => 'Cutting',
            3 => 'Assembly',
            4 => 'Sanding',
            5 => 'Finishing',
            6 => 'Final Assembly',
            7 => 'QC',
            8 => 'Packing',
        ];
    }
}

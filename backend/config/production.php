<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Production Stages Template
    |--------------------------------------------------------------------------
    |
    | These stages represent the default operational manufacturing workflow
    | defined in the TATAMEBEL Software Design Document (SDD).
    | They are instantiated per order when production preparation begins.
    |
    */
    'default_stages' => [
        [
            'name' => 'Material Preparation',
            'sequence' => 1,
        ],
        [
            'name' => 'Cutting',
            'sequence' => 2,
        ],
        [
            'name' => 'Assembly',
            'sequence' => 3,
        ],
        [
            'name' => 'Sanding',
            'sequence' => 4,
        ],
        [
            'name' => 'Finishing',
            'sequence' => 5,
        ],
        [
            'name' => 'Final Assembly',
            'sequence' => 6,
        ],
        [
            'name' => 'QC',
            'sequence' => 7,
        ],
        [
            'name' => 'Packing',
            'sequence' => 8,
        ],
    ],
];

<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default QC Checklist Template
    |--------------------------------------------------------------------------
    |
    | Standard quality control checklist items categorized by the 9 SDD categories.
    | Instantiated per QC inspection with initial unevaluated (null) status.
    |
    */
    'default_checklist' => [
        [
            'category' => 'dimension',
            'item' => 'Kesesuaian dimensi panjang, lebar, dan tinggi dengan spesifikasi LOCKED',
        ],
        [
            'category' => 'material',
            'item' => 'Kesesuaian jenis kayu, grade, dan tingkat kekeringan (moisture content)',
        ],
        [
            'category' => 'construction',
            'item' => 'Kekokohan konstruksi, kekuatan sambungan purus/dowel/tenon, dan baut',
        ],
        [
            'category' => 'surface',
            'item' => 'Kehalusan amplas permukaan, ketiadaan goresan/dempul kasar/mata kayu mati',
        ],
        [
            'category' => 'finishing',
            'item' => 'Kerapian lapisan cat/politur/melamine, ketiadaan lelehan (runs), gelembung, kulit jeruk',
        ],
        [
            'category' => 'color',
            'item' => 'Kesesuaian warna finishing dan kain jok dengan spesifikasi LOCKED',
        ],
        [
            'category' => 'quantity',
            'item' => 'Kelengkapan jumlah unit produk dan komponen lepasan',
        ],
        [
            'category' => 'accessories',
            'item' => 'Pemasangan dan fungsi hardware (engsel, rel laci, handle, kunci, bantalan kaki)',
        ],
        [
            'category' => 'packaging',
            'item' => 'Kesiapan dan standar proteksi kemasan (single face/karton sudut/bubble wrap)',
        ],
    ],
];

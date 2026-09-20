<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class WorkshopSeeder extends Seeder
{
    /**
     * Seed foundation workshop and core role accounts idempotently.
     */
    public function run(): void
    {
        $workshop = Workshop::firstOrCreate(
            ['slug' => 'workshop-kayu-lestari'],
            [
                'name' => 'Workshop Kayu Lestari',
                'phone' => '081234567890',
                'email' => 'info@kayulestari.com',
                'address' => 'Jl. Pengrajin Mebel No. 12, Jepara, Jawa Tengah',
                'timezone' => 'Asia/Jakarta',
            ]
        );

        $foundationUsers = [
            [
                'name' => 'Pak Bambang (Owner)',
                'email' => 'owner@kayulestari.com',
                'role' => UserRole::OWNER,
            ],
            [
                'name' => 'Siti Aminah (Admin)',
                'email' => 'admin@kayulestari.com',
                'role' => UserRole::ADMIN,
            ],
            [
                'name' => 'Joko Santoso (Kepala Produksi)',
                'email' => 'produksi@kayulestari.com',
                'role' => UserRole::PRODUCTION,
            ],
            [
                'name' => 'Budi Setiawan (Inspektur QC)',
                'email' => 'qc@kayulestari.com',
                'role' => UserRole::QC,
            ],
        ];

        foreach ($foundationUsers as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'workshop_id' => $workshop->id,
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'role' => $userData['role'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}

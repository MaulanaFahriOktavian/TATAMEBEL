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
                'name' => 'Tata Mebel Jepara',
                'phone' => '081234567890',
                'email' => 'kontak@tatamebel.com',
                'address' => 'Jl. Pemuda No. 45, Jepara, Jawa Tengah',
                'timezone' => 'Asia/Jakarta',
            ]
        );

        // Pastikan nama workshop dan profil menggunakan data profesional
        $workshop->update([
            'name' => 'Tata Mebel Jepara',
            'email' => 'kontak@tatamebel.com',
            'address' => 'Jl. Pemuda No. 45, Jepara, Jawa Tengah',
        ]);

        $foundationUsers = [
            // Akun Utama Tata Mebel (Mudah & Realistis)
            [
                'name' => 'Fahri Maulana',
                'email' => 'owner@tatamebel.com',
                'role' => UserRole::OWNER,
            ],
            [
                'name' => 'Siti Rahmawati',
                'email' => 'admin@tatamebel.com',
                'role' => UserRole::ADMIN,
            ],
            [
                'name' => 'Joko Prasetyo',
                'email' => 'produksi@tatamebel.com',
                'role' => UserRole::PRODUCTION,
            ],
            [
                'name' => 'Budi Setiawan',
                'email' => 'qc@tatamebel.com',
                'role' => UserRole::QC,
            ],
            // Akun alias yang sudah dibersihkan dari label dummy (Owner)/(Admin)
            [
                'name' => 'Bambang Sudiro',
                'email' => 'owner@kayulestari.com',
                'role' => UserRole::OWNER,
            ],
            [
                'name' => 'Siti Aminah',
                'email' => 'admin@kayulestari.com',
                'role' => UserRole::ADMIN,
            ],
            [
                'name' => 'Joko Santoso',
                'email' => 'produksi@kayulestari.com',
                'role' => UserRole::PRODUCTION,
            ],
            [
                'name' => 'Budi Setiawan',
                'email' => 'qc@kayulestari.com',
                'role' => UserRole::QC,
            ],
        ];

        foreach ($foundationUsers as $userData) {
            User::updateOrCreate(
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

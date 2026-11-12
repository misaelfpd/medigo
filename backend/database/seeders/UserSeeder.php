<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            // Admin Users
            [
                'nama_user' => 'Admin MediGO',
                'email_user' => 'admin@medigo.id',
                'password_user' => Hash::make('password'),
                'role_user' => 'admin',
            ],
            [
                'nama_user' => 'Super Admin MediGO',
                'email_user' => 'admin@medigo.test',
                'password_user' => Hash::make('password'),
                'role_user' => 'admin',
            ],

            // Dokter / Tenaga Medis
            [
                'nama_user' => 'dr. Sarah Faradilla, Sp.A',
                'email_user' => 'dr.sarah@medigo.id',
                'password_user' => Hash::make('password'),
                'role_user' => 'user',
            ],
            [
                'nama_user' => 'dr. Budi Santoso, Sp.PD',
                'email_user' => 'dr.budi@medigo.id',
                'password_user' => Hash::make('password'),
                'role_user' => 'user',
            ],
            [
                'nama_user' => 'dr. Andi Wijaya, Sp.JP',
                'email_user' => 'dr.andi@medigo.id',
                'password_user' => Hash::make('password'),
                'role_user' => 'user',
            ],
            [
                'nama_user' => 'drg. Maya Anggraini',
                'email_user' => 'drg.maya@medigo.id',
                'password_user' => Hash::make('password'),
                'role_user' => 'user',
            ],

            // Pasien / Pengguna Komunitas
            [
                'nama_user' => 'Dimas Pratama',
                'email_user' => 'dimas.pratama@gmail.com',
                'password_user' => Hash::make('password'),
                'role_user' => 'user',
            ],
            [
                'nama_user' => 'Siti Aminah',
                'email_user' => 'siti.aminah@gmail.com',
                'password_user' => Hash::make('password'),
                'role_user' => 'user',
            ],
            [
                'nama_user' => 'Reza Rahardian',
                'email_user' => 'reza.rahardian@yahoo.com',
                'password_user' => Hash::make('password'),
                'role_user' => 'user',
            ],
            [
                'nama_user' => 'Anisa Putri Lestari',
                'email_user' => 'anisa.putri@gmail.com',
                'password_user' => Hash::make('password'),
                'role_user' => 'user',
            ],
            [
                'nama_user' => 'Fajar Nugroho',
                'email_user' => 'fajar.nugroho@outlook.com',
                'password_user' => Hash::make('password'),
                'role_user' => 'user',
            ],
            [
                'nama_user' => 'Dewi Lestari',
                'email_user' => 'dewi.lestari@gmail.com',
                'password_user' => Hash::make('password'),
                'role_user' => 'user',
            ],
            [
                'nama_user' => 'Bayu Samudra',
                'email_user' => 'bayu.samudra@gmail.com',
                'password_user' => Hash::make('password'),
                'role_user' => 'user',
            ],
            [
                'nama_user' => 'Rina Wulandari',
                'email_user' => 'rina.wulandari@gmail.com',
                'password_user' => Hash::make('password'),
                'role_user' => 'user',
            ],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(
                ['email_user' => $u['email_user']],
                [
                    'nama_user' => $u['nama_user'],
                    'password_user' => $u['password_user'],
                    'role_user' => $u['role_user'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}

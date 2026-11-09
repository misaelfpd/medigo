<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'nama_user' => 'Test User',
            'email_user' => 'test@example.com',
            'role_user' => 'user',
        ]);

        User::factory()->admin()->create([
            'nama_user' => 'Admin Medigo',
            'email_user' => 'admin@medigo.test',
        ]);

        $this->call(FaskesSeeder::class);
    }
}

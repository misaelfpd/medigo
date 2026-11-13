<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_factory_and_database_seeder_succeed(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('user', [
            'email_user' => 'dr.sarah@medigo.id',
            'role_user' => 'user',
        ]);

        $this->assertDatabaseHas('user', [
            'email_user' => 'admin@medigo.test',
            'role_user' => 'admin',
        ]);

        $this->assertDatabaseHas('faskes', [
            'nama_faskes' => 'PUSKESMAS TEBET',
            'kota_kabupaten' => 'Jakarta Selatan',
        ]);

        $this->assertGreaterThan(700, DB::table('faskes')->count());
        $this->assertGreaterThan(700, DB::table('fasilitas')->count());
        $this->assertGreaterThan(100, DB::table('spesialisasi')->count());
        $this->assertGreaterThan(1000, DB::table('fasilitas_faskes')->count());
        $this->assertGreaterThan(1000, DB::table('spesialisasi_faskes')->count());

        // Kolom is_support_bpjs (True/False) dari CSV harus terbaca, tidak semuanya jadi BPJS
        $this->assertGreaterThan(0, DB::table('faskes')->where('_support_bpjs', 0)->count());
        $this->assertGreaterThan(0, DB::table('faskes')->where('_support_bpjs', 1)->count());

        // Kelas hanya boleh A-D atau kosong (bukan teks lain dari OSM), dan penulisan jenis "Rumah Sakit" seragam
        $this->assertSame(0, DB::table('faskes')->whereNotNull('kelas')->whereNotIn('kelas', ['A', 'B', 'C', 'D'])->count());
        $this->assertSame(0, DB::table('faskes')->where('jenis_faskes', 'Rumah sakit')->count());

        $faskesSample = DB::table('faskes')->first();
        $this->assertNotNull($faskesSample->created_at);
        $this->assertNotNull($faskesSample->updated_at);
        $this->assertNotNull($faskesSample->telepon);

        $userSample = User::where('email_user', 'dr.sarah@medigo.id')->first();
        $this->assertNotNull($userSample->created_at);
        $this->assertNotNull($userSample->updated_at);

        $generated = User::factory()->count(3)->create();
        $this->assertCount(3, $generated);
    }
}

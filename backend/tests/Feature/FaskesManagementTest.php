<?php

namespace Tests\Feature;

use App\Models\Faskes;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FaskesManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::create([
            'nama_user' => 'Test '.$role,
            'email_user' => $role.'@example.com',
            'password_user' => 'secret-password',
            'role_user' => $role,
        ]);
    }

    private function makeFaskes(): Faskes
    {
        return Faskes::create([
            'nama_faskes' => 'RS Sehat',
            'jenis_faskes' => 'Rumah Sakit',
            'kota_kabupaten' => 'Bogor',
            'alamat_faskes' => 'Jl. Contoh No. 1',
            '_support_bpjs' => true,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'nama_faskes' => 'Klinik Baru',
            'jenis_faskes' => 'Klinik',
            'alamat_faskes' => 'Jl. Baru No. 2',
            'kota_kabupaten' => 'Depok',
            '_support_bpjs' => '1',
        ];
    }

    public function test_guest_cannot_create_update_or_delete_faskes(): void
    {
        $faskes = $this->makeFaskes();

        $this->postJson('/api/faskes', $this->validPayload())->assertUnauthorized();
        $this->putJson("/api/faskes/{$faskes->id_faskes}", ['nama_faskes' => 'Diubah'])->assertUnauthorized();
        $this->deleteJson("/api/faskes/{$faskes->id_faskes}")->assertUnauthorized();

        $this->assertDatabaseCount('faskes', 1);
        $this->assertDatabaseHas('faskes', ['id_faskes' => $faskes->id_faskes, 'nama_faskes' => 'RS Sehat']);
    }

    public function test_regular_user_cannot_create_faskes(): void
    {
        Sanctum::actingAs($this->makeUser('user'));

        $this->postJson('/api/faskes', $this->validPayload())->assertForbidden();

        $this->assertDatabaseCount('faskes', 0);
    }

    public function test_admin_can_create_faskes(): void
    {
        Sanctum::actingAs($this->makeUser('admin'));

        $this->postJson('/api/faskes', $this->validPayload())
            ->assertCreated()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('faskes', ['nama_faskes' => 'Klinik Baru', 'kota_kabupaten' => 'Depok']);
    }

    public function test_admin_stats_requires_admin(): void
    {
        $this->getJson('/api/admin/stats')->assertUnauthorized();

        Sanctum::actingAs($this->makeUser('user'));
        $this->getJson('/api/admin/stats')->assertForbidden();
    }

    public function test_admin_stats_returns_real_counts_and_recent_activities(): void
    {
        $admin = $this->makeUser('admin');
        $this->makeFaskes();

        DB::table('log_aktivitas')->insert([
            'id_user' => $admin->id_user,
            'tipe_aksi' => 'Buat Diskusi',
            'deskripsi' => 'Topik kesehatan jantung',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($admin);
        $res = $this->getJson('/api/admin/stats');

        $res->assertOk()
            ->assertJsonPath('data.total_users', 1)
            ->assertJsonPath('data.total_faskes', 1)
            ->assertJsonPath('data.total_discussions', 0)
            ->assertJsonPath('data.activities.0.title', 'Topik kesehatan jantung')
            ->assertJsonPath('data.activities.0.type', 'forum');
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminAccessTest extends TestCase
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

    public function test_admin_can_open_admin_routes(): void
    {
        Sanctum::actingAs($this->makeUser('admin'));

        $this->getJson('/api/admin/logs')
            ->assertOk()
            ->assertJsonPath('message', 'Berhasil mengambil data aktivitas log');
    }

    public function test_regular_user_is_rejected_from_admin_routes(): void
    {
        Sanctum::actingAs($this->makeUser('user'));

        $this->getJson('/api/admin/logs')
            ->assertForbidden()
            ->assertJsonPath('message', 'Akses ditolak. Rute ini khusus Admin Medigo.');
    }

    public function test_guest_is_rejected_from_admin_routes(): void
    {
        $this->getJson('/api/admin/logs')->assertUnauthorized();
    }
}

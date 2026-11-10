<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_then_login(): void
    {
        $regResponse = $this->postJson('/api/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'secret12345',
        ]);

        $regResponse->assertCreated()
            ->assertJsonPath('message', 'Registrasi berhasil!')
            ->assertJsonStructure(['access_token', 'data']);

        $loginResponse = $this->postJson('/api/login', [
            'email' => 'budi@example.com',
            'password' => 'secret12345',
        ]);

        $loginResponse->assertOk()
            ->assertJsonPath('message', 'Login berhasil!')
            ->assertJsonStructure(['access_token', 'data']);

        $token = $loginResponse->json('access_token');

        $profileResponse = $this->withToken($token)->getJson('/api/user');
        $profileResponse->assertOk()
            ->assertJsonPath('email_user', 'budi@example.com');

        $userInDb = User::where('email_user', 'budi@example.com')->first();
        $this->assertNotNull($userInDb->created_at);
        $this->assertNotNull($userInDb->updated_at);

        $logoutResponse = $this->withToken($token)->postJson('/api/logout');
        $logoutResponse->assertOk()
            ->assertJsonPath('message', 'Logout berhasil, token dihapus!');
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'secret12345',
        ]);

        $this->postJson('/api/login', [
            'email' => 'budi@example.com',
            'password' => 'wrongpassword',
        ])->assertStatus(422);
    }
}

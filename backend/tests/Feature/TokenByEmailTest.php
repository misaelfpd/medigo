<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenByEmailTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'test-service-key-0123456789abcdef';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.frontend.service_key' => self::KEY]);
    }

    private function makeUser(string $email, string $role = 'user'): User
    {
        return User::create([
            'nama_user' => 'Test User',
            'email_user' => $email,
            'password_user' => 'secret-password',
            'role_user' => $role,
        ]);
    }

    public function test_it_returns_a_working_token_with_the_service_key(): void
    {
        $user = $this->makeUser('pasien@example.com');

        $response = $this->postJson('/api/auth/token-by-email', [
            'email' => 'pasien@example.com',
        ], ['X-Service-Key' => self::KEY]);

        $response->assertOk()
            ->assertJsonPath('user.email_user', 'pasien@example.com')
            ->assertJsonMissingPath('user.password_user');

        $token = $response->json('access_token');
        $this->assertNotEmpty($token);

        $this->withToken($token)->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('id_user', $user->id_user);
    }

    public function test_it_rejects_requests_without_the_service_key(): void
    {
        $this->makeUser('pasien@example.com');

        $this->postJson('/api/auth/token-by-email', [
            'email' => 'pasien@example.com',
        ])->assertUnauthorized()->assertJsonMissingPath('access_token');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_it_rejects_a_wrong_service_key(): void
    {
        $this->makeUser('pasien@example.com');

        $this->postJson('/api/auth/token-by-email', [
            'email' => 'pasien@example.com',
        ], ['X-Service-Key' => 'wrong-key'])->assertUnauthorized();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_it_is_disabled_when_no_service_key_is_configured(): void
    {
        config(['services.frontend.service_key' => '']);
        $this->makeUser('pasien@example.com');

        $this->postJson('/api/auth/token-by-email', [
            'email' => 'pasien@example.com',
        ], ['X-Service-Key' => ''])->assertUnauthorized();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_it_never_mints_tokens_for_admin_accounts(): void
    {
        $this->makeUser('admin@example.com', 'admin');

        $this->postJson('/api/auth/token-by-email', [
            'email' => 'admin@example.com',
        ], ['X-Service-Key' => self::KEY])->assertForbidden();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_it_does_not_create_unknown_users(): void
    {
        $this->postJson('/api/auth/token-by-email', [
            'email' => 'baru@example.com',
            'name' => 'Baru',
        ], ['X-Service-Key' => self::KEY])->assertNotFound();

        $this->assertDatabaseMissing('user', ['email_user' => 'baru@example.com']);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_it_requires_a_valid_email(): void
    {
        $this->postJson('/api/auth/token-by-email', [
            'email' => 'not-an-email',
        ], ['X-Service-Key' => self::KEY])->assertUnprocessable();
    }
}

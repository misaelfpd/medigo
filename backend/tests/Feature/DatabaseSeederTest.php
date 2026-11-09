<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_factory_and_database_seeder_succeed(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('user', [
            'email_user' => 'test@example.com',
            'role_user' => 'user',
        ]);

        $this->assertDatabaseHas('user', [
            'email_user' => 'admin@medigo.test',
            'role_user' => 'admin',
        ]);

        $generated = User::factory()->count(3)->create();
        $this->assertCount(3, $generated);
    }
}

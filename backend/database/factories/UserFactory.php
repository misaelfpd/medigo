<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_user' => fake()->name(),
            'email_user' => fake()->unique()->safeEmail(),
            'password_user' => static::$password ??= Hash::make('password'),
            'role_user' => 'user',
        ];
    }

    /**
     * State untuk user dengan role admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_user' => 'admin',
        ]);
    }
}

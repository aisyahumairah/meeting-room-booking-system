<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
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
            'staff_number' => 'STF-' . strtoupper(Str::random(6)),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'must_change_password' => false,
            'last_login_at' => null,
            'department' => fake()->randomElement(['IT', 'HR', 'Finance', 'Marketing', 'Operations', 'Sales']),
            'phone' => fake()->phoneNumber(),
            'role' => 'regular_user',
            'status' => 'active',
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the user is a Regular User.
     */
    public function regularUser(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'regular_user',
        ]);
    }

    /**
     * Indicate that the user is an Administrator.
     */
    public function administrator(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'administrator',
        ]);
    }

    /**
     * Indicate that the user is a Director.
     */
    public function director(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'director',
        ]);
    }

    /**
     * Indicate that the user is a System Admin.
     */
    public function systemAdmin(): static
    {
        return $this->state(fn(array $attributes) => [
            'role' => 'system_admin',
        ]);
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the user does not need to change password.
     */
    public function passwordChanged(): static
    {
        return $this->state(fn(array $attributes) => [
            'must_change_password' => false,
        ]);
    }
}

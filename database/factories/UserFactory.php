<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\College;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
            'last_name' => fake()->lastName(),
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->lastName(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => Role::Student,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * A student enrolled in a program, with a student number and year level.
     */
    public function student(?Program $program = null): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::Student,
            'program_id' => $program?->id ?? Program::factory(),
            'year_level' => fake()->numberBetween(1, 4),
        ]);
    }

    /**
     * A counselor, optionally assigned to the given colleges.
     *
     * @param  iterable<College>  $colleges
     */
    public function counselor(iterable $colleges = []): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::Counselor,
        ])->afterCreating(function (User $user) use ($colleges): void {
            $user->colleges()->attach(collect($colleges)->pluck('id'));
        });
    }

    public function director(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::Director,
        ]);
    }
}

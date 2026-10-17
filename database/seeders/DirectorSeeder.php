<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Creates the single HDC Director account from DIRECTOR_EMAIL / DIRECTOR_PASSWORD in .env.
 * If the account already exists it is left untouched (its password is not reset).
 */
class DirectorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $config = config('hdc.director');

        if (blank($config['email']) || blank($config['password'])) {
            throw new RuntimeException('Set DIRECTOR_EMAIL and DIRECTOR_PASSWORD in .env before seeding the director account.');
        }

        if (User::where('email', $config['email'])->exists()) {
            $this->command?->info("Director account {$config['email']} already exists; skipped.");

            return;
        }

        $director = new User([
            'first_name' => $config['first_name'],
            'last_name' => $config['last_name'],
            'email' => $config['email'],
            'username' => 'director',
            'password' => $config['password'],
        ]);
        $director->role = Role::Director;
        $director->email_verified_at = now();
        $director->save();
    }
}

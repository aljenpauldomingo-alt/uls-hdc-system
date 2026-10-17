<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\College;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Test-only counselor assigned to CIT. DatabaseSeeder skips this in production.
 */
class SampleCounselorSeeder extends Seeder
{
    public const EMAIL = 'counselor@uls.test';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $counselor = User::firstOrNew(['email' => self::EMAIL]);
        $counselor->fill([
            'first_name' => 'Sample',
            'last_name' => 'Counselor',
            'username' => 'counselor',
            'password' => 'password',
        ]);
        $counselor->role = Role::Counselor;
        $counselor->email_verified_at ??= now();
        $counselor->save();

        $counselor->colleges()->sync(College::where('code', 'CIT')->pluck('id'));
    }
}

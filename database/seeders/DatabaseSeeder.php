<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CollegeProgramSeeder::class,
            DirectorSeeder::class,
        ]);

        if (! app()->isProduction()) {
            $this->call(SampleCounselorSeeder::class);
            $this->call(SampleStudentSeeder::class);
        }
    }
}

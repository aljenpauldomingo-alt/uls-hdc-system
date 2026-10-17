<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\CumulativeRecord;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Sample students across colleges/programs with a mix of record states.
 * Development only — DatabaseSeeder should never run this in production.
 * Safe to re-run: users are matched by email.
 */
class SampleStudentSeeder extends Seeder
{
    /**
     * @var list<array{first: string, last: string, email: string, program: string, year: int, status: string|null}>
     */
    private const STUDENTS = [
        ['first' => 'Juan', 'last' => 'Dela Cruz', 'email' => 'juan@uls.test', 'program' => 'BSIT', 'year' => 1, 'status' => 'submitted'],
        ['first' => 'Maria', 'last' => 'Santos', 'email' => 'maria@uls.test', 'program' => 'BSCS', 'year' => 2, 'status' => 'reviewed'],
        ['first' => 'Pedro', 'last' => 'Reyes', 'email' => 'pedro@uls.test', 'program' => 'BSCE', 'year' => 3, 'status' => 'draft'],
        ['first' => 'Ana', 'last' => 'Lopez', 'email' => 'ana@uls.test', 'program' => 'BSN', 'year' => 1, 'status' => null],
        ['first' => 'Jose', 'last' => 'Ramos', 'email' => 'jose@uls.test', 'program' => 'BSBA', 'year' => 4, 'status' => 'submitted'],
        ['first' => 'Liza', 'last' => 'Mendoza', 'email' => 'liza@uls.test', 'program' => 'BSEd', 'year' => 2, 'status' => 'draft'],
    ];

    public function run(): void
    {
        foreach (self::STUDENTS as $i => $data) {
            $program = Program::where('code', $data['program'])->first();

            if (! $program) {
                continue;
            }

            $student = User::firstOrNew(['email' => $data['email']]);
            $student->fill([
                'first_name' => $data['first'],
                'last_name' => $data['last'],
                'username' => strtolower($data['first']),
                'password' => Hash::make('password'),
                'program_id' => $program->id,
                'year_level' => $data['year'],
            ]);
            $student->role = Role::Student;
            $student->email_verified_at ??= now();
            $student->save();

            if ($data['status'] === null) {
                continue;
            }

            if ($student->cumulativeRecords()->exists()) {
                continue;
            }

            $factory = CumulativeRecord::factory()->for($student, 'student');

            $record = match ($data['status']) {
                'submitted' => $factory->submitted()->create(),
                'reviewed' => $factory->reviewed()->create(),
                default => $factory->create(),
            };

            $record->forceFill(['program_id' => $program->id])->save();
        }
    }
}

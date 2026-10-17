<?php

namespace Tests\Feature;

use App\Models\CumulativeRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyStepValidationTest extends TestCase
{
    use RefreshDatabase;

    private function recordFor(User $student): CumulativeRecord
    {
        return CumulativeRecord::factory()->for($student, 'student')->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function familyData(array $overrides = []): array
    {
        return array_merge([
            'parents' => [
                'father' => ['name' => 'Pedro Dela Cruz', 'age' => 50, 'occupation' => 'Farmer'],
                'mother' => ['name' => 'Maria Dela Cruz', 'age' => 48],
            ],
            'parents_status' => ['married_in_church'],
            'siblings' => [
                ['name' => 'Ana Dela Cruz', 'sex' => 'female', 'age' => 15],
            ],
            'home_language' => 'Ilocano',
        ], $overrides);
    }

    public function test_family_step_rejects_missing_parent_ages(): void
    {
        $student = User::factory()->student()->create();
        $record = $this->recordFor($student);

        $this->actingAs($student)
            ->put(route('student.record.update', [$record, 'family']), $this->familyData([
                'parents' => [
                    'father' => ['name' => 'Pedro Dela Cruz'],
                    'mother' => ['name' => 'Maria Dela Cruz'],
                ],
            ]))
            ->assertSessionHasErrors(['parents.father.age', 'parents.mother.age']);
    }

    public function test_family_step_rejects_missing_parent_names(): void
    {
        $student = User::factory()->student()->create();
        $record = $this->recordFor($student);

        $this->actingAs($student)
            ->put(route('student.record.update', [$record, 'family']), $this->familyData([
                'parents' => [
                    'father' => ['age' => 50],
                    'mother' => ['age' => 48],
                ],
            ]))
            ->assertSessionHasErrors(['parents.father.name', 'parents.mother.name']);
    }

    public function test_family_step_requires_parent_status_and_home_language(): void
    {
        $student = User::factory()->student()->create();
        $record = $this->recordFor($student);

        $this->actingAs($student)
            ->put(route('student.record.update', [$record, 'family']), $this->familyData([
                'parents_status' => [],
                'home_language' => '',
            ]))
            ->assertSessionHasErrors(['parents_status', 'home_language']);
    }

    public function test_family_step_accepts_complete_parent_data(): void
    {
        $student = User::factory()->student()->create();
        $record = $this->recordFor($student);

        $this->actingAs($student)
            ->put(route('student.record.update', [$record, 'family']), $this->familyData())
            ->assertSessionHasNoErrors();

        $record->refresh();
        $this->assertSame(50, $record->parent('father')->age);
        $this->assertSame(48, $record->parent('mother')->age);
        $this->assertSame('Ilocano', $record->home_language);
    }
}

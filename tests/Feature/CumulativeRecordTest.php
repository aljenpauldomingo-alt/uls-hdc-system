<?php

namespace Tests\Feature;

use App\Enums\RecordStatus;
use App\Models\CumulativeRecord;
use App\Models\Program;
use App\Models\User;
use App\Support\Form5;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CumulativeRecordTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function personalData(array $overrides = []): array
    {
        return array_merge([
            'last_name' => 'Dela Cruz',
            'first_name' => 'Juan',
            'middle_name' => 'Santos',
            'program_id' => Program::factory()->create()->id,
            'year_level' => 1,
            'home_address' => '12 Rizal St., Brgy. Victory Norte, Santiago City, Isabela',
            'sex' => 'male',
            'civil_status' => 'single',
            'contact_number' => '09171234567',
            'email' => 'juan@example.com',
            'birth_date' => '2008-01-15',
            'place_of_birth' => 'Santiago City',
            'nationality' => 'Filipino',
            'emergency_contact_name' => 'Maria Dela Cruz',
            'emergency_contact_address' => 'Santiago City',
            'emergency_contact_number' => '09181234567',
        ], $overrides);
    }

    private function recordFor(User $student, string $state = 'draft'): CumulativeRecord
    {
        $factory = CumulativeRecord::factory()->for($student, 'student');

        return match ($state) {
            'completed' => $factory->completed()->create(),
            'submitted' => $factory->submitted()->create(),
            'reviewed' => $factory->reviewed()->create(),
            default => $factory->create(),
        };
    }

    public function test_student_can_start_a_record_prefilled_from_their_account(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD extension is not installed.');
        }

        Storage::fake();
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->post(route('student.record.store'), [
            'month_of_entry' => 8,
            'school_year' => '2026-2027',
            'semester' => '1st',
            'student_type' => 'new',
            'photo' => UploadedFile::fake()->image('me.jpg', 200, 200),
        ]);

        $response->assertSessionHasNoErrors();
        $record = $student->cumulativeRecords()->sole();
        $response->assertRedirect(route('student.record.edit', [$record, 'personal']));

        $this->assertSame(RecordStatus::Draft, $record->status);
        $this->assertSame($student->first_name, $record->first_name);
        $this->assertSame($student->last_name, $record->last_name);
        $this->assertSame($student->program_id, $record->program_id);
        $this->assertSame($student->year_level, $record->year_level);
        $this->assertTrue($record->hasCompletedStep('entry'));
    }

    public function test_student_can_save_a_draft_step_and_continue(): void
    {
        $student = User::factory()->student()->create();
        $record = $this->recordFor($student);

        $response = $this->actingAs($student)
            ->put(route('student.record.update', [$record, 'personal']), $this->personalData());

        $response->assertSessionHasNoErrors()
            ->assertRedirect(route('student.record.edit', [$record, 'family']));

        $record->refresh();
        $this->assertSame(RecordStatus::Draft, $record->status);
        $this->assertSame('Santiago City', $record->place_of_birth);
        $this->assertTrue($record->hasCompletedStep('personal'));
        $this->assertNull($record->submitted_at);
    }

    public function test_conditional_fields_are_cleared_when_they_do_not_apply(): void
    {
        $student = User::factory()->student()->create();
        $record = $this->recordFor($student);

        $this->actingAs($student)->put(route('student.record.update', [$record, 'personal']), $this->personalData([
            'civil_status' => 'single',
            'spouse_name' => 'Should not be kept',
            'number_of_children' => 2,
        ]))->assertSessionHasNoErrors();

        $this->assertNull($record->refresh()->spouse_name);
        $this->assertNull($record->number_of_children);
    }

    public function test_spouse_name_is_required_when_married(): void
    {
        $student = User::factory()->student()->create();
        $record = $this->recordFor($student);

        $this->actingAs($student)
            ->put(route('student.record.update', [$record, 'personal']), $this->personalData(['civil_status' => 'married']))
            ->assertSessionHasErrors('spouse_name');
    }

    public function test_family_step_saves_parents_and_siblings_and_encrypts_sensitive_text(): void
    {
        $student = User::factory()->student()->create();
        $record = $this->recordFor($student);

        $this->actingAs($student)->put(route('student.record.update', [$record, 'family']), [
            'parents' => [
                'father' => ['name' => 'Pedro Dela Cruz', 'age' => 50, 'occupation' => 'Farmer'],
                'mother' => ['name' => 'Maria Dela Cruz', 'age' => 48],
            ],
            'parents_status' => ['married_in_church', 'working_abroad'],
            'family_view' => 'We are close but money is tight.',
            'family_concern' => 'My father works far away.',
            'siblings' => [
                ['name' => 'Ana Dela Cruz', 'sex' => 'female', 'age' => 15],
                ['name' => '', 'sex' => '', 'age' => ''], // blank row is ignored
                ['name' => 'Juan Dela Cruz', 'sex' => 'male', 'age' => 18],
            ],
            'home_language' => 'Ilocano',
        ])->assertSessionHasNoErrors();

        $record->refresh();
        $this->assertSame('Farmer', $record->parent('father')->occupation);
        $this->assertSame(['Ana Dela Cruz', 'Juan Dela Cruz'], $record->siblings->pluck('name')->all());
        $this->assertSame(['married_in_church', 'working_abroad'], $record->parents_status);
        $this->assertSame('We are close but money is tight.', $record->family_view);

        $raw = DB::table('cumulative_records')->where('id', $record->id)->first();
        $this->assertStringNotContainsString('money is tight', $raw->family_view);
        $this->assertStringNotContainsString('works far away', $raw->family_concern);
    }

    public function test_self_description_requires_at_least_five_checks(): void
    {
        $student = User::factory()->student()->create();
        $record = $this->recordFor($student);

        $this->actingAs($student)
            ->put(route('student.record.update', [$record, 'self']), [
                'self_description' => ['careful', 'serious', 'calm', 'quiet'],
            ])
            ->assertSessionHasErrors(['self_description' => 'Please check at least five.']);

        $this->actingAs($student)
            ->put(route('student.record.update', [$record, 'self']), [
                'self_description' => ['careful', 'serious', 'calm', 'quiet', 'leader'],
            ])
            ->assertSessionHasNoErrors();

        $this->assertCount(5, $record->refresh()->self_description);
    }

    public function test_student_cannot_submit_without_consent(): void
    {
        $student = User::factory()->student()->create();
        $record = $this->recordFor($student, 'completed');

        $this->actingAs($student)
            ->post(route('student.record.submit', $record), ['consent_name' => 'Juan Santos Dela Cruz'])
            ->assertSessionHasErrors('consent');

        $record->refresh();
        $this->assertSame(RecordStatus::Draft, $record->status);
        $this->assertFalse($record->consent_given);
        $this->assertNull($record->submitted_at);
    }

    public function test_student_cannot_submit_an_incomplete_record(): void
    {
        $student = User::factory()->student()->create();
        $record = $this->recordFor($student);

        $this->actingAs($student)
            ->post(route('student.record.submit', $record), ['consent' => '1', 'consent_name' => 'Juan Dela Cruz'])
            ->assertSessionHasErrors('record');

        $this->assertSame(RecordStatus::Draft, $record->refresh()->status);
    }

    public function test_student_can_submit_a_completed_record_with_consent(): void
    {
        $student = User::factory()->student()->create();
        $record = $this->recordFor($student, 'completed');

        $this->actingAs($student)
            ->post(route('student.record.submit', $record), ['consent' => '1', 'consent_name' => 'Juan Santos Dela Cruz'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('student.record.show', $record));

        $record->refresh();
        $this->assertSame(RecordStatus::Submitted, $record->status);
        $this->assertTrue($record->consent_given);
        $this->assertSame('Juan Santos Dela Cruz', $record->consent_name);
        $this->assertNotNull($record->consented_at);
        $this->assertNotNull($record->submitted_at);
        $this->assertSame(100, $record->progressPercent());
    }

    public function test_student_cannot_view_or_edit_another_students_record(): void
    {
        $owner = User::factory()->student()->create();
        $other = User::factory()->student()->create();
        $record = $this->recordFor($owner, 'completed');

        $this->actingAs($other)->get(route('student.record.show', $record))->assertForbidden();
        $this->actingAs($other)->get(route('student.record.edit', [$record, 'personal']))->assertForbidden();
        $this->actingAs($other)->get(route('student.record.photo', $record))->assertForbidden();
        $this->actingAs($other)
            ->put(route('student.record.update', [$record, 'personal']), $this->personalData(['first_name' => 'Hacked']))
            ->assertForbidden();
        $this->actingAs($other)
            ->post(route('student.record.submit', $record), ['consent' => '1', 'consent_name' => 'X'])
            ->assertForbidden();

        $this->assertNotSame('Hacked', $record->refresh()->first_name);
        $this->assertSame(RecordStatus::Draft, $record->status);
    }

    public function test_student_cannot_edit_after_the_record_is_reviewed(): void
    {
        $student = User::factory()->student()->create();
        $record = $this->recordFor($student, 'reviewed');
        $originalName = $record->first_name;

        $this->actingAs($student)
            ->put(route('student.record.update', [$record, 'personal']), $this->personalData(['first_name' => 'Changed']))
            ->assertForbidden();
        $this->actingAs($student)
            ->post(route('student.record.submit', $record), ['consent' => '1', 'consent_name' => 'X'])
            ->assertForbidden();
        $this->actingAs($student)
            ->get(route('student.record.edit', [$record, 'personal']))
            ->assertRedirect(route('student.record.show', $record));

        $this->actingAs($student)
            ->get(route('student.record.show', $record))
            ->assertOk()
            ->assertSee('It can no longer be edited')
            ->assertDontSee('Edit my record');

        $this->assertSame($originalName, $record->refresh()->first_name);
    }

    public function test_student_can_still_edit_a_submitted_record_before_review(): void
    {
        $student = User::factory()->student()->create();
        $record = $this->recordFor($student, 'submitted');

        $this->actingAs($student)
            ->put(route('student.record.update', [$record, 'personal']), $this->personalData(['first_name' => 'Updated']))
            ->assertSessionHasNoErrors();

        $record->refresh();
        $this->assertSame('Updated', $record->first_name);
        $this->assertSame(RecordStatus::Submitted, $record->status);
    }

    public function test_every_wizard_step_renders(): void
    {
        $student = User::factory()->student()->create();
        $record = $this->recordFor($student, 'completed');

        foreach (array_keys(Form5::STEPS) as $step) {
            $this->actingAs($student)
                ->get(route('student.record.edit', [$record, $step]))
                ->assertOk()
                ->assertSee(Form5::STEPS[$step]);
        }

        $this->actingAs($student)->get(route('student.record.create'))->assertRedirect();
        $this->actingAs(User::factory()->student()->create())->get(route('student.record.create'))->assertOk();
    }

    public function test_review_step_shows_summary_and_consent(): void
    {
        $student = User::factory()->student()->create();
        $record = $this->recordFor($student, 'completed');

        $this->actingAs($student)
            ->get(route('student.record.edit', [$record, 'review']))
            ->assertOk()
            ->assertSee($record->place_of_birth)
            ->assertSee('ULS Data Privacy Notice')
            ->assertSee('Submit my record');
    }

    public function test_photo_is_stored_privately_and_served_to_the_owner(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('GD extension is not installed.');
        }

        Storage::fake();
        $student = User::factory()->student()->create();
        $record = $this->recordFor($student);

        $this->actingAs($student)->put(route('student.record.update', [$record, 'entry']), [
            'month_of_entry' => 8,
            'school_year' => '2026-2027',
            'semester' => '1st',
            'student_type' => 'new',
            'photo' => UploadedFile::fake()->image('me.jpg', 200, 200),
        ])->assertSessionHasNoErrors();

        $record->refresh();
        Storage::assertExists($record->photo_path);
        $this->actingAs($student)->get(route('student.record.photo', $record))->assertOk();
    }

    public function test_dashboard_shows_the_right_button_for_each_state(): void
    {
        $student = User::factory()->student()->create();
        $this->actingAs($student)->get(route('student.dashboard'))->assertSee('Start my record');

        $record = $this->recordFor($student);
        $this->actingAs($student->fresh())->get(route('student.dashboard'))->assertSee('Continue my record')->assertSee('Draft');

        $record->forceFill(['status' => RecordStatus::Submitted, 'submitted_at' => now()])->save();
        $this->actingAs($student->fresh())->get(route('student.dashboard'))->assertSee('View my record')->assertSee('Submitted');
    }

    public function test_staff_cannot_use_the_student_form(): void
    {
        $record = $this->recordFor(User::factory()->student()->create());

        $this->actingAs(User::factory()->counselor()->create())
            ->get(route('student.record.show', $record))
            ->assertForbidden();
    }
}

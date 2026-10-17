<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AppointmentControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function staffRoles(): array
    {
        return [
            'counselor' => ['counselor'],
            'director' => ['director'],
        ];
    }

    public function test_students_can_view_the_request_form_and_only_their_appointments(): void
    {
        $program = Program::factory()->create();
        $student = User::factory()->student($program)->create();
        $otherStudent = User::factory()->student($program)->create();
        $counselor = User::factory()->counselor([$program->college])->create();
        $ownAppointment = $this->createAppointment($student, $counselor, ['purpose' => 'Own request']);
        $this->createAppointment($otherStudent, $counselor, ['purpose' => 'Private request']);

        $response = $this->actingAs($student)->get(route('appointments.index'));

        $response->assertOk()
            ->assertSee('Request an appointment')
            ->assertSee('My appointment requests')
            ->assertSee('Appointments')
            ->assertSee('Own request')
            ->assertDontSee('Private request')
            ->assertSee(route('appointments.index'));
        $this->assertModelExists($ownAppointment);
    }

    public function test_student_can_request_an_appointment_with_staff_responsible_for_their_college(): void
    {
        $program = Program::factory()->create();
        $student = User::factory()->student($program)->create();
        $counselor = User::factory()->counselor([$program->college])->create();
        $scheduledAt = now()->addDays(2)->format('Y-m-d\TH:i');

        $response = $this->actingAs($student)->post(route('appointments.store'), [
            'staff_id' => $counselor->id,
            'scheduled_at' => $scheduledAt,
            'purpose' => 'Discuss study concerns',
            'notes' => 'Please contact me after class.',
        ]);

        $response->assertRedirect(route('appointments.index'));
        $appointment = Appointment::query()->firstOrFail();
        $this->assertSame($student->id, $appointment->student_id);
        $this->assertSame($counselor->id, $appointment->staff_id);
        $this->assertSame('pending', $appointment->status);
        $this->assertSame('Discuss study concerns', $appointment->purpose);
        $this->assertSame('Please contact me after class.', $appointment->notes);
        $this->assertSame($scheduledAt, $appointment->scheduled_at->format('Y-m-d\TH:i'));
    }

    public function test_student_cannot_request_an_appointment_with_staff_outside_their_college(): void
    {
        $studentProgram = Program::factory()->create();
        $otherProgram = Program::factory()->create();
        $student = User::factory()->student($studentProgram)->create();
        $unassignedCounselor = User::factory()->counselor([$otherProgram->college])->create();

        $this->actingAs($student)
            ->post(route('appointments.store'), [
                'staff_id' => $unassignedCounselor->id,
                'scheduled_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
                'purpose' => 'Private request',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_counselor_only_sees_their_assigned_students_appointments(): void
    {
        $assignedProgram = Program::factory()->create();
        $otherProgram = Program::factory()->create();
        $counselor = User::factory()->counselor([$assignedProgram->college])->create();
        $otherCounselor = User::factory()->counselor([$otherProgram->college])->create();
        $assignedStudent = User::factory()->student($assignedProgram)->create();
        $otherStudent = User::factory()->student($otherProgram)->create();
        $this->createAppointment($assignedStudent, $counselor, ['purpose' => 'Assigned request']);
        $this->createAppointment($otherStudent, $otherCounselor, ['purpose' => 'Unassigned request']);

        $this->actingAs($counselor)
            ->get(route('appointments.index'))
            ->assertOk()
            ->assertSee('Appointment requests')
            ->assertSee('Assigned request')
            ->assertDontSee('Unassigned request')
            ->assertSee(route('appointments.index'));
    }

    public function test_staff_appointment_details_escape_student_input(): void
    {
        $program = Program::factory()->create();
        $counselor = User::factory()->counselor([$program->college])->create();
        $student = User::factory()->student($program)->create();
        $unsafePurpose = '<script>alert(1)</script>';
        $unsafeNotes = '<img src=x onerror=alert(1)>';
        $this->createAppointment($student, $counselor, [
            'purpose' => $unsafePurpose,
            'notes' => $unsafeNotes,
        ]);

        $this->actingAs($counselor)
            ->get(route('appointments.index'))
            ->assertOk()
            ->assertSee($unsafePurpose)
            ->assertSee($unsafeNotes)
            ->assertDontSee($unsafePurpose, false)
            ->assertDontSee($unsafeNotes, false);
    }

    public function test_counselor_can_update_only_their_assigned_appointment_status(): void
    {
        $program = Program::factory()->create();
        $otherProgram = Program::factory()->create();
        $counselor = User::factory()->counselor([$program->college])->create();
        $otherCounselor = User::factory()->counselor([$otherProgram->college])->create();
        $student = User::factory()->student($program)->create();
        $otherStudent = User::factory()->student($otherProgram)->create();
        $appointment = $this->createAppointment($student, $counselor);
        $otherAppointment = $this->createAppointment($otherStudent, $otherCounselor);

        $this->actingAs($counselor)
            ->patch(route('appointments.status', $appointment), ['status' => 'approved'])
            ->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'approved',
        ]);

        $this->actingAs($counselor)
            ->patch(route('appointments.status', $otherAppointment), ['status' => 'approved'])
            ->assertNotFound();

        $this->assertDatabaseHas('appointments', [
            'id' => $otherAppointment->id,
            'status' => 'pending',
        ]);
    }

    public function test_director_can_view_and_update_appointments_across_colleges(): void
    {
        $program = Program::factory()->create();
        $student = User::factory()->student($program)->create();
        $counselor = User::factory()->counselor([$program->college])->create();
        $director = User::factory()->director()->create();
        $appointment = $this->createAppointment($student, $counselor, ['purpose' => 'Director view']);

        $this->actingAs($director)
            ->get(route('appointments.index'))
            ->assertOk()
            ->assertSee('Appointments')
            ->assertSee(route('appointments.index'))
            ->assertSee('Director view')
            ->assertSee($student->name);

        $this->actingAs($director)
            ->patch(route('appointments.status', $appointment), ['status' => 'approved'])
            ->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'approved',
        ]);
    }

    #[DataProvider('staffRoles')]
    public function test_staff_cannot_submit_appointment_requests(string $role): void
    {
        $staff = User::factory()->{$role}()->create();

        $this->actingAs($staff)
            ->post(route('appointments.store'))
            ->assertForbidden();

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_student_cannot_update_appointment_status(): void
    {
        $program = Program::factory()->create();
        $student = User::factory()->student($program)->create();
        $counselor = User::factory()->counselor([$program->college])->create();
        $appointment = $this->createAppointment($student, $counselor);

        $this->actingAs($student)
            ->patch(route('appointments.status', $appointment), ['status' => 'approved'])
            ->assertForbidden();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'pending',
        ]);
    }

    public function test_appointment_pages_redirect_guests_to_login(): void
    {
        $this->get(route('appointments.index'))->assertRedirect(route('login'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createAppointment(User $student, User $staff, array $overrides = []): Appointment
    {
        return Appointment::create(array_merge([
            'student_id' => $student->id,
            'staff_id' => $staff->id,
            'scheduled_at' => now()->addDay(),
            'purpose' => 'Appointment request',
            'status' => 'pending',
        ], $overrides));
    }
}

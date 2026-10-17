<?php

namespace App\Http\Controllers;

use App\Models\AccessLog;
use App\Models\Appointment;
use App\Models\User;
use App\Notifications\AppointmentRequested;
use App\Notifications\AppointmentStatusUpdated;
use Illuminate\Support\Facades\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AppointmentController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $status = $request->string('status')->toString() ?: null;
        $search = mb_substr(trim($request->string('q')->toString()), 0, 100) ?: null;
        $date = $request->date('date')?->toDateString();

        $applyFilters = function (Builder $query) use ($status, $search, $date): void {
            $query
                ->when($status, fn (Builder $q, string $s) => $q->where('status', $s))
                ->when($date, fn (Builder $q, string $d) => $q->whereDate('scheduled_at', $d))
                ->when($search, function (Builder $q, string $term): void {
                    $like = '%'.addcslashes($term, '\\%_').'%';
                    $q->where(function (Builder $inner) use ($like): void {
                        $inner->whereHas('student', fn (Builder $s) => $s->where('users.name', 'like', $like))
                            ->orWhereHas('staff', fn (Builder $s) => $s->where('users.name', 'like', $like))
                            ->orWhere('purpose', 'like', $like);
                    });
                });
        };

        if ($user->isStudent()) {
            $appointments = Appointment::query()
                ->where('student_id', $user->id)
                ->with('staff');
            $applyFilters($appointments);
            $appointments = $appointments->latest()->get();

            $staff = User::query()
                ->staffResponsibleFor($user->program?->college_id)
                ->orderBy('name')
                ->get();
        } elseif ($user->isCounselor()) {
            $appointments = Appointment::query()
                ->where('staff_id', $user->id)
                ->whereHas('student', function (Builder $students) use ($user): void {
                    $students->studentsVisibleTo($user);
                })
                ->with(['student.program.college', 'staff']);
            $applyFilters($appointments);
            $appointments = $appointments->latest()->get();

            $staff = collect();
        } else {
            $appointments = Appointment::query()
                ->with(['student.program.college', 'staff']);
            $applyFilters($appointments);
            $appointments = $appointments->latest()->get();

            $staff = collect();
        }

        $filters = ['status' => $status, 'q' => $search];

        return view('appointments.index', compact('appointments', 'staff', 'filters'));
    }

    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();
        $status = $request->string('status')->toString() ?: null;
        $search = mb_substr(trim($request->string('q')->toString()), 0, 100) ?: null;
        $date = $request->date('date')?->toDateString();

        if ($user->isStudent()) {
            $query = Appointment::query()->where('student_id', $user->id)->with('staff');
        } elseif ($user->isCounselor()) {
            $query = Appointment::query()
                ->where('staff_id', $user->id)
                ->whereHas('student', function (Builder $students) use ($user): void {
                    $students->studentsVisibleTo($user);
                })
                ->with(['student.program.college', 'staff']);
        } else {
            $query = Appointment::query()->with(['student.program.college', 'staff']);
        }

        $query
            ->when($status, fn (Builder $q, string $s) => $q->where('status', $s))
            ->when($date, fn (Builder $q, string $d) => $q->whereDate('scheduled_at', $d))
            ->when($search, function (Builder $q, string $term): void {
                $like = '%'.addcslashes($term, '\\%_').'%';
                $q->where(function (Builder $inner) use ($like): void {
                    $inner->whereHas('student', fn (Builder $s) => $s->where('users.name', 'like', $like))
                        ->orWhereHas('staff', fn (Builder $s) => $s->where('users.name', 'like', $like))
                        ->orWhere('purpose', 'like', $like);
                });
            });

        $filename = 'appointments-'.str()->slug($status ? "status-{$status}" : 'all').'-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['ID', 'Student', 'Program', 'College', 'Staff', 'Date & Time', 'Purpose', 'Notes', 'Status']);

            $query->chunk(500, function ($appointments) use ($out): void {
                foreach ($appointments as $appointment) {
                    fputcsv($out, [
                        $appointment->id,
                        $appointment->student?->name,
                        $appointment->student?->program?->code,
                        $appointment->student?->program?->college?->name,
                        $appointment->staff?->name,
                        $appointment->scheduled_at?->format('Y-m-d H:i A'),
                        $appointment->purpose,
                        $appointment->notes,
                        $appointment->status,
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function show(Request $request, Appointment $appointment): View
    {
        $user = $request->user();

        // Authorization: Student can view own, Staff can view assigned, Director can view all.
        if ($user->isStudent()) {
            abort_unless($appointment->student_id === $user->id, 403);
        } elseif (! $user->isDirector()) {
            abort_unless(
                $user->isCounselor()
                    && $appointment->staff_id === $user->id
                    && $appointment->student?->isVisibleTo($user),
                403,
            );
        }

        $appointment->load(['student.program.college', 'staff']);

        return view('appointments.show', compact('appointment'));
    }

    public function store(Request $request): RedirectResponse
    {
        $student = $request->user();
        abort_unless($student->isStudent(), 403);

        $validated = $request->validate([
            'staff_id' => ['required', 'integer', 'exists:users,id'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'purpose' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $staffMember = User::query()
            ->staffResponsibleFor($student->program?->college_id)
            ->findOrFail($validated['staff_id']);

        $appointment = Appointment::create([
            'student_id' => $student->id,
            'staff_id' => $staffMember->id,
            'scheduled_at' => $validated['scheduled_at'],
            'purpose' => $validated['purpose'],
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
        ]);

        $staffMember->notify(new AppointmentRequested($appointment));

        return redirect()->route('appointments.index')->with('status', 'Appointment requested successfully.');
    }

    public function updateStatus(Request $request, Appointment $appointment): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isDirector()) {
            abort_unless(
                $user->isCounselor()
                    && $appointment->staff_id === $user->id
                    && $appointment->student?->isVisibleTo($user),
                404,
            );
        }

        $validated = $request->validate([
            'status' => ['required', 'in:pending,approved,completed,rejected'],
        ]);

        $appointment->update(['status' => $validated['status']]);

        $appointment->student->notify(new AppointmentStatusUpdated($appointment));

        AccessLog::logAction($user->id, AccessLog::ACTION_APPOINTMENT, "Appointment #{$appointment->id} marked {$validated['status']}", $request->ip());

        return redirect()->route('appointments.index')->with('status', 'Appointment status updated successfully.');
    }
}

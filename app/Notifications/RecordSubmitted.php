<?php

namespace App\Notifications;

use App\Models\CumulativeRecord;
use Illuminate\Notifications\Notification;

/**
 * Tells staff a student submitted Form 5. Deliberately contains NO form answers:
 * only the student's name, program and a link to the record.
 */
class RecordSubmitted extends Notification
{
    public function __construct(public CumulativeRecord $record) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * @return array{record_id: int, student_name: string, program: string|null, url: string, message: string}
     */
    public function toArray(object $notifiable): array
    {
        $student = $this->record->student;
        $program = $student->program?->code;

        return [
            'record_id' => $this->record->id,
            'student_name' => $student->name,
            'program' => $program,
            'url' => route('staff.records.show', $this->record, absolute: false),
            'message' => $program
                ? "{$student->name} ({$program}) submitted their Student Cumulative Record."
                : "{$student->name} submitted their Student Cumulative Record.",
        ];
    }
}

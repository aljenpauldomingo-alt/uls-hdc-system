<?php

namespace App\Notifications;

use App\Models\CumulativeRecord;
use Illuminate\Notifications\Notification;

/**
 * Tells the director a submitted Form 5 includes an urgent self-reported concern. Deliberately
 * contains ONLY the student's name and a link: never which concern was answered.
 */
class RecordNeedsFollowUp extends Notification
{
    public function __construct(public CumulativeRecord $record) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{record_id: int, student_name: string, url: string, message: string}
     */
    public function toArray(object $notifiable): array
    {
        $studentName = $this->record->student->name;

        return [
            'record_id' => $this->record->id,
            'student_name' => $studentName,
            'url' => route('staff.records.show', $this->record, absolute: false),
            'message' => "{$studentName}: Student Cumulative Record needs follow-up.",
        ];
    }
}

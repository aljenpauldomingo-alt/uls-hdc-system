<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Notifications\Notification;

class AppointmentRequested extends Notification
{
    public function __construct(public Appointment $appointment) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        $student = $this->appointment->student;
        $date = $this->appointment->scheduled_at->format('M j, Y g:i A');

        return [
            'appointment_id' => $this->appointment->id,
            'student_name' => $student->name,
            'url' => route('appointments.show', $this->appointment, absolute: false),
            'message' => "{$student->name} requested an appointment for {$date}.",
        ];
    }
}
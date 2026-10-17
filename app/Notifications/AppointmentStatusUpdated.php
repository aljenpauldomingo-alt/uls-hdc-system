<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Notifications\Notification;

class AppointmentStatusUpdated extends Notification
{
    public function __construct(public Appointment $appointment) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        $status = ucfirst($this->appointment->status);
        $staff = $this->appointment->staff;
        $date = $this->appointment->scheduled_at->format('M j, Y g:i A');

        return [
            'appointment_id' => $this->appointment->id,
            'status' => $this->appointment->status,
            'url' => route('appointments.show', $this->appointment, absolute: false),
            'message' => "Your appointment with {$staff->name} for {$date} has been {$status}.",
        ];
    }
}
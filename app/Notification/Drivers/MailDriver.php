<?php

namespace App\Notification\Drivers;

use App\Mail\BookingConfirmedMail;
use App\Models\Appointment;
use App\Notification\Contracts\NotificationDriver;
use App\Notification\Exceptions\InvalidNotificationEventException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailDriver implements NotificationDriver
{
    public function send(Appointment $appointment, string $event): void
    {
        $mailable = match ($event) {
            'booking.confirmed' => new BookingConfirmedMail($appointment),
            default => throw new InvalidNotificationEventException($event),
        };

        $email = $appointment->customer?->email;

        if (! $email) {
            Log::warning('SendBookingConfirmation: skipped — appointment #'.$appointment->id.'has no customer email.');

            return;
        }

        Mail::to($email)->send($mailable);
    }
}

<?php

namespace App\Notification\Drivers;

use App\Models\Appointment;
use App\Notification\Contracts\NotificationDriver;

class MailDriver implements NotificationDriver
{
    public function send(Appointment $appointment, string $event): void
    {
        // implemented in Task 4
    }
}

<?php

namespace App\Notification\Drivers;

use App\Models\Appointment;
use App\Notification\Contracts\NotificationDriver;

class NullDriver implements NotificationDriver
{
    public function send(Appointment $appointment, string $event): void
    {
        // intentional no-op
    }
}

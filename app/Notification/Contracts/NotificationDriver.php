<?php

namespace App\Notification\Contracts;

use App\Models\Appointment;

interface NotificationDriver
{
    public function send(Appointment $appointment, string $event): void;
}

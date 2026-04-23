<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Notification\Contracts\NotificationDriver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Queue\Queueable;

class SendBookingConfirmation implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public readonly Appointment $appointment)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationDriver $driver): void
    {
        $this->appointment->loadMissing(['service', 'staff', 'customer', 'tenant']);

        $driver->send($this->appointment, 'booking.confirmed');
    }
}

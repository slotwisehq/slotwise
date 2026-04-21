<?php

namespace App\Jobs;

use App\Models\Appointment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Queue\Queueable;

class SendBookingConfirmation implements ShouldQueue, ShouldQueueAfterCommit
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public readonly Appointment $appointment) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
    }
}

<?php

namespace App\Jobs;

use App\Models\Appointment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendBookingConfirmation implements ShouldQueue
{
    use Queueable;

    /** Dispatch only after the DB transaction commits */
    public bool $afterCommit = true;

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
    public function handle(): void
    {
        //
    }
}

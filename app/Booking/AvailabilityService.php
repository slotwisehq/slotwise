<?php

namespace App\Booking;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Schedule;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AvailabilityService
{
    /**
     * Return available slot start times for a staff member on a given date.
     *
     * @return Collection<int, Carbon>
     */
    public function getSlots(int $staffId, int $serviceId, Carbon $date): Collection
    {
        $schedule = Schedule::where('staff_id', $staffId)
            ->where('day_of_week', $date->dayOfWeek)
            ->first();

        if ($schedule === null) {
            return collect();
        }

        $service = Service::find($serviceId);

        if ($service === null) {
            return collect();
        }

        $durationMinutes = $service->duration_minutes;

        $windowStart = $date->copy()->setTimeFromTimeString($schedule->start_time);
        $windowEnd = $date->copy()->setTimeFromTimeString($schedule->end_time);

        // Cross-midnight schedules are out of scope for v0.1
        if ($windowEnd->lessThanOrEqualTo($windowStart)) {
            return collect();
        }

        $appointments = Appointment::where('staff_id', $staffId)
            ->where('status', '!=', AppointmentStatus::Cancelled->value)
            ->where('starts_at', '<', $windowEnd)
            ->where('ends_at', '>', $windowStart)
            ->get(['starts_at', 'ends_at']);

        $slots = collect();
        $cursor = $windowStart->copy();

        while ($cursor->copy()->addMinutes($durationMinutes)->lessThanOrEqualTo($windowEnd)) {
            $slotEnd = $cursor->copy()->addMinutes($durationMinutes);

            $isBlocked = $appointments->contains(
                fn (Appointment $appt) => $cursor->lessThan($appt->ends_at)
                    && $slotEnd->greaterThan($appt->starts_at)
            );

            if (! $isBlocked) {
                $slots->push($cursor->copy());
            }

            $cursor->addMinutes($durationMinutes);
        }

        return $slots;
    }
}

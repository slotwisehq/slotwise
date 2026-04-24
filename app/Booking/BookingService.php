<?php

namespace App\Booking;

use App\Booking\Exceptions\PlanLimitException;
use App\Booking\Exceptions\SlotUnavailableException;
use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use App\Jobs\SendBookingConfirmation;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\DB;
use Throwable;

class BookingService
{
    /** @var Closure|null Test-only hook: fires inside the transaction after the Staff row lock is acquired. */
    public ?Closure $afterLock = null;

    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly PlanLimitChecker $limits,
    ) {
        //
    }

    /**
     * Create a confirmed Appointment for the given slot.
     *
     * @param  array{tenant_id: int, staff_id: int, service_id: int, customer_id: int, starts_at: Carbon, notes?: string|null}  $data
     *
     * @throws PlanLimitException
     * @throws SlotUnavailableException
     * @throws Throwable
     */
    public function create(array $data): Appointment
    {
        $tenant = Tenant::findOrFail($data['tenant_id']);

        $this->limits->assertCanCreateBooking($tenant);

        $startsAt = $data['starts_at'];

        // Phase 1: quick pre-flight outside the transaction.
        // Catches invalid slots (wrong hour, no schedule, already taken) before acquiring any lock.
        $availableSlots = $this->availability->getSlots(
            $data['staff_id'],
            $data['service_id'],
            $startsAt->copy()->startOfDay(),
        );

        if (! $availableSlots->contains(fn (Carbon $slot) => $slot->equalTo($startsAt))) {
            throw new SlotUnavailableException($startsAt, $data['staff_id'], $data['service_id']);
        }

        return DB::transaction(function () use ($data, $startsAt) {
            // Serialize concurrent bookings for this staff member by locking the Staff row.
            Staff::where('id', $data['staff_id'])->lockForUpdate()->firstOrFail();

            // Test injection point - null in production; set by the concurrency test.
            if ($this->afterLock !== null) {
                ($this->afterLock)();
            }

            $service = Service::findOrFail($data['service_id']);
            $endsAt = $startsAt->copy()->addMinutes($service->duration_minutes);

            // Phase 2: re-check for overlap inside the transaction (guards the TOCTOU window).
            $conflict = Appointment::where('staff_id', $data['staff_id'])
                ->where('status', '!=', AppointmentStatus::Cancelled->value)
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->exists();

            if ($conflict) {
                throw new SlotUnavailableException($startsAt, $data['staff_id'], $data['service_id']);
            }

            $appointment = Appointment::create([
                'tenant_id' => $data['tenant_id'],
                'staff_id' => $data['staff_id'],
                'service_id' => $service->id,
                'customer_id' => $data['customer_id'],
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => AppointmentStatus::Confirmed,
                'payment_status' => PaymentStatus::Unpaid,
                'notes' => $data['notes'] ?? null,
            ]);

            // Dispatched with afterCommit = true (set on the job class) - only fires on success.
            SendBookingConfirmation::dispatch($appointment);

            return $appointment;
        });
    }
}

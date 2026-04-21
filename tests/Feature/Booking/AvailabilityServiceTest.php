<?php

use App\Booking\AvailabilityService;
use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use Carbon\Carbon;
use Tests\Concerns\HasTenantContext;

uses(HasTenantContext::class);

beforeEach(function () {
    $this->setUpTenantContext();
    $this->service = new AvailabilityService;
});

afterEach(fn () => $this->tearDownTenantContext());

// ─────────────────────────────────────────────────────────────────────────────
// Helper: creates a Staff + Schedule for a given day/window, owned by $this->tenant
// ─────────────────────────────────────────────────────────────────────────────
function makeStaffWithSchedule(
    int $dayOfWeek,
    string $startTime,
    string $endTime,
    Tenant $tenant,
): Staff {
    $staff = Staff::factory()->for($tenant)->create();

    Schedule::factory()->forStaff($staff)->create([
        'day_of_week' => $dayOfWeek,
        'start_time' => $startTime,
        'end_time' => $endTime,
    ]);

    return $staff;
}

// ─────────────────────────────────────────────────────────────────────────────
// Helper: creates an active Appointment for the given staff/service/datetime
// ─────────────────────────────────────────────────────────────────────────────
function bookSlot(
    Staff $staff,
    Service $service,
    string $startsAt,
    string $endsAt,
    AppointmentStatus $status = AppointmentStatus::Confirmed,
): Appointment {
    $customer = Customer::factory()->for($staff->tenant)->create();

    return Appointment::factory()->create([
        'tenant_id' => $staff->tenant_id,
        'staff_id' => $staff->id,
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'starts_at' => Carbon::parse($startsAt),
        'ends_at' => Carbon::parse($endsAt),
        'status' => $status,
        'payment_status' => PaymentStatus::Unpaid,
        'notes' => null,
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// Happy path
// ─────────────────────────────────────────────────────────────────────────────
describe('happy path', function () {
    it('returns 8 slots for a 9am–5pm schedule, 60-min service, no bookings', function () {
        $date = Carbon::parse('2025-01-06'); // Monday
        $svc = Service::factory()->for($this->tenant)->create(['duration_minutes' => 60]);
        $staff = makeStaffWithSchedule($date->dayOfWeek, '09:00:00', '17:00:00', $this->tenant);

        $slots = $this->service->getSlots($staff->id, $svc->id, $date);

        expect($slots)->toHaveCount(8)
            ->and($slots->first()->format('H:i'))->toBe('09:00')
            ->and($slots->last()->format('H:i'))->toBe('16:00');
    });

    it('excludes the booked slot and returns 7 slots when one 60-min booking exists at 10:00', function () {
        $date = Carbon::parse('2025-01-06');
        $svc = Service::factory()->for($this->tenant)->create(['duration_minutes' => 60]);
        $staff = makeStaffWithSchedule($date->dayOfWeek, '09:00:00', '17:00:00', $this->tenant);

        bookSlot($staff, $svc, '2025-01-06 10:00:00', '2025-01-06 11:00:00');

        $slots = $this->service->getSlots($staff->id, $svc->id, $date);
        $startTimes = $slots->map(fn (Carbon $c) => $c->format('H:i'))->all();

        expect($slots)->toHaveCount(7)
            ->and($startTimes)->not->toContain('10:00')
            ->and($startTimes)->toContain('09:00')
            ->and($startTimes)->toContain('11:00');
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Edge cases
// ─────────────────────────────────────────────────────────────────────────────
describe('edge cases', function () {
    it('returns an empty collection when staff has no schedule for the requested day', function () {
        $date = Carbon::parse('2025-01-06'); // Monday = dayOfWeek 1
        $svc = Service::factory()->for($this->tenant)->create(['duration_minutes' => 60]);
        $staff = Staff::factory()->for($this->tenant)->create();

        // Schedule only exists for Tuesday (dayOfWeek 2), not Monday
        Schedule::factory()->forStaff($staff)->create([
            'day_of_week' => 2,
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
        ]);

        $slots = $this->service->getSlots($staff->id, $svc->id, $date);

        expect($slots)->toBeEmpty();
    });

    it('returns an empty collection when all slots are fully booked', function () {
        $date = Carbon::parse('2025-01-06');
        // 2-hour window, 60-min service → 2 possible slots (09:00, 10:00)
        $svc = Service::factory()->for($this->tenant)->create(['duration_minutes' => 60]);
        $staff = makeStaffWithSchedule($date->dayOfWeek, '09:00:00', '11:00:00', $this->tenant);

        bookSlot($staff, $svc, '2025-01-06 09:00:00', '2025-01-06 10:00:00');
        bookSlot($staff, $svc, '2025-01-06 10:00:00', '2025-01-06 11:00:00');

        $slots = $this->service->getSlots($staff->id, $svc->id, $date);

        expect($slots)->toBeEmpty();
    });

    it('blocks the first slot when appointment sits exactly at the schedule window start', function () {
        $date = Carbon::parse('2025-01-06');
        $svc = Service::factory()->for($this->tenant)->create(['duration_minutes' => 60]);
        $staff = makeStaffWithSchedule($date->dayOfWeek, '09:00:00', '11:00:00', $this->tenant);

        // Appointment starts exactly at window open (09:00)
        bookSlot($staff, $svc, '2025-01-06 09:00:00', '2025-01-06 10:00:00');

        $slots = $this->service->getSlots($staff->id, $svc->id, $date);
        $startTimes = $slots->map(fn (Carbon $c) => $c->format('H:i'))->all();

        expect($slots)->toHaveCount(1)
            ->and($startTimes)->not->toContain('09:00')
            ->and($startTimes)->toContain('10:00');
    });

    it('does not offer a phantom slot after an appointment that ends exactly at the schedule window end', function () {
        $date = Carbon::parse('2025-01-06');
        $svc = Service::factory()->for($this->tenant)->create(['duration_minutes' => 60]);
        $staff = makeStaffWithSchedule($date->dayOfWeek, '09:00:00', '11:00:00', $this->tenant);

        // Appointment ends exactly at window close (11:00)
        bookSlot($staff, $svc, '2025-01-06 10:00:00', '2025-01-06 11:00:00');

        $slots = $this->service->getSlots($staff->id, $svc->id, $date);
        $startTimes = $slots->map(fn (Carbon $c) => $c->format('H:i'))->all();

        // Only 09:00 is available; no slot at 11:00 (would require 12:00 end, past window)
        expect($slots)->toHaveCount(1)
            ->and($startTimes)->toContain('09:00')
            ->and($startTimes)->not->toContain('11:00');
    });

    it('does not offer a slot when the service duration is longer than the remaining gap', function () {
        $date = Carbon::parse('2025-01-06');
        // 90-min service, 2-hour window, one 60-min booking at 09:00
        // Remaining gap: 10:00–11:00 = 60 min < 90 min → no slot
        $svc = Service::factory()->for($this->tenant)->create(['duration_minutes' => 90]);
        $staff = makeStaffWithSchedule($date->dayOfWeek, '09:00:00', '11:00:00', $this->tenant);

        bookSlot($staff, $svc, '2025-01-06 09:00:00', '2025-01-06 10:00:00');

        $slots = $this->service->getSlots($staff->id, $svc->id, $date);

        expect($slots)->toBeEmpty();
    });

    it('does not offer a slot between two back-to-back appointments', function () {
        $date = Carbon::parse('2025-01-06');
        $svc = Service::factory()->for($this->tenant)->create(['duration_minutes' => 60]);
        $staff = makeStaffWithSchedule($date->dayOfWeek, '09:00:00', '13:00:00', $this->tenant);

        // 09:00–11:00 and 11:00–13:00 — no gap between them
        bookSlot($staff, $svc, '2025-01-06 09:00:00', '2025-01-06 11:00:00');
        bookSlot($staff, $svc, '2025-01-06 11:00:00', '2025-01-06 13:00:00');

        $slots = $this->service->getSlots($staff->id, $svc->id, $date);

        expect($slots)->toBeEmpty();
    });

    it('returns an empty collection for cross-midnight schedules (out of scope v0.1)', function () {
        $date = Carbon::parse('2025-01-06');
        $svc = Service::factory()->for($this->tenant)->create(['duration_minutes' => 60]);
        $staff = Staff::factory()->for($this->tenant)->create();

        // end_time < start_time — cross-midnight
        Schedule::factory()->forStaff($staff)->create([
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '22:00:00',
            'end_time' => '02:00:00',
        ]);

        $slots = $this->service->getSlots($staff->id, $svc->id, $date);

        expect($slots)->toBeEmpty();
    });

    it('treats cancelled appointments as non-blocking', function () {
        $date = Carbon::parse('2025-01-06');
        $svc = Service::factory()->for($this->tenant)->create(['duration_minutes' => 60]);
        $staff = makeStaffWithSchedule($date->dayOfWeek, '09:00:00', '11:00:00', $this->tenant);

        // A cancelled appointment should NOT block the slot
        bookSlot($staff, $svc, '2025-01-06 09:00:00', '2025-01-06 10:00:00', AppointmentStatus::Cancelled);

        $slots = $this->service->getSlots($staff->id, $svc->id, $date);

        // Both slots available — cancelled booking is invisible
        expect($slots)->toHaveCount(2);
    });
});

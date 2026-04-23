<?php

use App\Booking\BookingService;
use App\Booking\Exceptions\SlotUnavailableException;
use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use App\Jobs\SendBookingConfirmation;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\HasTenantContext;

uses(HasTenantContext::class);

// ─────────────────────────────────────────────────────────────────────────────
// Shared setup
// ─────────────────────────────────────────────────────────────────────────────

beforeEach(function () {
    $this->setUpTenantContext();
    $this->bookingService = app(BookingService::class);

    // Fixed Monday used across all tests
    $this->date = Carbon::parse('2025-01-06');
});

afterEach(fn () => $this->tearDownTenantContext());

// ─────────────────────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────────────────────

function makeStaffWithScheduleForBooking(
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
// Happy path
// ─────────────────────────────────────────────────────────────────────────────

describe('happy path', function () {
    it('creates an Appointment with status Confirmed and correct ends_at', function () {
        Bus::fake();

        $svc = Service::factory()->for($this->tenant)->create(['duration_minutes' => 60]);
        $staff = makeStaffWithScheduleForBooking($this->date->dayOfWeek, '09:00:00', '17:00:00', $this->tenant);
        $customer = Customer::factory()->for($this->tenant)->create();

        $appointment = $this->bookingService->create([
            'tenant_id' => $this->tenant->id,
            'staff_id' => $staff->id,
            'service_id' => $svc->id,
            'customer_id' => $customer->id,
            'starts_at' => Carbon::parse('2025-01-06 10:00:00'),
        ]);

        expect($appointment)->toBeInstanceOf(Appointment::class)
            ->and($appointment->status)->toBe(AppointmentStatus::Confirmed)
            ->and($appointment->payment_status)->toBe(PaymentStatus::Unpaid)
            ->and($appointment->ends_at->format('H:i'))->toBe('11:00')
            ->and($appointment->starts_at->format('H:i'))->toBe('10:00')
            ->and(Appointment::count())->toBe(1);
    });

    it('dispatches SendBookingConfirmation after a successful create', function () {
        Bus::fake();

        $svc = Service::factory()->for($this->tenant)->create(['duration_minutes' => 60]);
        $staff = makeStaffWithScheduleForBooking($this->date->dayOfWeek, '09:00:00', '17:00:00', $this->tenant);
        $customer = Customer::factory()->for($this->tenant)->create();

        $appointment = $this->bookingService->create([
            'tenant_id' => $this->tenant->id,
            'staff_id' => $staff->id,
            'service_id' => $svc->id,
            'customer_id' => $customer->id,
            'starts_at' => Carbon::parse('2025-01-06 10:00:00'),
        ]);

        Bus::assertDispatched(SendBookingConfirmation::class, function ($job) use ($appointment) {
            return $job->appointment->is($appointment);
        });
    });

    it('computes ends_at correctly for various service durations', function (int $minutes) {
        Bus::fake();

        $svc = Service::factory()->for($this->tenant)->create(['duration_minutes' => $minutes]);
        $staff = makeStaffWithScheduleForBooking($this->date->dayOfWeek, '09:00:00', '17:00:00', $this->tenant);
        $customer = Customer::factory()->for($this->tenant)->create();

        $startsAt = Carbon::parse('2025-01-06 09:00:00');

        $appointment = $this->bookingService->create([
            'tenant_id' => $this->tenant->id,
            'staff_id' => $staff->id,
            'service_id' => $svc->id,
            'customer_id' => $customer->id,
            'starts_at' => $startsAt->copy(),
        ]);

        expect($appointment->ends_at->equalTo($startsAt->copy()->addMinutes($minutes)))->toBeTrue();
    })->with([30, 60, 90, 120]);
});

// ─────────────────────────────────────────────────────────────────────────────
// Slot unavailability
// ─────────────────────────────────────────────────────────────────────────────

describe('SlotUnavailableException', function () {
    it('throws when the slot is already booked', function () {
        Bus::fake();

        $svc = Service::factory()->for($this->tenant)->create(['duration_minutes' => 60]);
        $staff = makeStaffWithScheduleForBooking($this->date->dayOfWeek, '09:00:00', '17:00:00', $this->tenant);
        $customer = Customer::factory()->for($this->tenant)->create();

        // Seed a confirmed appointment at the target slot
        Appointment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'staff_id' => $staff->id,
            'service_id' => $svc->id,
            'customer_id' => $customer->id,
            'starts_at' => Carbon::parse('2025-01-06 10:00:00'),
            'ends_at' => Carbon::parse('2025-01-06 11:00:00'),
            'status' => AppointmentStatus::Confirmed,
            'payment_status' => PaymentStatus::Unpaid,
            'notes' => null,
        ]);

        expect(fn () => $this->bookingService->create([
            'tenant_id' => $this->tenant->id,
            'staff_id' => $staff->id,
            'service_id' => $svc->id,
            'customer_id' => $customer->id,
            'starts_at' => Carbon::parse('2025-01-06 10:00:00'),
        ]))->toThrow(SlotUnavailableException::class)
            ->and(Appointment::count())->toBe(1);

        // Exactly one appointment exists — the pre-seeded one; no new record created
    });

    it('throws when starts_at is outside the staff schedule hours', function () {
        Bus::fake();

        $svc = Service::factory()->for($this->tenant)->create(['duration_minutes' => 60]);
        $staff = makeStaffWithScheduleForBooking($this->date->dayOfWeek, '09:00:00', '17:00:00', $this->tenant);
        $customer = Customer::factory()->for($this->tenant)->create();

        // 18:00 is past the 17:00 schedule end
        expect(fn () => $this->bookingService->create([
            'tenant_id' => $this->tenant->id,
            'staff_id' => $staff->id,
            'service_id' => $svc->id,
            'customer_id' => $customer->id,
            'starts_at' => Carbon::parse('2025-01-06 18:00:00'),
        ]))->toThrow(SlotUnavailableException::class)
            ->and(Appointment::count())->toBe(0);
    });

    it('rolls back the transaction cleanly — no orphaned records', function () {
        Bus::fake();

        $svc = Service::factory()->for($this->tenant)->create(['duration_minutes' => 60]);
        $staff = makeStaffWithScheduleForBooking($this->date->dayOfWeek, '09:00:00', '11:00:00', $this->tenant);
        $customer = Customer::factory()->for($this->tenant)->create();

        // Pre-seed a conflict so the inner re-check (inside the transaction) fires
        Appointment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'staff_id' => $staff->id,
            'service_id' => $svc->id,
            'customer_id' => $customer->id,
            'starts_at' => Carbon::parse('2025-01-06 09:00:00'),
            'ends_at' => Carbon::parse('2025-01-06 10:00:00'),
            'status' => AppointmentStatus::Confirmed,
            'payment_status' => PaymentStatus::Unpaid,
            'notes' => null,
        ]);

        try {
            $this->bookingService->create([
                'tenant_id' => $this->tenant->id,
                'staff_id' => $staff->id,
                'service_id' => $svc->id,
                'customer_id' => $customer->id,
                'starts_at' => Carbon::parse('2025-01-06 09:00:00'),
            ]);
        } catch (SlotUnavailableException) {
            // expected
        }

        // Only the one pre-seeded appointment exists; nothing partial was committed
        expect(Appointment::count())->toBe(1);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Concurrency — exercises the lock-then-recheck invariant
// ─────────────────────────────────────────────────────────────────────────────

describe('concurrency', function () {
    it('prevents double booking via the lock-then-recheck invariant', function () {
        Bus::fake();

        $svc = Service::factory()->for($this->tenant)->create(['duration_minutes' => 60]);
        $staff = makeStaffWithScheduleForBooking($this->date->dayOfWeek, '09:00:00', '17:00:00', $this->tenant);
        $customer = Customer::factory()->for($this->tenant)->create();

        /*
         * The afterLock hook fires INSIDE the transaction, AFTER the Staff row lock
         * is acquired, BEFORE the inner overlap re-check. It inserts a conflicting
         * appointment directly via DB::table() (no model, no tenant scope), simulating
         * the data that a concurrent T1 transaction would have committed between
         * acquiring and releasing the lock.
         *
         * Without the inner re-check (relying only on the pre-transaction outer check),
         * this hook would inject data the outer check never saw and the service would
         * silently create a double booking — so this test would FAIL. That failure is
         * what makes the test meaningful: it is sensitive to the structural requirement
         * that the re-check lives inside the transaction, after the lock.
         */
        $this->bookingService->afterLock = function () use ($staff, $svc, $customer) {
            DB::table('appointments')->insert([
                'tenant_id' => $this->tenant->id,
                'staff_id' => $staff->id,
                'service_id' => $svc->id,
                'customer_id' => $customer->id,
                'starts_at' => '2025-01-06 10:00:00',
                'ends_at' => '2025-01-06 11:00:00',
                'status' => AppointmentStatus::Confirmed->value,
                'payment_status' => PaymentStatus::Unpaid->value,
                'notes' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        };

        // This booking attempt (simulating T2) must detect the conflict and throw
        expect(fn () => $this->bookingService->create([
            'tenant_id' => $this->tenant->id,
            'staff_id' => $staff->id,
            'service_id' => $svc->id,
            'customer_id' => $customer->id,
            'starts_at' => Carbon::parse('2025-01-06 10:00:00'),
        ]))->toThrow(SlotUnavailableException::class)
            // Hook's insert and T2's attempted insert are in the same transaction;
            // rollback undoes both — zero appointments proves T2 committed nothing.
            ->and(Appointment::withoutGlobalScopes()->count())->toBe(0);

        Bus::assertNotDispatched(SendBookingConfirmation::class);
    });
});

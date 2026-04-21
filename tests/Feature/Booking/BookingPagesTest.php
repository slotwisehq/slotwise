<?php

use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Tenant\TenantContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;

afterEach(fn () => TenantContext::set(null));

// ─── Helpers ─────────────────────────────────────────────────────────────────

function makeTenantWithService(bool $serviceActive = true): array
{
    $tenant = Tenant::factory()->create(['slug' => 'test-salon']);
    $service = Service::factory()->for($tenant)->create(['is_active' => $serviceActive, 'duration_minutes' => 60]);

    return [$tenant, $service];
}

function makeScheduledStaff(Tenant $tenant): Staff
{
    $staff = Staff::factory()->for($tenant)->create();
    Schedule::factory()->forStaff($staff)->create(['day_of_week' => 1, 'start_time' => '09:00:00', 'end_time' => '17:00:00']);

    return $staff;
}

// ─── Service picker ───────────────────────────────────────────────────────────

describe('service picker', function () {
    it('returns 200 with active services for a valid tenant slug', function () {
        [$tenant, $service] = makeTenantWithService();

        $this->get(route('booking.show', $tenant->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('booking/ServicePicker')
                ->has('services', 1)
                ->where('services.0.name', $service->name)
            );
    });

    it('returns 404 for an invalid tenant slug', function () {
        $this->get(route('booking.show', 'does-not-exist'))
            ->assertNotFound();
    });

    it('excludes inactive services from the service picker', function () {
        [$tenant] = makeTenantWithService(serviceActive: false);

        $this->get(route('booking.show', $tenant->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('booking/ServicePicker')
                ->has('services', 0)
            );
    });
});

// ─── Staff picker ─────────────────────────────────────────────────────────────

describe('staff picker', function () {
    it('auto-redirects to slot picker when only one staff member has schedules', function () {
        [$tenant, $service] = makeTenantWithService();
        $staff = makeScheduledStaff($tenant);

        $this->get(route('booking.staff', [$tenant->slug, $service->id]))
            ->assertRedirect(route('booking.slots', [$tenant->slug, $service->id, $staff->id]));
    });
});

// ─── Slot picker ──────────────────────────────────────────────────────────────

describe('slot picker', function () {
    it('returns availability data in Inertia props', function () {
        [$tenant, $service] = makeTenantWithService();
        $staff = makeScheduledStaff($tenant);

        // Monday 2025-01-06 — schedule day_of_week = 1
        $this->get(route('booking.slots', [$tenant->slug, $service->id, $staff->id]).'?date=2025-01-06')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('booking/SlotPicker')
                ->has('slots')
                ->has('date')
                ->where('date', '2025-01-06')
            );
    });
});

// ─── Store (POST booking) ─────────────────────────────────────────────────────

describe('store', function () {
    it('creates an Appointment and redirects to confirmation on valid data', function () {
        Bus::fake();

        [$tenant, $service] = makeTenantWithService();
        $staff = makeScheduledStaff($tenant);

        $this->post(route('booking.store', [$tenant->slug, $service->id, $staff->id]), [
            'starts_at' => '2025-01-06 09:00:00',
            'customer_name' => 'Alice Martin',
            'customer_email' => 'alice@example.com',
            'customer_phone' => null,
        ])->assertRedirect();

        expect(Appointment::withoutGlobalScopes()->count())->toBe(1);
    });

    it('redirects back with flash error when slot is unavailable', function () {
        Bus::fake();

        [$tenant, $service] = makeTenantWithService();
        $staff = makeScheduledStaff($tenant);
        $customer = Customer::factory()->for($tenant)->create();

        // Block the target slot
        Appointment::factory()->create([
            'tenant_id' => $tenant->id,
            'staff_id' => $staff->id,
            'service_id' => $service->id,
            'customer_id' => $customer->id,
            'starts_at' => Carbon::parse('2025-01-06 09:00:00'),
            'ends_at' => Carbon::parse('2025-01-06 10:00:00'),
            'status' => AppointmentStatus::Confirmed,
            'payment_status' => PaymentStatus::Unpaid,
            'notes' => null,
        ]);

        // First visit to establish referrer
        $this->get(route('booking.slots', [$tenant->slug, $service->id, $staff->id]).'?date=2025-01-06');

        $this->post(route('booking.store', [$tenant->slug, $service->id, $staff->id]), [
            'starts_at' => '2025-01-06 09:00:00',
            'customer_name' => 'Bob Smith',
            'customer_email' => 'bob@example.com',
            'customer_phone' => null,
        ])->assertRedirect();

        expect(Appointment::withoutGlobalScopes()->count())->toBe(1); // only the pre-seeded one
    });

    it('redirects back with errors when required customer fields are missing', function () {
        [$tenant, $service] = makeTenantWithService();
        $staff = makeScheduledStaff($tenant);

        $this->post(route('booking.store', [$tenant->slug, $service->id, $staff->id]), [
            'starts_at' => '2025-01-06 09:00:00',
            // customer_name and customer_email missing
        ])->assertRedirect()
            ->assertSessionHasErrors(['customer_name', 'customer_email']);
    });
});

// ─── Confirmation ─────────────────────────────────────────────────────────────

describe('confirmation', function () {
    it('shows correct service, staff, date/time, and customer name', function () {
        Bus::fake();

        [$tenant, $service] = makeTenantWithService();
        $staff = makeScheduledStaff($tenant);
        $customer = Customer::factory()->for($tenant)->create(['name' => 'Clara Jones']);

        $appointment = Appointment::factory()->create([
            'tenant_id' => $tenant->id,
            'staff_id' => $staff->id,
            'service_id' => $service->id,
            'customer_id' => $customer->id,
            'starts_at' => Carbon::parse('2025-01-06 10:00:00'),
            'ends_at' => Carbon::parse('2025-01-06 11:00:00'),
            'status' => AppointmentStatus::Confirmed,
            'payment_status' => PaymentStatus::Unpaid,
            'notes' => null,
        ]);

        $this->get(route('booking.confirmation', [$tenant->slug, $appointment->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('booking/Confirmation')
                ->where('appointment.service_name', $service->name)
                ->where('appointment.staff_name', $staff->name)
                ->where('appointment.customer_name', 'Clara Jones')
                ->has('appointment.starts_at')
            );
    });
});

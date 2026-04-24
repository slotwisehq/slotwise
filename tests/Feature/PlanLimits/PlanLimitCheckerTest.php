<?php

use App\Booking\Exceptions\PlanLimitException;
use App\Booking\PlanLimitChecker;
use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Tenant\TenantContext;
use Carbon\Carbon;

afterEach(fn () => TenantContext::set(null));

// ─── Staff limit ──────────────────────────────────────────────────────────────

describe('assertCanAddStaff', function () {
    it('throws when free tenant already has 1 active staff member', function () {
        $tenant = Tenant::factory()->create(['plan' => 'free']);
        Staff::factory()->for($tenant)->create();

        expect(fn () => app(PlanLimitChecker::class)->assertCanAddStaff($tenant))
            ->toThrow(PlanLimitException::class);
    });

    it('does not throw when free tenant has no staff', function () {
        $tenant = Tenant::factory()->create(['plan' => 'free']);

        expect(fn () => app(PlanLimitChecker::class)->assertCanAddStaff($tenant))
            ->not->toThrow(PlanLimitException::class);
    });

    it('does not throw when free tenant has 1 soft-deleted staff member', function () {
        $tenant = Tenant::factory()->create(['plan' => 'free']);
        $staff = Staff::factory()->for($tenant)->create();
        $staff->delete(); // soft-delete

        expect(fn () => app(PlanLimitChecker::class)->assertCanAddStaff($tenant))
            ->not->toThrow(PlanLimitException::class);
    });

    it('does not throw for a pro tenant with multiple staff members', function () {
        $tenant = Tenant::factory()->create(['plan' => 'pro']);
        Staff::factory()->for($tenant)->count(5)->create();

        expect(fn () => app(PlanLimitChecker::class)->assertCanAddStaff($tenant))
            ->not->toThrow(PlanLimitException::class);
    });

    it('exception message contains the limit name and plan', function () {
        $tenant = Tenant::factory()->create(['plan' => 'free']);
        Staff::factory()->for($tenant)->create();

        expect(fn () => app(PlanLimitChecker::class)->assertCanAddStaff($tenant))
            ->toThrow(PlanLimitException::class, 'free');
    });
});

// ─── Service limit ────────────────────────────────────────────────────────────

describe('assertCanAddService', function () {
    it('throws when free tenant already has 1 active service', function () {
        $tenant = Tenant::factory()->create(['plan' => 'free']);
        Service::factory()->for($tenant)->create();

        expect(fn () => app(PlanLimitChecker::class)->assertCanAddService($tenant))
            ->toThrow(PlanLimitException::class);
    });

    it('does not throw when free tenant has no services', function () {
        $tenant = Tenant::factory()->create(['plan' => 'free']);

        expect(fn () => app(PlanLimitChecker::class)->assertCanAddService($tenant))
            ->not->toThrow(PlanLimitException::class);
    });

    it('does not throw when free tenant has 1 soft-deleted service', function () {
        $tenant = Tenant::factory()->create(['plan' => 'free']);
        $service = Service::factory()->for($tenant)->create();
        $service->delete();

        expect(fn () => app(PlanLimitChecker::class)->assertCanAddService($tenant))
            ->not->toThrow(PlanLimitException::class);
    });

    it('does not throw for a pro tenant with multiple services', function () {
        $tenant = Tenant::factory()->create(['plan' => 'pro']);
        Service::factory()->for($tenant)->count(10)->create();

        expect(fn () => app(PlanLimitChecker::class)->assertCanAddService($tenant))
            ->not->toThrow(PlanLimitException::class);
    });
});

// ─── Booking limit ────────────────────────────────────────────────────────────

describe('assertCanCreateBooking', function () {
    it('throws when free tenant has 50 confirmed bookings this month', function () {
        $tenant = Tenant::factory()->create(['plan' => 'free']);
        $service = Service::factory()->for($tenant)->create();
        $staff = Staff::factory()->for($tenant)->create();
        $customer = Customer::factory()->for($tenant)->create();

        Appointment::factory()->count(50)->create([
            'tenant_id' => $tenant->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'customer_id' => $customer->id,
            'starts_at' => Carbon::now()->startOfMonth()->addHours(10),
            'ends_at' => Carbon::now()->startOfMonth()->addHours(11),
            'status' => AppointmentStatus::Confirmed,
            'payment_status' => PaymentStatus::Unpaid,
        ]);

        expect(fn () => app(PlanLimitChecker::class)->assertCanCreateBooking($tenant))
            ->toThrow(PlanLimitException::class);
    });

    it('does not throw when free tenant has 49 confirmed bookings this month', function () {
        $tenant = Tenant::factory()->create(['plan' => 'free']);
        $service = Service::factory()->for($tenant)->create();
        $staff = Staff::factory()->for($tenant)->create();
        $customer = Customer::factory()->for($tenant)->create();

        Appointment::factory()->count(49)->create([
            'tenant_id' => $tenant->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'customer_id' => $customer->id,
            'starts_at' => Carbon::now()->startOfMonth()->addHours(10),
            'ends_at' => Carbon::now()->startOfMonth()->addHours(11),
            'status' => AppointmentStatus::Confirmed,
            'payment_status' => PaymentStatus::Unpaid,
        ]);

        expect(fn () => app(PlanLimitChecker::class)->assertCanCreateBooking($tenant))
            ->not->toThrow(PlanLimitException::class);
    });

    it('does not count cancelled bookings toward the monthly limit', function () {
        $tenant = Tenant::factory()->create(['plan' => 'free']);
        $service = Service::factory()->for($tenant)->create();
        $staff = Staff::factory()->for($tenant)->create();
        $customer = Customer::factory()->for($tenant)->create();

        Appointment::factory()->count(50)->create([
            'tenant_id' => $tenant->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'customer_id' => $customer->id,
            'starts_at' => Carbon::now()->startOfMonth()->addHours(10),
            'ends_at' => Carbon::now()->startOfMonth()->addHours(11),
            'status' => AppointmentStatus::Cancelled,
            'payment_status' => PaymentStatus::Unpaid,
        ]);

        expect(fn () => app(PlanLimitChecker::class)->assertCanCreateBooking($tenant))
            ->not->toThrow(PlanLimitException::class);
    });

    it('does not count bookings from a previous month', function () {
        $tenant = Tenant::factory()->create(['plan' => 'free']);
        $service = Service::factory()->for($tenant)->create();
        $staff = Staff::factory()->for($tenant)->create();
        $customer = Customer::factory()->for($tenant)->create();

        Appointment::factory()->count(50)->create([
            'tenant_id' => $tenant->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'customer_id' => $customer->id,
            'starts_at' => Carbon::now()->subMonth()->startOfMonth()->addHours(10),
            'ends_at' => Carbon::now()->subMonth()->startOfMonth()->addHours(11),
            'status' => AppointmentStatus::Confirmed,
            'payment_status' => PaymentStatus::Unpaid,
        ]);

        expect(fn () => app(PlanLimitChecker::class)->assertCanCreateBooking($tenant))
            ->not->toThrow(PlanLimitException::class);
    });

    it('does not throw for a pro tenant regardless of booking count', function () {
        $tenant = Tenant::factory()->create(['plan' => 'pro']);
        $service = Service::factory()->for($tenant)->create();
        $staff = Staff::factory()->for($tenant)->create();
        $customer = Customer::factory()->for($tenant)->create();

        Appointment::factory()->count(100)->create([
            'tenant_id' => $tenant->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'customer_id' => $customer->id,
            'starts_at' => Carbon::now()->startOfMonth()->addHours(10),
            'ends_at' => Carbon::now()->startOfMonth()->addHours(11),
            'status' => AppointmentStatus::Confirmed,
            'payment_status' => PaymentStatus::Unpaid,
        ]);

        expect(fn () => app(PlanLimitChecker::class)->assertCanCreateBooking($tenant))
            ->not->toThrow(PlanLimitException::class);
    });
});

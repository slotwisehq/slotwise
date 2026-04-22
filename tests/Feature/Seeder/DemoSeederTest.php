<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use App\Tenant\TenantContext;
use Database\Seeders\DemoSeeder;

afterEach(fn () => TenantContext::set(null));

it('creates the exact expected record counts', function () {
    $this->seed(DemoSeeder::class);

    $tenant = Tenant::where('slug', 'lumiere-salon')->firstOrFail();
    TenantContext::set($tenant);

    expect(Tenant::where('slug', 'lumiere-salon')->count())->toBe(1)
        ->and(User::withoutGlobalScopes()->where('email', 'admin@lumieresalon.test')->count())->toBe(1)
        ->and(Staff::count())->toBe(2)
        ->and(Schedule::count())->toBe(12)  // 6 days × 2 staff
        ->and(Service::count())->toBe(3)
        ->and(Customer::count())->toBe(10)
        ->and(
            Appointment::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('starts_at', '<', now())
                ->count()
        )->toBe(20)
        ->and(
            Appointment::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('starts_at', '>=', now())
                ->count()
        )->toBe(10);
});

it('is idempotent — seeding twice produces identical record counts, not doubled ones', function () {
    $this->seed(DemoSeeder::class);
    $this->seed(DemoSeeder::class);

    $tenant = Tenant::where('slug', 'lumiere-salon')->firstOrFail();
    TenantContext::set($tenant);

    expect(Tenant::where('slug', 'lumiere-salon')->count())->toBe(1)
        ->and(User::withoutGlobalScopes()->where('email', 'admin@lumieresalon.test')->count())->toBe(1)
        ->and(Staff::count())->toBe(2)
        ->and(Schedule::count())->toBe(12)
        ->and(Service::count())->toBe(3)
        ->and(Customer::count())->toBe(10)
        ->and(
            Appointment::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->count()
        )->toBe(30);  // 20 past + 10 upcoming
});

it('past appointments are a mix of Confirmed and Cancelled statuses', function () {
    $this->seed(DemoSeeder::class);

    $tenant = Tenant::where('slug', 'lumiere-salon')->firstOrFail();

    $confirmedCount = Appointment::withoutGlobalScopes()
        ->where('tenant_id', $tenant->id)
        ->where('starts_at', '<', now())
        ->where('status', AppointmentStatus::Confirmed->value)
        ->count();

    $cancelledCount = Appointment::withoutGlobalScopes()
        ->where('tenant_id', $tenant->id)
        ->where('starts_at', '<', now())
        ->where('status', AppointmentStatus::Cancelled->value)
        ->count();

    expect($confirmedCount)->toBeGreaterThan(0)
        ->and($cancelledCount)->toBeGreaterThan(0)
        ->and($confirmedCount + $cancelledCount)->toBe(20);
});

it('all upcoming appointments are Confirmed', function () {
    $this->seed(DemoSeeder::class);

    $tenant = Tenant::where('slug', 'lumiere-salon')->firstOrFail();

    $upcomingNonConfirmed = Appointment::withoutGlobalScopes()
        ->where('tenant_id', $tenant->id)
        ->where('starts_at', '>=', now())
        ->where('status', '!=', AppointmentStatus::Confirmed->value)
        ->count();

    expect($upcomingNonConfirmed)->toBe(0);
});

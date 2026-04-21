<?php

use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use Tests\Concerns\HasTenantContext;

uses(HasTenantContext::class);

beforeEach(fn () => $this->setUpTenantContext());
afterEach(fn () => $this->tearDownTenantContext());

it('creates a tenant with models and relationships', function () {
    $service = Service::factory()->for($this->tenant)->create();
    $staff = Staff::factory()->for($this->tenant)->create();
    $customer = Customer::factory()->for($this->tenant)->create();

    expect($this->tenant->services()->count())->toBe(1)
        ->and($this->tenant->staff()->count())->toBe(1)
        ->and($this->tenant->customers()->count())->toBe(1);
});

it('applies tenant scope so cross-tenant records are invisible', function () {
    $otherTenant = Tenant::factory()->create();
    Service::factory()->for($otherTenant)->create();

    expect(Service::count())->toBe(0);
});

it('creates appointments with correct enum casts', function () {
    $appointment = Appointment::factory()
        ->for($this->tenant)
        ->confirmed()
        ->create([
            'service_id' => Service::factory()->for($this->tenant)->create()->id,
            'staff_id' => Staff::factory()->for($this->tenant)->create()->id,
            'customer_id' => Customer::factory()->for($this->tenant)->create()->id,
        ]);

    expect($appointment->status)->toBe(AppointmentStatus::Confirmed)
        ->and($appointment->payment_status)->toBe(PaymentStatus::Unpaid);
});

it('creates schedules linked to staff', function () {
    $staff = Staff::factory()->for($this->tenant)->create();
    $schedule = Schedule::factory()->forStaff($staff)->create();

    expect($schedule->staff_id)->toBe($staff->id)
        ->and($schedule->tenant_id)->toBe($this->tenant->id);
});

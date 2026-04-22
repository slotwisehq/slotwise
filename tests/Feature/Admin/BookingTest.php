<?php

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use App\Tenant\TenantContext;

afterEach(fn () => TenantContext::set(null));

function bookingSetup(): array
{
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->owner()->forTenant($tenant)->create();
    $service = Service::factory()->create(['tenant_id' => $tenant->id]);
    $staff = Staff::factory()->create(['tenant_id' => $tenant->id]);
    $customer = Customer::factory()->create(['tenant_id' => $tenant->id]);

    return [$tenant, $owner, $service, $staff, $customer];
}

it('only shows bookings for the authenticated tenant', function () {
    [$tenant, $owner, $service, $staff, $customer] = bookingSetup();
    $otherTenant = Tenant::factory()->create();

    $myAppt = Appointment::factory()->create([
        'tenant_id' => $tenant->id,
        'service_id' => $service->id,
        'staff_id' => $staff->id,
        'customer_id' => $customer->id,
    ]);

    // Other tenant appointment — must not appear
    $otherService = Service::factory()->create(['tenant_id' => $otherTenant->id]);
    $otherStaff = Staff::factory()->create(['tenant_id' => $otherTenant->id]);
    $otherCustomer = Customer::factory()->create(['tenant_id' => $otherTenant->id]);
    Appointment::factory()->create([
        'tenant_id' => $otherTenant->id,
        'service_id' => $otherService->id,
        'staff_id' => $otherStaff->id,
        'customer_id' => $otherCustomer->id,
    ]);

    $this->actingAs($owner)
        ->get(route('admin.bookings.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/bookings/Index')
            ->has('appointments.data', 1)
            ->where('appointments.data.0.id', $myAppt->id)
        );
});

it('cancels a confirmed appointment and updates status', function () {
    [$tenant, $owner, $service, $staff, $customer] = bookingSetup();

    $appt = Appointment::factory()->confirmed()->create([
        'tenant_id' => $tenant->id,
        'service_id' => $service->id,
        'staff_id' => $staff->id,
        'customer_id' => $customer->id,
    ]);

    $this->actingAs($owner)
        ->patch(route('admin.bookings.cancel', $appt))
        ->assertRedirect();

    expect($appt->fresh()->status)->toBe(AppointmentStatus::Cancelled)
        ->and(Appointment::find($appt->id))->not->toBeNull();
    // record preserved
});

it('returns 422 when cancelling an already cancelled appointment', function () {
    [$tenant, $owner, $service, $staff, $customer] = bookingSetup();

    $appt = Appointment::factory()->cancelled()->create([
        'tenant_id' => $tenant->id,
        'service_id' => $service->id,
        'staff_id' => $staff->id,
        'customer_id' => $customer->id,
    ]);

    $this->actingAs($owner)
        ->patch(route('admin.bookings.cancel', $appt))
        ->assertInvalid(['status']);
});

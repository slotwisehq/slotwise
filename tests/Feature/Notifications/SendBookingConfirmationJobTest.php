<?php

use App\Jobs\SendBookingConfirmation;
use App\Mail\BookingConfirmedMail;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Notification\Contracts\NotificationDriver;
use App\Notification\Drivers\NullDriver;
use App\Tenant\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

afterEach(fn () => TenantContext::set(null));

function makeAppointmentForJob(): Appointment
{
    $tenant = Tenant::factory()->create();
    TenantContext::set($tenant);
    $service = Service::factory()->for($tenant)->create();
    $staff = Staff::factory()->for($tenant)->create();
    $customer = Customer::factory()->for($tenant)->create(['email' => 'job-test@example.com']);

    return Appointment::factory()->create([
        'tenant_id' => $tenant->id,
        'service_id' => $service->id,
        'staff_id' => $staff->id,
        'customer_id' => $customer->id,
    ]);
}

// ─── Happy path ───────────────────────────────────────────────────────────────

it('sends BookingConfirmedMail when the job handles successfully', function () {
    Mail::fake();

    $appointment = makeAppointmentForJob();

    $job = new SendBookingConfirmation($appointment);
    $job->handle(app(NotificationDriver::class));

    Mail::assertSent(BookingConfirmedMail::class);
});

// ─── No email edge case ───────────────────────────────────────────────────────

it('completes without throwing when customer has no email address', function () {
    Mail::fake();

    $tenant = Tenant::factory()->create();
    TenantContext::set($tenant);
    $service = Service::factory()->for($tenant)->create();
    $staff = Staff::factory()->for($tenant)->create();
    $customer = Customer::factory()->for($tenant)->create(['email' => null]);

    $appointment = Appointment::factory()->create([
        'tenant_id' => $tenant->id,
        'service_id' => $service->id,
        'staff_id' => $staff->id,
        'customer_id' => $customer->id,
    ]);

    $job = new SendBookingConfirmation($appointment);

    expect(fn () => $job->handle(app(NotificationDriver::class)))
        ->not->toThrow(Throwable::class);

    Mail::assertNothingSent();
});

// ─── Eager loading — N+1 guard ────────────────────────────────────────────────

it('eager loads all appointment relationships so no lazy loading occurs', function () {
    app()->bind(NotificationDriver::class, NullDriver::class);
    Model::preventLazyLoading();

    $appointment = makeAppointmentForJob();

    // Pass a fresh un-loaded instance to force the job to eager-load
    $fresh = Appointment::withoutGlobalScopes()->find($appointment->id);
    assert($fresh !== null);

    $job = new SendBookingConfirmation($fresh);

    expect(fn () => $job->handle(app(NotificationDriver::class)))
        ->not->toThrow(Throwable::class);

    Model::preventLazyLoading(false);
});

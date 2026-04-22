<?php

use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use App\Mail\BookingConfirmedMail;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Notification\Contracts\NotificationDriver;
use App\Notification\Drivers\MailDriver;
use App\Notification\Drivers\NullDriver;
use App\Notification\Exceptions\InvalidNotificationEventException;
use App\Tenant\TenantContext;

afterEach(fn () => TenantContext::set(null));

// ─── Container binding ────────────────────────────────────────────────────────

it('resolves MailDriver from the container in a non-test environment', function () {
    expect(app(NotificationDriver::class))->toBeInstanceOf(MailDriver::class);
});

// ─── NullDriver ───────────────────────────────────────────────────────────────

it('NullDriver::send() does nothing and never throws', function () {
    $driver = new NullDriver;

    expect(fn () => $driver->send(
        new Appointment, // un-persisted; NullDriver ignores it
        'booking.confirmed',
    ))->not->toThrow(Throwable::class);
});

// ─── InvalidNotificationEventException ───────────────────────────────────────

it('InvalidNotificationEventException carries the event name', function () {
    $exception = new InvalidNotificationEventException('foo.bar');

    expect($exception->getMessage())->toContain('foo.bar');
});

// ─── MailDriver happy path ────────────────────────────────────────────────────

it('MailDriver sends BookingConfirmedMail for booking.confirmed event', function () {
    Mail::fake();

    $tenant = Tenant::factory()->create(['slug' => 'mail-test']);
    TenantContext::set($tenant);
    $service = Service::factory()->for($tenant)->create();
    $staff = Staff::factory()->for($tenant)->create();
    $customer = Customer::factory()->for($tenant)->create(['email' => 'user@example.com']);

    $appointment = Appointment::factory()->create([
        'tenant_id' => $tenant->id,
        'service_id' => $service->id,
        'staff_id' => $staff->id,
        'customer_id' => $customer->id,
        'status' => AppointmentStatus::Confirmed,
        'payment_status' => PaymentStatus::Unpaid,
    ]);
    $appointment->load(['service', 'staff', 'customer', 'tenant']);

    (new MailDriver)->send($appointment, 'booking.confirmed');

    Mail::assertSent(BookingConfirmedMail::class, fn ($m) => $m->hasTo('user@example.com'));
});

// ─── MailDriver — unknown event ───────────────────────────────────────────────

it('MailDriver throws InvalidNotificationEventException for an unrecognised event', function () {
    $tenant = Tenant::factory()->create();
    TenantContext::set($tenant);
    $service = Service::factory()->for($tenant)->create();
    $staff = Staff::factory()->for($tenant)->create();
    $customer = Customer::factory()->for($tenant)->create(['email' => 'x@x.com']);

    $appointment = Appointment::factory()->create([
        'tenant_id' => $tenant->id,
        'service_id' => $service->id,
        'staff_id' => $staff->id,
        'customer_id' => $customer->id,
    ]);
    $appointment->load(['service', 'staff', 'customer', 'tenant']);

    expect(fn () => (new MailDriver)->send($appointment, 'some.unknown.event'))
        ->toThrow(InvalidNotificationEventException::class);
});

// ─── MailDriver — missing customer email ─────────────────────────────────────

it('MailDriver skips sending and does not throw when customer has no email', function () {
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
    $appointment->load(['service', 'staff', 'customer', 'tenant']);

    expect(fn () => (new MailDriver)->send($appointment, 'booking.confirmed'))
        ->not->toThrow(Throwable::class);

    Mail::assertNothingSent();
});

// ─── NullDriver when config says null ────────────────────────────────────────

it('uses NullDriver when NOTIFICATION_DRIVER=null — no mail sent', function () {
    config(['notifications.driver' => 'null']);
    app()->bind(NotificationDriver::class, NullDriver::class);

    Mail::fake();

    $tenant = Tenant::factory()->create();
    TenantContext::set($tenant);
    $service = Service::factory()->for($tenant)->create();
    $staff = Staff::factory()->for($tenant)->create();
    $customer = Customer::factory()->for($tenant)->create(['email' => 'x@x.com']);

    $appointment = Appointment::factory()->create([
        'tenant_id' => $tenant->id,
        'service_id' => $service->id,
        'staff_id' => $staff->id,
        'customer_id' => $customer->id,
    ]);
    $appointment->load(['service', 'staff', 'customer', 'tenant']);

    app(NotificationDriver::class)->send($appointment, 'booking.confirmed');

    Mail::assertNothingSent();
});

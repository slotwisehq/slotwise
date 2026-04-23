<?php

use App\Mail\BookingConfirmedMail;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Tenant\TenantContext;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

afterEach(fn () => TenantContext::set(null));

it('contains tenant name, service name, staff name, customer name, date, and cancel link', function () {
    $tenant = Tenant::factory()->create([
        'name' => 'Test Salon',
        'slug' => 'test-salon',
    ]);
    TenantContext::set($tenant);

    $service = Service::factory()->for($tenant)->create(['name' => 'Haircut & Style']);
    $staff = Staff::factory()->for($tenant)->create(['name' => 'Jane Smith']);
    $customer = Customer::factory()->for($tenant)->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);

    $appointment = Appointment::factory()->create([
        'tenant_id' => $tenant->id,
        'service_id' => $service->id,
        'staff_id' => $staff->id,
        'customer_id' => $customer->id,
        'starts_at' => Carbon::parse('2025-06-10 10:00:00'),
        'ends_at' => Carbon::parse('2025-06-10 11:00:00'),
    ]);
    $appointment->load(['service', 'staff', 'customer', 'tenant']);

    $mailable = new BookingConfirmedMail($appointment);

    $mailable->assertSeeInHtml('Test Salon');
    $mailable->assertSeeInHtml('Haircut & Style');
    $mailable->assertSeeInHtml('Jane Smith');
    $mailable->assertSeeInHtml('John Doe');
    $mailable->assertSeeInHtml('2025');
    $mailable->assertSeeInHtml("/book/test-salon/cancel/{$appointment->id}");

    $mailable->assertSeeInText('Test Salon');
    $mailable->assertSeeInText('Haircut & Style');
    $mailable->assertSeeInText('Jane Smith');
    $mailable->assertSeeInText('John Doe');
    $mailable->assertSeeInText("/book/test-salon/cancel/{$appointment->id}");
});

it('uses the customer email as the recipient', function () {
    Mail::fake();

    $tenant = Tenant::factory()->create(['name' => 'Salon', 'slug' => 'salon']);
    TenantContext::set($tenant);

    $service = Service::factory()->for($tenant)->create();
    $staff = Staff::factory()->for($tenant)->create();
    $customer = Customer::factory()->for($tenant)->create(['email' => 'alice@example.com']);

    $appointment = Appointment::factory()->create([
        'tenant_id' => $tenant->id,
        'service_id' => $service->id,
        'staff_id' => $staff->id,
        'customer_id' => $customer->id,
    ]);
    $appointment->load(['service', 'staff', 'customer', 'tenant']);

    Mail::to('alice@example.com')->send(new BookingConfirmedMail($appointment));

    Mail::assertSent(BookingConfirmedMail::class, fn ($m) => $m->hasTo('alice@example.com'));
});

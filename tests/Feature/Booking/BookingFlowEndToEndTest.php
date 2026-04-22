<?php

use App\Jobs\SendBookingConfirmation;
use App\Models\Appointment;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Tenant\TenantContext;
use Illuminate\Support\Facades\Bus;

afterEach(fn () => TenantContext::set(null));

it('completes the full booking flow: service picker → staff picker → slot picker → POST → confirmation + job queued', function () {
    Bus::fake();

    $tenant = Tenant::factory()->create(['slug' => 'e2e-salon']);
    $service = Service::factory()->for($tenant)->create([
        'is_active' => true,
        'duration_minutes' => 60,
    ]);

    // Two staff members with schedules — prevents the auto-redirect so the staff picker renders
    $staff1 = Staff::factory()->for($tenant)->create(['name' => 'Anna']);
    $staff2 = Staff::factory()->for($tenant)->create(['name' => 'Ben']);
    Schedule::factory()->forStaff($staff1)->create(['day_of_week' => 1, 'start_time' => '09:00:00', 'end_time' => '17:00:00']);
    Schedule::factory()->forStaff($staff2)->create(['day_of_week' => 1, 'start_time' => '09:00:00', 'end_time' => '17:00:00']);

    // Step 1 — Service picker
    $this->get(route('booking.show', $tenant->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('booking/ServicePicker')
            ->has('services', 1)
        );

    // Step 2 — Staff picker (2 staff, no auto-redirect)
    $this->get(route('booking.staff', [$tenant->slug, $service->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('booking/StaffPicker')
            ->has('staff', 2)
        );

    // Step 3 — Slot picker (Monday 2025-01-06, schedule day_of_week=1)
    $this->get(route('booking.slots', [$tenant->slug, $service->id, $staff1->id]).'?date=2025-01-06')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('booking/SlotPicker')
            ->has('slots')
        );

    // Step 4 — POST booking
    $this->post(
        route('booking.store', [$tenant->slug, $service->id, $staff1->id]),
        [
            'starts_at' => '2025-01-06 09:00:00',
            'customer_name' => 'End-to-End Customer',
            'customer_email' => 'e2e@example.com',
            'customer_phone' => null,
        ]
    )->assertRedirect();

    $appointment = Appointment::withoutGlobalScopes()->latest('id')->first();
    expect($appointment)->not->toBeNull();

    // Step 5 — Confirmation page
    $this->get(route('booking.confirmation', [$tenant->slug, $appointment->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('booking/Confirmation')
            ->where('appointment.customer_name', 'End-to-End Customer')
        );

    // Step 6 — Confirm job was queued
    Bus::assertDispatched(SendBookingConfirmation::class, fn ($job) => $job->appointment->is($appointment));
});

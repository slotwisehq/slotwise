<?php

use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use App\Tenant\TenantContext;

afterEach(fn () => TenantContext::set(null));

// ─── Public booking routes ────────────────────────────────────────────────────

describe('public booking route isolation', function () {
    it('service picker only returns active services belonging to the route tenant', function () {
        $tenantA = Tenant::factory()->create(['slug' => 'salon-a']);
        $tenantB = Tenant::factory()->create(['slug' => 'salon-b']);

        Service::factory()->for($tenantA)->create(['is_active' => true, 'name' => 'Haircut A']);
        Service::factory()->for($tenantB)->create(['is_active' => true, 'name' => 'Haircut B']);

        $this->get(route('booking.show', $tenantA->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('services', 1)
                ->where('services.0.name', 'Haircut A')
            );
    });

    it('staff picker only returns staff belonging to the route tenant', function () {
        $tenantA = Tenant::factory()->create(['slug' => 'salon-a']);
        $tenantB = Tenant::factory()->create(['slug' => 'salon-b']);

        $serviceA = Service::factory()->for($tenantA)->create(['is_active' => true]);

        // Two staff on Tenant A — prevents the auto-redirect so the StaffPicker renders
        $staffA1 = Staff::factory()->for($tenantA)->create(['name' => 'Anna']);
        $staffA2 = Staff::factory()->for($tenantA)->create(['name' => 'Alice']);
        $staffB = Staff::factory()->for($tenantB)->create(['name' => 'Boris']);
        Schedule::factory()->forStaff($staffA1)->create(['day_of_week' => 1]);
        Schedule::factory()->forStaff($staffA2)->create(['day_of_week' => 1]);
        Schedule::factory()->forStaff($staffB)->create(['day_of_week' => 1]);

        $this->get(route('booking.staff', [$tenantA->slug, $serviceA->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('staff', 2)
            );
    });

    it('confirmation page cannot be accessed for an appointment belonging to a different tenant', function () {
        $tenantA = Tenant::factory()->create(['slug' => 'salon-a']);
        $tenantB = Tenant::factory()->create(['slug' => 'salon-b']);

        $serviceB = Service::factory()->for($tenantB)->create();
        $staffB = Staff::factory()->for($tenantB)->create();
        $customerB = Customer::factory()->for($tenantB)->create();

        $apptB = Appointment::factory()->create([
            'tenant_id' => $tenantB->id,
            'service_id' => $serviceB->id,
            'staff_id' => $staffB->id,
            'customer_id' => $customerB->id,
        ]);

        // Tenant A's slug in URL, but appointment belongs to Tenant B — must return 404
        $this->get(route('booking.confirmation', [$tenantA->slug, $apptB->id]))
            ->assertNotFound();
    });
});

// ─── Null TenantContext ───────────────────────────────────────────────────────

describe('null TenantContext', function () {
    it('querying a BelongsToTenant model with no context set returns all records (fail-open)', function () {
        Tenant::factory()->count(2)->create()->each(function (Tenant $tenant) {
            Service::factory()->for($tenant)->create();
        });

        TenantContext::set(null);

        expect(Service::count())->toBe(2);
    });

    it('setting a tenant context filters records to that tenant only', function () {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        Service::factory()->for($tenantA)->create(['name' => 'Service A']);
        Service::factory()->for($tenantB)->create(['name' => 'Service B']);

        TenantContext::set($tenantA);

        $services = Service::all();
        expect($services)->toHaveCount(1)
            ->and($services->first()->name)->toBe('Service A');
    });
});

// ─── Admin cross-tenant gate ──────────────────────────────────────────────────

describe('admin cross-tenant gate', function () {
    it('owner of tenant B cannot read appointments belonging to tenant A via admin routes', function () {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $ownerB = User::factory()->owner()->forTenant($tenantB)->create();

        $serviceA = Service::factory()->for($tenantA)->create();
        $staffA = Staff::factory()->for($tenantA)->create();
        $customerA = Customer::factory()->for($tenantA)->create();

        Appointment::factory()->create([
            'tenant_id' => $tenantA->id,
            'service_id' => $serviceA->id,
            'staff_id' => $staffA->id,
            'customer_id' => $customerA->id,
        ]);

        $this->actingAs($ownerB)
            ->get(route('admin.bookings.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('appointments.data', 0)
            );
    });

    it('owner of tenant B cannot access the detail page for an appointment belonging to tenant A', function () {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $ownerB = User::factory()->owner()->forTenant($tenantB)->create();

        $serviceA = Service::factory()->for($tenantA)->create();
        $staffA = Staff::factory()->for($tenantA)->create();
        $customerA = Customer::factory()->for($tenantA)->create();

        $apptA = Appointment::factory()->create([
            'tenant_id' => $tenantA->id,
            'service_id' => $serviceA->id,
            'staff_id' => $staffA->id,
            'customer_id' => $customerA->id,
        ]);

        $this->actingAs($ownerB)
            ->get(route('admin.bookings.show', $apptA))
            ->assertNotFound();
    });
});

// ─── TenantContext cleanup ────────────────────────────────────────────────────

describe('TenantContext cleanup', function () {
    it('TenantContext is null at the start of a test (afterEach cleanup works)', function () {
        expect(TenantContext::current())->toBeNull();
    });

    it('setting then clearing TenantContext restores open visibility', function () {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();
        Service::factory()->for($tenantA)->create();
        Service::factory()->for($tenantB)->create();

        TenantContext::set($tenantA);
        expect(Service::count())->toBe(1);

        TenantContext::set(null);
        expect(Service::count())->toBe(2);
    });
});

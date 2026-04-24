<?php

use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use App\Tenant\TenantContext;

afterEach(fn () => TenantContext::set(null));

// ─── Staff ────────────────────────────────────────────────────────────────────

it('free tenant cannot create a 2nd staff member — redirects back with plan error', function () {
    $tenant = Tenant::factory()->create(['plan' => 'free']);
    $owner = User::factory()->owner()->forTenant($tenant)->create();
    Staff::factory()->for($tenant)->create();

    $this->actingAs($owner)
        ->post(route('admin.staff.store'), [
            'name' => 'Second Staff',
            'bio' => null,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('plan');

    expect(Staff::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count())->toBe(1);
});

it('free tenant with 1 soft-deleted staff can create a new one', function () {
    $tenant = Tenant::factory()->create(['plan' => 'free']);
    $owner = User::factory()->owner()->forTenant($tenant)->create();
    $staff = Staff::factory()->for($tenant)->create();
    $staff->delete();

    $this->actingAs($owner)
        ->post(route('admin.staff.store'), [
            'name' => 'New Staff',
            'bio' => null,
        ])
        ->assertRedirect(route('admin.staff.index'));

    expect(Staff::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count())->toBe(2); // 1 deleted + 1 new
});

it('pro tenant can create staff beyond the free limit', function () {
    $tenant = Tenant::factory()->create(['plan' => 'pro']);
    $owner = User::factory()->owner()->forTenant($tenant)->create();
    Staff::factory()->for($tenant)->create();

    $this->actingAs($owner)
        ->post(route('admin.staff.store'), [
            'name' => 'Second Staff',
            'bio' => null,
        ])
        ->assertRedirect(route('admin.staff.index'));
});

// ─── Services ─────────────────────────────────────────────────────────────────

it('free tenant cannot create a 2nd service — redirects back with plan error', function () {
    $tenant = Tenant::factory()->create(['plan' => 'free']);
    $owner = User::factory()->owner()->forTenant($tenant)->create();
    Service::factory()->for($tenant)->create();

    $this->actingAs($owner)
        ->post(route('admin.services.store'), [
            'name' => 'Second Service',
            'duration_minutes' => 60,
            'price' => '20.00',
            'is_active' => true,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('plan');

    expect(Service::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count())->toBe(1);
});

it('pro tenant can create services beyond the free limit', function () {
    $tenant = Tenant::factory()->create(['plan' => 'pro']);
    $owner = User::factory()->owner()->forTenant($tenant)->create();
    Service::factory()->for($tenant)->create();

    $this->actingAs($owner)
        ->post(route('admin.services.store'), [
            'name' => 'Second Service',
            'duration_minutes' => 60,
            'price' => '20.00',
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.services.index'));
});

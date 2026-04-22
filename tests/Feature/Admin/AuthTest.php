<?php

use App\Models\Tenant;
use App\Models\User;
use App\Tenant\TenantContext;

afterEach(fn () => TenantContext::set(null));

it('redirects unauthenticated user to login', function () {
    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));
});

it('returns 403 for a customer role user', function () {
    $tenant = Tenant::factory()->create();
    $customer = User::factory()->customer()->forTenant($tenant)->create();

    $this->actingAs($customer)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

it('allows owner role user to access the dashboard', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->owner()->forTenant($tenant)->create();

    $this->actingAs($owner)
        ->get(route('admin.dashboard'))
        ->assertOk();
});

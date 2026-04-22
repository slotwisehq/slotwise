<?php

use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Tenant\TenantContext;

afterEach(fn () => TenantContext::set(null));

// ─── Shared setup ─────────────────────────────────────────────────────────────

function adminWithTenant(): array
{
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->owner()->forTenant($tenant)->create();

    return [$tenant, $owner];
}

// ─── Index ────────────────────────────────────────────────────────────────────

it('lists only non-deleted tenant-scoped services', function () {
    [$tenant, $owner] = adminWithTenant();
    $otherTenant = Tenant::factory()->create();

    $myService = Service::factory()->create(['tenant_id' => $tenant->id]);
    Service::factory()->create(['tenant_id' => $otherTenant->id]); // other tenant — should not appear
    $deletedService = Service::factory()->create(['tenant_id' => $tenant->id]);
    $deletedService->delete(); // soft-deleted — should not appear

    $this->actingAs($owner)
        ->get(route('admin.services.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/services/Index')
            ->has('services', 1)
            ->where('services.0.id', $myService->id)
        );
});

// ─── Store (validation) ───────────────────────────────────────────────────────

it('returns validation errors for invalid service data', function () {
    [, $owner] = adminWithTenant();

    $this->actingAs($owner)
        ->post(route('admin.services.store'), [
            'name' => '',
            'duration_minutes' => 3,
            'price' => -1,
        ])
        ->assertInvalid(['name', 'duration_minutes', 'price']);
});

it('creates a service with valid data', function () {
    [$tenant, $owner] = adminWithTenant();

    $this->actingAs($owner)
        ->post(route('admin.services.store'), [
            'name' => 'Haircut',
            'duration_minutes' => 60,
            'price' => 45.00,
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.services.index'));

    expect(Service::where('tenant_id', $tenant->id)->where('name', 'Haircut')->exists())->toBeTrue();
});

// ─── Toggle ───────────────────────────────────────────────────────────────────

it('flips is_active on PATCH toggle', function () {
    [$tenant, $owner] = adminWithTenant();
    $service = Service::factory()->create(['tenant_id' => $tenant->id, 'is_active' => true]);

    $this->actingAs($owner)
        ->patch(route('admin.services.toggle', $service))
        ->assertRedirect();

    expect($service->fresh()->is_active)->toBeFalse();

    $this->actingAs($owner)
        ->patch(route('admin.services.toggle', $service))
        ->assertRedirect();

    expect($service->fresh()->is_active)->toBeTrue();
});

// ─── Destroy ──────────────────────────────────────────────────────────────────

it('soft-deletes a service and it disappears from the index', function () {
    [$tenant, $owner] = adminWithTenant();
    $service = Service::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($owner)
        ->delete(route('admin.services.destroy', $service))
        ->assertRedirect(route('admin.services.index'));

    expect(Service::find($service->id))->toBeNull()
        ->and(Service::withTrashed()->find($service->id))->not->toBeNull();

    $this->actingAs($owner)
        ->get(route('admin.services.index'))
        ->assertInertia(fn ($page) => $page->has('services', 0));
});

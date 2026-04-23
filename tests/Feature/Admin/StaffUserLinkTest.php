<?php

use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use App\Tenant\TenantContext;

afterEach(fn () => TenantContext::set(null));

it('has a nullable user_id by default', function () {
    $staff = Staff::factory()->create();

    expect($staff->user_id)->toBeNull()
        ->and($staff->user)->toBeNull();
});

it('can be linked to a user account via withUser state', function () {
    $staff = Staff::factory()->withUser()->create();

    expect($staff->user_id)->not->toBeNull()
        ->and($staff->user)->toBeInstanceOf(User::class)
        ->and($staff->user->tenant_id)->toBe($staff->tenant_id);
});

it('resolves the user relationship from an existing user', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->asStaff()->forTenant($tenant)->create();
    $staff = Staff::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $user->id]);

    expect($staff->user->id)->toBe($user->id);
});

it('sets user_id to null when the linked user is deleted', function () {
    $staff = Staff::factory()->withUser()->create();
    $userId = $staff->user_id;

    User::find($userId)->delete();

    expect($staff->fresh()->user_id)->toBeNull();
});

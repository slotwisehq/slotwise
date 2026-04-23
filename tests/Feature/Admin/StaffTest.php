<?php

use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use App\Tenant\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

afterEach(fn () => TenantContext::set(null));

it('accepts a valid image avatar and stores it', function () {
    Storage::fake('public');

    $tenant = Tenant::factory()->create();
    $owner = User::factory()->owner()->forTenant($tenant)->create();
    $image = UploadedFile::fake()->image('avatar.jpg', 200, 200);

    $this->actingAs($owner)
        ->post(route('admin.staff.store'), [
            'name' => 'Jane Doe',
            'bio' => 'Senior stylist',
            'avatar' => $image,
        ])
        ->assertRedirect(route('admin.staff.index'));

    $staff = Staff::where('tenant_id', $tenant->id)->where('name', 'Jane Doe')->firstOrFail();
    expect($staff->avatar_path)->not->toBeNull();
    Storage::disk('public')->assertExists($staff->avatar_path);
});

it('rejects a non-image file for avatar', function () {
    Storage::fake('public');

    $tenant = Tenant::factory()->create();
    $owner = User::factory()->owner()->forTenant($tenant)->create();
    $pdf = UploadedFile::fake()->create('resume.pdf', 500, 'application/pdf');

    $this->actingAs($owner)
        ->post(route('admin.staff.store'), [
            'name' => 'John Smith',
            'avatar' => $pdf,
        ])
        ->assertInvalid(['avatar']);
});

it('creates staff without avatar', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->owner()->forTenant($tenant)->create();

    $this->actingAs($owner)
        ->post(route('admin.staff.store'), ['name' => 'Bob'])
        ->assertRedirect(route('admin.staff.index'));

    expect(Staff::where('tenant_id', $tenant->id)->where('name', 'Bob')->exists())->toBeTrue();
});

it('lists only non-deleted tenant-scoped staff', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->owner()->forTenant($tenant)->create();
    $otherTenant = Tenant::factory()->create();

    $myStaff = Staff::factory()->create(['tenant_id' => $tenant->id]);
    Staff::factory()->create(['tenant_id' => $otherTenant->id]);
    $deleted = Staff::factory()->create(['tenant_id' => $tenant->id]);
    $deleted->delete();

    $this->actingAs($owner)
        ->get(route('admin.staff.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/staff/Index')
            ->has('staff', 1)
            ->where('staff.0.id', $myStaff->id)
        );
});

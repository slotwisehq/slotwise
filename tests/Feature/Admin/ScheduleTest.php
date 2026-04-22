<?php

use App\Models\Schedule;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use App\Tenant\TenantContext;

afterEach(fn () => TenantContext::set(null));

function disabledDays(int $exceptDay = -1): array
{
    return array_values(array_map(
        fn ($d) => ['day_of_week' => $d, 'enabled' => false, 'start_time' => null, 'end_time' => null],
        array_filter(range(0, 6), fn ($d) => $d !== $exceptDay)
    ));
}

it('rejects an enabled day without end_time', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->owner()->forTenant($tenant)->create();
    $staff = Staff::factory()->create(['tenant_id' => $tenant->id]);

    $days = array_merge(
        [['day_of_week' => 0, 'enabled' => true, 'start_time' => '09:00', 'end_time' => null]],
        disabledDays(0)
    );
    usort($days, fn ($a, $b) => $a['day_of_week'] <=> $b['day_of_week']);

    $this->actingAs($owner)
        ->patch(route('admin.staff.schedule.update', $staff), ['days' => $days])
        ->assertInvalid(['days.0.end_time']);
});

it('rejects end_time before start_time', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->owner()->forTenant($tenant)->create();
    $staff = Staff::factory()->create(['tenant_id' => $tenant->id]);

    $days = array_merge(
        [['day_of_week' => 0, 'enabled' => true, 'start_time' => '17:00', 'end_time' => '09:00']],
        disabledDays(0)
    );
    usort($days, fn ($a, $b) => $a['day_of_week'] <=> $b['day_of_week']);

    $this->actingAs($owner)
        ->patch(route('admin.staff.schedule.update', $staff), ['days' => $days])
        ->assertInvalid(['days.0.end_time']);
});

it('upserts enabled days and deletes disabled days', function () {
    $tenant = Tenant::factory()->create();
    $owner = User::factory()->owner()->forTenant($tenant)->create();
    $staff = Staff::factory()->create(['tenant_id' => $tenant->id]);

    // Existing schedule for day 1 (Tuesday) that should be removed
    Schedule::factory()->forStaff($staff)->create(['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '17:00']);

    $days = array_merge(
        disabledDays(-1),  // all disabled as baseline
        [
            ['day_of_week' => 0, 'enabled' => true, 'start_time' => '09:00', 'end_time' => '17:00'],
            ['day_of_week' => 1, 'enabled' => false, 'start_time' => null, 'end_time' => null],
        ]
    );
    // Keep only 7 entries for the 7 days, remove the duplicates
    $uniqueDays = [];
    foreach ($days as $day) {
        $uniqueDays[$day['day_of_week']] = $day;
    }
    ksort($uniqueDays);

    $this->actingAs($owner)
        ->patch(route('admin.staff.schedule.update', $staff), ['days' => array_values($uniqueDays)])
        ->assertRedirect();

    expect(Schedule::where('staff_id', $staff->id)->where('day_of_week', 0)->exists())->toBeTrue()
        ->and(Schedule::where('staff_id', $staff->id)->where('day_of_week', 1)->exists())->toBeFalse();
});

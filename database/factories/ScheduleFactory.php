<?php

namespace Database\Factories;

use App\Models\Schedule;
use App\Models\Staff;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $staff = Staff::factory()->create();

        return [
            'tenant_id' => $staff->tenant_id,
            'staff_id' => $staff->id,
            'day_of_week' => fake()->numberBetween(0, 6),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
        ];
    }

    public function forStaff(Staff $staff): self
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $staff->tenant_id,
            'staff_id' => $staff->id,
        ]);
    }
}

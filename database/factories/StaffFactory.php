<?php

namespace Database\Factories;

use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Staff>
 */
class StaffFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => null,
            'name' => fake()->name(),
            'bio' => fake()->optional(0.7)->paragraph(),
            'avatar_path' => null,
        ];
    }

    public function withUser(): static
    {
        return $this->afterCreating(function (Staff $staff) {
            $user = User::factory()->asStaff()->create(['tenant_id' => $staff->tenant_id]);
            $staff->update(['user_id' => $user->id]);
        });
    }
}

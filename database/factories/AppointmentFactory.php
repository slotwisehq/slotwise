<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('-30 days', '+14 days');

        return [
            'tenant_id' => Tenant::factory(),
            'service_id' => Service::factory(),
            'staff_id' => Staff::factory(),
            'customer_id' => Customer::factory(),
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+60 minutes'),
            'status' => fake()->randomElement(AppointmentStatus::cases()),
            'payment_status' => PaymentStatus::Unpaid,
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentStatus::Confirmed,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentStatus::Cancelled,
        ]);
    }

    public function upcoming(): static
    {
        return $this->state(function () {
            $startsAt = fake()->dateTimeBetween('now', '+14 days');

            return [
                'starts_at' => $startsAt,
                'ends_at' => (clone $startsAt)->modify('+60 minutes'),
            ];
        });
    }
}

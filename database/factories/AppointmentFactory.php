<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use DateMalformedStringException;
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
     *
     * @throws DateMalformedStringException
     */
    public function definition(): array
    {
        $tenant = Tenant::factory()->create();
        $service = Service::factory()->for($tenant)->create();
        $staff = Staff::factory()->for($tenant)->create();
        $customer = Customer::factory()->for($tenant)->create();
        $startsAt = fake()->dateTimeBetween('-30 days', '+14 days');
        $endsAt = (clone $startsAt)->modify("+$service->duration_minutes minutes");

        return [
            'tenant_id' => $tenant->id,
            'service_id' => $service->id,
            'staff_id' => $staff->id,
            'customer_id' => $customer->id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
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

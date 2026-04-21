<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    private static array $services = [
        ['name' => 'Haircut', 'duration_minutes' => 60, 'price' => 45.00],
        ['name' => 'Colour Treatment', 'duration_minutes' => 120, 'price' => 90.00],
        ['name' => 'Blowdry', 'duration_minutes' => 30, 'price' => 25.00],
        ['name' => 'Beard Trim', 'duration_minutes' => 30, 'price' => 20.00],
        ['name' => 'Deep Conditioning', 'duration_minutes' => 45, 'price' => 35.00],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $service = fake()->randomElement(self::$services);

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $service['name'],
            'duration_minutes' => $service['duration_minutes'],
            'price' => $service['price'],
            'is_active' => true,
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}

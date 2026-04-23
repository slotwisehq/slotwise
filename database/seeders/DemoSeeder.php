<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\User;
use App\Tenant\TenantContext;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ── Tenant ────────────────────────────────────────────────────────────
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'lumiere-salon'],
            [
                'name' => 'Lumière Salon',
                'plan' => 'free',
                'settings' => [
                    'logo_url' => null,
                    'primary_color' => '#c9a96e',
                ],
            ]
        );

        TenantContext::set($tenant);

        // ── Owner ─────────────────────────────────────────────────────────────
        User::firstOrCreate(
            ['email' => 'admin@lumieresalon.test'],
            [
                'name' => 'Lumière Admin',
                'password' => Hash::make('password'),
                'role' => UserRole::Owner,
                'tenant_id' => $tenant->id,
                'email_verified_at' => now(),
            ]
        );

        // ── Staff ─────────────────────────────────────────────────────────────
        $sophie = Staff::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Sophie Laurent'],
            ['bio' => 'Senior stylist with 10 years of experience.']
        );

        $marc = Staff::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Marc Dubois'],
            ['bio' => 'Colour specialist and blowdry expert.']
        );

        // ── Schedules — Sophie: Mon–Sat (1–6), 09:00–18:00 ───────────────────
        foreach (range(1, 6) as $day) {
            Schedule::updateOrCreate(
                ['staff_id' => $sophie->id, 'day_of_week' => $day],
                ['tenant_id' => $tenant->id, 'start_time' => '09:00:00', 'end_time' => '18:00:00']
            );
        }

        // ── Schedules — Marc: Tue–Sun (2,3,4,5,6,0), 10:00–19:00 ─────────────
        foreach ([2, 3, 4, 5, 6, 0] as $day) {
            Schedule::updateOrCreate(
                ['staff_id' => $marc->id, 'day_of_week' => $day],
                ['tenant_id' => $tenant->id, 'start_time' => '10:00:00', 'end_time' => '19:00:00']
            );
        }

        // ── Services ──────────────────────────────────────────────────────────
        $servicesData = [
            ['name' => 'Haircut & Style', 'duration_minutes' => 60, 'price' => 45.00],
            ['name' => 'Colour Treatment', 'duration_minutes' => 120, 'price' => 90.00],
            ['name' => 'Blowdry', 'duration_minutes' => 30, 'price' => 25.00],
        ];

        $services = [];
        foreach ($servicesData as $svc) {
            $services[] = Service::updateOrCreate(
                ['tenant_id' => $tenant->id, 'name' => $svc['name']],
                ['duration_minutes' => $svc['duration_minutes'], 'price' => $svc['price'], 'is_active' => true]
            );
        }

        // ── Customers (10 distinct) ───────────────────────────────────────────
        $customersData = [
            ['name' => 'Claire Fontaine', 'email' => 'claire@lumiere.test'],
            ['name' => 'Thomas Renard', 'email' => 'thomas@lumiere.test'],
            ['name' => 'Margot Blanc', 'email' => 'margot@lumiere.test'],
            ['name' => 'Baptiste Moreau', 'email' => 'baptiste@lumiere.test'],
            ['name' => 'Amélie Dupont', 'email' => 'amelie@lumiere.test'],
            ['name' => 'Louis Martin', 'email' => 'louis@lumiere.test'],
            ['name' => 'Camille Bernard', 'email' => 'camille@lumiere.test'],
            ['name' => 'Hugo Lefebvre', 'email' => 'hugo@lumiere.test'],
            ['name' => 'Sophie Leroy', 'email' => 'sophie.leroy@lumiere.test'],
            ['name' => 'Lucas Girard', 'email' => 'lucas@lumiere.test'],
        ];

        $customers = [];
        foreach ($customersData as $c) {
            $customers[] = Customer::firstOrCreate(
                ['tenant_id' => $tenant->id, 'email' => $c['email']],
                ['name' => $c['name']]
            );
        }

        $allStaff = [$sophie, $marc];

        // ── Past appointments (20, last 60 days, mix of Confirmed/Cancelled) ──
        // notes field is the idempotency key — each seeded record has a unique tag
        for ($i = 0; $i < 20; $i++) {
            $startsAt = Carbon::now()->subDays(($i * 3) + 1)->setTime(9 + ($i % 8), 0);
            $service = $services[$i % 3];
            $endsAt = $startsAt->copy()->addMinutes($service->duration_minutes);

            Appointment::updateOrCreate(
                ['tenant_id' => $tenant->id, 'notes' => "[seeder:past:$i]"],
                [
                    'staff_id' => $allStaff[$i % 2]->id,
                    'service_id' => $service->id,
                    'customer_id' => $customers[$i % count($customers)]->id,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'status' => $i % 4 === 0 ? AppointmentStatus::Cancelled : AppointmentStatus::Confirmed,
                    'payment_status' => PaymentStatus::Unpaid,
                ]
            );
        }

        // ── Upcoming appointments (10, next 14 days, all Confirmed) ──────────
        for ($i = 0; $i < 10; $i++) {
            $startsAt = Carbon::now()->addDays($i + 1)->setTime(10 + ($i % 8), 0);
            $service = $services[$i % 3];
            $endsAt = $startsAt->copy()->addMinutes($service->duration_minutes);

            Appointment::updateOrCreate(
                ['tenant_id' => $tenant->id, 'notes' => "[seeder:upcoming:$i]"],
                [
                    'staff_id' => $allStaff[$i % 2]->id,
                    'service_id' => $service->id,
                    'customer_id' => $customers[$i % count($customers)]->id,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'status' => AppointmentStatus::Confirmed,
                    'payment_status' => PaymentStatus::Unpaid,
                ]
            );
        }

        TenantContext::set(null);
    }
}

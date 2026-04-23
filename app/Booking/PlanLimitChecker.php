<?php

namespace App\Booking;

use App\Booking\Exceptions\PlanLimitException;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Tenant\Scopes\TenantScope;

class PlanLimitChecker
{
    /**
     * @throws PlanLimitException
     */
    public function assertCanAddStaff(Tenant $tenant): void
    {
        if ($tenant->plan !== 'free') {
            return;
        }

        $count = Staff::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->id)
            ->count();

        if ($count >= 1) {
            throw new PlanLimitException('staff members', $tenant->plan);
        }
    }

    /**
     * @throws PlanLimitException
     */
    public function assertCanAddService(Tenant $tenant): void
    {
        if ($tenant->plan !== 'free') {
            return;
        }

        $count = Service::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->id)
            ->count();

        if ($count >= 1) {
            throw new PlanLimitException('services', $tenant->plan);
        }
    }

    /**
     * @throws PlanLimitException
     */
    public function assertCanCreateBooking(Tenant $tenant): void
    {
        if ($tenant->plan !== 'free') {
            return;
        }

        $count = Appointment::withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', [AppointmentStatus::Confirmed->value, AppointmentStatus::Pending->value])
            ->whereBetween('starts_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        if ($count >= 50) {
            throw new PlanLimitException('bookings this month', $tenant->plan);
        }
    }
}

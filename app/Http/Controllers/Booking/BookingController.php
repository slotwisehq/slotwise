<?php

namespace App\Http\Controllers\Booking;

use App\Booking\AvailabilityService;
use App\Booking\BookingService;
use App\Booking\Exceptions\SlotUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Tenant\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly BookingService $booking,
    ) {
        //
    }

    public function show(Tenant $tenant): Response
    {
        TenantContext::set($tenant);

        $services = Service::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'duration_minutes', 'price'])
            ->map(fn (Service $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'duration_minutes' => $s->duration_minutes,
                'price' => number_format((float) $s->price, 2),
            ]);

        return Inertia::render('booking/ServicePicker', [
            'tenant' => $tenant,
            'services' => $services,
        ]);
    }

    public function selectStaff(Tenant $tenant, Service $service): Response|RedirectResponse
    {
        TenantContext::set($tenant);

        $staffMembers = Staff::has('services')
            ->orderBy('name')
            ->get(['id', 'name', 'bio', 'avatar_path'])
            ->map(fn (Staff $staff) => [
                'id' => $staff->id,
                'name' => $staff->name,
                'bio' => $staff->bio,
                'avatar_path' => $staff->avatar_path ? asset('storage/'.$staff->avatar_path) : null,
            ]);

        if ($staffMembers->count() === 1) {
            return redirect()->route('booking.slots', [
                $tenant->slug,
                $service->id,
                $staffMembers->first()['id'],
            ]);
        }

        return Inertia::render('booking/StaffPicker', [
            'tenant' => $this->tenantMeta($tenant),
            'service' => ['id' => $service->id, 'name' => $service->name],
            'staff' => $staffMembers,
        ]);
    }

    public function selectSlot(Tenant $tenant, Service $service, Staff $staff): Response
    {
        TenantContext::set($tenant);

        $date = Carbon::parse(request()->query('date', now()->toDateString()));

        $slots = $this->availability
            ->getSlots($staff->id, $service->id, $date)
            ->map(fn (Carbon $slot) => [
                'time' => $slot->format('H:i'),
                'starts_at' => $slot->format('Y-m-d H:i:s'),
            ])
            ->values();

        return Inertia::render('booking/SlotPicker', [
            'tenant' => $this->tenantMeta($tenant),
            'service' => ['id' => $service->id, 'name' => $service->name, 'duration_minutes' => $service->duration_minutes],
            'staff' => ['id' => $staff->id, 'name' => $staff->name],
            'date' => $date->toDateString(),
            'slots' => $slots,
        ]);
    }

    public function showCustomerForm(Tenant $tenant, Service $service, Staff $staff): Response
    {
        TenantContext::set($tenant);

        $startsAt = request()->query('starts_at', '');

        return Inertia::render('booking/CustomerForm', [
            'tenant' => $this->tenantMeta($tenant),
            'service' => ['id' => $service->id, 'name' => $service->name, 'duration_minutes' => $service->duration_minutes],
            'staff' => ['id' => $staff->id, 'name' => $staff->name],
            'starts_at' => $startsAt,
        ]);
    }

    public function store(StoreBookingRequest $request, Tenant $tenant, Service $service, Staff $staff): RedirectResponse
    {
        TenantContext::set($tenant);

        $customer = Customer::firstOrCreate(
            ['tenant_id' => $tenant->id, 'email' => $request->customer_email],
            ['name' => $request->customer_name, 'phone' => $request->customer_phone],
        );

        try {
            $appointment = $this->booking->create([
                'tenant_id' => $tenant->id,
                'staff_id' => $staff->id,
                'service_id' => $service->id,
                'customer_id' => $customer->id,
                'starts_at' => Carbon::parse($request->starts_at),
            ]);

            return redirect()->route('booking.confirmation', [
                $tenant->slug,
                $appointment->id,
            ]);
        } catch (SlotUnavailableException) {
            return Inertia::flash('error', 'That slot was just taken — please choose another time.')
                ->back();
        }
    }

    public function confirmation(Tenant $tenant, Appointment $appointment): Response
    {
        TenantContext::set($tenant);

        $appointment->load('service', 'staff', 'customer');

        return Inertia::render('booking/Confirmation', [
            'tenant' => $this->tenantMeta($tenant),
            'appointment' => [
                'id' => $appointment->id,
                'service_name' => $appointment->service->name,
                'staff_name' => $appointment->staff->name,
                'customer_name' => $appointment->customer->name,
                'starts_at' => $appointment->starts_at->toIso8601String(),
                'ends_at' => $appointment->ends_at->toIso8601String(),
            ],
        ]);
    }

    protected function tenantMeta(Tenant $tenant): array
    {
        $settings = $tenant->settings ?? [];

        return [
            'name' => $tenant->name,
            'logo_url' => $settings['logo_url'] ?? null,
        ];
    }
}

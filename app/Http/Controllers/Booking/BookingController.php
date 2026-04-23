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
            'tenant' => $this->tenantMeta($tenant),
            'services' => $services,
        ]);
    }

    public function selectStaff(Tenant $tenant, Service $service): Response|RedirectResponse
    {
        $staffMembers = Staff::has('schedules')
            ->orderBy('name')
            ->get(['id', 'name', 'bio', 'avatar_path'])
            ->map(fn (Staff $staff) => [
                'id' => $staff->id,
                'name' => $staff->name,
                'bio' => $staff->bio,
                'avatar_path' => $staff->avatar_path ? asset('storage/'.$staff->avatar_path) : null,
            ]);

        if ($staffMembers->count() === 1) {
            /** @var array{id: int} $only */
            $only = $staffMembers->first();

            return redirect()->route('booking.slots', [
                $tenant->slug,
                $service->id,
                $only['id'],
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
        $validated = request()->validate([
            'date' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:today',
                'before_or_equal:'.now()->addDays(90)->toDateString(),
            ],
        ]);

        $date = Carbon::parse($validated['date'] ?? now()->toDateString());

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
        $validated = request()->validate([
            'starts_at' => ['required', 'date_format:Y-m-d H:i:s', 'after:now'],
        ]);

        return Inertia::render('booking/CustomerForm', [
            'tenant' => $this->tenantMeta($tenant),
            'service' => ['id' => $service->id, 'name' => $service->name, 'duration_minutes' => $service->duration_minutes],
            'staff' => ['id' => $staff->id, 'name' => $staff->name],
            'starts_at' => $validated['starts_at'],
        ]);
    }

    public function store(StoreBookingRequest $request, Tenant $tenant, Service $service, Staff $staff): RedirectResponse
    {
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
                'starts_at' => Carbon::parse($request->string('starts_at')->value()),
            ]);

            return redirect()->route('booking.confirmation', [
                $tenant->slug,
                $appointment->id,
            ]);
        } catch (SlotUnavailableException) {
            return back()->with('error', 'That slot was just taken — please choose another time.');
        }
    }

    public function confirmation(Tenant $tenant, Appointment $appointment): Response
    {
        abort_if($appointment->tenant_id !== $tenant->id, 404);

        $appointment->load('service', 'staff', 'customer');

        assert($appointment->service !== null && $appointment->staff !== null && $appointment->customer !== null);

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

    /** @return array{slug: string, name: string, logo_url: string|null} */
    protected function tenantMeta(Tenant $tenant): array
    {
        $settings = is_array($tenant->settings) ? $tenant->settings : [];

        $logoUrl = $settings['logo_url'] ?? null;

        return [
            'slug' => $tenant->slug,
            'name' => $tenant->name,
            'logo_url' => is_string($logoUrl) ? $logoUrl : null,
        ];
    }
}

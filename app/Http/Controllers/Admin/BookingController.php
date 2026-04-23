<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CancelBookingRequest;
use App\Models\Appointment;
use App\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function index(Request $request): Response
    {
        $appointments = Appointment::with(['service:id,name', 'staff:id,name', 'customer:id,name,email,phone'])
            ->when($request->date_from, fn ($q) => $q->whereDate('starts_at', '>=', (string) $request->string('date_from')))
            ->when($request->date_to, fn ($q) => $q->whereDate('starts_at', '<=', (string) $request->string('date_to')))
            ->when($request->staff_id, fn ($q) => $q->where('staff_id', $request->staff_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderBy('starts_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/bookings/Index', [
            'appointments' => $appointments,
            'staff' => Staff::select('id', 'name')->orderBy('name')->get(),
            'filters' => $request->only('date_from', 'date_to', 'staff_id', 'status'),
        ]);
    }

    public function show(Appointment $appointment): Response
    {
        $appointment->load(['service:id,name', 'staff:id,name', 'customer:id,name,email,phone']);

        assert($appointment->service !== null && $appointment->staff !== null && $appointment->customer !== null);

        return Inertia::render('admin/bookings/Detail', [
            'appointment' => [
                'id' => $appointment->id,
                'starts_at' => $appointment->starts_at->toIso8601String(),
                'ends_at' => $appointment->ends_at->toIso8601String(),
                'status' => $appointment->status->value,
                'notes' => $appointment->notes,
                'service' => ['id' => $appointment->service->id, 'name' => $appointment->service->name],
                'staff' => ['id' => $appointment->staff->id, 'name' => $appointment->staff->name],
                'customer' => [
                    'id' => $appointment->customer->id,
                    'name' => $appointment->customer->name,
                    'email' => $appointment->customer->email,
                    'phone' => $appointment->customer->phone,
                ],
            ],
        ]);
    }

    public function cancel(CancelBookingRequest $request, Appointment $appointment): RedirectResponse
    {
        $appointment->update(['status' => AppointmentStatus::Cancelled]);

        return back();
    }
}

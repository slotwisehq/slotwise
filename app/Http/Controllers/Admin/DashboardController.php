<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $groups = Appointment::with(['service:id,name', 'staff:id,name', 'customer:id,name'])
            ->where('starts_at', '>=', now()->startOfDay())
            ->where('starts_at', '<=', now()->addDays(7)->endOfDay())
            ->orderBy('starts_at')
            ->limit(50)
            ->get()
            ->groupBy(fn (Appointment $a) => $a->starts_at->toDateString())
            ->map(fn ($group, $date) => [
                'date' => $date,
                'appointments' => $group->values(),
            ])
            ->values();

        return Inertia::render('admin/Dashboard', [
            'appointmentGroups' => $groups,
        ]);
    }
}

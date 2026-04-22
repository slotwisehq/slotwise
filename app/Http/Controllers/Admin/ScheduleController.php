<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateScheduleRequest;
use App\Models\Schedule;
use App\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function edit(Staff $staff): Response
    {
        $schedules = Schedule::where('staff_id', $staff->id)
            ->get()
            ->keyBy('day_of_week');

        $days = collect(range(0, 6))->map(function (int $dow) use ($schedules) {
            $s = $schedules->get($dow);

            return [
                'day_of_week' => $dow,
                'enabled' => $s !== null,
                'start_time' => $s?->start_time ? substr($s->start_time, 0, 5) : '09:00',
                'end_time' => $s?->end_time ? substr($s->end_time, 0, 5) : '17:00',
            ];
        });

        return Inertia::render('admin/staff/Schedule', [
            'staff' => $staff->only('id', 'name'),
            'days' => $days,
        ]);
    }

    public function update(UpdateScheduleRequest $request, Staff $staff): RedirectResponse
    {
        /** @var array<int, array{enabled: bool, day_of_week: int, start_time: string, end_time: string}> $days */
        $days = $request->validated()['days'];
        foreach ($days as $day) {
            if ($day['enabled']) {
                Schedule::updateOrCreate(
                    ['staff_id' => $staff->id, 'day_of_week' => $day['day_of_week']],
                    ['start_time' => $day['start_time'], 'end_time' => $day['end_time']]
                );
            } else {
                Schedule::where('staff_id', $staff->id)
                    ->where('day_of_week', $day['day_of_week'])
                    ->delete();
            }
        }

        return back();
    }
}

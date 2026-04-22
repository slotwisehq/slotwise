<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStaffRequest;
use App\Http\Requests\Admin\UpdateStaffRequest;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function index(): Response
    {
        $staff = Staff::orderBy('name')->get()->map(fn (Staff $s) => [
            ...$s->toArray(),
            'avatar_url' => $s->avatar_path
                ? Storage::disk('public')->url($s->avatar_path)
                : null,
        ]);

        return Inertia::render('admin/staff/Index', ['staff' => $staff]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/staff/Form');
    }

    public function store(StoreStaffRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('avatar');

        if ($request->hasFile('avatar')) {
            /** @var User $user */
            $user = auth()->user();
            $data['avatar_path'] = $request->file('avatar')
                ->store("avatars/{$user->tenant_id}", 'public');
        }

        Staff::create($data);

        return redirect()->route('admin.staff.index');
    }

    public function edit(Staff $staff): Response
    {
        return Inertia::render('admin/staff/Form', [
            'staff' => [
                ...$staff->toArray(),
                'avatar_url' => $staff->avatar_path
                    ? Storage::disk('public')->url($staff->avatar_path)
                    : null,
            ],
        ]);
    }

    public function update(UpdateStaffRequest $request, Staff $staff): RedirectResponse
    {
        $data = $request->safe()->except('avatar');

        if ($request->hasFile('avatar')) {
            if ($staff->avatar_path) {
                Storage::disk('public')->delete($staff->avatar_path);
            }
            /** @var User $user */
            $user = auth()->user();
            $data['avatar_path'] = $request->file('avatar')
                ->store("avatars/{$user->tenant_id}", 'public');
        }

        $staff->update($data);

        return redirect()->route('admin.staff.index');
    }

    public function destroy(Staff $staff): RedirectResponse
    {
        $staff->delete();

        return redirect()->route('admin.staff.index');
    }
}

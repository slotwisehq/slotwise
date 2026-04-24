<?php

namespace App\Http\Controllers\Admin;

use App\Booking\Exceptions\PlanLimitException;
use App\Booking\PlanLimitChecker;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreServiceRequest;
use App\Http\Requests\Admin\UpdateServiceRequest;
use App\Models\Service;
use App\Models\Tenant;
use App\Tenant\TenantContext;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function __construct(private readonly PlanLimitChecker $limits)
    {
        //
    }

    public function index(): Response
    {
        return Inertia::render('admin/services/Index', [
            'services' => Service::orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/services/Create');
    }

    public function store(StoreServiceRequest $request): RedirectResponse
    {
        /** @var Tenant $tenant */
        $tenant = TenantContext::current();

        try {
            $this->limits->assertCanAddService($tenant);
        } catch (PlanLimitException $e) {
            return back()->withErrors(['plan' => $e->getMessage()]);
        }

        Service::create($request->validated());

        return redirect()->route('admin.services.index');
    }

    public function edit(Service $service): Response
    {
        return Inertia::render('admin/services/Edit', ['service' => $service]);
    }

    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        $service->update($request->validated());

        return redirect()->route('admin.services.index');
    }

    public function toggle(Service $service): RedirectResponse
    {
        $service->update(['is_active' => ! $service->is_active]);

        return back();
    }

    public function destroy(Service $service): RedirectResponse
    {
        $service->delete();

        return redirect()->route('admin.services.index');
    }
}

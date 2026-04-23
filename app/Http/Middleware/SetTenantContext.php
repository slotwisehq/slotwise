<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Tenant\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->route('tenant');

        if ($tenant instanceof Tenant) {
            TenantContext::set($tenant);
        }

        return $next($request);
    }
}

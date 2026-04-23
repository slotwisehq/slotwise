<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Tenant\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, [UserRole::Owner, UserRole::Staff])) {
            abort(403);
        }

        $tenant = $user->tenant;

        if (! $tenant) {
            abort(403);
        }

        TenantContext::set($tenant);

        return $next($request);
    }
}

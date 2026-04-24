<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class RegisterController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('auth/Register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $slug = $this->generateSlug($request->string('business_name')->toString());

        /** @var User $user */
        $user = DB::transaction(function () use ($request, $slug) {
            $tenant = Tenant::create([
                'name' => $request->string('business_name')->toString(),
                'slug' => $slug,
                'plan' => 'free',
            ]);

            return User::create([
                'tenant_id' => $tenant->id,
                'name' => $request->string('owner_name')->toString(),
                'email' => $request->string('email')->toString(),
                'password' => Hash::make($request->string('password')->toString()),
                'role' => UserRole::Owner,
            ]);
        });

        Auth::login($user);

        return redirect()->route('admin.dashboard');
    }

    protected function generateSlug(string $businessName): string
    {
        $base = Str::slug($businessName);

        for ($i = 1; $i <= 10; $i++) {
            $candidate = $i === 1 ? $base : "$base-$i";

            if (Tenant::where('slug', $candidate)->doesntExist()) {
                return $candidate;
            }
        }

        throw new RuntimeException("Could not generate a unique slug after 10 attempts for: $businessName");
    }
}

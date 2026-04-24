<?php

namespace App\Feature;

use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

class Feature
{
    public static function check(string $feature, Tenant $tenant): bool
    {
        /** @var ?string[] $allowedPlans */
        $allowedPlans = config("features.$feature");

        if ($allowedPlans === null) {
            Log::warning("Feature::check called with unknown feature key: $feature");

            return false;
        }

        return in_array($tenant->plan, $allowedPlans, strict: true);
    }
}

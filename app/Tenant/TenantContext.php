<?php

namespace App\Tenant;

use App\Models\Tenant;

class TenantContext
{
    private static ?Tenant $current = null;

    public static function set(?Tenant $tenant): void
    {
        static::$current = $tenant;
    }

    public static function current(): ?Tenant
    {
        return static::$current;
    }
}
